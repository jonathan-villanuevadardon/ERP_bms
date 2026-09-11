<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Proveedor de servicios de la aplicación ERP BMS.
 *
 * Aquí se registran bindings de contenedor y se puede registrar middleware
 * o configuraciones globales si el proyecto crece.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra servicios en el contenedor.
     */
    public function register(): void
    {
        //
    }

    /**
     * Inicializa servicios tras resolver el contenedor.
     */
    public function boot(): void
    {
        //
    }
}