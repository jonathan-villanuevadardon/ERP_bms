<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('perfiles', 'puede_gestionar_inventario')) {
            Schema::table('perfiles', function (Blueprint $table) {
                $table->boolean('puede_gestionar_inventario')->default(false);
            });
        }

        if (! Schema::hasColumn('perfiles', 'puede_gestionar_prestamos_herramientas')) {
            Schema::table('perfiles', function (Blueprint $table) {
                $table->boolean('puede_gestionar_prestamos_herramientas')->default(false);
            });
        }

        DB::table('perfiles')->where('es_admin', true)->update([
            'puede_gestionar_inventario' => true,
            'puede_gestionar_prestamos_herramientas' => true,
        ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('perfiles', 'puede_gestionar_prestamos_herramientas')) {
            Schema::table('perfiles', function (Blueprint $table) {
                $table->dropColumn('puede_gestionar_prestamos_herramientas');
            });
        }

        if (Schema::hasColumn('perfiles', 'puede_gestionar_inventario')) {
            Schema::table('perfiles', function (Blueprint $table) {
                $table->dropColumn('puede_gestionar_inventario');
            });
        }
    }
};
