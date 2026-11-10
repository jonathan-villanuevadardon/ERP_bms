<?php

namespace App\Http\Controllers;

use App\Models\AlmacenAlmacen;
use App\Models\AlmacenArticulo;
use App\Models\AlmacenExistencia;
use App\Models\AlmacenMovimiento;
use App\Models\AlmacenUbicacion;
use App\Services\AlmacenService;
use App\Services\EmpleadoService;
use App\Services\SeccionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InventarioController extends Controller
{
    private const TIPOS_ARTICULO = ['MATERIAL', 'HERRAMIENTA'];

    private const TIPOS_MOVIMIENTO = ['ENTRADA', 'SALIDA', 'AJUSTE_ENTRADA', 'AJUSTE_SALIDA', 'TRANSFERENCIA'];

    private const UNIDADES_MEDIDA = [
        'pieza' => 'Pieza',
        'juego' => 'Juego',
        'caja' => 'Caja',
        'paquete' => 'Paquete',
        'rollo' => 'Rollo',
        'gramo' => 'Gramo (g)',
        'kilogramo' => 'Kilogramo (kg)',
        'tonelada' => 'Tonelada (t)',
        'mililitro' => 'Mililitro (ml)',
        'litro' => 'Litro (L)',
        'centimetro' => 'Centímetro (cm)',
        'metro' => 'Metro (m)',
    ];

    public function index(Request $request)
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'tipo' => ['nullable', Rule::in(self::TIPOS_ARTICULO)],
            'alerta' => ['nullable', Rule::in(['MINIMO', 'MAXIMO'])],
            'almacen' => ['nullable', 'integer', 'exists:almacen_almacenes,id'],
            'ubicacion' => ['nullable', 'integer', 'exists:almacen_ubicaciones,id'],
        ]);

        $base = AlmacenExistencia::query()
            ->with(['articulo', 'ubicacion.almacen'])
            ->whereHas('articulo', fn ($query) => $query->where('activo', true))
            ->whereHas('ubicacion', function ($query) use ($request) {
                $query->where('activo', true)->whereHas('almacen', function ($almacen) use ($request) {
                    $almacen->visiblesPara($request->user())->where('activo', true);
                });
            });

        $consulta = (clone $base)
            ->when($filtros['q'] ?? null, function ($query, $termino) {
                $query->whereHas('articulo', function ($articulo) use ($termino) {
                    $articulo->where('sku', 'like', "%{$termino}%")
                        ->orWhere('nombre', 'like', "%{$termino}%")
                        ->orWhere('numero_serie', 'like', "%{$termino}%");
                });
            })
            ->when($filtros['tipo'] ?? null, fn ($query, $tipo) => $query->whereHas('articulo', fn ($articulo) => $articulo->where('tipo', $tipo)))
            ->when($filtros['almacen'] ?? null, fn ($query, $almacen) => $query->whereHas('ubicacion', fn ($ubicacion) => $ubicacion->where('almacen_id', $almacen)))
            ->when($filtros['ubicacion'] ?? null, fn ($query, $ubicacion) => $query->where('ubicacion_id', $ubicacion))
            ->when(($filtros['alerta'] ?? null) === 'MINIMO', fn ($query) => $query->whereColumn('cantidad', '<=', 'stock_minimo'))
            ->when(($filtros['alerta'] ?? null) === 'MAXIMO', function ($query) {
                $query->whereNotNull('stock_maximo')->whereColumn('cantidad', '>=', 'stock_maximo');
            });

        $resumen = (clone $base)->get();
        $totalesPorUnidad = $resumen->groupBy(fn ($existencia) => $existencia->articulo->unidad_medida)
            ->map(fn ($existencias, $unidad) => (object) [
                'unidad_medida' => $unidad,
                'existencia_total' => $existencias->sum(fn ($existencia) => (float) $existencia->cantidad),
            ])->values();

        $almacenes = AlmacenAlmacen::query()->visiblesPara($request->user())
            ->where('activo', true)->with(['ubicaciones' => fn ($query) => $query->where('activo', true)->orderBy('codigo')])
            ->orderBy('nombre')->get();

        return view('inventario.index', [
            'existencias' => $consulta->orderBy('articulo_id')->orderBy('ubicacion_id')->paginate(25)->withQueryString(),
            'almacenes' => $almacenes,
            'filtros' => $filtros,
            'totalArticulos' => $resumen->pluck('articulo_id')->unique()->count(),
            'valorTotal' => $resumen->sum(fn ($existencia) => (float) $existencia->cantidad * (float) $existencia->articulo->costo_promedio),
            'totalesPorUnidad' => $totalesPorUnidad,
            'alertasMinimo' => (clone $base)->whereColumn('cantidad', '<=', 'stock_minimo')->count(),
            'alertasMaximo' => (clone $base)->whereNotNull('stock_maximo')->whereColumn('cantidad', '>=', 'stock_maximo')->count(),
        ]);
    }

    public function create(Request $request)
    {
        return view('inventario.articulo-form', [
            'articulo' => null,
            'ubicaciones' => $this->ubicacionesPara($request),
            'existencias' => collect(),
            'unidadesMedida' => self::UNIDADES_MEDIDA,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validarArticulo($request);
        $stockInicial = (float) $data['stock_inicial'];
        $costoInicial = (float) ($data['costo_unitario_inicial'] ?? 0);
        $ubicacionId = (int) $data['ubicacion_id'];
        AlmacenService::autorizarUbicacion($request->user(), $ubicacionId);

        if ($stockInicial > 0 && $costoInicial <= 0) {
            throw ValidationException::withMessages([
                'costo_unitario_inicial' => 'Indica el costo unitario del inventario inicial.',
            ]);
        }

        DB::transaction(function () use ($data, $stockInicial, $costoInicial, $ubicacionId, $request) {
            $articulo = AlmacenArticulo::query()->create([
                'sku' => $data['sku'],
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
                'tipo' => $data['tipo'],
                'unidad_medida' => $data['unidad_medida'],
                'numero_serie' => $data['numero_serie'] ?? null,
                'ubicacion_id' => $ubicacionId,
                'stock_actual' => 0,
                'stock_minimo' => $data['stock_minimo'],
                'stock_maximo' => $data['stock_maximo'] ?? null,
                'costo_promedio' => 0,
                'activo' => $data['activo'],
            ]);

            AlmacenExistencia::query()->create([
                'articulo_id' => $articulo->id,
                'ubicacion_id' => $ubicacionId,
                'cantidad' => 0,
                'stock_minimo' => $data['stock_minimo'],
                'stock_maximo' => $data['stock_maximo'] ?? null,
            ]);

            if ($stockInicial > 0) {
                AlmacenService::aplicar($articulo, [
                    'tipo' => 'ENTRADA',
                    'cantidad' => $stockInicial,
                    'costo_unitario' => $costoInicial,
                    'ubicacion_destino_id' => $ubicacionId,
                    'referencia' => 'INVENTARIO-INICIAL',
                    'usuario_id' => $request->user()->id,
                    'observaciones' => 'Carga inicial del artículo.',
                ]);
            }
        });

        return redirect()->route('inventario.index')->with('success', 'Artículo registrado correctamente.');
    }

    public function edit(Request $request, AlmacenArticulo $articulo)
    {
        $this->autorizarEdicionArticulo($request, $articulo);

        return view('inventario.articulo-form', [
            'articulo' => $articulo,
            'ubicaciones' => $this->ubicacionesPara($request),
            'existencias' => $articulo->existencias()
                ->whereHas('ubicacion.almacen', fn ($query) => $query->visiblesPara($request->user()))
                ->with('ubicacion.almacen')->orderBy('ubicacion_id')->get(),
            'unidadesMedida' => self::UNIDADES_MEDIDA,
        ]);
    }

    public function update(Request $request, AlmacenArticulo $articulo)
    {
        $this->autorizarEdicionArticulo($request, $articulo);
        $data = $this->validarArticulo($request, $articulo);
        $tienePrestamosAbiertos = $articulo->prestamos()->whereIn('estado', ['ACTIVO', 'PARCIAL'])->exists();
        if ($tienePrestamosAbiertos && ($data['tipo'] !== 'HERRAMIENTA' || ! $data['activo'])) {
            throw ValidationException::withMessages([
                'activo' => 'No puedes cambiar el tipo ni desactivar una herramienta con préstamos pendientes.',
            ]);
        }
        if (! $data['activo'] && $articulo->existencias()->where('cantidad', '>', 0)->exists()) {
            throw ValidationException::withMessages([
                'activo' => 'No puedes desactivar un artículo con existencias disponibles.',
            ]);
        }
        $articulo->update($data);

        return redirect()->route('inventario.index')->with('success', 'Artículo actualizado correctamente.');
    }

    public function movimientos(Request $request)
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'tipo' => ['nullable', 'string', 'max:30'],
            'trabajador' => ['nullable', 'string', 'max:150'],
            'almacen' => ['nullable', 'integer', 'exists:almacen_almacenes,id'],
        ]);
        $secciones = SeccionService::permitidas($request->user());

        $consulta = AlmacenMovimiento::query()->with(['articulo', 'ubicacionOrigen.almacen', 'ubicacionDestino.almacen'])
            ->when($secciones !== null, function ($query) use ($secciones) {
                $query->where(function ($alcance) use ($secciones) {
                    $alcance->where(function ($soloOrigen) use ($secciones) {
                        $soloOrigen->whereNotNull('ubicacion_origen_id')->whereNull('ubicacion_destino_id')
                            ->whereHas('ubicacionOrigen.almacen', fn ($almacen) => $almacen->whereIn('seccion', $secciones));
                    })->orWhere(function ($soloDestino) use ($secciones) {
                        $soloDestino->whereNull('ubicacion_origen_id')->whereNotNull('ubicacion_destino_id')
                            ->whereHas('ubicacionDestino.almacen', fn ($almacen) => $almacen->whereIn('seccion', $secciones));
                    })->orWhere(function ($transferencia) use ($secciones) {
                        $transferencia->whereNotNull('ubicacion_origen_id')->whereNotNull('ubicacion_destino_id')
                            ->whereHas('ubicacionOrigen.almacen', fn ($almacen) => $almacen->whereIn('seccion', $secciones))
                            ->whereHas('ubicacionDestino.almacen', fn ($almacen) => $almacen->whereIn('seccion', $secciones));
                    })
                        ->orWhere(function ($legado) use ($secciones) {
                            $legado->whereNull('ubicacion_origen_id')->whereNull('ubicacion_destino_id')
                                ->whereIn('empleado_seccion', $secciones);
                        });
                });
            })
            ->when($filtros['q'] ?? null, function ($query, $termino) {
                $query->whereHas('articulo', function ($articulo) use ($termino) {
                    $articulo->where('sku', 'like', "%{$termino}%")->orWhere('nombre', 'like', "%{$termino}%");
                });
            })
            ->when($filtros['tipo'] ?? null, fn ($query, $tipo) => $query->where('tipo', $tipo))
            ->when($filtros['trabajador'] ?? null, function ($query, $termino) {
                $query->where(function ($trabajador) use ($termino) {
                    $trabajador->where('empleado_nombre', 'like', "%{$termino}%")
                        ->orWhere('numero_empleado', 'like', "%{$termino}%");
                });
            })
            ->when($filtros['almacen'] ?? null, function ($query, $almacen) {
                $query->where(function ($ubicacion) use ($almacen) {
                    $ubicacion->whereHas('ubicacionOrigen', fn ($origen) => $origen->where('almacen_id', $almacen))
                        ->orWhereHas('ubicacionDestino', fn ($destino) => $destino->where('almacen_id', $almacen));
                });
            });

        return view('inventario.movimientos', [
            'movimientos' => $consulta->orderByDesc('fecha_movimiento')->paginate(30)->withQueryString(),
            'almacenes' => AlmacenAlmacen::query()->visiblesPara($request->user())->where('activo', true)->orderBy('nombre')->get(),
            'filtros' => $filtros,
        ]);
    }

    public function crearMovimiento(Request $request)
    {
        $usuario = $request->user();

        return view('inventario.movimiento-form', [
            'articulos' => AlmacenArticulo::query()
                ->where('activo', true)
                ->withSum(['existencias as stock_visible' => function ($query) use ($usuario) {
                    $query->whereHas('ubicacion.almacen', fn ($almacen) => $almacen->visiblesPara($usuario));
                }], 'cantidad')
                ->orderBy('nombre')->get(),
            'ubicaciones' => $this->ubicacionesPara($request),
            'empleados' => EmpleadoService::buscarParaSelector(
                $request->input('termino'),
                30,
                SeccionService::permitidas($request->user()),
            ),
            'empleadoSeleccionado' => $request->integer('numero_empleado') ?: null,
        ]);
    }

    public function guardarMovimiento(Request $request)
    {
        $data = $request->validate([
            'articulo_id' => ['required', 'integer', 'exists:almacen_articulos,id'],
            'tipo' => ['required', Rule::in(self::TIPOS_MOVIMIENTO)],
            'cantidad' => ['required', 'numeric', 'gt:0', 'max:999999999999999.999'],
            'costo_unitario' => ['nullable', 'numeric', 'gte:0', 'max:99999999999999.9999'],
            'ubicacion_origen_id' => ['nullable', 'integer', 'exists:almacen_ubicaciones,id'],
            'ubicacion_destino_id' => ['nullable', 'integer', 'exists:almacen_ubicaciones,id'],
            'referencia' => ['nullable', 'string', 'max:100'],
            'numero_empleado' => ['nullable', 'integer', 'min:1', 'max:9223372036854775807'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $esEntrada = in_array($data['tipo'], ['ENTRADA', 'AJUSTE_ENTRADA'], true);
        $esSalida = in_array($data['tipo'], ['SALIDA', 'AJUSTE_SALIDA'], true);
        $esTransferencia = $data['tipo'] === 'TRANSFERENCIA';
        if (($esSalida || $esTransferencia) && empty($data['ubicacion_origen_id'])) {
            throw ValidationException::withMessages(['ubicacion_origen_id' => 'Selecciona la ubicación de origen.']);
        }
        if (($esEntrada || $esTransferencia) && empty($data['ubicacion_destino_id'])) {
            throw ValidationException::withMessages(['ubicacion_destino_id' => 'Selecciona la ubicación de destino.']);
        }
        if ($esEntrada && ! empty($data['ubicacion_origen_id'])) {
            throw ValidationException::withMessages(['ubicacion_origen_id' => 'Las entradas no pueden indicar una ubicación de origen.']);
        }
        if ($esSalida && ! empty($data['ubicacion_destino_id'])) {
            throw ValidationException::withMessages(['ubicacion_destino_id' => 'Las salidas no pueden indicar una ubicación de destino.']);
        }
        if ($data['tipo'] === 'ENTRADA' && (float) ($data['costo_unitario'] ?? 0) <= 0) {
            throw ValidationException::withMessages(['costo_unitario' => 'La entrada requiere un costo unitario mayor a cero.']);
        }

        $ubicacionOrigen = null;
        if (! empty($data['ubicacion_origen_id'])) {
            $ubicacionOrigen = AlmacenService::autorizarUbicacion($request->user(), (int) $data['ubicacion_origen_id']);
        }
        if (! empty($data['ubicacion_destino_id'])) {
            AlmacenService::autorizarUbicacion($request->user(), (int) $data['ubicacion_destino_id']);
        }

        $articulo = AlmacenArticulo::query()->findOrFail($data['articulo_id']);
        if ($data['tipo'] === 'SALIDA' && $articulo->tipo === 'HERRAMIENTA') {
            throw ValidationException::withMessages([
                'articulo_id' => 'Las herramientas deben salir mediante el módulo de préstamos.',
            ]);
        }
        if (! $esEntrada) {
            $data['costo_unitario'] = null;
        }

        $empleado = null;
        if ($data['tipo'] === 'SALIDA') {
            if (empty($data['numero_empleado'])) {
                throw ValidationException::withMessages(['numero_empleado' => 'Selecciona al trabajador que recibe el material.']);
            }
            $empleado = EmpleadoService::buscarPorClave((int) $data['numero_empleado']);
            if (! $empleado) {
                throw ValidationException::withMessages(['numero_empleado' => 'Trabajador no encontrado en HC.']);
            }
            SeccionService::autorizar($request->user(), $empleado['seccion']);
            if (trim((string) $ubicacionOrigen?->almacen?->seccion) !== trim((string) $empleado['seccion'])) {
                throw ValidationException::withMessages([
                    'numero_empleado' => 'El trabajador debe pertenecer a la misma sección que el almacén de origen.',
                ]);
            }
        }

        AlmacenService::registrar([
            ...$data,
            'numero_empleado' => $empleado['clave'] ?? null,
            'empleado_nombre' => $empleado['nombre_completo'] ?? null,
            'empleado_area' => $empleado['area'] ?? null,
            'empleado_seccion' => $empleado['seccion'] ?? null,
            'usuario_id' => $request->user()->id,
        ]);

        return redirect()->route('inventario.movimientos')->with('success', 'Movimiento registrado correctamente.');
    }

    private function validarArticulo(Request $request, ?AlmacenArticulo $articulo = null): array
    {
        $unidadesPermitidas = array_keys(self::UNIDADES_MEDIDA);
        if ($articulo && ! in_array($articulo->unidad_medida, $unidadesPermitidas, true)) {
            $unidadesPermitidas[] = $articulo->unidad_medida;
        }

        $reglas = [
            'sku' => ['required', 'string', 'max:80', Rule::unique('almacen_articulos', 'sku')->ignore($articulo?->id)],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'tipo' => ['required', Rule::in(self::TIPOS_ARTICULO)],
            'unidad_medida' => ['required', 'string', 'max:30', Rule::in($unidadesPermitidas)],
            'numero_serie' => ['nullable', 'string', 'max:100'],
            'activo' => ['nullable', 'boolean'],
        ];
        if (! $articulo) {
            $reglas += [
                'ubicacion_id' => ['required', 'integer', 'exists:almacen_ubicaciones,id'],
                'stock_minimo' => ['required', 'numeric', 'gte:0', 'max:999999999999999.999'],
                'stock_maximo' => ['nullable', 'numeric', 'gt:stock_minimo', 'max:999999999999999.999'],
                'stock_inicial' => ['required', 'numeric', 'gte:0', 'max:999999999999999.999'],
                'costo_unitario_inicial' => ['nullable', 'numeric', 'gte:0', 'max:99999999999999.9999'],
            ];
        }

        $data = $request->validate($reglas);
        $data['activo'] = $request->boolean('activo', true);

        return $data;
    }

    private function ubicacionesPara(Request $request)
    {
        return AlmacenUbicacion::query()
            ->where('activo', true)
            ->whereHas('almacen', fn ($query) => $query->visiblesPara($request->user())->where('activo', true))
            ->with('almacen')->orderBy('almacen_id')->orderBy('codigo')->get();
    }

    private function autorizarEdicionArticulo(Request $request, AlmacenArticulo $articulo): void
    {
        $secciones = SeccionService::permitidas($request->user());
        if ($secciones === null) {
            return;
        }

        $tieneExistenciaVisible = $articulo->existencias()
            ->whereHas('ubicacion.almacen', fn ($query) => $query->whereIn('seccion', $secciones))
            ->exists();
        $tieneExistenciaFueraDelAlcance = $articulo->existencias()
            ->whereDoesntHave('ubicacion.almacen', fn ($query) => $query->whereIn('seccion', $secciones))
            ->exists();

        abort_unless($tieneExistenciaVisible && ! $tieneExistenciaFueraDelAlcance, 403);
    }
}
