<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Si la aplicación está en mantenimiento, carga la respuesta preparada.
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Registra las dependencias instaladas por Composer.
require __DIR__.'/../vendor/autoload.php';

// Inicializa Laravel y atiende la solicitud HTTP capturada.
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
