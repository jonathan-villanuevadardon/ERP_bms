<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('perfiles', 'puede_gestionar_viaticos')) {
            Schema::table('perfiles', function (Blueprint $table) {
                $table->boolean('puede_gestionar_viaticos')->default(false);
            });
        }

        DB::table('perfiles')->where('es_admin', true)->update([
            'puede_gestionar_viaticos' => true,
        ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('perfiles', 'puede_gestionar_viaticos')) {
            Schema::table('perfiles', function (Blueprint $table) {
                $table->dropColumn('puede_gestionar_viaticos');
            });
        }
    }
};
