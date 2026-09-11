<?php

namespace App\Models;

use App\Casts\SqlServerDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo "Vacacion".
 *
 * Periodo de vacaciones YA aprobado por el Admin. Una vez aprobado, es de
 * solo lectura para RH; solo el Admin puede corregirlo por excepción.
 */
class Vacacion extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabla asociada.
     */
    protected $table = 'vacaciones';

    /**
     * Atributos asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'clave',
        'nombre_completo',
        'area',
        'seccion',
        'fecha_inicio',
        'fecha_fin',
        'observaciones',
        'pendiente_id',
    ];

    /**
     * Casts de tipo de datos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'clave' => 'integer',
        'fecha_inicio' => SqlServerDate::class,
        'fecha_fin' => SqlServerDate::class,
    ];
}
