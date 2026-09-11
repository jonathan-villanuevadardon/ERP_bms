<?php

namespace App\Services;

use App\Models\Empleado;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;

/**
 * Centraliza el catálogo dinámico y la seguridad por sección operativa.
 */
class SeccionService
{
    /**
     * @return array<int, string>
     */
    public static function disponibles(): array
    {
        return Empleado::query()
            ->selectRaw('LTRIM(RTRIM(seccion)) AS seccion')
            ->whereNotNull('seccion')
            ->whereRaw("LTRIM(RTRIM(seccion)) <> ''")
            ->distinct()
            ->orderBy('seccion')
            ->pluck('seccion')
            ->map(fn ($seccion) => trim((string) $seccion))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Devuelve null cuando el usuario puede operar todas las secciones.
     *
     * @return array<int, string>|null
     */
    public static function permitidas(Usuario $usuario): ?array
    {
        if ($usuario->esAdmin() || empty($usuario->perfil?->secciones)) {
            return null;
        }

        return array_values(array_filter(array_map('trim', $usuario->perfil->secciones)));
    }

    /**
     * @return array<int, string>
     */
    public static function disponiblesPara(Usuario $usuario): array
    {
        $disponibles = self::disponibles();
        $permitidas = self::permitidas($usuario);

        return $permitidas === null
            ? $disponibles
            : array_values(array_intersect($disponibles, $permitidas));
    }

    public static function aplicar(Builder $consulta, Usuario $usuario, string $columna = 'seccion'): Builder
    {
        $permitidas = self::permitidas($usuario);

        if ($permitidas !== null) {
            $consulta->whereIn($columna, $permitidas);
        }

        return $consulta;
    }

    public static function autorizar(Usuario $usuario, ?string $seccion): void
    {
        if (! $usuario->puedeVerSeccion(trim((string) $seccion))) {
            abort(403, 'No tienes permiso para operar la sección de este empleado.');
        }
    }
}
