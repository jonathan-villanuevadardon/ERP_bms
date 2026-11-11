<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlmacenArticulo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'almacen_articulos';

    protected $fillable = [
        'sku', 'nombre', 'descripcion', 'tipo', 'unidad_medida', 'numero_serie',
        'ubicacion_id', 'stock_actual', 'stock_minimo', 'stock_maximo',
        'costo_promedio', 'activo',
    ];

    protected $casts = [
        'stock_actual' => 'decimal:3',
        'stock_minimo' => 'decimal:3',
        'stock_maximo' => 'decimal:3',
        'costo_promedio' => 'decimal:4',
        'activo' => 'boolean',
    ];

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(AlmacenUbicacion::class, 'ubicacion_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(AlmacenMovimiento::class, 'articulo_id');
    }

    public function existencias(): HasMany
    {
        return $this->hasMany(AlmacenExistencia::class, 'articulo_id');
    }

    public function prestamos(): HasMany
    {
        return $this->hasMany(AlmacenPrestamo::class, 'articulo_id');
    }

    public function ajustesCosto(): HasMany
    {
        return $this->hasMany(AlmacenAjusteCosto::class, 'articulo_id');
    }
}
