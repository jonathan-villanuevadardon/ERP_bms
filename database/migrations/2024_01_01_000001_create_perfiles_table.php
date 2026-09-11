<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla "perfiles" (roles de acceso del sistema).
 *
 * Un perfil define qué módulos y secciones puede ver un usuario.
 * El perfil "ADMIN" (slug) tiene acceso total y es el único que puede
 * aprobar asignaciones fijas, vacaciones y permisos con goce de sueldo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfiles', function (Blueprint $table) {
            $table->id();
            // Nombre legible del perfil, p.ej. "Recursos Humanos Planta".
            $table->string('nombre', 100);
            // Identificador único interno, p.ej. "rh_planta", "admin".
            $table->string('slug', 50)->unique();
            // Descripción para mantenimiento.
            $table->string('descripcion', 255)->nullable();

            // Permisos por módulo (booleans). Verdadero = habilitado.
            $table->boolean('puede_asignar_rol')->default(false);
            $table->boolean('puede_gestionar_descansos')->default(false);
            $table->boolean('puede_gestionar_vacaciones')->default(false);
            $table->boolean('puede_gestionar_permisos')->default(false);
            // Permiso de aprobación (solo Admin en la práctica).
            $table->boolean('puede_aprobar')->default(false);
            // Permitida la gestión de perfiles/usuarios (solo Admin).
            $table->boolean('es_admin')->default(false);
            // Visor de cumplimiento de rol.
            $table->boolean('puede_ver_visor')->default(false);

            // Secciones a las que está limitado (JSON). [] = todas.
            $table->json('secciones')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfiles');
    }
};