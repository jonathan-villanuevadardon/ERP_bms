<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autoriza una ruta cuando el perfil tiene al menos uno de los permisos dados.
 */
class RequierePermiso
{
    public function handle(Request $request, Closure $next, string ...$permisos): Response
    {
        $usuario = $request->user();

        if (! $usuario || ! $usuario->perfil) {
            abort(403, 'No tienes un perfil autorizado.');
        }

        if ($usuario->esAdmin()) {
            return $next($request);
        }

        foreach ($permisos as $permiso) {
            if ($usuario->perfil->getAttribute($permiso) === true) {
                return $next($request);
            }
        }

        abort(403, 'No tienes permiso para realizar esta acción.');
    }
}
