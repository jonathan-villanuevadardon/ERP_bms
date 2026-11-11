<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('almacen_prestamos', function (Blueprint $table) {
            $table->decimal('cantidad_baja', 18, 3)->default(0);
        });

        Schema::table('almacen_prestamo_movimientos', function (Blueprint $table) {
            $table->string('condicion', 20)->nullable();
            $table->index(['condicion', 'fecha_movimiento']);
        });
    }

    public function down(): void
    {
        Schema::table('almacen_prestamo_movimientos', function (Blueprint $table) {
            $table->dropIndex(['condicion', 'fecha_movimiento']);
            $table->dropColumn('condicion');
        });

        Schema::table('almacen_prestamos', function (Blueprint $table) {
            $table->dropColumn('cantidad_baja');
        });
    }
};
