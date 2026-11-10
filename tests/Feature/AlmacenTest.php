<?php

namespace Tests\Feature;

use App\Models\AlmacenAlmacen;
use App\Models\AlmacenArticulo;
use App\Models\AlmacenExistencia;
use App\Models\AlmacenPrestamo;
use App\Models\AlmacenUbicacion;
use App\Models\Perfil;
use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AlmacenTest extends TestCase
{
    private Usuario $usuario;

    private AlmacenAlmacen $almacenPlanta;

    private AlmacenAlmacen $almacenTajo;

    private AlmacenUbicacion $ubicacionPlanta;

    private AlmacenUbicacion $ubicacionTajo;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        $this->crearEsquema();

        DB::table('HCBMS_ALL_OP')->insert([
            ['clave' => 100, 'Nombre_completo' => 'Empleado Planta', 'categoria' => 'Operador', 'dept_name' => 'Operaciones', 'seccion' => 'PLANTA'],
            ['clave' => 200, 'Nombre_completo' => 'Empleado Tajo', 'categoria' => 'Operador', 'dept_name' => 'Mina', 'seccion' => 'TAJO'],
        ]);

        $this->almacenPlanta = AlmacenAlmacen::query()->create([
            'codigo' => 'ALM-PLANTA', 'nombre' => 'Almacén Planta', 'seccion' => 'PLANTA', 'activo' => true,
        ]);
        $this->ubicacionPlanta = $this->almacenPlanta->ubicaciones()->create([
            'codigo' => 'PL-A1-N1', 'anaquel' => 'A1', 'nivel' => 'N1', 'activo' => true,
        ]);
        $this->almacenTajo = AlmacenAlmacen::query()->create([
            'codigo' => 'ALM-TAJO', 'nombre' => 'Almacén Tajo', 'seccion' => 'TAJO', 'activo' => true,
        ]);
        $this->ubicacionTajo = $this->almacenTajo->ubicaciones()->create([
            'codigo' => 'TJ-A1-N1', 'anaquel' => 'A1', 'nivel' => 'N1', 'activo' => true,
        ]);

        $perfil = Perfil::query()->create([
            'nombre' => 'Almacén',
            'slug' => 'almacen',
            'puede_gestionar_inventario' => true,
            'puede_gestionar_prestamos_herramientas' => true,
        ]);
        $this->usuario = Usuario::query()->create([
            'name' => 'Almacenista',
            'email' => 'almacen@example.test',
            'password' => 'password',
            'perfil_id' => $perfil->id,
            'activo' => true,
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            'almacen_prestamo_movimientos', 'almacen_prestamos', 'almacen_movimientos',
            'almacen_existencias', 'almacen_articulos', 'almacen_ubicaciones', 'almacen_almacenes',
            'HCBMS_ALL_OP', 'usuarios', 'perfiles',
        ] as $tabla) {
            Schema::dropIfExists($tabla);
        }

        parent::tearDown();
    }

    public function test_permisos_de_modulo_son_independientes(): void
    {
        $perfil = Perfil::query()->create(['nombre' => 'Sin almacén', 'slug' => 'sin-almacen']);
        $usuario = Usuario::query()->create([
            'name' => 'Sin permiso', 'email' => 'sin-permiso@example.test', 'password' => 'password',
            'perfil_id' => $perfil->id, 'activo' => true,
        ]);

        $this->actingAs($usuario)->get(route('inventario.index'))->assertForbidden();
        $this->actingAs($usuario)->get(route('prestamos-herramientas.index'))->assertForbidden();
    }

    public function test_crea_almacenes_con_secciones_dinamicas_de_hc_y_ubicaciones(): void
    {
        $this->actingAs($this->usuario)->get(route('inventario.almacenes'))
            ->assertOk()->assertSee('PLANTA')->assertSee('TAJO')->assertSee('Agregar almacén');

        $this->post(route('inventario.almacenes.guardar'), [
            'codigo' => 'ALM-NUEVO', 'nombre' => 'Almacén Nuevo', 'seccion' => 'PLANTA',
        ])->assertRedirectToRoute('inventario.almacenes');
        $almacen = AlmacenAlmacen::query()->where('codigo', 'ALM-NUEVO')->firstOrFail();

        $this->post(route('inventario.ubicaciones.guardar', $almacen), [
            'codigo' => 'NUEVO-A1-N1', 'anaquel' => 'A1', 'nivel' => 'N1',
        ])->assertRedirectToRoute('inventario.almacenes');

        $this->assertDatabaseHas('almacen_ubicaciones', ['almacen_id' => $almacen->id, 'codigo' => 'NUEVO-A1-N1']);
    }

    public function test_entradas_actualizan_stock_por_ubicacion_y_costo_promedio(): void
    {
        $articulo = $this->crearArticulo('MAT-001', 'MATERIAL', 0, 0);

        $this->actingAs($this->usuario)->post(route('inventario.movimientos.guardar'), [
            'articulo_id' => $articulo->id, 'tipo' => 'ENTRADA', 'cantidad' => 10,
            'costo_unitario' => 5, 'ubicacion_destino_id' => $this->ubicacionPlanta->id,
        ])->assertRedirectToRoute('inventario.movimientos');
        $this->post(route('inventario.movimientos.guardar'), [
            'articulo_id' => $articulo->id, 'tipo' => 'ENTRADA', 'cantidad' => 10,
            'costo_unitario' => 7, 'ubicacion_destino_id' => $this->ubicacionTajo->id,
        ])->assertRedirectToRoute('inventario.movimientos');

        $this->assertSame(20.0, (float) $articulo->refresh()->stock_actual);
        $this->assertSame(6.0, (float) $articulo->costo_promedio);
        $this->assertSame(10.0, $this->cantidadEn($articulo, $this->ubicacionPlanta));
        $this->assertSame(10.0, $this->cantidadEn($articulo, $this->ubicacionTajo));
    }

    public function test_registra_unidades_de_medida_y_las_muestra_en_la_salida(): void
    {
        $this->actingAs($this->usuario)->get(route('inventario.articulos.crear'))
            ->assertOk()
            ->assertSee('Kilogramo (kg)')
            ->assertSee('Gramo (g)')
            ->assertSee('Litro (L)');

        $this->post(route('inventario.articulos.guardar'), [
            'sku' => 'MAT-KG-001',
            'nombre' => 'Material a granel',
            'tipo' => 'MATERIAL',
            'unidad_medida' => 'kilogramo',
            'ubicacion_id' => $this->ubicacionPlanta->id,
            'stock_inicial' => 10.5,
            'costo_unitario_inicial' => 20,
            'stock_minimo' => 1,
            'stock_maximo' => 50,
            'activo' => 1,
        ])->assertRedirectToRoute('inventario.index');

        $articulo = AlmacenArticulo::query()->where('sku', 'MAT-KG-001')->firstOrFail();
        $this->get(route('inventario.movimientos.crear', ['articulo_id' => $articulo->id]))
            ->assertOk()
            ->assertSee('data-unidad="kilogramo"', false);

        $this->post(route('inventario.movimientos.guardar'), [
            'articulo_id' => $articulo->id,
            'tipo' => 'SALIDA',
            'cantidad' => 1.25,
            'numero_empleado' => 100,
            'ubicacion_origen_id' => $this->ubicacionPlanta->id,
        ])->assertRedirectToRoute('inventario.movimientos');

        $this->assertSame('kilogramo', $articulo->refresh()->unidad_medida);
        $this->assertSame(9.25, (float) $articulo->stock_actual);
        $this->get(route('inventario.movimientos'))
            ->assertOk()
            ->assertSee('1.250')
            ->assertSee('kilogramo');
    }

    public function test_transferencia_mueve_stock_sin_cambiar_total_ni_costo(): void
    {
        $articulo = $this->crearArticulo('MAT-TRANS', 'MATERIAL', 10, 5);

        $this->actingAs($this->usuario)->post(route('inventario.movimientos.guardar'), [
            'articulo_id' => $articulo->id,
            'tipo' => 'TRANSFERENCIA',
            'cantidad' => 4,
            'ubicacion_origen_id' => $this->ubicacionPlanta->id,
            'ubicacion_destino_id' => $this->ubicacionTajo->id,
        ])->assertRedirectToRoute('inventario.movimientos');

        $this->assertSame(6.0, $this->cantidadEn($articulo, $this->ubicacionPlanta));
        $this->assertSame(4.0, $this->cantidadEn($articulo, $this->ubicacionTajo));
        $this->assertSame(10.0, (float) $articulo->refresh()->stock_actual);
        $this->assertSame(5.0, (float) $articulo->costo_promedio);
        $this->assertDatabaseHas('almacen_movimientos', [
            'tipo' => 'TRANSFERENCIA',
            'ubicacion_origen_id' => $this->ubicacionPlanta->id,
            'ubicacion_destino_id' => $this->ubicacionTajo->id,
            'existencia_origen_nueva' => 6,
            'existencia_destino_nueva' => 4,
        ]);
    }

    public function test_salida_usa_hc_costo_promedio_y_evitar_stock_negativo(): void
    {
        $articulo = $this->crearArticulo('MAT-002', 'MATERIAL', 10, 5);

        $this->actingAs($this->usuario)->post(route('inventario.movimientos.guardar'), [
            'articulo_id' => $articulo->id, 'tipo' => 'SALIDA', 'cantidad' => 2,
            'numero_empleado' => 100, 'costo_unitario' => 1,
            'ubicacion_origen_id' => $this->ubicacionPlanta->id,
        ])->assertRedirectToRoute('inventario.movimientos');

        $this->assertDatabaseHas('almacen_movimientos', [
            'articulo_id' => $articulo->id, 'numero_empleado' => 100,
            'empleado_nombre' => 'Empleado Planta', 'empleado_seccion' => 'PLANTA',
            'costo_unitario' => 5, 'existencia_origen_nueva' => 8,
        ]);

        $this->post(route('inventario.movimientos.guardar'), [
            'articulo_id' => $articulo->id, 'tipo' => 'SALIDA', 'cantidad' => 20,
            'numero_empleado' => 100, 'ubicacion_origen_id' => $this->ubicacionPlanta->id,
        ])->assertSessionHasErrors('cantidad');

        $this->assertSame(8.0, $this->cantidadEn($articulo, $this->ubicacionPlanta));
        $this->assertDatabaseCount('almacen_movimientos', 1);
    }

    public function test_prestamo_devuelve_a_la_ubicacion_original_y_conserva_historial(): void
    {
        $herramienta = $this->crearArticulo('HER-001', 'HERRAMIENTA', 2, 50);
        $existencia = $this->existenciaEn($herramienta, $this->ubicacionPlanta);

        $this->actingAs($this->usuario)->post(route('prestamos-herramientas.guardar'), [
            'existencia_id' => $existencia->id, 'numero_empleado' => 100,
            'tipo_asignacion' => 'RESGUARDO', 'cantidad' => 1,
        ])->assertRedirectToRoute('prestamos-herramientas.index');

        $prestamo = AlmacenPrestamo::query()->firstOrFail();
        $this->assertSame($this->ubicacionPlanta->id, $prestamo->ubicacion_id);
        $this->assertSame(1.0, $this->cantidadEn($herramienta, $this->ubicacionPlanta));

        $this->post(route('prestamos-herramientas.devolver', $prestamo), ['cantidad' => 0.4])
            ->assertRedirectToRoute('prestamos-herramientas.index');
        $this->assertSame('PARCIAL', $prestamo->refresh()->estado);
        $this->assertSame(1.4, $this->cantidadEn($herramienta, $this->ubicacionPlanta));

        $this->post(route('prestamos-herramientas.devolver', $prestamo), ['cantidad' => 0.6]);
        $this->assertSame('DEVUELTO', $prestamo->refresh()->estado);
        $this->assertSame(2.0, $this->cantidadEn($herramienta, $this->ubicacionPlanta));
        $this->assertDatabaseCount('almacen_prestamo_movimientos', 3);
        $this->assertDatabaseHas('almacen_movimientos', [
            'tipo' => 'DEVOLUCION', 'ubicacion_destino_id' => $this->ubicacionPlanta->id,
        ]);
    }

    public function test_busca_herramientas_disponibles_por_codigo_nombre_o_serie(): void
    {
        $taladro = $this->crearArticulo('HER-TAL-001', 'HERRAMIENTA', 2, 50);
        $taladro->update(['nombre' => 'Taladro industrial', 'numero_serie' => 'SERIE-7788']);
        $this->crearArticulo('HER-LLA-002', 'HERRAMIENTA', 1, 25)
            ->update(['nombre' => 'Llave combinada']);
        $this->crearArticulo('MAT-TAL-003', 'MATERIAL', 3, 10)
            ->update(['nombre' => 'Taladro de práctica']);

        $this->actingAs($this->usuario)
            ->get(route('prestamos-herramientas.crear'))
            ->assertOk()
            ->assertSee('Buscar herramienta')
            ->assertDontSee('Taladro industrial');

        $this->getJson(route('prestamos-herramientas.buscar-herramientas', ['q' => 'HER-TAL']))
            ->assertOk()
            ->assertJsonCount(1, 'resultados')
            ->assertJsonPath('resultados.0.nombre', 'Taladro industrial');

        $this->getJson(route('prestamos-herramientas.buscar-herramientas', ['q' => 'industrial']))
            ->assertOk()
            ->assertJsonPath('resultados.0.sku', 'HER-TAL-001');

        $this->getJson(route('prestamos-herramientas.buscar-herramientas', ['q' => '7788']))
            ->assertOk()
            ->assertJsonPath('resultados.0.numero_serie', 'SERIE-7788');

        $this->getJson(route('prestamos-herramientas.buscar-herramientas', ['q' => 'T']))
            ->assertOk()
            ->assertJsonCount(0, 'resultados');
    }

    public function test_perfil_restringido_no_opera_almacen_ni_empleado_de_otra_seccion(): void
    {
        $perfil = Perfil::query()->create([
            'nombre' => 'Almacén Planta', 'slug' => 'almacen-planta',
            'puede_gestionar_inventario' => true, 'puede_gestionar_prestamos_herramientas' => true,
            'secciones' => ['PLANTA'],
        ]);
        $usuario = Usuario::query()->create([
            'name' => 'Almacenista Planta', 'email' => 'planta@example.test', 'password' => 'password',
            'perfil_id' => $perfil->id, 'activo' => true,
        ]);
        $herramienta = $this->crearArticulo('HER-002', 'HERRAMIENTA', 1, 100);
        $existencia = $this->existenciaEn($herramienta, $this->ubicacionPlanta);

        $this->actingAs($usuario)->post(route('inventario.movimientos.guardar'), [
            'articulo_id' => $herramienta->id, 'tipo' => 'ENTRADA', 'cantidad' => 1,
            'costo_unitario' => 100, 'ubicacion_destino_id' => $this->ubicacionTajo->id,
        ])->assertForbidden();

        $this->post(route('prestamos-herramientas.guardar'), [
            'existencia_id' => $existencia->id, 'numero_empleado' => 200,
            'tipo_asignacion' => 'PRESTAMO', 'cantidad' => 1,
            'fecha_compromiso' => now()->addDay()->format('Y-m-d'),
        ])->assertForbidden();

        $this->get(route('inventario.index'))->assertOk()->assertSee('ALM-PLANTA')->assertDontSee('ALM-TAJO');
    }

    public function test_alertas_por_ubicacion_y_filtro_por_trabajador(): void
    {
        $material = $this->crearArticulo('MAT-MIN', 'MATERIAL', 1, 3);
        $this->existenciaEn($material, $this->ubicacionPlanta)->update(['stock_minimo' => 2, 'stock_maximo' => 10]);
        $herramienta = $this->crearArticulo('HER-FILTRO', 'HERRAMIENTA', 1, 50);

        $this->actingAs($this->usuario)->get(route('inventario.index', ['alerta' => 'MINIMO']))
            ->assertOk()->assertSee('MAT-MIN')->assertDontSee('HER-FILTRO');

        $this->post(route('prestamos-herramientas.guardar'), [
            'existencia_id' => $this->existenciaEn($herramienta, $this->ubicacionPlanta)->id,
            'numero_empleado' => 100, 'tipo_asignacion' => 'PRESTAMO', 'cantidad' => 1,
            'fecha_compromiso' => now()->addDay()->format('Y-m-d'),
        ]);

        $this->get(route('prestamos-herramientas.index', ['trabajador' => 'Empleado Planta']))
            ->assertOk()->assertSee('Empleado Planta');
        $this->get(route('prestamos-herramientas.index', ['trabajador' => 'Empleado Tajo']))
            ->assertOk()->assertDontSee('Empleado Planta');
    }

    public function test_herramienta_no_puede_salir_como_material(): void
    {
        $herramienta = $this->crearArticulo('HER-003', 'HERRAMIENTA', 1, 75);

        $this->actingAs($this->usuario)->post(route('inventario.movimientos.guardar'), [
            'articulo_id' => $herramienta->id, 'tipo' => 'SALIDA', 'cantidad' => 1,
            'numero_empleado' => 100, 'ubicacion_origen_id' => $this->ubicacionPlanta->id,
        ])->assertSessionHasErrors('articulo_id');

        $this->assertSame(1.0, $this->cantidadEn($herramienta, $this->ubicacionPlanta));
        $this->assertDatabaseCount('almacen_movimientos', 0);
    }

    public function test_devolucion_conserva_costo_de_salida_en_promedio_ponderado(): void
    {
        $herramienta = $this->crearArticulo('HER-COSTO', 'HERRAMIENTA', 2, 50);
        $this->actingAs($this->usuario)->post(route('prestamos-herramientas.guardar'), [
            'existencia_id' => $this->existenciaEn($herramienta, $this->ubicacionPlanta)->id,
            'numero_empleado' => 100, 'tipo_asignacion' => 'RESGUARDO', 'cantidad' => 1,
        ]);
        $prestamo = AlmacenPrestamo::query()->firstOrFail();

        $this->post(route('inventario.movimientos.guardar'), [
            'articulo_id' => $herramienta->id, 'tipo' => 'ENTRADA', 'cantidad' => 1,
            'costo_unitario' => 100, 'ubicacion_destino_id' => $this->ubicacionPlanta->id,
        ]);
        $this->assertSame(75.0, (float) $herramienta->refresh()->costo_promedio);

        $this->post(route('prestamos-herramientas.devolver', $prestamo), ['cantidad' => 1]);
        $this->assertSame(3.0, (float) $herramienta->refresh()->stock_actual);
        $this->assertSame(66.6667, (float) $herramienta->costo_promedio);
    }

    public function test_no_se_desactiva_herramienta_con_prestamo_abierto(): void
    {
        $herramienta = $this->crearArticulo('HER-ACTIVA', 'HERRAMIENTA', 1, 50);
        $this->actingAs($this->usuario)->post(route('prestamos-herramientas.guardar'), [
            'existencia_id' => $this->existenciaEn($herramienta, $this->ubicacionPlanta)->id,
            'numero_empleado' => 100, 'tipo_asignacion' => 'RESGUARDO', 'cantidad' => 1,
        ]);

        $this->put(route('inventario.articulos.actualizar', $herramienta), [
            'sku' => $herramienta->sku, 'nombre' => $herramienta->nombre,
            'tipo' => 'HERRAMIENTA', 'unidad_medida' => 'pieza', 'activo' => 0,
        ])->assertSessionHasErrors('activo');

        $this->assertTrue($herramienta->refresh()->activo);
    }

    public function test_rechaza_ubicaciones_ajenas_al_tipo_y_concilia_el_total(): void
    {
        $articulo = $this->crearArticulo('MAT-CONCILIA', 'MATERIAL', 10, 5);

        $this->actingAs($this->usuario)->post(route('inventario.movimientos.guardar'), [
            'articulo_id' => $articulo->id, 'tipo' => 'ENTRADA', 'cantidad' => 1,
            'costo_unitario' => 5, 'ubicacion_origen_id' => $this->ubicacionPlanta->id,
            'ubicacion_destino_id' => $this->ubicacionTajo->id,
        ])->assertSessionHasErrors('ubicacion_origen_id');

        $this->post(route('inventario.movimientos.guardar'), [
            'articulo_id' => $articulo->id, 'tipo' => 'SALIDA', 'cantidad' => 1,
            'numero_empleado' => 100, 'ubicacion_origen_id' => $this->ubicacionPlanta->id,
            'ubicacion_destino_id' => $this->ubicacionTajo->id,
        ])->assertSessionHasErrors('ubicacion_destino_id');

        $articulo->update(['stock_actual' => 999]);
        $this->post(route('inventario.movimientos.guardar'), [
            'articulo_id' => $articulo->id, 'tipo' => 'ENTRADA', 'cantidad' => 1,
            'costo_unitario' => 5, 'ubicacion_destino_id' => $this->ubicacionTajo->id,
        ])->assertRedirectToRoute('inventario.movimientos');

        $this->assertSame(11.0, (float) $articulo->refresh()->stock_actual);
        $this->assertSame(10.0, $this->cantidadEn($articulo, $this->ubicacionPlanta));
        $this->assertSame(1.0, $this->cantidadEn($articulo, $this->ubicacionTajo));
        $this->assertDatabaseCount('almacen_movimientos', 1);
    }

    public function test_salida_de_material_exige_misma_seccion_que_el_almacen(): void
    {
        $articulo = $this->crearArticulo('MAT-SECCION', 'MATERIAL', 2, 5);

        $this->actingAs($this->usuario)->post(route('inventario.movimientos.guardar'), [
            'articulo_id' => $articulo->id, 'tipo' => 'SALIDA', 'cantidad' => 1,
            'numero_empleado' => 200, 'ubicacion_origen_id' => $this->ubicacionPlanta->id,
        ])->assertSessionHasErrors('numero_empleado');

        $this->assertSame(2.0, $this->cantidadEn($articulo, $this->ubicacionPlanta));
        $this->assertDatabaseCount('almacen_movimientos', 0);
    }

    public function test_perfil_restringido_no_edita_articulo_compartido_ni_ve_transferencia_externa(): void
    {
        $perfil = Perfil::query()->create([
            'nombre' => 'Inventario Planta', 'slug' => 'inventario-planta',
            'puede_gestionar_inventario' => true, 'secciones' => ['PLANTA'],
        ]);
        $usuario = Usuario::query()->create([
            'name' => 'Inventario Planta', 'email' => 'inventario-planta@example.test',
            'password' => 'password', 'perfil_id' => $perfil->id, 'activo' => true,
        ]);
        $articulo = $this->crearArticulo('MAT-COMPARTIDO', 'MATERIAL', 2, 5);

        $this->actingAs($this->usuario)->post(route('inventario.movimientos.guardar'), [
            'articulo_id' => $articulo->id, 'tipo' => 'TRANSFERENCIA', 'cantidad' => 1,
            'ubicacion_origen_id' => $this->ubicacionPlanta->id,
            'ubicacion_destino_id' => $this->ubicacionTajo->id,
            'referencia' => 'TRANSFERENCIA-EXTERNA',
        ])->assertRedirectToRoute('inventario.movimientos');

        $this->actingAs($usuario)->get(route('inventario.articulos.editar', $articulo))->assertForbidden();
        $this->get(route('inventario.movimientos'))->assertOk()->assertDontSee('TRANSFERENCIA-EXTERNA');
    }

    public function test_no_desactiva_articulo_con_stock_ni_cambia_seccion_del_almacen(): void
    {
        $articulo = $this->crearArticulo('MAT-ACTIVO', 'MATERIAL', 1, 5);

        $this->actingAs($this->usuario)->put(route('inventario.articulos.actualizar', $articulo), [
            'sku' => $articulo->sku, 'nombre' => $articulo->nombre,
            'tipo' => 'MATERIAL', 'unidad_medida' => 'pieza', 'activo' => 0,
        ])->assertSessionHasErrors('activo');

        $this->put(route('inventario.almacenes.actualizar', $this->almacenPlanta), [
            'codigo' => $this->almacenPlanta->codigo,
            'nombre' => $this->almacenPlanta->nombre,
            'seccion' => 'TAJO',
            'activo' => 1,
        ])->assertSessionHasErrors('seccion');

        $this->assertTrue($articulo->refresh()->activo);
        $this->assertSame('PLANTA', $this->almacenPlanta->refresh()->seccion);
    }

    private function crearArticulo(string $sku, string $tipo, float $stock, float $costo): AlmacenArticulo
    {
        $articulo = AlmacenArticulo::query()->create([
            'sku' => $sku, 'nombre' => "Artículo {$sku}", 'tipo' => $tipo,
            'unidad_medida' => 'pieza', 'ubicacion_id' => $this->ubicacionPlanta->id,
            'stock_actual' => $stock, 'stock_minimo' => 0, 'costo_promedio' => $costo, 'activo' => true,
        ]);
        AlmacenExistencia::query()->create([
            'articulo_id' => $articulo->id, 'ubicacion_id' => $this->ubicacionPlanta->id,
            'cantidad' => $stock, 'stock_minimo' => 0,
        ]);

        return $articulo;
    }

    private function existenciaEn(AlmacenArticulo $articulo, AlmacenUbicacion $ubicacion): AlmacenExistencia
    {
        return AlmacenExistencia::query()->where('articulo_id', $articulo->id)
            ->where('ubicacion_id', $ubicacion->id)->firstOrFail();
    }

    private function cantidadEn(AlmacenArticulo $articulo, AlmacenUbicacion $ubicacion): float
    {
        return (float) $this->existenciaEn($articulo, $ubicacion)->cantidad;
    }

    private function crearEsquema(): void
    {
        Schema::create('perfiles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->boolean('puede_gestionar_inventario')->default(false);
            $table->boolean('puede_gestionar_prestamos_herramientas')->default(false);
            $table->boolean('es_admin')->default(false);
            $table->json('secciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->foreignId('perfil_id')->nullable();
            $table->boolean('activo')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('HCBMS_ALL_OP', function (Blueprint $table) {
            $table->unsignedBigInteger('clave')->primary();
            $table->string('Nombre_completo');
            $table->string('categoria')->nullable();
            $table->string('dept_name')->nullable();
            $table->string('subarea')->nullable();
            $table->string('Nivel_2')->nullable();
            $table->string('seccion');
        });
        Schema::create('almacen_almacenes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->string('seccion')->nullable();
            $table->string('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('almacen_ubicaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('almacen_id');
            $table->string('codigo')->unique();
            $table->string('anaquel');
            $table->string('nivel');
            $table->string('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('almacen_articulos', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->string('tipo');
            $table->string('unidad_medida');
            $table->string('numero_serie')->nullable();
            $table->unsignedBigInteger('ubicacion_id')->nullable();
            $table->decimal('stock_actual', 18, 3)->default(0);
            $table->decimal('stock_minimo', 18, 3)->default(0);
            $table->decimal('stock_maximo', 18, 3)->nullable();
            $table->decimal('costo_promedio', 18, 4)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('almacen_existencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('articulo_id');
            $table->unsignedBigInteger('ubicacion_id');
            $table->decimal('cantidad', 18, 3)->default(0);
            $table->decimal('stock_minimo', 18, 3)->default(0);
            $table->decimal('stock_maximo', 18, 3)->nullable();
            $table->timestamps();
            $table->unique(['articulo_id', 'ubicacion_id']);
        });
        Schema::create('almacen_movimientos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('articulo_id');
            $table->string('tipo');
            $table->decimal('cantidad', 18, 3);
            $table->decimal('costo_unitario', 18, 4)->default(0);
            $table->decimal('costo_total', 20, 4)->default(0);
            $table->decimal('existencia_anterior', 18, 3);
            $table->decimal('existencia_nueva', 18, 3);
            $table->unsignedBigInteger('ubicacion_origen_id')->nullable();
            $table->unsignedBigInteger('ubicacion_destino_id')->nullable();
            $table->decimal('existencia_origen_anterior', 18, 3)->nullable();
            $table->decimal('existencia_origen_nueva', 18, 3)->nullable();
            $table->decimal('existencia_destino_anterior', 18, 3)->nullable();
            $table->decimal('existencia_destino_nueva', 18, 3)->nullable();
            $table->string('referencia')->nullable();
            $table->bigInteger('numero_empleado')->nullable();
            $table->string('empleado_nombre')->nullable();
            $table->string('empleado_area')->nullable();
            $table->string('empleado_seccion')->nullable();
            $table->unsignedBigInteger('prestamo_id')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('observaciones')->nullable();
            $table->timestamp('fecha_movimiento');
            $table->timestamps();
        });
        Schema::create('almacen_prestamos', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->unique();
            $table->unsignedBigInteger('articulo_id');
            $table->bigInteger('numero_empleado');
            $table->string('empleado_nombre');
            $table->string('empleado_area')->nullable();
            $table->string('empleado_seccion')->nullable();
            $table->string('tipo_asignacion');
            $table->string('estado');
            $table->decimal('cantidad_entregada', 18, 3);
            $table->decimal('cantidad_devuelta', 18, 3)->default(0);
            $table->decimal('costo_unitario_salida', 18, 4)->default(0);
            $table->unsignedBigInteger('ubicacion_id');
            $table->timestamp('fecha_salida');
            $table->date('fecha_compromiso')->nullable();
            $table->timestamp('fecha_devolucion')->nullable();
            $table->unsignedBigInteger('usuario_entrega_id')->nullable();
            $table->unsignedBigInteger('usuario_recibe_id')->nullable();
            $table->string('observaciones')->nullable();
            $table->timestamps();
        });
        Schema::create('almacen_prestamo_movimientos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prestamo_id');
            $table->string('tipo');
            $table->decimal('cantidad', 18, 3);
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('observaciones')->nullable();
            $table->timestamp('fecha_movimiento');
            $table->timestamps();
        });
    }
}
