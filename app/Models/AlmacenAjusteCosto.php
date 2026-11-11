<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlmacenAjusteCosto extends Model
{
    use HasFactory;

    protected $table = 'almacen_ajustes_costo';

    protected $fillable = [
        'articulo_id', 'costo_anterior', 'costo_nuevo', 'existencia_actual',
        'motivo', 'usuario_id', 'fecha_ajuste',
    ];

    protected $casts = [
        'costo_anterior' => 'decimal:4',
        'costo_nuevo' => 'decimal:4',
        'existencia_actual' => 'decimal:3',
        'fecha_ajuste' => 'datetime',
    ];

    public function articulo(): BelongsTo
    {
        return $this->belongsTo(AlmacenArticulo::class, 'articulo_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
