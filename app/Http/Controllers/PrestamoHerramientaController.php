<?php

namespace App\Http\Controllers;

use App\Models\AlmacenAlmacen;
use App\Models\AlmacenExistencia;
use App\Models\AlmacenPrestamo;
use App\Models\AlmacenPrestamoMovimiento;
use App\Services\AlmacenService;
use App\Services\EmpleadoService;
use App\Services\PrestamoHerramientaService;
use App\Services\SeccionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PrestamoHerramientaController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'trabajador' => ['nullable', 'string', 'max:150'],
            'estado' => ['nullable', Rule::in(['ACTIVO', 'PARCIAL', 'DEVUELTO'])],
            'tipo' => ['nullable', Rule::in(['PRESTAMO', 'RESGUARDO'])],
            'q' => ['nullable', 'string', 'max:100'],
            'almacen' => ['nullable', 'integer', 'exists:almacen_almacenes,id'],
        ]);

        $consulta = AlmacenPrestamo::query()->with(['articulo', 'ubicacion.almacen'])
            ->whereHas('ubicacion.almacen', fn ($query) => $query->visiblesPara($request->user()))
            ->when($filtros['trabajador'] ?? null, function ($query, $termino) {
                $query->where(function ($trabajador) use ($termino) {
                    $trabajador->where('empleado_nombre', 'like', "%{$termino}%")
                        ->orWhere('numero_empleado', 'like', "%{$termino}%");
                });
            })
            ->when($filtros['estado'] ?? null, fn ($query, $estado) => $query->where('estado', $estado))
            ->when($filtros['tipo'] ?? null, fn ($query, $tipo) => $query->where('tipo_asignacion', $tipo))
            ->when($filtros['almacen'] ?? null, fn ($query, $almacen) => $query->whereHas('ubicacion', fn ($ubicacion) => $ubicacion->where('almacen_id', $almacen)))
            ->when($filtros['q'] ?? null, function ($query, $termino) {
                $query->where(function ($busqueda) use ($termino) {
                    $busqueda->where('folio', 'like', "%{$termino}%")
                        ->orWhereHas('articulo', function ($articulo) use ($termino) {
                            $articulo->where('sku', 'like', "%{$termino}%")->orWhere('nombre', 'like', "%{$termino}%");
                        });
                });
            });

        return view('prestamos-herramientas.index', [
            'prestamos' => $consulta->orderByRaw("CASE WHEN estado IN ('ACTIVO', 'PARCIAL') THEN 0 ELSE 1 END")
                ->orderByDesc('fecha_salida')->paginate(25)->withQueryString(),
            'almacenes' => $this->almacenesPara($request),
            'filtros' => $filtros,
        ]);
    }

    public function create(Request $request)
    {
        $existenciaId = $request->integer('existencia_id') ?: (int) $request->old('existencia_id');

        return view('prestamos-herramientas.create', [
            'existenciaSeleccionada' => $existenciaId
                ? $this->existenciasDisponibles($request)->find($existenciaId)
                : null,
            'empleados' => EmpleadoService::buscarParaSelector(
                $request->input('termino'),
                30,
                SeccionService::permitidas($request->user()),
            ),
            'empleadoSeleccionado' => $request->integer('numero_empleado') ?: null,
        ]);
    }

    public function buscarHerramientas(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $termino = trim((string) ($data['q'] ?? ''));

        if (mb_strlen($termino) < 2) {
            return response()->json(['resultados' => []]);
        }

        $resultados = $this->existenciasDisponibles($request)
            ->whereHas('articulo', function ($articulo) use ($termino) {
                $articulo->where(function ($busqueda) use ($termino) {
                    $busqueda->where('sku', 'like', "%{$termino}%")
                        ->orWhere('nombre', 'like', "%{$termino}%")
                        ->orWhere('numero_serie', 'like', "%{$termino}%");
                });
            })
            ->orderBy('articulo_id')
            ->orderBy('ubicacion_id')
            ->limit(20)
            ->get()
            ->map(fn ($existencia) => [
                'id' => $existencia->id,
                'sku' => $existencia->articulo->sku,
                'nombre' => $existencia->articulo->nombre,
                'numero_serie' => $existencia->articulo->numero_serie,
                'almacen' => $existencia->ubicacion->almacen->codigo,
                'ubicacion' => $existencia->ubicacion->codigo,
                'seccion' => $existencia->ubicacion->almacen->seccion,
                'disponible' => (float) $existencia->cantidad,
                'unidad' => $existencia->articulo->unidad_medida,
            ]);

        return response()->json(['resultados' => $resultados]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'existencia_id' => ['required', 'integer', 'exists:almacen_existencias,id'],
            'numero_empleado' => ['required', 'integer', 'min:1', 'max:9223372036854775807'],
            'tipo_asignacion' => ['required', Rule::in(['PRESTAMO', 'RESGUARDO'])],
            'cantidad' => ['required', 'numeric', 'gt:0', 'max:999999999999999.999'],
            'fecha_compromiso' => ['nullable', 'required_if:tipo_asignacion,PRESTAMO', 'date_format:Y-m-d', 'after_or_equal:today'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);
        $existencia = AlmacenExistencia::query()->with(['articulo', 'ubicacion.almacen'])->findOrFail($data['existencia_id']);
        AlmacenService::autorizarUbicacion($request->user(), $existencia->ubicacion_id);
        if ($existencia->articulo->tipo !== 'HERRAMIENTA') {
            throw ValidationException::withMessages(['existencia_id' => 'Selecciona una existencia de herramienta.']);
        }

        $empleado = EmpleadoService::buscarPorClave((int) $data['numero_empleado']);
        if (! $empleado) {
            throw ValidationException::withMessages(['numero_empleado' => 'Trabajador no encontrado en HC.']);
        }
        SeccionService::autorizar($request->user(), $empleado['seccion']);
        $seccionAlmacen = trim((string) $existencia->ubicacion->almacen->seccion);
        if ($seccionAlmacen !== '' && $seccionAlmacen !== trim((string) $empleado['seccion'])) {
            throw ValidationException::withMessages([
                'numero_empleado' => 'El trabajador debe pertenecer a la sección asignada al almacén.',
            ]);
        }

        $prestamo = PrestamoHerramientaService::entregar([
            ...$data,
            'articulo_id' => $existencia->articulo_id,
            'ubicacion_id' => $existencia->ubicacion_id,
        ], $empleado, $request->user());

        return redirect()->route('prestamos-herramientas.index')
            ->with('success', "Salida {$prestamo->folio} registrada correctamente.");
    }

    public function devolver(Request $request, int $prestamo)
    {
        $registro = $this->prestamoVisible($request, $prestamo);
        $data = $request->validate([
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        PrestamoHerramientaService::recibir(
            $registro->id,
            (float) $data['cantidad'],
            $request->user(),
            $data['observaciones'] ?? null,
        );

        return redirect()->route('prestamos-herramientas.index')->with('success', 'Recepción registrada correctamente.');
    }

    public function historial(Request $request)
    {
        $filtros = $request->validate([
            'trabajador' => ['nullable', 'string', 'max:150'],
            'tipo' => ['nullable', Rule::in(['SALIDA', 'DEVOLUCION'])],
            'q' => ['nullable', 'string', 'max:100'],
            'almacen' => ['nullable', 'integer', 'exists:almacen_almacenes,id'],
        ]);

        $consulta = AlmacenPrestamoMovimiento::query()->with(['prestamo.articulo', 'prestamo.ubicacion.almacen'])
            ->whereHas('prestamo.ubicacion.almacen', fn ($query) => $query->visiblesPara($request->user()))
            ->when($filtros['trabajador'] ?? null, function ($query, $termino) {
                $query->whereHas('prestamo', function ($prestamo) use ($termino) {
                    $prestamo->where('empleado_nombre', 'like', "%{$termino}%")
                        ->orWhere('numero_empleado', 'like', "%{$termino}%");
                });
            })
            ->when($filtros['tipo'] ?? null, fn ($query, $tipo) => $query->where('tipo', $tipo))
            ->when($filtros['almacen'] ?? null, fn ($query, $almacen) => $query->whereHas('prestamo.ubicacion', fn ($ubicacion) => $ubicacion->where('almacen_id', $almacen)))
            ->when($filtros['q'] ?? null, function ($query, $termino) {
                $query->whereHas('prestamo', function ($prestamo) use ($termino) {
                    $prestamo->where('folio', 'like', "%{$termino}%")
                        ->orWhereHas('articulo', function ($articulo) use ($termino) {
                            $articulo->where('sku', 'like', "%{$termino}%")->orWhere('nombre', 'like', "%{$termino}%");
                        });
                });
            });

        return view('prestamos-herramientas.historial', [
            'movimientos' => $consulta->orderByDesc('fecha_movimiento')->paginate(30)->withQueryString(),
            'almacenes' => $this->almacenesPara($request),
            'filtros' => $filtros,
        ]);
    }

    private function prestamoVisible(Request $request, int $prestamo): AlmacenPrestamo
    {
        return AlmacenPrestamo::query()
            ->whereHas('ubicacion.almacen', fn ($query) => $query->visiblesPara($request->user()))
            ->findOrFail($prestamo);
    }

    private function almacenesPara(Request $request)
    {
        return AlmacenAlmacen::query()->visiblesPara($request->user())->where('activo', true)->orderBy('nombre')->get();
    }

    private function existenciasDisponibles(Request $request)
    {
        return AlmacenExistencia::query()
            ->where('cantidad', '>', 0)
            ->whereHas('articulo', fn ($query) => $query->where('tipo', 'HERRAMIENTA')->where('activo', true))
            ->whereHas('ubicacion', function ($query) use ($request) {
                $query->where('activo', true)->whereHas('almacen', fn ($almacen) => $almacen->visiblesPara($request->user())->where('activo', true));
            })
            ->with(['articulo', 'ubicacion.almacen']);
    }
}
