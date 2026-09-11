<?php

namespace App\Http\Controllers;

use App\Models\DescansoPendiente;
use App\Models\IncapacidadPendiente;
use App\Models\PermisoPendiente;
use App\Models\VacacionPendiente;
use App\Services\SeccionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
     * @return View
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $puedeAprobar = $user->esAdmin() || $user->perfil->puede_aprobar;
        $contar = fn ($consulta) => $puedeAprobar
            ? SeccionService::aplicar($consulta->where('estado', 'pendiente'), $user)->count()
            : 0;

        $pendientesDescansos = $contar(DescansoPendiente::query());
        $pendientesVacaciones = $contar(VacacionPendiente::query());
        $pendientesPermisos = $contar(PermisoPendiente::query());
        $pendientesIncapacidades = $contar(IncapacidadPendiente::query());

        return view('dashboard', compact(
            'user',
            'pendientesDescansos',
            'pendientesVacaciones',
            'pendientesPermisos',
            'pendientesIncapacidades',
        ));
    }
}
