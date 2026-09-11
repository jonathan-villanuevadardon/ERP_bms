<?php

namespace App\Http\Controllers;

use App\Models\Vacacion;
use App\Models\VacacionPendiente;
use App\Services\EmpleadoService;
use App\Services\SeccionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Controlador "VacacionController".
 *
 * Módulo "Vacaciones". Los empleados de RH agregan periodos de vacaciones.
 * ANTES de pasar a la tabla "vacaciones" deben ser aprobados por el Admin.
 * Mientras estén pendientes, ambos lados pueden modificarlos; una vez
 * aprobadas pasan a "vacaciones" (solo lectura para RH).
 */
class VacacionController extends Controller
{
    /**
     * Lista las vacaciones aprobadas.
     *
     * @return View
     */
    public function index(Request $request)
    {
        $vacaciones = SeccionService::aplicar(Vacacion::query(), $request->user())
            ->orderBy('fecha_inicio', 'desc')->paginate(25);

        return view('vacaciones.index', compact('vacaciones'));
    }

    /**
     * Muestra el formulario para agregar vacaciones.
     *
     * @return View
     */
    public function create(Request $request)
    {
        $empleados = [];
        if ($termino = $request->input('termino')) {
            $empleados = EmpleadoService::listar(null, $termino, 50, SeccionService::permitidas($request->user()));
        }

        return view('vacaciones.create', compact('empleados'));
    }

    /**
     * Guarda una solicitud de vacaciones en el buzón de aprobación.
     *
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $data = $this->validar($request);

        $empleado = EmpleadoService::buscarPorClave($data['clave']);
        if (! $empleado) {
            return back()->withInput()->withErrors(['clave' => 'Número de empleado no encontrado.']);
        }

        SeccionService::autorizar($request->user(), $empleado['seccion']);

        VacacionPendiente::create([
            'clave' => $data['clave'],
            'nombre_completo' => $empleado['nombre_completo'],
            'area' => $empleado['area'],
            'seccion' => $empleado['seccion'],
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'],
            'observaciones' => $data['observaciones'] ?? null,
            'estado' => 'pendiente',
            'creado_por' => $request->user()->id,
        ]);

        return redirect()->route('vacaciones.pendientes')
            ->with('success', 'Vacaciones enviadas a aprobación.');
    }

    /**
     * Buzón de aprobación de vacaciones pendientes.
     *
     * @return View
     */
    public function pendientes(Request $request)
    {
        $pendientes = SeccionService::aplicar(VacacionPendiente::where('estado', 'pendiente'), $request->user())
            ->orderBy('fecha_inicio')
            ->get();

        return view('vacaciones.pendientes', compact('pendientes'));
    }

    /**
     * Muestra el formulario de edición de una solicitud pendiente.
     *
     * @return View
     */
    public function editarPendiente(Request $request, VacacionPendiente $pendiente)
    {
        $this->autorizarPendiente($request, $pendiente);

        return view('vacaciones.editar_pendiente', compact('pendiente'));
    }

    /**
     * Actualiza una solicitud pendiente (solo antes de aprobarse).
     *
     * @return RedirectResponse
     */
    public function actualizarPendiente(Request $request, VacacionPendiente $pendiente)
    {
        $this->autorizarPendiente($request, $pendiente);
        $data = $this->validarEdicion($request);
        $pendiente->update([
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'],
            'observaciones' => $data['observaciones'] ?? null,
        ]);

        return redirect()->route('vacaciones.pendientes')->with('success', 'Solicitud actualizada.');
    }

    /**
     * Elimina una solicitud pendiente.
     *
     * @return RedirectResponse
     */
    public function eliminarPendiente(Request $request, VacacionPendiente $pendiente)
    {
        $this->autorizarPendiente($request, $pendiente);
        $pendiente->delete();

        return redirect()->route('vacaciones.pendientes')->with('success', 'Solicitud eliminada.');
    }

    /**
     * Aprueba una solicitud de vacaciones y la copia a "vacaciones".
     *
     * @return RedirectResponse
     */
    public function aprobar(Request $request, VacacionPendiente $pendiente)
    {
        SeccionService::autorizar($request->user(), $pendiente->seccion);
        DB::transaction(function () use ($request, $pendiente) {
            $registro = VacacionPendiente::whereKey($pendiente->id)->lockForUpdate()->firstOrFail();
            if ($registro->estado !== 'pendiente') {
                throw ValidationException::withMessages(['vacacion' => 'La solicitud ya fue procesada.']);
            }

            Vacacion::create([
                'clave' => $registro->clave, 'nombre_completo' => $registro->nombre_completo,
                'area' => $registro->area, 'seccion' => $registro->seccion,
                'fecha_inicio' => $registro->fecha_inicio, 'fecha_fin' => $registro->fecha_fin,
                'observaciones' => $registro->observaciones, 'pendiente_id' => $registro->id,
            ]);
            $registro->update(['estado' => 'aprobado', 'aprobado_por' => $request->user()->id, 'aprobado_en' => now()]);
        });

        return redirect()->route('vacaciones.pendientes')->with('success', 'Vacaciones aprobadas.');
    }

    /**
     * Rechaza una solicitud de vacaciones pendiente.
     *
     * @return RedirectResponse
     */
    public function rechazar(Request $request, VacacionPendiente $pendiente)
    {
        SeccionService::autorizar($request->user(), $pendiente->seccion);
        $actualizados = VacacionPendiente::whereKey($pendiente->id)->where('estado', 'pendiente')->update([
            'estado' => 'rechazado',
            'aprobado_por' => $request->user()->id,
            'aprobado_en' => now(),
        ]);

        if ($actualizados === 0) {
            return back()->with('error', 'La solicitud ya fue procesada.');
        }

        return redirect()->route('vacaciones.pendientes')->with('success', 'Vacaciones rechazadas.');
    }

    /**
     * Valida los datos de vacaciones.
     */
    private function validar(Request $request): array
    {
        return $request->validate([
            'clave' => ['required', 'integer'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function validarEdicion(Request $request): array
    {
        return $request->validate([
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'fecha_fin' => ['required', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function autorizarPendiente(Request $request, VacacionPendiente $pendiente): void
    {
        SeccionService::autorizar($request->user(), $pendiente->seccion);
        if ($pendiente->estado !== 'pendiente') {
            abort(409, 'Solo se pueden modificar solicitudes pendientes.');
        }
    }
}
