<?php

namespace App\Services;

use App\Models\HechoAsistencia;
use App\Models\RolDescanso;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Servicio "VisorService".
 *
 * Lógica para el visor de cumplimiento de rol de descanso. Responde la
 * pregunta: "¿el empleado está cumpliendo su rol (días trabajados vs días
 * de descanso)?".
 *
 * Para cada empleado con rol asignado y activo se calcula:
 *   - días trabajados acumulados (desde la tabla de hechos).
 *   - ciclo del rol = dias_trabajo + dias_descanso.
 *   - cumplimiento: evalúa si el conteo de días trabajados respeta el ciclo
 *     (es decir, no excede días de trabajo consecutivos sin el descanso).
 */
class VisorService
{
    /**
     * Refresca (incrementalmente) la tabla de hechos invocando el SP.
     *
     * Inserta solo los días que aún no existen, respetando la data viva del
     * checador (asistencias que se cargan con retraso).
     *
     * @return int días nuevos insertados tras el refresco
     */
    public static function refrescarHechos(): int
    {
        $result = DB::select('EXEC dbo.sp_refrescar_hechos_asistencia');

        $filas = 0;
        if (! empty($result)) {
            $filas = (int) ($result[0]->filas_insertadas ?? 0);
        }

        return $filas;
    }

    /**
     * Genera el detalle de cumplimiento por empleado.
     *
     * @param  string|null  $seccion  filtrar por sección (null = todas)
     * @return Collection
     */
    public static function cumplimiento(?string $seccion = null, ?int $clave = null, ?array $seccionesPermitidas = null)
    {
        $roles = RolDescanso::where('activo', true)
            ->when($seccion, fn ($q) => $q->where('seccion', $seccion))
            ->when($clave, fn ($q) => $q->where('clave', $clave))
            ->when($seccionesPermitidas !== null, fn ($q) => $q->whereIn('seccion', $seccionesPermitidas))
            ->get();

        return $roles->map(function ($rol) {
            // Fechas de vigencia del rol.
            $desde = $rol->fecha_inicio ? $rol->fecha_inicio->toDateString() : null;
            $hasta = $rol->fecha_fin ? $rol->fecha_fin->toDateString() : null;

            $fechasTrabajadas = HechoAsistencia::where('clave', $rol->clave)
                ->when($desde, fn ($q) => $q->where('day_f', '>=', $desde))
                ->when($hasta, fn ($q) => $q->where('day_f', '<=', $hasta))
                ->orderBy('day_f')
                ->pluck('day_f')
                ->map(fn ($fecha) => CarbonImmutable::parse($fecha)->startOfDay());
            $diasTrabajados = $fechasTrabajadas->count();

            // Días de descanso esperados según el rol.
            $ciclo = $rol->dias_trabajo + $rol->dias_descanso;
            $diasDescansoEsperados = $ciclo > 0
                ? (int) floor($diasTrabajados / $rol->dias_trabajo) * $rol->dias_descanso
                : 0;

            $bloqueActual = 0;
            $bloqueMaximo = 0;
            $anterior = null;
            foreach ($fechasTrabajadas as $fecha) {
                $bloqueActual = $anterior && $anterior->diffInDays($fecha) === 1
                    ? $bloqueActual + 1
                    : 1;
                $bloqueMaximo = max($bloqueMaximo, $bloqueActual);
                $anterior = $fecha;
            }

            $cumple = $rol->dias_trabajo > 0 && $bloqueMaximo <= $rol->dias_trabajo;

            return [
                'clave' => $rol->clave,
                'nombre_completo' => $rol->nombre_completo,
                'area' => $rol->area,
                'seccion' => $rol->seccion,
                'rol' => "{$rol->dias_trabajo}x{$rol->dias_descanso}",
                'dias_trabajo_rol' => $rol->dias_trabajo,
                'dias_descanso_rol' => $rol->dias_descanso,
                'dias_trabajados' => $diasTrabajados,
                'dias_descanso_esperados' => $diasDescansoEsperados,
                'bloque_actual' => $bloqueActual,
                'bloque_maximo' => $bloqueMaximo,
                'cumple' => $cumple,
            ];
        })->sortBy('nombre_completo')->values();
    }
}
