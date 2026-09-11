<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware "RequiereAdmin".
 *
 * Garantiza que solo usuarios con el perfil administrador (es_admin = true)
 * puedan ejecutar la acción (aprobar, gestionar perfiles/usuarios, etc.).
 */
class RequiereAdmin
{
    /**
     * Maneja la solicitud entrante.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->esAdmin()) {
            abort(403, 'No tienes permisos de administrador.');
        }

        return $next($request);
    }
}