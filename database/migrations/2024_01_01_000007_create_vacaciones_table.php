<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla "vacaciones".
 *
 * Periodos de vacaciones YA aprobados por el Admin. Una vez aprobados,
 * NO se modifican desde RH (solo lectura); el Admin conserva la capacidad
 * de corregir por excepción.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacaciones', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('clave');
            $table->string('nombre_completo', 150)->nullable();
            $table->string('area', 100)->nullable();
            $table->string('seccion', 20)->nullable();

            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('observaciones', 500)->nullable();

            $table->unsignedBigInteger('pendiente_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('clave');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacaciones');
    }
};