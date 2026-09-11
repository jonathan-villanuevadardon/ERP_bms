<?php

namespace App\Http\Controllers;

use App\Services\SeccionService;
use App\Services\VisorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Controlador "VisorController".
 *
 * Módulo "Visor de cumplimiento de rol". Consulta si cada empleado está
 * cumpliendo el rol de descanso asignado (días trabajados vs días de
 * descanso). Permite refrescar la tabla de hechos de asistencia vía SP.
 */
class VisorController extends Controller
{
    /**
     * Muestra el visor con filtro opcional por sección.
     *
     * @return View
     */
    public function index(Request $request)
    {
        $secciones = SeccionService::disponiblesPara($request->user());
        $filtros = $request->validate([
            'seccion' => ['nullable', Rule::in($secciones)],
            'clave' => ['nullable', 'integer', 'min:1'],
        ]);
        $seccion = $filtros['seccion'] ?? null;
        $clave = isset($filtros['clave']) ? (int) $filtros['clave'] : null;
        $filas = VisorService::cumplimiento($seccion, $clave, SeccionService::permitidas($request->user()));

        return view('visor.index', compact('filas', 'secciones', 'seccion', 'clave'));
    }

    /**
     * Refresca la tabla de hechos de asistencia desde la vista fuente.
     *
     * Solo accesible por el administrador (botón de emergencia). Se ejecuta el
     * SP sp_refrescar_hechos_asistencia de forma incremental.
     *
     * @return RedirectResponse
     */
    public function refrescar(Request $request)
    {
        // Doble verificación: solo el administrador puede disparar el SP.
        if (! $request->user() || ! $request->user()->esAdmin()) {
            abort(403, 'No tienes permisos para ejecutar esta acción.');
        }

        $filas = VisorService::refrescarHechos();

        return back()->with('success', "Sincronización completa: {$filas} días nuevos insertados.");
    }
}
