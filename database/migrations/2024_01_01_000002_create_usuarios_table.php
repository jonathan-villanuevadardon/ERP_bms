<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla "usuarios" del sistema ERP.
 *
 * Los usuarios administran el ERP vía login (email + password).
 * Cada usuario pertenece a un perfil que define sus permisos y secciones.
 * El campo "clave_empleado" vincula opcionalmente al número de empleado
 * (HCBMS_ALL_OP.clave) cuando el usuario es también un operador/empleado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();

            // Número de empleado (nulo si es un administrador puro del sistema).
            $table->bigInteger('clave_empleado')->nullable();

            // Relación con el perfil (rol de acceso).
            $table->foreignId('perfil_id')->nullable()->constrained('perfiles')->nullOnDelete();

            // Estado: 1 = activo, 0 = suspendido.
            $table->boolean('activo')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });

        // Tabla de sesiones para el driver "database".
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('usuarios');
    }
};