<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use App\Models\PermisoPendiente;
use App\Services\EmpleadoService;
use Illuminate\Http\Request;

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
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $permisos = Permiso::orderBy('fecha_inicio', 'desc')->paginate(25);

        return view('permisos.index', compact('permisos'));
    }

    /**
     * Muestra el formulario para agregar un permiso.
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

        return view('permisos.create', compact('empleados'));
    }

    /**
     * Guarda un permiso.
     *
     * Si es "con_goce" se envía a aprobación; si es "sin_goce" se registra
     * directo en "permisos".
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

        $base = [
            'clave'           => $data['clave'],
            'nombre_completo' => $empleado['nombre_completo'],
            'area'            => $empleado['area'],
            'seccion'         => $empleado['seccion'],
            'fecha_inicio'    => $data['fecha_inicio'],
            'fecha_fin'       => $data['fecha_fin'] ?? null,
            'motivo'          => $data['motivo'] ?? null,
        ];

        if ($data['tipo'] === 'con_goce') {
            PermisoPendiente::create(array_merge($base, [
                'tipo'       => 'con_goce',
                'estado'     => 'pendiente',
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
     * @return \Illuminate\View\View
     */
    public function pendientes()
    {
        $pendientes = PermisoPendiente::where('estado', 'pendiente')
            ->orderBy('fecha_inicio')
            ->get();

        return view('permisos.pendientes', compact('pendientes'));
    }

    /**
     * Muestra el formulario de edición de un permiso pendiente.
     *
     * @param  \App\Models\PermisoPendiente  $pendiente
     * @return \Illuminate\View\View
     */
    public function editarPendiente(PermisoPendiente $pendiente)
    {
        return view('permisos.editar_pendiente', compact('pendiente'));
    }

    /**
     * Actualiza un permiso pendiente (solo antes de aprobarse).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PermisoPendiente  $pendiente
     * @return \Illuminate\Http\RedirectResponse
     */
    public function actualizarPendiente(Request $request, PermisoPendiente $pendiente)
    {
        if ($pendiente->estado !== 'pendiente') {
            return back()->with('error', 'Solo se pueden modificar permisos pendientes.');
        }

        $data = $this->validar($request);
        $pendiente->update([
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin'    => $data['fecha_fin'] ?? null,
            'motivo'       => $data['motivo'] ?? null,
        ]);

        return redirect()->route('permisos.pendientes')->with('success', 'Permiso actualizado.');
    }

    /**
     * Elimina un permiso pendiente.
     *
     * @param  \App\Models\PermisoPendiente  $pendiente
     * @return \Illuminate\Http\RedirectResponse
     */
    public function eliminarPendiente(PermisoPendiente $pendiente)
    {
        if ($pendiente->estado !== 'pendiente') {
            return back()->with('error', 'Solo se puede eliminar un permiso pendiente.');
        }

        $pendiente->delete();

        return redirect()->route('permisos.pendientes')->with('success', 'Permiso eliminado.');
    }

    /**
     * Aprueba un permiso con goce y lo copia a "permisos".
     *
     * @param  \App\Models\PermisoPendiente  $pendiente
     * @return \Illuminate\Http\RedirectResponse
     */
    public function aprobar(PermisoPendiente $pendiente)
    {
        Permiso::create([
            'clave'           => $pendiente->clave,
            'nombre_completo' => $pendiente->nombre_completo,
            'area'            => $pendiente->area,
            'seccion'         => $pendiente->seccion,
            'tipo'            => 'con_goce',
            'fecha_inicio'    => $pendiente->fecha_inicio,
            'fecha_fin'       => $pendiente->fecha_fin,
            'motivo'          => $pendiente->motivo,
            'pendiente_id'    => $pendiente->id,
        ]);

        $pendiente->update([
            'estado'       => 'aprobado',
            'aprobado_por' => auth()->id(),
            'aprobado_en'  => now(),
        ]);

        return redirect()->route('permisos.pendientes')->with('success', 'Permiso aprobado.');
    }

    /**
     * Rechaza un permiso con goce pendiente.
     *
     * @param  \App\Models\PermisoPendiente  $pendiente
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rechazar(PermisoPendiente $pendiente)
    {
        $pendiente->update([
            'estado'       => 'rechazado',
            'aprobado_por' => auth()->id(),
            'aprobado_en'  => now(),
        ]);

        return redirect()->route('permisos.pendientes')->with('success', 'Permiso rechazado.');
    }

    /**
     * Valida los datos de un permiso.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    private function validar(Request $request): array
    {
        return $request->validate([
            'clave'        => ['required', 'integer'],
            'tipo'         => ['required', 'in:con_goce,sin_goce'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin'    => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'motivo'       => ['nullable', 'string', 'max:500'],
        ]);
    }
}