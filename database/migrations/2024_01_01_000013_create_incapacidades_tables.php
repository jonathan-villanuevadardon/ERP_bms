<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea el buzón de aprobación y la tabla final de incapacidades IMSS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incapacidades_pendientes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('clave');
            $table->string('nombre_completo', 150);
            $table->string('area', 100)->nullable();
            $table->string('seccion', 100)->nullable();
            // enfermedad_general | riesgo_trabajo | maternidad
            $table->string('tipo', 30);
            $table->string('folio', 100);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->integer('dias');
            $table->string('observaciones', 500)->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->unsignedBigInteger('aprobado_por')->nullable();
            $table->timestamp('aprobado_en')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['clave', 'fecha_inicio', 'fecha_fin']);
            $table->index(['estado', 'seccion']);
        });

        Schema::create('incapacidades', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('clave');
            $table->string('nombre_completo', 150);
            $table->string('area', 100)->nullable();
            $table->string('seccion', 100)->nullable();
            $table->string('tipo', 30);
            $table->string('folio', 100);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->integer('dias');
            $table->string('observaciones', 500)->nullable();
            $table->unsignedBigInteger('pendiente_id')->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['clave', 'fecha_inicio', 'fecha_fin']);
            $table->index('seccion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incapacidades');
        Schema::dropIfExists('incapacidades_pendientes');
    }
};
