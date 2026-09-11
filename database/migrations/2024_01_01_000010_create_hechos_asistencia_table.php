<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla de hechos "hechos_asistencia".
 *
 * Tabla de hechos materializada con los días trabajados por empleado.
 * Se alimenta desde la vista VW_Listas_asistencia_unic a través del
 * procedimiento almacenado sp_refrescar_hechos_asistencia, que realiza una
 * carga INCREMENTAL (solo inserta días nuevos, sin truncar) para respetar la
 * "data viva" del checador (asistencias que llegan con retraso/sin internet).
 *
 * El índice ÚNICO sobre (clave, day_f) garantiza que no haya duplicados,
 * reforzando la idempotencia del SP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hechos_asistencia', function (Blueprint $table) {
            $table->id();
            // Número de empleado normalizado a entero.
            $table->bigInteger('clave');
            // Fecha trabajada.
            $table->date('day_f');

            $table->timestamps();

            // Índice ÚNICO compuesto: evita duplicados (empleado + día).
            $table->unique(['clave', 'day_f']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hechos_asistencia');
    }
};