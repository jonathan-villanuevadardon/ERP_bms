<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncapacidadPendiente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'incapacidades_pendientes';

    protected $fillable = [
        'clave', 'nombre_completo', 'area', 'seccion', 'tipo', 'folio',
        'fecha_inicio', 'fecha_fin', 'dias', 'observaciones', 'estado',
        'creado_por', 'aprobado_por', 'aprobado_en',
    ];

    protected $casts = [
        'clave' => 'integer',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'dias' => 'integer',
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
