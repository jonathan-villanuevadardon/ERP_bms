<?php

namespace App\Services;

use App\Models\Empleado;
use App\Models\HechoAsistencia;
use Illuminate\Support\Collection;

/**
 * Servicio "EmpleadoService".
 *
 * Centraliza el acceso a los datos de empleados provenientes de la vista
 * HCBMS_ALL_OP (solo lectura) y el cómputo de días trabajados a partir de la
 * tabla de hechos (hechos_asistencia), materializada desde VW_Listas_asistencia_unic.
 */
class EmpleadoService
{
    /**
     * Buscar un empleado por su número de empleado (clave).
     *
     * Devuelve un array normalizado con las claves de dominio usadas por la
     * aplicación (minúsculas y coherentes), o null si no existe.
     */
    public static function buscarPorClave(int $clave): ?array
    {
        $e = Empleado::where('clave', $clave)->first();
        if (! $e) {
            return null;
        }

        return self::normalizar($e);
    }

    /**
     * Resuelve empleados en una sola consulta, indexados por clave.
     *
     * @param  array<int, int>  $claves
     */
    public static function buscarVarios(array $claves)
    {
        return Empleado::whereIn('clave', array_values(array_unique($claves)))
            ->get()
            ->mapWithKeys(function (Empleado $empleado) {
                $normalizado = self::normalizar($empleado);

                return [$normalizado['clave'] => $normalizado];
            });
    }

    /**
     * Listar empleados con filtros opcionales (sección, término de búsqueda).
     *
     * @return Collection
     */
    public static function listar(?string $seccion = null, ?string $termino = null, int $limite = 100, ?array $seccionesPermitidas = null)
    {
        $q = Empleado::query();

        if ($seccion) {
            $q->where('seccion', $seccion);
        }

        if ($seccionesPermitidas !== null) {
            $q->whereIn('seccion', $seccionesPermitidas);
        }

        if ($termino) {
            $t = trim($termino);
            $q->where(function ($sub) use ($t) {
                $sub->where('Nombre_completo', 'like', "%{$t}%")
                    ->orWhere('categoria', 'like', "%{$t}%")
                    ->orWhere('dept_name', 'like', "%{$t}%")
                    ->orWhere('clave', 'like', "%{$t}%");
            });
        }

        $empleados = $q->orderBy('Nombre_completo')->limit($limite)->get();

        // Añade a cada empleado los días trabajados acumulados (total histórico
        // desde la tabla de hechos) para mostrarlo al momento de asignar roles.
        return $empleados->map(function ($e) {
            $dias = HechoAsistencia::where('clave', (int) $e->clave)->count();

            return [
                'clave' => (int) $e->clave,
                'nombre_completo' => trim((string) $e->Nombre_completo),
                'cargo' => trim((string) $e->categoria),
                'area' => trim((string) $e->dept_name),
                'seccion' => trim((string) $e->seccion),
                'dias_trabajados' => $dias,
            ];
        });
    }

    /**
     * Obtener el total de días trabajados por un empleado en un rango de fechas.
     *
     * Consulta la tabla de hechos (materializada) para un conteo rápido.
     *
     * @param  string|null  $desde  fecha 'Y-m-d' (opcional)
     * @param  string|null  $hasta  fecha 'Y-m-d' (opcional)
     */
    public static function diasTrabajados(int $clave, ?string $desde = null, ?string $hasta = null): int
    {
        $q = HechoAsistencia::where('clave', $clave);

        if ($desde) {
            $q->where('day_f', '>=', $desde);
        }
        if ($hasta) {
            $q->where('day_f', '<=', $hasta);
        }

        return $q->count();
    }

    /**
     * Normalizar una fila de la vista a claves de dominio coherentes.
     */
    private static function normalizar(Empleado $e): array
    {
        return [
            'clave' => (int) $e->clave,
            'nombre_completo' => trim((string) $e->Nombre_completo),
            'cargo' => trim((string) $e->categoria),
            'area' => trim((string) $e->dept_name),
            'subarea' => trim((string) $e->subarea),
            'nivel2' => trim((string) $e->Nivel_2),
            'seccion' => trim((string) $e->seccion),
        ];
    }
}
