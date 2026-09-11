<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla "roles_descanso".
 *
 * Representa el rol de descanso asignado a un empleado:
 *   - "10 días trabajados por 5 de descanso" (dias_trabajo / dias_descanso).
 *
 * Se puede asignar de forma masiva (Excel), individual o por selección.
 * Es la base para el visor de cumplimiento: se compara contra los días
 * efectivamente trabajados (VW_Listas_asistencia_unic / tabla de hechos).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles_descanso', function (Blueprint $table) {
            $table->id();
            // Número de empleado (clave) — identidad maestra del dominio.
            $table->bigInteger('clave');
            // Nombre completo (espejo de HCBMS_ALL_OP para consultas rápidas).
            $table->string('nombre_completo', 150)->nullable();
            // Área / departamento del empleado.
            $table->string('area', 100)->nullable();
            // Cargo (categoria) del empleado.
            $table->string('cargo', 100)->nullable();
            // Sección a la que pertenece (PLANTA / TAJO / TALLER 1).
            $table->string('seccion', 20)->nullable();

            // Días trabajados del ciclo.
            $table->integer('dias_trabajo');
            // Días de descanso del ciclo.
            $table->integer('dias_descanso');

            // Fecha de inicio de vigencia del rol.
            $table->date('fecha_inicio')->nullable();
            // Fecha de fin (nula = indefinido).
            $table->date('fecha_fin')->nullable();

            // Estado: 1 = activo, 0 = inactivo.
            $table->boolean('activo')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Índice por clave para búsquedas por número de empleado.
            $table->index('clave');
            $table->index('seccion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles_descanso');
    }
};