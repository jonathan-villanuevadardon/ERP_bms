<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla "descansos".
 *
 * Periodos de descanso agregados por RH. Existen dos tipos:
 *   - "fijo": descanso recurrente/rotativo asignado a un empleado de forma
 *     permanente; ANTES de existir aquí pasa por aprobación del Admin.
 *   - "por_periodo": descanso puntual con fechas de inicio/fin.
 *
 * Los periodos "fijos" NO aprobados viven temporalmente en
 * "descansos_pendientes"; al aprobarse se insertan/actualizan aquí.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('descansos', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('clave');
            $table->string('nombre_completo', 150)->nullable();
            $table->string('area', 100)->nullable();
            $table->string('seccion', 20)->nullable();

            $table->string('tipo', 20)->default('por_periodo'); // fijo | por_periodo

            // Fechas del descanso (para por_periodo son rango; para fijo es ciclo).
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();

            $table->string('observaciones', 500)->nullable();

            $table->boolean('activo')->default(true);

            // Referencia al origen si provino de una aprobación.
            $table->unsignedBigInteger('pendiente_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('clave');
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descansos');
    }
};