<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AlmacenMigrationsTest extends TestCase
{
    public function test_migraciones_crean_permisos_y_tablas_del_almacen(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        Schema::create('perfiles', function (Blueprint $table) {
            $table->id();
            $table->boolean('es_admin')->default(false);
        });

        $permisos = require database_path('migrations/2024_01_01_000018_add_almacen_permissions_to_perfiles.php');
        $inventario = require database_path('migrations/2024_01_01_000019_create_almacen_inventario_tables.php');
        $prestamos = require database_path('migrations/2024_01_01_000020_create_almacen_prestamos_tables.php');
        $multiAlmacen = require database_path('migrations/2024_01_01_000021_add_multi_warehouse_inventory.php');

        $permisos->up();
        $inventario->up();
        $prestamos->up();

        $ahora = now();
        $ubicacionInactiva = DB::table('almacen_ubicaciones')->insertGetId([
            'codigo' => 'INACTIVA', 'anaquel' => 'A', 'nivel' => '1', 'activo' => false,
            'created_at' => $ahora, 'updated_at' => $ahora,
        ]);
        $articulo = DB::table('almacen_articulos')->insertGetId([
            'sku' => 'LEG-001', 'nombre' => 'Artículo legado', 'tipo' => 'HERRAMIENTA',
            'unidad_medida' => 'pieza', 'ubicacion_id' => $ubicacionInactiva,
            'stock_actual' => 5, 'stock_minimo' => 1, 'costo_promedio' => 10,
            'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora,
        ]);
        $prestamo = DB::table('almacen_prestamos')->insertGetId([
            'folio' => 'PRE-LEG-001', 'articulo_id' => $articulo, 'numero_empleado' => 100,
            'empleado_nombre' => 'Empleado legado', 'tipo_asignacion' => 'PRESTAMO',
            'estado' => 'ACTIVO', 'cantidad_entregada' => 1, 'cantidad_devuelta' => 0,
            'costo_unitario_salida' => 10, 'fecha_salida' => $ahora,
            'created_at' => $ahora, 'updated_at' => $ahora,
        ]);

        $multiAlmacen->up();

        $this->assertTrue(Schema::hasColumns('perfiles', [
            'puede_gestionar_inventario',
            'puede_gestionar_prestamos_herramientas',
        ]));
        $this->assertTrue(Schema::hasTable('almacen_ubicaciones'));
        $this->assertTrue(Schema::hasTable('almacen_articulos'));
        $this->assertTrue(Schema::hasTable('almacen_movimientos'));
        $this->assertTrue(Schema::hasTable('almacen_prestamos'));
        $this->assertTrue(Schema::hasTable('almacen_prestamo_movimientos'));
        $this->assertTrue(Schema::hasColumn('almacen_prestamos', 'costo_unitario_salida'));
        $this->assertTrue(Schema::hasTable('almacen_almacenes'));
        $this->assertTrue(Schema::hasTable('almacen_existencias'));
        $this->assertTrue(Schema::hasColumn('almacen_ubicaciones', 'almacen_id'));
        $this->assertTrue(Schema::hasColumns('almacen_movimientos', ['ubicacion_origen_id', 'ubicacion_destino_id']));
        $this->assertTrue(Schema::hasColumn('almacen_prestamos', 'ubicacion_id'));
        $this->assertDatabaseHas('almacen_almacenes', ['codigo' => 'LEGACY']);
        $ubicacionLegada = DB::table('almacen_ubicaciones')->where('codigo', 'LEGACY-SIN-UBICACION')->first();
        $this->assertNotNull($ubicacionLegada);
        $this->assertNotSame($ubicacionInactiva, $ubicacionLegada->id);
        $this->assertDatabaseHas('almacen_articulos', ['id' => $articulo, 'ubicacion_id' => $ubicacionLegada->id]);
        $this->assertDatabaseHas('almacen_existencias', [
            'articulo_id' => $articulo, 'ubicacion_id' => $ubicacionLegada->id, 'cantidad' => 5,
        ]);
        $this->assertDatabaseHas('almacen_prestamos', ['id' => $prestamo, 'ubicacion_id' => $ubicacionLegada->id]);

        DB::table('almacen_existencias')->where('articulo_id', $articulo)->update(['cantidad' => 7]);
        DB::table('almacen_articulos')->where('id', $articulo)->update(['stock_actual' => 999]);

        $multiAlmacen->down();
        $this->assertDatabaseHas('almacen_articulos', ['id' => $articulo, 'stock_actual' => 7]);
        $prestamos->down();
        $inventario->down();
        $permisos->down();

        $this->assertFalse(Schema::hasTable('almacen_articulos'));
        $this->assertFalse(Schema::hasColumn('perfiles', 'puede_gestionar_inventario'));
    }
}
