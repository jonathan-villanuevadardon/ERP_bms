<?php

namespace App\Http\Controllers;

use App\Models\DescansoPendiente;
use App\Models\PermisoPendiente;
use App\Models\VacacionPendiente;
use Illuminate\Http\Request;

/**
 * Controlador "DashboardController".
 *
 * Página principal tras el login. Muestra un resumen operativo: pendientes de
 * aprobación (descansos fijos, vacaciones, permisos con goce) y accesos a
 * módulos según el perfil del usuario.
 */
class DashboardController extends Controller
{
    /**
     * Muestra el dashboard.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Contadores de pendientes de aprobación (visibles para el Admin).
        $pendientesDescansos = DescansoPendiente::where('estado', 'pendiente')->count();
        $pendientesVacaciones = VacacionPendiente::where('estado', 'pendiente')->count();
        $pendientesPermisos = PermisoPendiente::where('estado', 'pendiente')->count();

        return view('dashboard', compact(
            'user',
            'pendientesDescansos',
            'pendientesVacaciones',
            'pendientesPermisos',
        ));
    }
}