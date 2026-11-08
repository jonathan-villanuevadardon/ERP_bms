<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlmacenUbicacion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'almacen_ubicaciones';

    protected $fillable = [
        'almacen_id', 'codigo', 'anaquel', 'nivel', 'descripcion', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(AlmacenAlmacen::class, 'almacen_id');
    }

    public function existencias(): HasMany
    {
        return $this->hasMany(AlmacenExistencia::class, 'ubicacion_id');
    }

    public function prestamos(): HasMany
    {
        return $this->hasMany(AlmacenPrestamo::class, 'ubicacion_id');
    }
}
