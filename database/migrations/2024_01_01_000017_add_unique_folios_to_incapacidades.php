<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tablasConDuplicados = collect(['incapacidades_pendientes', 'incapacidades'])
            ->filter(fn (string $tabla) => DB::table($tabla)
                ->select('folio')
                ->groupBy('folio')
                ->havingRaw('COUNT(*) > 1')
                ->exists())
            ->implode(', ');

        if ($tablasConDuplicados !== '') {
            throw new RuntimeException("No se pueden crear índices únicos: existen folios duplicados en {$tablasConDuplicados}.");
        }

        Schema::table('incapacidades_pendientes', function (Blueprint $table) {
            $table->unique('folio', 'incapacidades_pendientes_folio_unique');
        });

        Schema::table('incapacidades', function (Blueprint $table) {
            $table->unique('folio', 'incapacidades_folio_unique');
        });
    }

    public function down(): void
    {
        Schema::table('incapacidades', function (Blueprint $table) {
            $table->dropUnique('incapacidades_folio_unique');
        });

        Schema::table('incapacidades_pendientes', function (Blueprint $table) {
            $table->dropUnique('incapacidades_pendientes_folio_unique');
        });
    }
};
