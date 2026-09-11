<?php

namespace App\Http\Controllers;

use App\Models\Vacacion;
use App\Models\VacacionPendiente;
use App\Services\EmpleadoService;
use Illuminate\Http\Request;

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
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $vacaciones = Vacacion::orderBy('fecha_inicio', 'desc')->paginate(25);

        return view('vacaciones.index', compact('vacaciones'));
    }

    /**
     * Muestra el formulario para agregar vacaciones.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        $empleados = [];
        if ($termino = $request->input('termino')) {
            $empleados = EmpleadoService::listar(null, $termino, 50);
        }

        return view('vacaciones.create', compact('empleados'));
    }

    /**
     * Guarda una solicitud de vacaciones en el buzón de aprobación.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $data = $this->validar($request);

        $empleado = EmpleadoService::buscarPorClave($data['clave']);
        if (! $empleado) {
            return back()->withInput()->withErrors(['clave' => 'Número de empleado no encontrado.']);
        }

        VacacionPendiente::create([
            'clave'           => $data['clave'],
            'nombre_completo' => $empleado['nombre_completo'],
            'area'            => $empleado['area'],
            'seccion'         => $empleado['seccion'],
            'fecha_inicio'    => $data['fecha_inicio'],
            'fecha_fin'       => $data['fecha_fin'],
            'observaciones'   => $data['observaciones'] ?? null,
            'estado'          => 'pendiente',
            'creado_por'      => $request->user()->id,
        ]);

        return redirect()->route('vacaciones.pendientes')
            ->with('success', 'Vacaciones enviadas a aprobación.');
    }

    /**
     * Buzón de aprobación de vacaciones pendientes.
     *
     * @return \Illuminate\View\View
     */
    public function pendientes()
    {
        $pendientes = VacacionPendiente::where('estado', 'pendiente')
            ->orderBy('fecha_inicio')
            ->get();

        return view('vacaciones.pendientes', compact('pendientes'));
    }

    /**
     * Muestra el formulario de edición de una solicitud pendiente.
     *
     * @param  \App\Models\VacacionPendiente  $pendiente
     * @return \Illuminate\View\View
     */
    public function editarPendiente(VacacionPendiente $pendiente)
    {
        return view('vacaciones.editar_pendiente', compact('pendiente'));
    }

    /**
     * Actualiza una solicitud pendiente (solo antes de aprobarse).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\VacacionPendiente  $pendiente
     * @return \Illuminate\Http\RedirectResponse
     */
    public function actualizarPendiente(Request $request, VacacionPendiente $pendiente)
    {
        if ($pendiente->estado !== 'pendiente') {
            return back()->with('error', 'Solo se pueden modificar solicitudes pendientes.');
        }

        $data = $this->validar($request);
        $pendiente->update([
            'fecha_inicio'  => $data['fecha_inicio'],
            'fecha_fin'     => $data['fecha_fin'],
            'observaciones' => $data['observaciones'] ?? null,
        ]);

        return redirect()->route('vacaciones.pendientes')->with('success', 'Solicitud actualizada.');
    }

    /**
     * Elimina una solicitud pendiente.
     *
     * @param  \App\Models\VacacionPendiente  $pendiente
     * @return \Illuminate\Http\RedirectResponse
     */
    public function eliminarPendiente(VacacionPendiente $pendiente)
    {
        if ($pendiente->estado !== 'pendiente') {
            return back()->with('error', 'Solo se puede eliminar una solicitud pendiente.');
        }

        $pendiente->delete();

        return redirect()->route('vacaciones.pendientes')->with('success', 'Solicitud eliminada.');
    }

    /**
     * Aprueba una solicitud de vacaciones y la copia a "vacaciones".
     *
     * @param  \App\Models\VacacionPendiente  $pendiente
     * @return \Illuminate\Http\RedirectResponse
     */
    public function aprobar(VacacionPendiente $pendiente)
    {
        Vacacion::create([
            'clave'           => $pendiente->clave,
            'nombre_completo' => $pendiente->nombre_completo,
            'area'            => $pendiente->area,
            'seccion'         => $pendiente->seccion,
            'fecha_inicio'    => $pendiente->fecha_inicio,
            'fecha_fin'       => $pendiente->fecha_fin,
            'observaciones'   => $pendiente->observaciones,
            'pendiente_id'    => $pendiente->id,
        ]);

        $pendiente->update([
            'estado'       => 'aprobado',
            'aprobado_por' => auth()->id(),
            'aprobado_en'  => now(),
        ]);

        return redirect()->route('vacaciones.pendientes')->with('success', 'Vacaciones aprobadas.');
    }

    /**
     * Rechaza una solicitud de vacaciones pendiente.
     *
     * @param  \App\Models\VacacionPendiente  $pendiente
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rechazar(VacacionPendiente $pendiente)
    {
        $pendiente->update([
            'estado'       => 'rechazado',
            'aprobado_por' => auth()->id(),
            'aprobado_en'  => now(),
        ]);

        return redirect()->route('vacaciones.pendientes')->with('success', 'Vacaciones rechazadas.');
    }

    /**
     * Valida los datos de vacaciones.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    private function validar(Request $request): array
    {
        return $request->validate([
            'clave'         => ['required', 'integer'],
            'fecha_inicio'  => ['required', 'date'],
            'fecha_fin'     => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);
    }
}