<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla "descansos_pendientes" (buzón de aprobación).
 *
 * Los descansos "fijos" creados por RH se guardan aquí en estado "pendiente".
 * El Admin los aprueba (se copian a "descansos") o los rechaza/elimina.
 * Mientras están pendientes, ambos lados pueden modificar el registro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('descansos_pendientes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('clave');
            $table->string('nombre_completo', 150)->nullable();
            $table->string('area', 100)->nullable();
            $table->string('seccion', 20)->nullable();
            $table->string('tipo', 20)->default('fijo');

            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->string('observaciones', 500)->nullable();

            // Estado: pendiente | aprobado | rechazado.
            $table->string('estado', 20)->default('pendiente');

            // Quién creó y quién aprobó.
            // Sin constraint físico: FreeTDS falla al aplicar varios ALTER TABLE
            // consecutivos. Eloquent conserva las relaciones con usuarios.
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->unsignedBigInteger('aprobado_por')->nullable();
            $table->timestamp('aprobado_en')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('clave');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descansos_pendientes');
    }
};
