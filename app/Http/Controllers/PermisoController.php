<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use App\Models\PermisoPendiente;
use App\Services\EmpleadoService;
use App\Services\SeccionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Controlador "PermisoController".
 *
 * Módulo "Permisos" (con o sin goce de sueldo).
 *   - "sin_goce": se registra directo en "permisos" (no requiere aprobación).
 *   - "con_goce": pasa primero por "permisos_pendientes" y requiere aprobación
 *     del Admin; al aprobarse se copia a "permisos".
 *
 * Mientras estén pendientes, ambos lados pueden modificarlos.
 */
class PermisoController extends Controller
{
    /**
     * Lista los permisos registrados.
     *
     * @return View
     */
    public function index(Request $request)
    {
        $permisos = SeccionService::aplicar(Permiso::query(), $request->user())
            ->orderBy('fecha_inicio', 'desc')->paginate(25);

        return view('permisos.index', compact('permisos'));
    }

    /**
     * Muestra el formulario para agregar un permiso.
     *
     * @return View
     */
    public function create(Request $request)
    {
        $empleados = [];
        if ($termino = $request->input('termino')) {
            $empleados = EmpleadoService::listar(null, $termino, 50, SeccionService::permitidas($request->user()));
        }

        return view('permisos.create', compact('empleados'));
    }

    /**
     * Guarda un permiso.
     *
     * Si es "con_goce" se envía a aprobación; si es "sin_goce" se registra
     * directo en "permisos".
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

        $base = [
            'clave' => $data['clave'],
            'nombre_completo' => $empleado['nombre_completo'],
            'area' => $empleado['area'],
            'seccion' => $empleado['seccion'],
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'] ?? null,
            'motivo' => $data['motivo'] ?? null,
        ];

        if ($data['tipo'] === 'con_goce') {
            PermisoPendiente::create(array_merge($base, [
                'tipo' => 'con_goce',
                'estado' => 'pendiente',
                'creado_por' => $request->user()->id,
            ]));

            return redirect()->route('permisos.pendientes')
                ->with('success', 'Permiso con goce enviado a aprobación.');
        }

        Permiso::create(array_merge($base, ['tipo' => 'sin_goce']));

        return redirect()->route('permisos.index')->with('success', 'Permiso registrado.');
    }

    /**
     * Buzón de aprobación de permisos con goce pendientes.
     *
     * @return View
     */
    public function pendientes(Request $request)
    {
        $pendientes = SeccionService::aplicar(PermisoPendiente::where('estado', 'pendiente'), $request->user())
            ->orderBy('fecha_inicio')
            ->get();

        return view('permisos.pendientes', compact('pendientes'));
    }

    /**
     * Muestra el formulario de edición de un permiso pendiente.
     *
     * @return View
     */
    public function editarPendiente(Request $request, PermisoPendiente $pendiente)
    {
        $this->autorizarPendiente($request, $pendiente);

        return view('permisos.editar_pendiente', compact('pendiente'));
    }

    /**
     * Actualiza un permiso pendiente (solo antes de aprobarse).
     *
     * @return RedirectResponse
     */
    public function actualizarPendiente(Request $request, PermisoPendiente $pendiente)
    {
        $this->autorizarPendiente($request, $pendiente);
        $data = $this->validarEdicion($request);
        $pendiente->update([
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'] ?? null,
            'motivo' => $data['motivo'] ?? null,
        ]);

        return redirect()->route('permisos.pendientes')->with('success', 'Permiso actualizado.');
    }

    /**
     * Elimina un permiso pendiente.
     *
     * @return RedirectResponse
     */
    public function eliminarPendiente(Request $request, PermisoPendiente $pendiente)
    {
        $this->autorizarPendiente($request, $pendiente);
        $pendiente->delete();

        return redirect()->route('permisos.pendientes')->with('success', 'Permiso eliminado.');
    }

    /**
     * Aprueba un permiso con goce y lo copia a "permisos".
     *
     * @return RedirectResponse
     */
    public function aprobar(Request $request, PermisoPendiente $pendiente)
    {
        SeccionService::autorizar($request->user(), $pendiente->seccion);
        DB::transaction(function () use ($request, $pendiente) {
            $registro = PermisoPendiente::whereKey($pendiente->id)->lockForUpdate()->firstOrFail();
            if ($registro->estado !== 'pendiente') {
                throw ValidationException::withMessages(['permiso' => 'La solicitud ya fue procesada.']);
            }

            Permiso::create([
                'clave' => $registro->clave, 'nombre_completo' => $registro->nombre_completo,
                'area' => $registro->area, 'seccion' => $registro->seccion, 'tipo' => 'con_goce',
                'fecha_inicio' => $registro->fecha_inicio, 'fecha_fin' => $registro->fecha_fin,
                'motivo' => $registro->motivo, 'pendiente_id' => $registro->id,
            ]);
            $registro->update(['estado' => 'aprobado', 'aprobado_por' => $request->user()->id, 'aprobado_en' => now()]);
        });

        return redirect()->route('permisos.pendientes')->with('success', 'Permiso aprobado.');
    }

    /**
     * Rechaza un permiso con goce pendiente.
     *
     * @return RedirectResponse
     */
    public function rechazar(Request $request, PermisoPendiente $pendiente)
    {
        SeccionService::autorizar($request->user(), $pendiente->seccion);
        $actualizados = PermisoPendiente::whereKey($pendiente->id)->where('estado', 'pendiente')->update([
            'estado' => 'rechazado',
            'aprobado_por' => $request->user()->id,
            'aprobado_en' => now(),
        ]);

        if ($actualizados === 0) {
            return back()->with('error', 'La solicitud ya fue procesada.');
        }

        return redirect()->route('permisos.pendientes')->with('success', 'Permiso rechazado.');
    }

    /**
     * Valida los datos de un permiso.
     */
    private function validar(Request $request): array
    {
        return $request->validate([
            'clave' => ['required', 'integer'],
            'tipo' => ['required', 'in:con_goce,sin_goce'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'motivo' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function validarEdicion(Request $request): array
    {
        return $request->validate([
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'fecha_fin' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
            'motivo' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function autorizarPendiente(Request $request, PermisoPendiente $pendiente): void
    {
        SeccionService::autorizar($request->user(), $pendiente->seccion);
        if ($pendiente->estado !== 'pendiente') {
            abort(409, 'Solo se pueden modificar permisos pendientes.');
        }
    }
}
