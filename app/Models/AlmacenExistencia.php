<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlmacenExistencia extends Model
{
    use HasFactory;

    protected $table = 'almacen_existencias';

    protected $fillable = [
        'articulo_id', 'ubicacion_id', 'cantidad', 'stock_minimo', 'stock_maximo',
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
        'stock_minimo' => 'decimal:3',
        'stock_maximo' => 'decimal:3',
    ];

    public function articulo(): BelongsTo
    {
        return $this->belongsTo(AlmacenArticulo::class, 'articulo_id');
    }

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(AlmacenUbicacion::class, 'ubicacion_id');
    }
}
