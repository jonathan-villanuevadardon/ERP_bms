<?php

namespace App\Services;

use App\Models\RolDescanso;
use App\Models\HechoAsistencia;
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
     * @return int  días nuevos insertados tras el refresco
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
     * @param  string|null  $seccion   filtrar por sección (null = todas)
     * @return \Illuminate\Support\Collection
     */
    public static function cumplimiento(?string $seccion = null)
    {
        $roles = RolDescanso::where('activo', true)
            ->when($seccion, fn ($q) => $q->where('seccion', $seccion))
            ->get();

        return $roles->map(function ($rol) {
            // Fechas de vigencia del rol.
            $desde = $rol->fecha_inicio ? $rol->fecha_inicio->toDateString() : null;
            $hasta = $rol->fecha_fin ? $rol->fecha_fin->toDateString() : null;

            // Días trabajados totales en el periodo del rol.
            $diasTrabajados = HechoAsistencia::where('clave', $rol->clave)
                ->when($desde, fn ($q) => $q->where('day_f', '>=', $desde))
                ->when($hasta, fn ($q) => $q->where('day_f', '<=', $hasta))
                ->count();

            // Días de descanso esperados según el rol.
            $ciclo = $rol->dias_trabajo + $rol->dias_descanso;
            $diasDescansoEsperados = $ciclo > 0
                ? (int) floor($diasTrabajados / $rol->dias_trabajo) * $rol->dias_descanso
                : 0;

            // Razón trabajado/descanso real vs esperado (indicador de cumplimiento).
            // cumple = el empleado NO excede el bloque de trabajo sin descanso.
            $sobreTrabajo = $rol->dias_trabajo > 0
                ? ($diasTrabajados % $rol->dias_trabajo)
                : 0;

            $cumple = $sobreTrabajo < $rol->dias_trabajo; // bloque actual aún en curso es válido

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
                'bloque_actual' => $sobreTrabajo,
                'cumple' => $cumple,
            ];
        })->sortBy('nombre_completo')->values();
    }
}