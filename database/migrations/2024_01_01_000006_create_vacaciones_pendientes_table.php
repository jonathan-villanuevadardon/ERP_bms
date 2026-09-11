<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla "vacaciones_pendientes" (buzón de aprobación).
 *
 * RH agrega periodos de vacaciones; ANTES de pasar a "vacaciones" deben ser
 * aprobados por el Admin. Mientras están pendientes, ambos lados pueden
 * modificarlos. Tras ser aprobadas se copian a "vacaciones" (solo lectura).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacaciones_pendientes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('clave');
            $table->string('nombre_completo', 150)->nullable();
            $table->string('area', 100)->nullable();
            $table->string('seccion', 20)->nullable();

            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('observaciones', 500)->nullable();

            $table->string('estado', 20)->default('pendiente'); // pendiente | aprobado | rechazado

            $table->foreignId('creado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('aprobado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('aprobado_en')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('clave');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacaciones_pendientes');
    }
};