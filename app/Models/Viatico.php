<?php

namespace App\Models;

use App\Casts\SqlServerDate;
use Illuminate\Database\Eloquent\Model;

class Viatico extends Model
{
    protected $table = 'tb_viaticos';

    protected $primaryKey = 'id_viatico';

    public $timestamps = false;

    protected $fillable = [
        'numero_empleado',
        'tipo_transporte',
        'costo',
        'origen',
        'destino',
        'fecha_salida',
        'nombre_persona',
        'transportista',
        'referencia_boleto',
        'semana_pagar',
    ];

    protected $hidden = [
        'imagen_boleto',
    ];

    protected $casts = [
        'costo' => 'decimal:2',
        'fecha_salida' => SqlServerDate::class,
        'fecha_ingreso' => 'datetime',
        'fecha_revision' => 'datetime',
        'fecha_aprobacion' => 'datetime',
        'confianza_ocr' => 'decimal:4',
    ];
}
