<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlmacenPrestamoMovimiento extends Model
{
    use HasFactory;

    protected $table = 'almacen_prestamo_movimientos';

    protected $fillable = [
        'prestamo_id', 'tipo', 'cantidad', 'usuario_id', 'observaciones',
        'fecha_movimiento',
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
        'fecha_movimiento' => 'datetime',
    ];

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(AlmacenPrestamo::class, 'prestamo_id');
    }
}
