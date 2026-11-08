<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('almacen_prestamos', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 50)->unique();
            $table->unsignedBigInteger('articulo_id');
            $table->bigInteger('numero_empleado');
            $table->string('empleado_nombre', 150);
            $table->string('empleado_area', 100)->nullable();
            $table->string('empleado_seccion', 100)->nullable();
            $table->string('tipo_asignacion', 20);
            $table->string('estado', 20)->default('ACTIVO');
            $table->decimal('cantidad_entregada', 18, 3);
            $table->decimal('cantidad_devuelta', 18, 3)->default(0);
            $table->decimal('costo_unitario_salida', 18, 4)->default(0);
            $table->timestamp('fecha_salida');
            $table->date('fecha_compromiso')->nullable();
            $table->timestamp('fecha_devolucion')->nullable();
            $table->unsignedBigInteger('usuario_entrega_id')->nullable();
            $table->unsignedBigInteger('usuario_recibe_id')->nullable();
            $table->string('observaciones', 500)->nullable();
            $table->timestamps();

            $table->index(['articulo_id', 'estado']);
            $table->index(['numero_empleado', 'estado']);
            $table->index(['tipo_asignacion', 'estado']);
            $table->index('empleado_seccion');
        });

        Schema::create('almacen_prestamo_movimientos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prestamo_id');
            $table->string('tipo', 20);
            $table->decimal('cantidad', 18, 3);
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('observaciones', 500)->nullable();
            $table->timestamp('fecha_movimiento');
            $table->timestamps();

            $table->index(['prestamo_id', 'fecha_movimiento']);
            $table->index(['tipo', 'fecha_movimiento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('almacen_prestamo_movimientos');
        Schema::dropIfExists('almacen_prestamos');
    }
};
