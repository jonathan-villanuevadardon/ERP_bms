<?php

namespace App\Providers;

use App\Database\Connectors\SqlServerConnector;
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
        // Conserva SqlServerConnection y la gramática T-SQL de Laravel, pero
        // permite construir el PDO mediante FreeTDS cuando el hosting lo exige.
        $this->app->bind('db.connector.sqlsrv', function ($app) {
            $preferredDriver = $app['config']->get(
                'database.connections.sqlsrv.pdo_driver',
                'sqlsrv'
            );

            return new SqlServerConnector($preferredDriver);
        });
    }

    /**
     * Inicializa servicios tras resolver el contenedor.
     */
    public function boot(): void
    {
        //
    }
}
