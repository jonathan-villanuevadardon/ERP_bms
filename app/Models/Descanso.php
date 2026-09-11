<?php

namespace App\Models;

use App\Casts\SqlServerDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo "Descanso".
 *
 * Periodo de descanso YA vigente/registrado. Puede ser de tipo "fijo"
 * (recurrente/rotativo, previamente aprobado) o "por_periodo" (puntual).
 */
class Descanso extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabla asociada.
     */
    protected $table = 'descansos';

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
        'observaciones',
        'activo',
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
        'activo' => 'boolean',
    ];
}
