<?php

namespace App\Models;

use App\Casts\SqlServerDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo "HechoAsistencia".
 *
 * Tabla de hechos materializada: una fila por (empleado, día trabajado).
 * Se alimenta con el procedimiento almacenado sp_refrescar_hechos_asistencia
 * desde la vista VW_Listas_asistencia_unic. Facilita el cómputo rápido de
 * días trabajados para el visor de cumplimiento de rol.
 */
class HechoAsistencia extends Model
{
    use HasFactory;

    /**
     * Tabla asociada.
     */
    protected $table = 'hechos_asistencia';

    /**
     * Atributos asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'clave',
        'day_f',
    ];

    /**
     * Casts de tipo de datos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'clave' => 'integer',
        'day_f' => SqlServerDate::class,
    ];
}
