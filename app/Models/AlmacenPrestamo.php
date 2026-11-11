<?php

namespace App\Models;

use App\Casts\SqlServerDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlmacenPrestamo extends Model
{
    use HasFactory;

    protected $table = 'almacen_prestamos';

    protected $fillable = [
        'folio', 'articulo_id', 'numero_empleado', 'empleado_nombre',
        'empleado_area', 'empleado_seccion', 'tipo_asignacion', 'estado',
        'cantidad_entregada', 'cantidad_devuelta', 'cantidad_baja', 'costo_unitario_salida',
        'fecha_salida', 'fecha_compromiso', 'fecha_devolucion', 'usuario_entrega_id',
        'usuario_recibe_id', 'ubicacion_id', 'observaciones',
    ];

    protected $casts = [
        'numero_empleado' => 'integer',
        'cantidad_entregada' => 'decimal:3',
        'cantidad_devuelta' => 'decimal:3',
        'cantidad_baja' => 'decimal:3',
        'costo_unitario_salida' => 'decimal:4',
        'fecha_salida' => 'datetime',
        'fecha_compromiso' => SqlServerDate::class,
        'fecha_devolucion' => 'datetime',
    ];

    public function articulo(): BelongsTo
    {
        return $this->belongsTo(AlmacenArticulo::class, 'articulo_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(AlmacenPrestamoMovimiento::class, 'prestamo_id');
    }

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(AlmacenUbicacion::class, 'ubicacion_id');
    }
}
