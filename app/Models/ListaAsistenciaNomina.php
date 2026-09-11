<?php

namespace App\Models;

use App\Casts\SqlServerDate;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de solo lectura sobre la vista diaria utilizada por nómina.
 */
class ListaAsistenciaNomina extends Model
{
    protected $table = 'VW_ERP_LISTA_ASISTENCIA_NOMINA';

    public $timestamps = false;

    public $incrementing = false;

    protected $casts = [
        'clave' => 'integer',
        'fecha' => SqlServerDate::class,
        'tiene_asistencia' => 'boolean',
        'tiene_descanso' => 'boolean',
        'tiene_vacaciones' => 'boolean',
        'tiene_permiso' => 'boolean',
        'tiene_incapacidad' => 'boolean',
    ];
}
