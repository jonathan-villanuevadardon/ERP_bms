<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DescansoController;
use App\Http\Controllers\IncapacidadController;
use App\Http\Controllers\ListaAsistenciaController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\RolDescansoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VacacionController;
use App\Http\Controllers\VisorController;
use App\Http\Middleware\RequiereAdmin;
use App\Http\Middleware\VerificarPerfil;
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

Route::middleware(['auth', VerificarPerfil::class])->group(function () {

    // Dashboard principal.
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | Módulo: Asignación de Rol
    |----------------------------------------------------------------------
    */
    Route::prefix('roles')->name('roles.')->middleware('permiso:puede_asignar_rol')->group(function () {
        Route::get('/', [RolDescansoController::class, 'index'])->name('index');
        Route::get('crear', [RolDescansoController::class, 'create'])->name('create');
        Route::post('/', [RolDescansoController::class, 'store'])->name('store');
        Route::get('{rol}/editar', [RolDescansoController::class, 'edit'])->name('edit');
        Route::put('{rol}', [RolDescansoController::class, 'update'])->name('update');
        Route::delete('{rol}', [RolDescansoController::class, 'destroy'])->name('destroy');
        Route::middleware('permiso:puede_cargas_masivas')->group(function () {
            Route::get('plantilla/descargar', [RolDescansoController::class, 'plantilla'])->name('plantilla');
            Route::post('importar', [RolDescansoController::class, 'importar'])->name('importar');
        });
    });

    /*
    |----------------------------------------------------------------------
    | Módulo: Descansos
    |----------------------------------------------------------------------
    */
    Route::prefix('descansos')->name('descansos.')->group(function () {
        Route::middleware('permiso:puede_gestionar_descansos')->group(function () {
            Route::get('/', [DescansoController::class, 'index'])->name('index');
            Route::get('crear', [DescansoController::class, 'create'])->name('create');
            Route::post('/', [DescansoController::class, 'store'])->name('store');
            Route::get('{descanso}/editar', [DescansoController::class, 'edit'])->name('edit');
            Route::put('{descanso}', [DescansoController::class, 'update'])->name('update');
            Route::delete('{descanso}', [DescansoController::class, 'destroy'])->name('destroy');
            Route::middleware('permiso:puede_cargas_masivas')->group(function () {
                Route::get('carga/importar', [DescansoController::class, 'importar'])->name('importar');
                Route::get('carga/plantilla', [DescansoController::class, 'plantilla'])->name('plantilla');
                Route::post('carga/previsualizar', [DescansoController::class, 'previsualizar'])->name('previsualizar');
                Route::post('carga/confirmar', [DescansoController::class, 'confirmarImportacion'])->name('confirmar_importacion');
            });
        });

        Route::get('pendientes/lista', [DescansoController::class, 'pendientes'])->middleware('permiso:puede_gestionar_descansos,puede_aprobar')->name('pendientes');
        Route::middleware('permiso:puede_aprobar')->group(function () {
            Route::post('pendientes/{pendiente}/aprobar', [DescansoController::class, 'aprobar'])->name('aprobar');
            Route::post('pendientes/{pendiente}/rechazar', [DescansoController::class, 'rechazar'])->name('rechazar');
        });
    });

    /*
    |----------------------------------------------------------------------
    | Módulo: Vacaciones
    |----------------------------------------------------------------------
    */
    Route::prefix('vacaciones')->name('vacaciones.')->group(function () {
        Route::middleware('permiso:puede_gestionar_vacaciones')->group(function () {
            Route::get('/', [VacacionController::class, 'index'])->name('index');
            Route::get('crear', [VacacionController::class, 'create'])->name('create');
            Route::post('/', [VacacionController::class, 'store'])->name('store');
            Route::get('pendientes/{pendiente}/editar', [VacacionController::class, 'editarPendiente'])->name('editar_pendiente');
            Route::put('pendientes/{pendiente}', [VacacionController::class, 'actualizarPendiente'])->name('actualizar_pendiente');
            Route::delete('pendientes/{pendiente}', [VacacionController::class, 'eliminarPendiente'])->name('eliminar_pendiente');
        });

        Route::get('pendientes/lista', [VacacionController::class, 'pendientes'])->middleware('permiso:puede_gestionar_vacaciones,puede_aprobar')->name('pendientes');
        Route::middleware('permiso:puede_aprobar')->group(function () {
            Route::post('pendientes/{pendiente}/aprobar', [VacacionController::class, 'aprobar'])->name('aprobar');
            Route::post('pendientes/{pendiente}/rechazar', [VacacionController::class, 'rechazar'])->name('rechazar');
        });
    });

    /*
    |----------------------------------------------------------------------
    | Módulo: Permisos
    |----------------------------------------------------------------------
    */
    Route::prefix('permisos')->name('permisos.')->group(function () {
        Route::middleware('permiso:puede_gestionar_permisos')->group(function () {
            Route::get('/', [PermisoController::class, 'index'])->name('index');
            Route::get('crear', [PermisoController::class, 'create'])->name('create');
            Route::post('/', [PermisoController::class, 'store'])->name('store');
            Route::get('pendientes/{pendiente}/editar', [PermisoController::class, 'editarPendiente'])->name('editar_pendiente');
            Route::put('pendientes/{pendiente}', [PermisoController::class, 'actualizarPendiente'])->name('actualizar_pendiente');
            Route::delete('pendientes/{pendiente}', [PermisoController::class, 'eliminarPendiente'])->name('eliminar_pendiente');
        });

        Route::get('pendientes/lista', [PermisoController::class, 'pendientes'])->middleware('permiso:puede_gestionar_permisos,puede_aprobar')->name('pendientes');
        Route::middleware('permiso:puede_aprobar')->group(function () {
            Route::post('pendientes/{pendiente}/aprobar', [PermisoController::class, 'aprobar'])->name('aprobar');
            Route::post('pendientes/{pendiente}/rechazar', [PermisoController::class, 'rechazar'])->name('rechazar');
        });
    });

    Route::prefix('incapacidades')->name('incapacidades.')->group(function () {
        Route::middleware('permiso:puede_gestionar_incapacidades')->group(function () {
            Route::get('/', [IncapacidadController::class, 'index'])->name('index');
            Route::get('crear', [IncapacidadController::class, 'create'])->name('create');
            Route::post('/', [IncapacidadController::class, 'store'])->name('store');
            Route::get('pendientes/{pendiente}/editar', [IncapacidadController::class, 'editarPendiente'])->name('editar_pendiente');
            Route::put('pendientes/{pendiente}', [IncapacidadController::class, 'actualizarPendiente'])->name('actualizar_pendiente');
            Route::delete('pendientes/{pendiente}', [IncapacidadController::class, 'eliminarPendiente'])->name('eliminar_pendiente');
        });

        Route::get('pendientes/lista', [IncapacidadController::class, 'pendientes'])->middleware('permiso:puede_gestionar_incapacidades,puede_aprobar')->name('pendientes');
        Route::middleware('permiso:puede_aprobar')->group(function () {
            Route::post('pendientes/{pendiente}/aprobar', [IncapacidadController::class, 'aprobar'])->name('aprobar');
            Route::post('pendientes/{pendiente}/rechazar', [IncapacidadController::class, 'rechazar'])->name('rechazar');
        });
    });

    /*
    |----------------------------------------------------------------------
    | Módulo: Visor de cumplimiento de rol
    |----------------------------------------------------------------------
    */
    Route::prefix('visor')->name('visor.')->middleware('permiso:puede_ver_visor')->group(function () {
        Route::get('/', [VisorController::class, 'index'])->name('index');
        Route::post('refrescar', [VisorController::class, 'refrescar'])->name('refrescar');
    });

    Route::prefix('lista-asistencia')->name('lista_asistencia.')->middleware('permiso:puede_ver_lista_asistencia')->group(function () {
        Route::get('/', [ListaAsistenciaController::class, 'index'])->name('index');
        Route::get('exportar', [ListaAsistenciaController::class, 'exportar'])->name('exportar');
    });

    /*
    |----------------------------------------------------------------------
    | Administración (solo Admin): perfiles y usuarios
    |----------------------------------------------------------------------
    */
    Route::middleware(RequiereAdmin::class)->group(function () {
        // Se especifica el parámetro singular para evitar que el inflector
        // convierta "perfiles" incorrectamente en "perfile".
        Route::resource('perfiles', PerfilController::class)
            ->parameters(['perfiles' => 'perfil'])
            ->except(['show']);
        Route::resource('usuarios', UsuarioController::class)->except(['show']);
    });
});
