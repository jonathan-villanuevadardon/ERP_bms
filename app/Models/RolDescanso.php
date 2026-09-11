<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo "RolDescanso".
 *
 * Representa el rol de descanso asignado a un empleado: la regla
 * "N días trabajados por M días de descanso". Es la base para verificar el
 * cumplimiento del rol (visado contra los días trabajados reales).
 */
class RolDescanso extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabla asociada.
     */
    protected $table = 'roles_descanso';

    /**
     * Atributos asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'clave',
        'nombre_completo',
        'area',
        'cargo',
        'seccion',
        'dias_trabajo',
        'dias_descanso',
        'fecha_inicio',
        'fecha_fin',
        'activo',
    ];

    /**
     * Casts de tipo de datos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'clave' => 'integer',
        'dias_trabajo' => 'integer',
        'dias_descanso' => 'integer',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activo' => 'boolean',
    ];
}