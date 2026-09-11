<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo "DescansoPendiente".
 *
 * Registro de descanso "fijo" en buzón de aprobación (estado pendiente).
 * El Admin aprueba (se copia a "Descanso") o rechaza. Mientras esté pendiente,
 * RH y Admin pueden modificar el registro.
 */
class DescansoPendiente extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabla asociada.
     */
    protected $table = 'descansos_pendientes';

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
        'estado',
        'creado_por',
        'aprobado_por',
        'aprobado_en',
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
        'aprobado_en' => 'datetime',
    ];

    /**
     * Relación: usuario que creó el registro.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creadoPor()
    {
        return $this->belongsTo(Usuario::class, 'creado_por');
    }

    /**
     * Relación: usuario que aprobó el registro.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function aprobadoPor()
    {
        return $this->belongsTo(Usuario::class, 'aprobado_por');
    }
}