<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('almacen_almacenes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 150);
            $table->string('seccion', 100)->nullable();
            $table->string('descripcion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['seccion', 'activo'], 'idx_almacen_seccion');
        });

        Schema::table('almacen_ubicaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('almacen_id')->nullable();
            $table->index('almacen_id', 'idx_ubic_almacen');
        });

        Schema::create('almacen_existencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('articulo_id');
            $table->unsignedBigInteger('ubicacion_id');
            $table->decimal('cantidad', 18, 3)->default(0);
            $table->decimal('stock_minimo', 18, 3)->default(0);
            $table->decimal('stock_maximo', 18, 3)->nullable();
            $table->timestamps();

            $table->unique(['articulo_id', 'ubicacion_id'], 'uq_exist_art_ubic');
            $table->index('ubicacion_id', 'idx_exist_ubic');
        });

        Schema::table('almacen_movimientos', function (Blueprint $table) {
            $table->unsignedBigInteger('ubicacion_origen_id')->nullable();
            $table->unsignedBigInteger('ubicacion_destino_id')->nullable();
            $table->decimal('existencia_origen_anterior', 18, 3)->nullable();
            $table->decimal('existencia_origen_nueva', 18, 3)->nullable();
            $table->decimal('existencia_destino_anterior', 18, 3)->nullable();
            $table->decimal('existencia_destino_nueva', 18, 3)->nullable();
            $table->index('ubicacion_origen_id', 'idx_mov_ubic_orig');
            $table->index('ubicacion_destino_id', 'idx_mov_ubic_dest');
        });

        Schema::table('almacen_prestamos', function (Blueprint $table) {
            $table->unsignedBigInteger('ubicacion_id')->nullable();
            $table->index('ubicacion_id', 'idx_prest_ubic');
        });

        $ahora = now();
        $almacenLegadoId = DB::table('almacen_almacenes')->insertGetId([
            'codigo' => 'LEGACY',
            'nombre' => 'Inventario anterior',
            'seccion' => null,
            'descripcion' => 'Almacén temporal creado para conservar existencias previas.',
            'activo' => true,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]);

        DB::table('almacen_ubicaciones')->whereNull('almacen_id')->update([
            'almacen_id' => $almacenLegadoId,
        ]);

        $ubicacionSinAsignar = DB::table('almacen_ubicaciones')->where('codigo', 'LEGACY-SIN-UBICACION')->first();
        if ($ubicacionSinAsignar) {
            $ubicacionSinAsignarId = $ubicacionSinAsignar->id;
            DB::table('almacen_ubicaciones')->where('id', $ubicacionSinAsignarId)->update([
                'almacen_id' => $almacenLegadoId,
                'activo' => true,
                'deleted_at' => null,
            ]);
        } else {
            $ubicacionSinAsignarId = DB::table('almacen_ubicaciones')->insertGetId([
                'almacen_id' => $almacenLegadoId,
                'codigo' => 'LEGACY-SIN-UBICACION',
                'anaquel' => 'Sin asignar',
                'nivel' => 'General',
                'descripcion' => 'Existencias anteriores sin ubicación registrada.',
                'activo' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }

        $ubicaciones = DB::table('almacen_ubicaciones')
            ->where('activo', true)
            ->whereNull('deleted_at')
            ->pluck('id')->mapWithKeys(
                fn ($id) => [(int) $id => true]
            );
        $ubicacionPorArticulo = [];

        DB::table('almacen_articulos')->orderBy('id')->chunk(200, function ($articulos) use ($ubicaciones, $ubicacionSinAsignarId, $ahora, &$ubicacionPorArticulo) {
            $filas = [];
            $articulosPorUbicacion = [];
            foreach ($articulos as $articulo) {
                $ubicacionId = $articulo->ubicacion_id && $ubicaciones->has((int) $articulo->ubicacion_id)
                    ? (int) $articulo->ubicacion_id
                    : $ubicacionSinAsignarId;
                $ubicacionPorArticulo[(int) $articulo->id] = $ubicacionId;
                $articulosPorUbicacion[$ubicacionId][] = $articulo->id;
                $filas[] = [
                    'articulo_id' => $articulo->id,
                    'ubicacion_id' => $ubicacionId,
                    'cantidad' => $articulo->stock_actual,
                    'stock_minimo' => $articulo->stock_minimo,
                    'stock_maximo' => $articulo->stock_maximo,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ];
            }
            if ($filas !== []) {
                DB::table('almacen_existencias')->insert($filas);
            }
            foreach ($articulosPorUbicacion as $ubicacionId => $articuloIds) {
                DB::table('almacen_articulos')->whereIn('id', $articuloIds)->update([
                    'ubicacion_id' => $ubicacionId,
                ]);
            }
        });

        DB::table('almacen_prestamos')->orderBy('id')->chunk(200, function ($prestamos) use (&$ubicacionPorArticulo, $ubicacionSinAsignarId) {
            foreach ($prestamos as $prestamo) {
                DB::table('almacen_prestamos')->where('id', $prestamo->id)->update([
                    'ubicacion_id' => $ubicacionPorArticulo[(int) $prestamo->articulo_id] ?? $ubicacionSinAsignarId,
                ]);
            }
        });

        $agregarForeignKeys = DB::getDriverName() !== 'sqlite';

        Schema::table('almacen_ubicaciones', function (Blueprint $table) {
            $table->dropIndex('idx_ubic_almacen');
        });
        Schema::table('almacen_ubicaciones', function (Blueprint $table) use ($agregarForeignKeys) {
            $table->unsignedBigInteger('almacen_id')->nullable(false)->change();
            $table->index('almacen_id', 'idx_ubic_almacen');
            if ($agregarForeignKeys) {
                $table->foreign('almacen_id', 'fk_ubic_almacen')
                    ->references('id')->on('almacen_almacenes')->noActionOnDelete();
            }
        });
        if ($agregarForeignKeys) {
            Schema::table('almacen_existencias', function (Blueprint $table) {
                $table->foreign('articulo_id', 'fk_exist_articulo')
                    ->references('id')->on('almacen_articulos')->noActionOnDelete();
                $table->foreign('ubicacion_id', 'fk_exist_ubicacion')
                    ->references('id')->on('almacen_ubicaciones')->noActionOnDelete();
            });
            Schema::table('almacen_movimientos', function (Blueprint $table) {
                $table->foreign('ubicacion_origen_id', 'fk_mov_ubic_origen')
                    ->references('id')->on('almacen_ubicaciones')->noActionOnDelete();
                $table->foreign('ubicacion_destino_id', 'fk_mov_ubic_destino')
                    ->references('id')->on('almacen_ubicaciones')->noActionOnDelete();
            });
        }
        Schema::table('almacen_prestamos', function (Blueprint $table) {
            $table->dropIndex('idx_prest_ubic');
        });
        Schema::table('almacen_prestamos', function (Blueprint $table) use ($agregarForeignKeys) {
            $table->unsignedBigInteger('ubicacion_id')->nullable(false)->change();
            $table->index('ubicacion_id', 'idx_prest_ubic');
            if ($agregarForeignKeys) {
                $table->foreign('ubicacion_id', 'fk_prest_ubicacion')
                    ->references('id')->on('almacen_ubicaciones')->noActionOnDelete();
            }
        });
    }

    public function down(): void
    {
        $puedeEliminarForeignPorNombre = DB::getDriverName() !== 'sqlite';

        DB::table('almacen_articulos')->orderBy('id')->chunk(200, function ($articulos) {
            foreach ($articulos as $articulo) {
                $cantidad = DB::table('almacen_existencias')
                    ->where('articulo_id', $articulo->id)
                    ->sum('cantidad');
                DB::table('almacen_articulos')->where('id', $articulo->id)->update([
                    'stock_actual' => $cantidad,
                ]);
            }
        });

        Schema::table('almacen_prestamos', function (Blueprint $table) use ($puedeEliminarForeignPorNombre) {
            if ($puedeEliminarForeignPorNombre) {
                $table->dropForeign('fk_prest_ubicacion');
            }
            $table->dropIndex('idx_prest_ubic');
            $table->dropColumn('ubicacion_id');
        });

        Schema::table('almacen_movimientos', function (Blueprint $table) use ($puedeEliminarForeignPorNombre) {
            if ($puedeEliminarForeignPorNombre) {
                $table->dropForeign('fk_mov_ubic_origen');
                $table->dropForeign('fk_mov_ubic_destino');
            }
            $table->dropIndex('idx_mov_ubic_orig');
            $table->dropIndex('idx_mov_ubic_dest');
            $table->dropColumn([
                'ubicacion_origen_id',
                'ubicacion_destino_id',
                'existencia_origen_anterior',
                'existencia_origen_nueva',
                'existencia_destino_anterior',
                'existencia_destino_nueva',
            ]);
        });

        Schema::dropIfExists('almacen_existencias');

        Schema::table('almacen_ubicaciones', function (Blueprint $table) use ($puedeEliminarForeignPorNombre) {
            if ($puedeEliminarForeignPorNombre) {
                $table->dropForeign('fk_ubic_almacen');
            }
            $table->dropIndex('idx_ubic_almacen');
            $table->dropColumn('almacen_id');
        });

        Schema::dropIfExists('almacen_almacenes');
    }
};
