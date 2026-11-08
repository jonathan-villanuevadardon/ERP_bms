<?php

namespace App\Services;

use App\Models\AlmacenArticulo;
use App\Models\AlmacenPrestamo;
use App\Models\AlmacenPrestamoMovimiento;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PrestamoHerramientaService
{
    public static function entregar(array $datos, array $empleado, Usuario $usuario): AlmacenPrestamo
    {
        return DB::transaction(function () use ($datos, $empleado, $usuario) {
            $articulo = AlmacenArticulo::query()->lockForUpdate()->findOrFail($datos['articulo_id']);
            if ($articulo->tipo !== 'HERRAMIENTA') {
                throw ValidationException::withMessages(['articulo_id' => 'Solo se pueden prestar artículos de tipo herramienta.']);
            }

            $prestamo = AlmacenPrestamo::query()->create([
                'folio' => 'HER-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)),
                'articulo_id' => $articulo->id,
                'numero_empleado' => $empleado['clave'],
                'empleado_nombre' => $empleado['nombre_completo'],
                'empleado_area' => $empleado['area'],
                'empleado_seccion' => $empleado['seccion'],
                'tipo_asignacion' => $datos['tipo_asignacion'],
                'estado' => 'ACTIVO',
                'cantidad_entregada' => $datos['cantidad'],
                'cantidad_devuelta' => 0,
                'costo_unitario_salida' => $articulo->costo_promedio,
                'ubicacion_id' => $datos['ubicacion_id'],
                'fecha_salida' => now(),
                'fecha_compromiso' => $datos['fecha_compromiso'] ?? null,
                'usuario_entrega_id' => $usuario->id,
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            AlmacenService::aplicar($articulo, [
                'tipo' => 'PRESTAMO',
                'cantidad' => $datos['cantidad'],
                'ubicacion_origen_id' => $datos['ubicacion_id'],
                'prestamo_id' => $prestamo->id,
                'referencia' => $prestamo->folio,
                'numero_empleado' => $empleado['clave'],
                'empleado_nombre' => $empleado['nombre_completo'],
                'empleado_area' => $empleado['area'],
                'empleado_seccion' => $empleado['seccion'],
                'usuario_id' => $usuario->id,
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            AlmacenPrestamoMovimiento::query()->create([
                'prestamo_id' => $prestamo->id,
                'tipo' => 'SALIDA',
                'cantidad' => $datos['cantidad'],
                'usuario_id' => $usuario->id,
                'observaciones' => $datos['observaciones'] ?? null,
                'fecha_movimiento' => now(),
            ]);

            return $prestamo;
        });
    }

    public static function recibir(int $prestamoId, float $cantidad, Usuario $usuario, ?string $observaciones): AlmacenPrestamo
    {
        return DB::transaction(function () use ($prestamoId, $cantidad, $usuario, $observaciones) {
            $prestamo = AlmacenPrestamo::query()->lockForUpdate()->findOrFail($prestamoId);
            $pendiente = round((float) $prestamo->cantidad_entregada - (float) $prestamo->cantidad_devuelta, 3);
            $cantidad = round($cantidad, 3);

            if (! in_array($prestamo->estado, ['ACTIVO', 'PARCIAL'], true) || $cantidad <= 0 || $cantidad > $pendiente) {
                throw ValidationException::withMessages([
                    'cantidad' => "La devolución debe ser mayor a cero y no superar {$pendiente}.",
                ]);
            }

            $articulo = AlmacenArticulo::query()->lockForUpdate()->findOrFail($prestamo->articulo_id);
            AlmacenService::aplicar($articulo, [
                'tipo' => 'DEVOLUCION',
                'cantidad' => $cantidad,
                'costo_unitario' => $prestamo->costo_unitario_salida,
                'ubicacion_destino_id' => $prestamo->ubicacion_id,
                'prestamo_id' => $prestamo->id,
                'referencia' => $prestamo->folio,
                'numero_empleado' => $prestamo->numero_empleado,
                'empleado_nombre' => $prestamo->empleado_nombre,
                'empleado_area' => $prestamo->empleado_area,
                'empleado_seccion' => $prestamo->empleado_seccion,
                'usuario_id' => $usuario->id,
                'observaciones' => $observaciones,
            ]);

            $devuelto = round((float) $prestamo->cantidad_devuelta + $cantidad, 3);
            $completo = $devuelto >= (float) $prestamo->cantidad_entregada;
            $prestamo->forceFill([
                'cantidad_devuelta' => $devuelto,
                'estado' => $completo ? 'DEVUELTO' : 'PARCIAL',
                'fecha_devolucion' => $completo ? now() : null,
                'usuario_recibe_id' => $usuario->id,
            ])->save();

            AlmacenPrestamoMovimiento::query()->create([
                'prestamo_id' => $prestamo->id,
                'tipo' => 'DEVOLUCION',
                'cantidad' => $cantidad,
                'usuario_id' => $usuario->id,
                'observaciones' => $observaciones,
                'fecha_movimiento' => now(),
            ]);

            return $prestamo;
        });
    }
}
