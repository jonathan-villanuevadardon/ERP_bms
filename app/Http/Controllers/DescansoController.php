<?php

namespace App\Http\Controllers;

use App\Models\Descanso;
use App\Models\DescansoPendiente;
use App\Services\EmpleadoService;
use Illuminate\Http\Request;

/**
 * Controlador "DescansoController".
 *
 * Módulo "Descansos". Los empleados de RH agregan periodos de descanso:
 *   - "fijo": descanso recurrente que ANTES de pasar a la tabla "descansos"
 *     debe ser aprobado por el Admin (flujo de aprobación).
 *   - "por_periodo": descanso puntual; se registra directo en "descansos".
 *
 * Los fijos pendientes se gestionan en "descansos_pendientes". Mientras estén
 * pendientes, tanto RH como Admin pueden modificarlos. Tras aprobarse pasan a
 * la tabla "descansos".
 */
class DescansoController extends Controller
{
    /**
     * Lista los descansos ya registrados (aprobados/por_periodo).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $descansos = Descanso::orderBy('fecha_inicio', 'desc')->paginate(25);

        return view('descansos.index', compact('descansos'));
    }

    /**
     * Muestra el formulario para agregar un descanso.
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

        return view('descansos.create', compact('empleados'));
    }

    /**
     * Almacena un nuevo descanso.
     *
     * Si es "fijo" se guarda en el buzón de pendientes (espera aprobación).
     * Si es "por_periodo" se guarda directo en "descansos".
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
            'observaciones'   => $data['observaciones'] ?? null,
        ];

        if ($data['tipo'] === 'fijo') {
            DescansoPendiente::create(array_merge($base, [
                'tipo'       => 'fijo',
                'estado'     => 'pendiente',
                'creado_por' => $request->user()->id,
            ]));

            return redirect()->route('descansos.pendientes')
                ->with('success', 'Descanso fijo enviado a aprobación.');
        }

        // por_periodo: registro directo.
        Descanso::create(array_merge($base, ['tipo' => 'por_periodo', 'activo' => true]));

        return redirect()->route('descansos.index')
            ->with('success', 'Descanso agregado correctamente.');
    }

    /**
     * Muestra el formulario de edición de un descanso ya registrado.
     *
     * @param  \App\Models\Descanso  $descanso
     * @return \Illuminate\View\View
     */
    public function edit(Descanso $descanso)
    {
        return view('descansos.edit', compact('descanso'));
    }

    /**
     * Actualiza un descanso registrado.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Descanso  $descanso
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Descanso $descanso)
    {
        $data = $this->validar($request);
        $descanso->update($data);

        return redirect()->route('descansos.index')->with('success', 'Descanso actualizado.');
    }

    /**
     * Elimina un descanso registrado.
     *
     * @param  \App\Models\Descanso  $descanso
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Descanso $descanso)
    {
        $descanso->delete();

        return redirect()->route('descansos.index')->with('success', 'Descanso eliminado.');
    }

    /**
     * Buzón de aprobación: lista los descansos fijos pendientes.
     *
     * @return \Illuminate\View\View
     */
    public function pendientes()
    {
        $pendientes = DescansoPendiente::where('estado', 'pendiente')
            ->orderBy('fecha_inicio')
            ->get();

        return view('descansos.pendientes', compact('pendientes'));
    }

    /**
     * Aprueba un descanso fijo pendiente y lo copia a "descansos".
     *
     * @param  \App\Models\DescansoPendiente  $pendiente
     * @return \Illuminate\Http\RedirectResponse
     */
    public function aprobar(DescansoPendiente $pendiente)
    {
        $descanso = Descanso::create([
            'clave'           => $pendiente->clave,
            'nombre_completo' => $pendiente->nombre_completo,
            'area'            => $pendiente->area,
            'seccion'         => $pendiente->seccion,
            'tipo'            => 'fijo',
            'fecha_inicio'    => $pendiente->fecha_inicio,
            'fecha_fin'       => $pendiente->fecha_fin,
            'observaciones'   => $pendiente->observaciones,
            'activo'          => true,
            'pendiente_id'    => $pendiente->id,
        ]);

        $pendiente->update([
            'estado'       => 'aprobado',
            'aprobado_por' => auth()->id(),
            'aprobado_en'  => now(),
        ]);

        return redirect()->route('descansos.pendientes')->with('success', 'Descanso fijo aprobado.');
    }

    /**
     * Rechaza/elimina un descanso fijo pendiente.
     *
     * @param  \App\Models\DescansoPendiente  $pendiente
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rechazar(DescansoPendiente $pendiente)
    {
        $pendiente->update([
            'estado'       => 'rechazado',
            'aprobado_por' => auth()->id(),
            'aprobado_en'  => now(),
        ]);

        return redirect()->route('descansos.pendientes')->with('success', 'Descanso fijo rechazado.');
    }

    /**
     * Valida los datos de un descanso.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    private function validar(Request $request): array
    {
        return $request->validate([
            'clave'         => ['required', 'integer'],
            'tipo'          => ['required', 'in:fijo,por_periodo'],
            'fecha_inicio'  => ['required', 'date'],
            'fecha_fin'     => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);
    }
}