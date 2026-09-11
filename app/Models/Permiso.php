<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo "Permiso".
 *
 * Permiso ya registrado. Puede ser "sin_goce" (se registra directo) o
 * "con_goce" (previo paso por aprobación del Admin, copiado aquí al aprobar).
 */
class Permiso extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabla asociada.
     */
    protected $table = 'permisos';

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
        'tipo',
        'fecha_inicio',
        'fecha_fin',
        'motivo',
        'pendiente_id',
    ];

    /**
     * Casts de tipo de datos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'clave' => 'integer',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];
}