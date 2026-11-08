<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlmacenMovimiento extends Model
{
    use HasFactory;

    protected $table = 'almacen_movimientos';

    protected $fillable = [
        'articulo_id', 'tipo', 'cantidad', 'costo_unitario', 'costo_total',
        'existencia_anterior', 'existencia_nueva', 'referencia', 'numero_empleado',
        'empleado_nombre', 'empleado_area', 'empleado_seccion', 'prestamo_id',
        'ubicacion_origen_id', 'ubicacion_destino_id',
        'existencia_origen_anterior', 'existencia_origen_nueva',
        'existencia_destino_anterior', 'existencia_destino_nueva',
        'usuario_id', 'observaciones', 'fecha_movimiento',
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
        'costo_unitario' => 'decimal:4',
        'costo_total' => 'decimal:4',
        'existencia_anterior' => 'decimal:3',
        'existencia_nueva' => 'decimal:3',
        'existencia_origen_anterior' => 'decimal:3',
        'existencia_origen_nueva' => 'decimal:3',
        'existencia_destino_anterior' => 'decimal:3',
        'existencia_destino_nueva' => 'decimal:3',
        'numero_empleado' => 'integer',
        'fecha_movimiento' => 'datetime',
    ];

    public function articulo(): BelongsTo
    {
        return $this->belongsTo(AlmacenArticulo::class, 'articulo_id');
    }

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(AlmacenPrestamo::class, 'prestamo_id');
    }

    public function ubicacionOrigen(): BelongsTo
    {
        return $this->belongsTo(AlmacenUbicacion::class, 'ubicacion_origen_id');
    }

    public function ubicacionDestino(): BelongsTo
    {
        return $this->belongsTo(AlmacenUbicacion::class, 'ubicacion_destino_id');
    }
}
