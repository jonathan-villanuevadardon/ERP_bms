<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * Controlador base de la aplicación ERP.
 *
 * Proporciona los traits comunes de autorización y validación a todos los
 * controladores del sistema.
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}