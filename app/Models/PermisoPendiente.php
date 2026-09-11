<?php

namespace App\Models;

use App\Casts\SqlServerDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo "PermisoPendiente".
 *
 * Permiso CON goce de sueldo en buzón de aprobación. Solo los permisos con
 * goce requieren aprobación del Admin. Mientras esté pendiente, ambos lados
 * pueden modificarlo.
 */
class PermisoPendiente extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabla asociada.
     */
    protected $table = 'permisos_pendientes';

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
        'fecha_inicio' => SqlServerDate::class,
        'fecha_fin' => SqlServerDate::class,
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
