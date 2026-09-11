<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla "permisos" (final, aprobados o sin aprobación).
 *
 * Los permisos sin goce de sueldo se registran directo aquí.
 * Los permisos CON goce de sueldo pasan primero por "permisos_pendientes"
 * y, al ser aprobados por el Admin, se copian a esta tabla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permisos', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('clave');
            $table->string('nombre_completo', 150)->nullable();
            $table->string('area', 100)->nullable();
            $table->string('seccion', 20)->nullable();

            // tipo: con_goce | sin_goce
            $table->string('tipo', 20)->default('sin_goce');

            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->string('motivo', 500)->nullable();

            $table->unsignedBigInteger('pendiente_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('clave');
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permisos');
    }
};