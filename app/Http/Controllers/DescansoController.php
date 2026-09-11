<?php

namespace App\Http\Controllers;

use App\Exports\DescansosPlantilla;
use App\Imports\DescansosPreviewImport;
use App\Models\Descanso;
use App\Models\DescansoPendiente;
use App\Services\EmpleadoService;
use App\Services\SeccionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

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
     * @return View
     */
    public function index(Request $request)
    {
        $descansos = SeccionService::aplicar(Descanso::query(), $request->user())
            ->orderBy('fecha_inicio', 'desc')->paginate(25);

        return view('descansos.index', compact('descansos'));
    }

    /**
     * Muestra el formulario para agregar un descanso.
     *
     * @return View
     */
    public function create(Request $request)
    {
        $empleados = [];
        if ($termino = $request->input('termino')) {
            $empleados = EmpleadoService::listar(null, $termino, 50, SeccionService::permitidas($request->user()));
        }

        return view('descansos.create', compact('empleados'));
    }

    /**
     * Almacena un nuevo descanso.
     *
     * Si es "fijo" se guarda en el buzón de pendientes (espera aprobación).
     * Si es "por_periodo" se guarda directo en "descansos".
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
            'observaciones' => $data['observaciones'] ?? null,
        ];

        if ($data['tipo'] === 'fijo') {
            DescansoPendiente::create(array_merge($base, [
                'tipo' => 'fijo',
                'estado' => 'pendiente',
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
     * @return View
     */
    public function edit(Request $request, Descanso $descanso)
    {
        SeccionService::autorizar($request->user(), $descanso->seccion);

        return view('descansos.edit', compact('descanso'));
    }

    /**
     * Actualiza un descanso registrado.
     *
     * @return RedirectResponse
     */
    public function update(Request $request, Descanso $descanso)
    {
        SeccionService::autorizar($request->user(), $descanso->seccion);
        $data = $this->validar($request);
        if ($data['tipo'] !== $descanso->tipo) {
            return back()->withInput()->withErrors(['tipo' => 'No se puede cambiar el tipo de un descanso existente.']);
        }
        $empleado = EmpleadoService::buscarPorClave($data['clave']);
        if (! $empleado) {
            return back()->withInput()->withErrors(['clave' => 'Número de empleado no encontrado.']);
        }
        SeccionService::autorizar($request->user(), $empleado['seccion']);
        $data = array_merge($data, [
            'nombre_completo' => $empleado['nombre_completo'],
            'area' => $empleado['area'],
            'seccion' => $empleado['seccion'],
        ]);
        $descanso->update($data);

        return redirect()->route('descansos.index')->with('success', 'Descanso actualizado.');
    }

    /**
     * Elimina un descanso registrado.
     *
     * @return RedirectResponse
     */
    public function destroy(Request $request, Descanso $descanso)
    {
        SeccionService::autorizar($request->user(), $descanso->seccion);
        $descanso->delete();

        return redirect()->route('descansos.index')->with('success', 'Descanso eliminado.');
    }

    /**
     * Buzón de aprobación: lista los descansos fijos pendientes.
     *
     * @return View
     */
    public function pendientes(Request $request)
    {
        $pendientes = SeccionService::aplicar(DescansoPendiente::where('estado', 'pendiente'), $request->user())
            ->orderBy('fecha_inicio')
            ->get();

        return view('descansos.pendientes', compact('pendientes'));
    }

    /**
     * Aprueba un descanso fijo pendiente y lo copia a "descansos".
     *
     * @return RedirectResponse
     */
    public function aprobar(Request $request, DescansoPendiente $pendiente)
    {
        SeccionService::autorizar($request->user(), $pendiente->seccion);

        DB::transaction(function () use ($request, $pendiente) {
            $registro = DescansoPendiente::whereKey($pendiente->id)->lockForUpdate()->firstOrFail();
            if ($registro->estado !== 'pendiente') {
                throw ValidationException::withMessages(['descanso' => 'La solicitud ya fue procesada.']);
            }

            Descanso::create([
                'clave' => $registro->clave,
                'nombre_completo' => $registro->nombre_completo,
                'area' => $registro->area,
                'seccion' => $registro->seccion,
                'tipo' => 'fijo',
                'fecha_inicio' => $registro->fecha_inicio,
                'fecha_fin' => $registro->fecha_fin,
                'observaciones' => $registro->observaciones,
                'activo' => true,
                'pendiente_id' => $registro->id,
            ]);
            $registro->update(['estado' => 'aprobado', 'aprobado_por' => $request->user()->id, 'aprobado_en' => now()]);
        });

        return redirect()->route('descansos.pendientes')->with('success', 'Descanso fijo aprobado.');
    }

    /**
     * Rechaza/elimina un descanso fijo pendiente.
     *
     * @return RedirectResponse
     */
    public function rechazar(Request $request, DescansoPendiente $pendiente)
    {
        SeccionService::autorizar($request->user(), $pendiente->seccion);
        $actualizados = DescansoPendiente::whereKey($pendiente->id)->where('estado', 'pendiente')->update([
            'estado' => 'rechazado',
            'aprobado_por' => $request->user()->id,
            'aprobado_en' => now(),
        ]);

        if ($actualizados === 0) {
            return back()->with('error', 'La solicitud ya fue procesada.');
        }

        return redirect()->route('descansos.pendientes')->with('success', 'Descanso fijo rechazado.');
    }

    /**
     * Valida los datos de un descanso.
     */
    private function validar(Request $request): array
    {
        return $request->validate([
            'clave' => ['required', 'integer'],
            'tipo' => ['required', 'in:fijo,por_periodo'],
            'fecha_inicio' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.now()->subDays(10)->format('Y-m-d')],
            'fecha_fin' => ['required', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);
    }

    public function importar()
    {
        return view('descansos.importar');
    }

    public function plantilla()
    {
        return Excel::download(new DescansosPlantilla, 'plantilla_descansos.xlsx');
    }

    public function previsualizar(Request $request)
    {
        $request->validate(['archivo' => ['required', 'file', 'mimes:xlsx', 'max:5120']]);
        $hojas = Excel::toCollection(new DescansosPreviewImport, $request->file('archivo'));
        $filasCrudas = $hojas->first() ?? collect();

        if ($filasCrudas->isEmpty()) {
            return back()->withErrors(['archivo' => 'El archivo no contiene filas.']);
        }
        if ($filasCrudas->count() > 1000) {
            return back()->withErrors(['archivo' => 'La carga admite como máximo 1,000 filas.']);
        }

        $claves = $filasCrudas->pluck('numero_empleado')
            ->filter(fn ($valor) => is_numeric($valor))
            ->map(fn ($valor) => (int) $valor)
            ->all();
        $empleados = EmpleadoService::buscarVarios($claves);
        $filas = [];
        $errores = [];
        $minimo = now()->subDays(10)->format('Y-m-d');

        foreach ($filasCrudas as $indice => $fila) {
            if ($fila->filter(fn ($valor) => $valor !== null && $valor !== '')->isEmpty()) {
                continue;
            }

            $numero = $indice + 2;
            $datos = [
                'numero_empleado' => $fila['numero_empleado'] ?? null,
                'tipo' => $fila['tipo'] ?? null,
                'fecha_inicio' => $fila['fecha_inicio'] ?? null,
                'fecha_fin' => $fila['fecha_fin'] ?? null,
                'observaciones' => $fila['observaciones'] ?? null,
            ];
            $validador = validator($datos, [
                'numero_empleado' => ['required', 'integer'],
                'tipo' => ['required', 'in:fijo,por_periodo'],
                'fecha_inicio' => ['required', 'date_format:Y-m-d', "after_or_equal:{$minimo}"],
                'fecha_fin' => ['required', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
                'observaciones' => ['nullable', 'string', 'max:500'],
            ]);

            if ($validador->fails()) {
                foreach ($validador->errors()->all() as $error) {
                    $errores[] = "Fila {$numero}: {$error}";
                }

                continue;
            }

            $clave = (int) $datos['numero_empleado'];
            $empleado = $empleados->get($clave);
            if (! $empleado) {
                $errores[] = "Fila {$numero}: número de empleado {$clave} no encontrado.";

                continue;
            }
            if (! $request->user()->puedeVerSeccion($empleado['seccion'])) {
                $errores[] = "Fila {$numero}: no tienes acceso a la sección del empleado {$clave}.";

                continue;
            }

            $filas[] = [
                'clave' => $clave,
                'nombre_completo' => $empleado['nombre_completo'],
                'area' => $empleado['area'],
                'seccion' => $empleado['seccion'],
                'tipo' => $datos['tipo'],
                'fecha_inicio' => $datos['fecha_inicio'],
                'fecha_fin' => $datos['fecha_fin'],
                'observaciones' => $datos['observaciones'],
            ];
        }

        if ($errores) {
            return back()->withInput()->withErrors(['archivo' => $errores]);
        }
        if (! $filas) {
            return back()->withErrors(['archivo' => 'El archivo no contiene filas con datos.']);
        }

        $token = (string) Str::uuid();
        Cache::put("descansos_preview:{$token}", [
            'usuario_id' => $request->user()->id,
            'filas' => $filas,
        ], now()->addMinutes(30));

        return view('descansos.previsualizar', compact('filas', 'token'));
    }

    public function confirmarImportacion(Request $request)
    {
        $request->validate(['token' => ['required', 'uuid']]);
        $claveCache = 'descansos_preview:'.$request->input('token');
        $procesados = Cache::lock("confirmar:{$claveCache}", 15)->get(function () use ($request, $claveCache) {
            $carga = Cache::get($claveCache);
            if (! $carga || $carga['usuario_id'] !== $request->user()->id) {
                return null;
            }

            foreach ($carga['filas'] as $fila) {
                SeccionService::autorizar($request->user(), $fila['seccion']);
            }

            DB::transaction(function () use ($request, $carga) {
                foreach ($carga['filas'] as $fila) {
                    if ($fila['tipo'] === 'fijo') {
                        DescansoPendiente::create(array_merge($fila, [
                            'estado' => 'pendiente',
                            'creado_por' => $request->user()->id,
                        ]));
                    } else {
                        Descanso::create(array_merge($fila, ['activo' => true]));
                    }
                }
            });

            Cache::forget($claveCache);

            return count($carga['filas']);
        });

        if (! is_int($procesados)) {
            return redirect()->route('descansos.importar')->with('error', 'La previsualización venció o ya está siendo procesada. Carga el archivo nuevamente.');
        }

        return redirect()->route('descansos.index')
            ->with('success', $procesados.' descansos procesados correctamente.');
    }
}
