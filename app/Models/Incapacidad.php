<?php

namespace App\Models;

use App\Casts\SqlServerDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Incapacidad extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'incapacidades';

    protected $fillable = [
        'clave', 'nombre_completo', 'area', 'seccion', 'tipo', 'folio',
        'fecha_inicio', 'fecha_fin', 'dias', 'observaciones', 'pendiente_id',
    ];

    protected $casts = [
        'clave' => 'integer',
        'fecha_inicio' => SqlServerDate::class,
        'fecha_fin' => SqlServerDate::class,
        'dias' => 'integer',
    ];
}
