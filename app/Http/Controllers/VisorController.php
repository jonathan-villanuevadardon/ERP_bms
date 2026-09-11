<?php

namespace App\Http\Controllers;

use App\Services\VisorService;
use Illuminate\Http\Request;

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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $seccion = $request->input('seccion');
        $filas = VisorService::cumplimiento($seccion);

        $secciones = config('erp.secciones');

        return view('visor.index', compact('filas', 'secciones', 'seccion'));
    }

    /**
     * Refresca la tabla de hechos de asistencia desde la vista fuente.
     *
     * Solo accesible por el administrador (botón de emergencia). Se ejecuta el
     * SP sp_refrescar_hechos_asistencia de forma incremental.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refrescar(\Illuminate\Http\Request $request)
    {
        // Doble verificación: solo el administrador puede disparar el SP.
        if (! $request->user() || ! $request->user()->esAdmin()) {
            abort(403, 'No tienes permisos para ejecutar esta acción.');
        }

        $filas = VisorService::refrescarHechos();

        return back()->with('success', "Sincronización completa: {$filas} días nuevos insertados.");
    }
}