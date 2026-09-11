<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo "VacacionPendiente".
 *
 * Registro de vacaciones en buzón de aprobación. RH y Admin pueden
 * modificarlo solo ANTES de ser aprobado. Al aprobarse se copia a "Vacacion".
 */
class VacacionPendiente extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabla asociada.
     */
    protected $table = 'vacaciones_pendientes';

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

    public function creadoPor()
    {
        return $this->belongsTo(Usuario::class, 'creado_por');
    }

    public function aprobadoPor()
    {
        return $this->belongsTo(Usuario::class, 'aprobado_por');
    }
}