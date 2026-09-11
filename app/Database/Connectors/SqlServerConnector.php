<?php

namespace App\Database\Connectors;

use Illuminate\Database\Connectors\SqlServerConnector as BaseSqlServerConnector;

/**
 * Conector SQL Server con selección explícita del controlador PDO.
 *
 * Hostinger Shared Hosting ofrece FreeTDS (`pdo_dblib`) pero no el Microsoft
 * ODBC Driver requerido por `pdo_sqlsrv`. Laravel prioriza `pdo_sqlsrv` cuando
 * ambos módulos están cargados; esta clase permite forzar `dblib` mediante la
 * variable DB_SQLSERVER_PDO_DRIVER sin cambiar la gramática T-SQL de Laravel.
 */
class SqlServerConnector extends BaseSqlServerConnector
{
    /**
     * Crea el conector indicando el controlador PDO preferido.
     */
    public function __construct(private readonly string $preferredDriver = 'sqlsrv')
    {
    }

    /**
     * Devuelve los controladores que Laravel debe considerar al crear el DSN.
     *
     * @return array<int, string>
     */
    protected function getAvailableDrivers()
    {
        if ($this->preferredDriver === 'dblib') {
            return ['dblib'];
        }

        return parent::getAvailableDrivers();
    }
}
