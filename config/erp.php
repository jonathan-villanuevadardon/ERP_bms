<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Límites de autenticación
    |--------------------------------------------------------------------------
    | Intentos máximos de login y tiempo de espera entre reintentos.
    */
    'throttle' => [
        'max_attempts' => 5,
        'decay_minutes' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sesión de usuario
    |--------------------------------------------------------------------------
    */
    'session' => [
        'lifetime' => env('SESSION_LIFETIME', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Secciones de negocio (dominio)
    |--------------------------------------------------------------------------
    | Un perfil puede restringirse a una o varias secciones. Estos valores
    | provienen de la columna "seccion" de la vista HCBMS_ALL_OP.
    */
    'secciones' => [
        'PLANTA',
        'TAJO',
        'TALLER 1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Nombre del rol/perfil administrador maestro
    |--------------------------------------------------------------------------
    */
    'admin_role' => 'ADMIN',

    'admin_email' => env('ADMIN_EMAIL', 'admin@erpbms.local'),

];