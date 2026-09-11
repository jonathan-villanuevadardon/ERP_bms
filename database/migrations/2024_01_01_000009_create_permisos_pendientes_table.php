<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla "permisos_pendientes" (buzón de aprobación).
 *
 * Solo aplica a permisos CON goce de sueldo, que requieren aprobación del
 * Admin antes de pasar a "permisos". Los permisos sin goce no pasan por aquí.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permisos_pendientes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('clave');
            $table->string('nombre_completo', 150)->nullable();
            $table->string('area', 100)->nullable();
            $table->string('seccion', 20)->nullable();

            $table->string('tipo', 20)->default('con_goce');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->string('motivo', 500)->nullable();

            $table->string('estado', 20)->default('pendiente'); // pendiente | aprobado | rechazado

            // Relaciones gestionadas por Eloquent para compatibilidad FreeTDS.
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
        Schema::dropIfExists('permisos_pendientes');
    }
};
