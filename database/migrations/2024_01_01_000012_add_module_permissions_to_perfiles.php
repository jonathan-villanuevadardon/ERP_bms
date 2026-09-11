<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Amplía los perfiles con permisos para cargas masivas y módulos nuevos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perfiles', function (Blueprint $table) {
            $table->boolean('puede_cargas_masivas')->default(false);
        });
        Schema::table('perfiles', function (Blueprint $table) {
            $table->boolean('puede_gestionar_incapacidades')->default(false);
        });
        Schema::table('perfiles', function (Blueprint $table) {
            $table->boolean('puede_ver_lista_asistencia')->default(false);
        });

        // El administrador conserva acceso total tras ampliar el catálogo.
        DB::table('perfiles')->where('es_admin', true)->update([
            'puede_cargas_masivas' => true,
            'puede_gestionar_incapacidades' => true,
            'puede_ver_lista_asistencia' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('perfiles', function (Blueprint $table) {
            $table->dropColumn('puede_ver_lista_asistencia');
        });
        Schema::table('perfiles', function (Blueprint $table) {
            $table->dropColumn('puede_gestionar_incapacidades');
        });
        Schema::table('perfiles', function (Blueprint $table) {
            $table->dropColumn('puede_cargas_masivas');
        });
    }
};
