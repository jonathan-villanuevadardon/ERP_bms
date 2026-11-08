<?php

namespace App\Services;

use App\Models\AlmacenArticulo;
use App\Models\AlmacenExistencia;
use App\Models\AlmacenMovimiento;
use App\Models\AlmacenUbicacion;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AlmacenService
{
    private const ENTRADAS = ['ENTRADA', 'AJUSTE_ENTRADA', 'DEVOLUCION'];

    private const SALIDAS = ['SALIDA', 'AJUSTE_SALIDA', 'PRESTAMO'];

    public static function registrar(array $datos): AlmacenMovimiento
    {
        return DB::transaction(function () use ($datos) {
            $articulo = AlmacenArticulo::query()
                ->lockForUpdate()
                ->findOrFail($datos['articulo_id']);

            return self::aplicar($articulo, $datos);
        });
    }

    public static function aplicar(AlmacenArticulo $articulo, array $datos): AlmacenMovimiento
    {
        $tipo = (string) $datos['tipo'];
        $esEntrada = in_array($tipo, self::ENTRADAS, true);
        $esSalida = in_array($tipo, self::SALIDAS, true);
        $esTransferencia = $tipo === 'TRANSFERENCIA';
        $cantidad = round((float) $datos['cantidad'], 3);

        if (! $articulo->activo && $tipo !== 'DEVOLUCION') {
            throw ValidationException::withMessages(['articulo_id' => 'El artículo está inactivo.']);
        }
        if ($cantidad <= 0 || (! $esEntrada && ! $esSalida && ! $esTransferencia)) {
            throw ValidationException::withMessages(['cantidad' => 'El movimiento solicitado no es válido.']);
        }

        $origenId = isset($datos['ubicacion_origen_id']) ? (int) $datos['ubicacion_origen_id'] : null;
        $destinoId = isset($datos['ubicacion_destino_id']) ? (int) $datos['ubicacion_destino_id'] : null;
        if (($esSalida || $esTransferencia) && ! $origenId) {
            throw ValidationException::withMessages(['ubicacion_origen_id' => 'Selecciona la ubicación de origen.']);
        }
        if (($esEntrada || $esTransferencia) && ! $destinoId) {
            throw ValidationException::withMessages(['ubicacion_destino_id' => 'Selecciona la ubicación de destino.']);
        }
        if ($esEntrada && $origenId) {
            throw ValidationException::withMessages(['ubicacion_origen_id' => 'Las entradas no pueden indicar una ubicación de origen.']);
        }
        if ($esSalida && $destinoId) {
            throw ValidationException::withMessages(['ubicacion_destino_id' => 'Las salidas no pueden indicar una ubicación de destino.']);
        }
        if ($esTransferencia && $origenId === $destinoId) {
            throw ValidationException::withMessages(['ubicacion_destino_id' => 'El destino debe ser distinto del origen.']);
        }

        $idsUbicacion = array_values(array_unique(array_filter([$origenId, $destinoId])));
        $ubicaciones = AlmacenUbicacion::query()->with('almacen')->whereIn('id', $idsUbicacion)->get()->keyBy('id');
        foreach ($idsUbicacion as $ubicacionId) {
            $ubicacion = $ubicaciones->get($ubicacionId);
            if (! $ubicacion || ! $ubicacion->activo || ! $ubicacion->almacen?->activo) {
                throw ValidationException::withMessages(['ubicacion_origen_id' => 'La ubicación seleccionada no está disponible.']);
            }
        }

        $existencias = AlmacenExistencia::query()
            ->where('articulo_id', $articulo->id)
            ->whereIn('ubicacion_id', $idsUbicacion)
            ->orderBy('ubicacion_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('ubicacion_id');

        $existenciaOrigen = $origenId ? $existencias->get($origenId) : null;
        if ($origenId && ! $existenciaOrigen) {
            throw ValidationException::withMessages(['ubicacion_origen_id' => 'El artículo no tiene existencia en la ubicación de origen.']);
        }

        $existenciaDestino = $destinoId ? $existencias->get($destinoId) : null;
        if ($destinoId && ! $existenciaDestino) {
            $existenciaDestino = AlmacenExistencia::query()->create([
                'articulo_id' => $articulo->id,
                'ubicacion_id' => $destinoId,
                'cantidad' => 0,
                'stock_minimo' => 0,
            ]);
        }

        $origenAnterior = $existenciaOrigen ? round((float) $existenciaOrigen->cantidad, 3) : null;
        $destinoAnterior = $existenciaDestino ? round((float) $existenciaDestino->cantidad, 3) : null;
        if ($existenciaOrigen && $origenAnterior < $cantidad) {
            throw ValidationException::withMessages([
                'cantidad' => "Stock insuficiente en la ubicación de origen. Disponible: {$origenAnterior} {$articulo->unidad_medida}.",
            ]);
        }

        $origenNuevo = $existenciaOrigen ? round($origenAnterior - $cantidad, 3) : null;
        $destinoNuevo = $existenciaDestino ? round($destinoAnterior + $cantidad, 3) : null;
        if ($destinoNuevo !== null && $destinoNuevo > 999999999999999.999) {
            throw ValidationException::withMessages(['cantidad' => 'La existencia resultante supera el límite permitido.']);
        }

        $anterior = round((float) AlmacenExistencia::query()
            ->where('articulo_id', $articulo->id)
            ->sum('cantidad'), 3);
        $nuevo = round($esTransferencia ? $anterior : ($esEntrada ? $anterior + $cantidad : $anterior - $cantidad), 3);
        if ($nuevo > 999999999999999.999) {
            throw ValidationException::withMessages(['cantidad' => 'La existencia total supera el límite permitido.']);
        }
        if ($nuevo < 0) {
            throw ValidationException::withMessages(['cantidad' => 'La existencia total no puede quedar negativa.']);
        }

        $costoAnterior = (float) $articulo->costo_promedio;
        $costoUnitario = ($esSalida || $esTransferencia)
            ? $costoAnterior
            : (array_key_exists('costo_unitario', $datos) && $datos['costo_unitario'] !== null
                ? round((float) $datos['costo_unitario'], 4)
                : $costoAnterior);
        $costoPromedio = $costoAnterior;
        if ($esEntrada && $costoUnitario > 0 && $nuevo > 0) {
            $costoPromedio = round((($anterior * $costoAnterior) + ($cantidad * $costoUnitario)) / $nuevo, 4);
        }
        $costoTotal = round($cantidad * $costoUnitario, 4);
        if ($costoTotal > 9999999999999999.9999) {
            throw ValidationException::withMessages(['cantidad' => 'El importe del movimiento supera el límite permitido.']);
        }

        if ($existenciaOrigen) {
            $existenciaOrigen->forceFill(['cantidad' => $origenNuevo])->save();
        }
        if ($existenciaDestino) {
            $existenciaDestino->forceFill(['cantidad' => $destinoNuevo])->save();
        }

        $stockConciliado = round((float) AlmacenExistencia::query()
            ->where('articulo_id', $articulo->id)
            ->sum('cantidad'), 3);
        if ($stockConciliado !== $nuevo) {
            throw ValidationException::withMessages(['cantidad' => 'No fue posible conciliar la existencia total del artículo.']);
        }

        $articulo->forceFill([
            'ubicacion_id' => $articulo->ubicacion_id ?: $destinoId,
            'stock_actual' => $stockConciliado,
            'costo_promedio' => $costoPromedio,
        ])->save();

        return AlmacenMovimiento::query()->create([
            'articulo_id' => $articulo->id,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'costo_unitario' => $costoUnitario,
            'costo_total' => $costoTotal,
            'existencia_anterior' => $anterior,
            'existencia_nueva' => $nuevo,
            'ubicacion_origen_id' => $origenId,
            'ubicacion_destino_id' => $destinoId,
            'existencia_origen_anterior' => $origenAnterior,
            'existencia_origen_nueva' => $origenNuevo,
            'existencia_destino_anterior' => $destinoAnterior,
            'existencia_destino_nueva' => $destinoNuevo,
            'referencia' => $datos['referencia'] ?? null,
            'numero_empleado' => $datos['numero_empleado'] ?? null,
            'empleado_nombre' => $datos['empleado_nombre'] ?? null,
            'empleado_area' => $datos['empleado_area'] ?? null,
            'empleado_seccion' => $datos['empleado_seccion'] ?? null,
            'prestamo_id' => $datos['prestamo_id'] ?? null,
            'usuario_id' => $datos['usuario_id'] ?? null,
            'observaciones' => $datos['observaciones'] ?? null,
            'fecha_movimiento' => now(),
        ]);
    }

    public static function autorizarUbicacion(Usuario $usuario, int $ubicacionId): AlmacenUbicacion
    {
        $ubicacion = AlmacenUbicacion::query()->with('almacen')->findOrFail($ubicacionId);
        if (! $ubicacion->activo || ! $ubicacion->almacen?->activo) {
            throw ValidationException::withMessages(['ubicacion_id' => 'La ubicación seleccionada no está disponible.']);
        }
        SeccionService::autorizar($usuario, $ubicacion->almacen->seccion);

        return $ubicacion;
    }
}
