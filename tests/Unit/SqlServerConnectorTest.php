<?php

namespace Tests\Unit;

use App\Database\Connectors\SqlServerConnector;
use PHPUnit\Framework\TestCase;

class SqlServerConnectorTest extends TestCase
{
    public function test_configura_dateformat_ymd_al_conectar(): void
    {
        $pdo = new class
        {
            /** @var array<int, string> */
            public array $sentencias = [];

            public function exec(string $sentencia): int
            {
                $this->sentencias[] = $sentencia;

                return 0;
            }
        };

        $connector = new class($pdo) extends SqlServerConnector
        {
            public function __construct(private readonly object $pdo)
            {
                parent::__construct('sqlsrv');
            }

            public function createConnection($dsn, array $config, array $options)
            {
                return $this->pdo;
            }

            protected function getAvailableDrivers()
            {
                return ['sqlsrv'];
            }
        };

        $conexion = $connector->connect([
            'host' => 'localhost',
            'port' => '1433',
            'database' => 'pruebas',
            'username' => 'usuario',
            'password' => 'secreto',
        ]);

        $this->assertSame($pdo, $conexion);
        $this->assertSame(['SET DATEFORMAT ymd'], $pdo->sentencias);
    }
}
