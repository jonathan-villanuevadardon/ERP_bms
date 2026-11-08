<?php

namespace App\Models;

use App\Services\SeccionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlmacenAlmacen extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'almacen_almacenes';

    protected $fillable = [
        'codigo', 'nombre', 'seccion', 'descripcion', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function ubicaciones(): HasMany
    {
        return $this->hasMany(AlmacenUbicacion::class, 'almacen_id');
    }

    public function scopeVisiblesPara(Builder $query, Usuario $usuario): Builder
    {
        $secciones = SeccionService::permitidas($usuario);

        return $secciones === null ? $query : $query->whereIn('seccion', $secciones);
    }
}
