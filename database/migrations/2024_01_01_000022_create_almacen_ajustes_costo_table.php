<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('almacen_ajustes_costo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('articulo_id');
            $table->decimal('costo_anterior', 18, 4);
            $table->decimal('costo_nuevo', 18, 4);
            $table->decimal('existencia_actual', 18, 3);
            $table->string('motivo', 500);
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamp('fecha_ajuste');
            $table->timestamps();

            $table->index(['articulo_id', 'fecha_ajuste']);
            $table->index('usuario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('almacen_ajustes_costo');
    }
};
