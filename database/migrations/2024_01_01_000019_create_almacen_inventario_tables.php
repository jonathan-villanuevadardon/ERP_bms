<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('almacen_ubicaciones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('anaquel', 80);
            $table->string('nivel', 50);
            $table->string('descripcion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['anaquel', 'nivel']);
        });

        Schema::create('almacen_articulos', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 80)->unique();
            $table->string('nombre', 150);
            $table->string('descripcion', 500)->nullable();
            $table->string('tipo', 20);
            $table->string('unidad_medida', 30)->default('pieza');
            $table->string('numero_serie', 100)->nullable();
            $table->unsignedBigInteger('ubicacion_id')->nullable();
            $table->decimal('stock_actual', 18, 3)->default(0);
            $table->decimal('stock_minimo', 18, 3)->default(0);
            $table->decimal('stock_maximo', 18, 3)->nullable();
            $table->decimal('costo_promedio', 18, 4)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tipo', 'activo']);
            $table->index('ubicacion_id');
            $table->index('nombre');
        });

        Schema::create('almacen_movimientos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('articulo_id');
            $table->string('tipo', 30);
            $table->decimal('cantidad', 18, 3);
            $table->decimal('costo_unitario', 18, 4)->default(0);
            $table->decimal('costo_total', 20, 4)->default(0);
            $table->decimal('existencia_anterior', 18, 3);
            $table->decimal('existencia_nueva', 18, 3);
            $table->string('referencia', 100)->nullable();
            $table->bigInteger('numero_empleado')->nullable();
            $table->string('empleado_nombre', 150)->nullable();
            $table->string('empleado_area', 100)->nullable();
            $table->string('empleado_seccion', 100)->nullable();
            $table->unsignedBigInteger('prestamo_id')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('observaciones', 500)->nullable();
            $table->timestamp('fecha_movimiento');
            $table->timestamps();

            $table->index(['articulo_id', 'fecha_movimiento']);
            $table->index(['tipo', 'fecha_movimiento']);
            $table->index(['numero_empleado', 'fecha_movimiento']);
            $table->index('prestamo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('almacen_movimientos');
        Schema::dropIfExists('almacen_articulos');
        Schema::dropIfExists('almacen_ubicaciones');
    }
};
