<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DescansoController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\RolDescansoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VacacionController;
use App\Http\Controllers\VisorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de autenticación (públicas)
|--------------------------------------------------------------------------
*/

Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login'])->name('login.store');
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Rutas protegidas (requieren sesión + perfil activo)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', \App\Http\Middleware\VerificarPerfil::class])->group(function () {

    // Dashboard principal.
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | Módulo: Asignación de Rol
    |----------------------------------------------------------------------
    */
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RolDescansoController::class, 'index'])->name('index');
        Route::get('crear', [RolDescansoController::class, 'create'])->name('create');
        Route::post('/', [RolDescansoController::class, 'store'])->name('store');
        Route::get('{rol}/editar', [RolDescansoController::class, 'edit'])->name('edit');
        Route::put('{rol}', [RolDescansoController::class, 'update'])->name('update');
        Route::delete('{rol}', [RolDescansoController::class, 'destroy'])->name('destroy');
        Route::get('plantilla/descargar', [RolDescansoController::class, 'plantilla'])->name('plantilla');
        Route::post('importar', [RolDescansoController::class, 'importar'])->name('importar');
    });

    /*
    |----------------------------------------------------------------------
    | Módulo: Descansos
    |----------------------------------------------------------------------
    */
    Route::prefix('descansos')->name('descansos.')->group(function () {
        Route::get('/', [DescansoController::class, 'index'])->name('index');
        Route::get('crear', [DescansoController::class, 'create'])->name('create');
        Route::post('/', [DescansoController::class, 'store'])->name('store');
        Route::get('{descanso}/editar', [DescansoController::class, 'edit'])->name('edit');
        Route::put('{descanso}', [DescansoController::class, 'update'])->name('update');
        Route::delete('{descanso}', [DescansoController::class, 'destroy'])->name('destroy');

        // Buzón de aprobación (Admin).
        Route::get('pendientes/lista', [DescansoController::class, 'pendientes'])->name('pendientes');
        Route::post('pendientes/{pendiente}/aprobar', [DescansoController::class, 'aprobar'])->name('aprobar');
        Route::post('pendientes/{pendiente}/rechazar', [DescansoController::class, 'rechazar'])->name('rechazar');
    });

    /*
    |----------------------------------------------------------------------
    | Módulo: Vacaciones
    |----------------------------------------------------------------------
    */
    Route::prefix('vacaciones')->name('vacaciones.')->group(function () {
        Route::get('/', [VacacionController::class, 'index'])->name('index');
        Route::get('crear', [VacacionController::class, 'create'])->name('create');
        Route::post('/', [VacacionController::class, 'store'])->name('store');

        // Buzón de aprobación y ediciones pre-aprobación.
        Route::get('pendientes/lista', [VacacionController::class, 'pendientes'])->name('pendientes');
        Route::get('pendientes/{pendiente}/editar', [VacacionController::class, 'editarPendiente'])->name('editar_pendiente');
        Route::put('pendientes/{pendiente}', [VacacionController::class, 'actualizarPendiente'])->name('actualizar_pendiente');
        Route::delete('pendientes/{pendiente}', [VacacionController::class, 'eliminarPendiente'])->name('eliminar_pendiente');
        Route::post('pendientes/{pendiente}/aprobar', [VacacionController::class, 'aprobar'])->name('aprobar');
        Route::post('pendientes/{pendiente}/rechazar', [VacacionController::class, 'rechazar'])->name('rechazar');
    });

    /*
    |----------------------------------------------------------------------
    | Módulo: Permisos
    |----------------------------------------------------------------------
    */
    Route::prefix('permisos')->name('permisos.')->group(function () {
        Route::get('/', [PermisoController::class, 'index'])->name('index');
        Route::get('crear', [PermisoController::class, 'create'])->name('create');
        Route::post('/', [PermisoController::class, 'store'])->name('store');

        // Buzón de aprobación (con goce) y ediciones pre-aprobación.
        Route::get('pendientes/lista', [PermisoController::class, 'pendientes'])->name('pendientes');
        Route::get('pendientes/{pendiente}/editar', [PermisoController::class, 'editarPendiente'])->name('editar_pendiente');
        Route::put('pendientes/{pendiente}', [PermisoController::class, 'actualizarPendiente'])->name('actualizar_pendiente');
        Route::delete('pendientes/{pendiente}', [PermisoController::class, 'eliminarPendiente'])->name('eliminar_pendiente');
        Route::post('pendientes/{pendiente}/aprobar', [PermisoController::class, 'aprobar'])->name('aprobar');
        Route::post('pendientes/{pendiente}/rechazar', [PermisoController::class, 'rechazar'])->name('rechazar');
    });

    /*
    |----------------------------------------------------------------------
    | Módulo: Visor de cumplimiento de rol
    |----------------------------------------------------------------------
    */
    Route::prefix('visor')->name('visor.')->group(function () {
        Route::get('/', [VisorController::class, 'index'])->name('index');
        Route::post('refrescar', [VisorController::class, 'refrescar'])->name('refrescar');
    });

    /*
    |----------------------------------------------------------------------
    | Administración (solo Admin): perfiles y usuarios
    |----------------------------------------------------------------------
    */
    Route::middleware(\App\Http\Middleware\RequiereAdmin::class)->group(function () {
        // Se especifica el parámetro singular para evitar que el inflector
        // convierta "perfiles" incorrectamente en "perfile".
        Route::resource('perfiles', PerfilController::class)
            ->parameters(['perfiles' => 'perfil'])
            ->except(['show']);
        Route::resource('usuarios', UsuarioController::class)->except(['show']);
    });
});
