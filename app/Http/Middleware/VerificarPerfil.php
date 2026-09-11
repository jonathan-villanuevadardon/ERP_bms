<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware "VerificarPerfil".
 *
 * Verifica que el usuario autenticado esté activo y tenga un perfil asignado.
 * Se ejecuta tras el middleware de autenticación para todas las rutas
 * protegidas del ERP. Si el usuario está suspendido, se cierra la sesión.
 */
class VerificarPerfil
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

        // Si no tiene perfil o está inactivo, invalidar y redirigir al login.
        if ($user && ($user->activo === false || ! $user->perfil)) {
            auth()->logout();

            return redirect()->route('login')->with('error', 'Tu cuenta no está activa.');
        }

        return $next($request);
    }
}