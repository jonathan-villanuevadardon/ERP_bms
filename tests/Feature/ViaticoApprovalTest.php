<?php

namespace Tests\Feature;

use App\Models\Perfil;
use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ViaticoApprovalTest extends TestCase
{
    private Usuario $usuario;

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

        Schema::create('perfiles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->boolean('puede_gestionar_viaticos')->default(false);
            $table->boolean('puede_aprobar')->default(false);
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

        Schema::create('tb_viaticos', function (Blueprint $table) {
            $table->increments('id_viatico');
            $table->string('folio_ingreso');
            $table->string('numero_empleado')->nullable();
            $table->string('tipo_transporte')->nullable();
            $table->decimal('costo', 18, 2)->nullable();
            $table->string('origen')->nullable();
            $table->string('destino')->nullable();
            $table->date('fecha_salida')->nullable();
            $table->string('nombre_persona')->nullable();
            $table->string('transportista')->nullable();
            $table->string('referencia_boleto')->nullable();
            $table->string('estatus_aprobacion');
            $table->string('semana_pagar')->nullable();
            $table->string('usuario_revisa')->nullable();
            $table->string('usuario_aprueba')->nullable();
            $table->dateTime('fecha_ingreso')->nullable();
            $table->dateTime('fecha_revision')->nullable();
            $table->dateTime('fecha_aprobacion')->nullable();
            $table->string('imagen_mime')->nullable();
            $table->string('imagen_nombre')->nullable();
            $table->binary('imagen_boleto')->nullable();
            $table->string('hash_archivo', 64)->nullable()->unique();
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

        Schema::create('hechos_asistencia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clave');
        });

        $this->crearEmpleado(100, 'Empleado Planta', 'PLANTA');
        $this->crearEmpleado(200, 'Empleado Tajo', 'TAJO');
        $this->crearEmpleado(300, 'Empleado Taller', 'TALLER 1');

        $perfil = Perfil::query()->create([
            'nombre' => 'Aprobadores',
            'slug' => 'aprobadores',
            'puede_gestionar_viaticos' => true,
            'puede_aprobar' => true,
        ]);

        $this->usuario = Usuario::query()->create([
            'name' => 'Usuario Aprobador',
            'email' => 'aprobador@example.test',
            'password' => 'password',
            'perfil_id' => $perfil->id,
            'activo' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('hechos_asistencia');
        Schema::dropIfExists('HCBMS_ALL_OP');
        Schema::dropIfExists('tb_viaticos');
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('perfiles');

        parent::tearDown();
    }

    public function test_usuario_con_permiso_puede_aprobar_un_viatico(): void
    {
        $id = $this->crearViatico();

        $this->actingAs($this->usuario)
            ->post(route('viaticos.aprobar'), ['viaticos' => [$id]])
            ->assertRedirectToRoute('viaticos.index')
            ->assertSessionHas('success', 'Viático(s) aprobado(s) correctamente.');

        $viatico = DB::table('tb_viaticos')->where('id_viatico', $id)->first();

        $this->assertSame('APROBADO', $viatico->estatus_aprobacion);
        $this->assertSame('Usuario Aprobador', $viatico->usuario_revisa);
        $this->assertSame('Usuario Aprobador', $viatico->usuario_aprueba);
        $this->assertNotNull($viatico->fecha_revision);
        $this->assertNotNull($viatico->fecha_aprobacion);
    }

    public function test_rechazo_actualiza_el_estado_protegido(): void
    {
        $id = $this->crearViatico();

        $this->actingAs($this->usuario)
            ->post(route('viaticos.rechazar'), ['viaticos' => [$id]])
            ->assertRedirectToRoute('viaticos.index')
            ->assertSessionHas('success', 'Viático(s) rechazado(s) correctamente.');

        $viatico = DB::table('tb_viaticos')->where('id_viatico', $id)->first();

        $this->assertSame('RECHAZADO', $viatico->estatus_aprobacion);
        $this->assertSame('Usuario Aprobador', $viatico->usuario_revisa);
        $this->assertNotNull($viatico->fecha_revision);
    }

    public function test_usuario_sin_permiso_no_puede_aprobar(): void
    {
        $perfil = Perfil::query()->create([
            'nombre' => 'Capturistas',
            'slug' => 'capturistas',
            'puede_gestionar_viaticos' => true,
            'puede_aprobar' => false,
        ]);
        $usuario = Usuario::query()->create([
            'name' => 'Usuario Capturista',
            'email' => 'capturista@example.test',
            'password' => 'password',
            'perfil_id' => $perfil->id,
            'activo' => true,
        ]);
        $id = $this->crearViatico();

        $this->actingAs($usuario)
            ->post(route('viaticos.aprobar'), ['viaticos' => [$id]])
            ->assertForbidden();

        $this->assertDatabaseHas('tb_viaticos', [
            'id_viatico' => $id,
            'estatus_aprobacion' => 'PENDIENTE_REVISION',
        ]);
    }

    public function test_proxy_https_genera_rutas_seguras(): void
    {
        Route::get('/_proxy-url-test', fn () => route('viaticos.aprobar'));

        $this->withHeader('X-Forwarded-Proto', 'https')
            ->get('http://erp.example.test/_proxy-url-test')
            ->assertOk()
            ->assertContent('https://erp.example.test/viaticos/aprobar');
    }

    public function test_formulario_usa_acciones_relativas_seguras(): void
    {
        $this->crearViatico();

        $this->actingAs($this->usuario)
            ->withHeader('X-Forwarded-Proto', 'https')
            ->get('http://erp.example.test/viaticos')
            ->assertOk()
            ->assertSee('formaction="/viaticos/aprobar"', false)
            ->assertSee('formaction="/viaticos/rechazar"', false)
            ->assertDontSee('formaction="http://', false);
    }

    public function test_usuario_solo_ve_viaticos_de_sus_secciones(): void
    {
        $usuario = $this->crearUsuarioSeccion('PLANTA');
        $this->crearViatico(100, 'VIA-PLANTA');
        $this->crearViatico(200, 'VIA-TAJO');

        $this->actingAs($usuario)
            ->get(route('viaticos.index'))
            ->assertOk()
            ->assertSee('VIA-PLANTA')
            ->assertDontSee('VIA-TAJO');
    }

    public function test_usuario_no_puede_operar_viatico_de_otra_seccion(): void
    {
        $usuario = $this->crearUsuarioSeccion('PLANTA');
        $idAjeno = $this->crearViatico(200, 'VIA-TAJO', 'boleto ajeno');

        $this->actingAs($usuario)->get(route('viaticos.editar', $idAjeno))->assertNotFound();
        $this->actingAs($usuario)->get(route('viaticos.imagen', $idAjeno))->assertNotFound();

        $this->actingAs($usuario)
            ->post(route('viaticos.aprobar'), ['viaticos' => [$idAjeno]])
            ->assertSessionHasErrors('viaticos');
        $this->actingAs($usuario)
            ->post(route('viaticos.rechazar'), ['viaticos' => [$idAjeno]])
            ->assertSessionHasErrors('viaticos');

        $this->assertDatabaseHas('tb_viaticos', [
            'id_viatico' => $idAjeno,
            'estatus_aprobacion' => 'PENDIENTE_REVISION',
        ]);
    }

    public function test_lote_mixto_no_procesa_ningun_viatico(): void
    {
        $usuario = $this->crearUsuarioSeccion('PLANTA');
        $idPermitido = $this->crearViatico(100, 'VIA-PLANTA');
        $idAjeno = $this->crearViatico(200, 'VIA-TAJO');

        $this->actingAs($usuario)
            ->post(route('viaticos.aprobar'), ['viaticos' => [$idPermitido, $idAjeno]])
            ->assertSessionHasErrors('viaticos');

        $this->assertSame(2, DB::table('tb_viaticos')->where('estatus_aprobacion', 'PENDIENTE_REVISION')->count());
    }

    public function test_alta_usa_identidad_oficial_y_conserva_boleto_binario(): void
    {
        $usuario = $this->crearUsuarioSeccion('PLANTA');
        $contenido = "%PDF-1.4\n".random_bytes(128);

        $this->actingAs($usuario)
            ->post(route('viaticos.guardar'), [
                'numero_empleado' => 100,
                'nombre_persona' => 'Nombre manipulado',
                'tipo_transporte' => 'autobus',
                'costo' => '123.45',
                'origen' => 'Origen',
                'destino' => 'Destino',
                'fecha_salida' => '2026-10-16',
                'transportista' => 'Transportes',
                'referencia_boleto' => 'REF-001',
                'semana_pagar' => '2026-W42',
                'boleto' => UploadedFile::fake()->createWithContent('boleto.pdf', $contenido),
            ])
            ->assertRedirectToRoute('viaticos.index');

        $viatico = DB::table('tb_viaticos')->where('numero_empleado', '100')->first();
        $this->assertNotNull($viatico);
        $this->assertSame('Empleado Planta', $viatico->nombre_persona);
        $this->assertSame($contenido, $viatico->imagen_boleto);
        $this->assertSame(hash('sha256', $contenido), $viatico->hash_archivo);
    }

    public function test_alta_rechaza_empleado_de_otra_seccion(): void
    {
        $usuario = $this->crearUsuarioSeccion('PLANTA');

        $this->actingAs($usuario)
            ->post(route('viaticos.guardar'), [
                'numero_empleado' => 200,
                'tipo_transporte' => 'autobus',
                'costo' => '123.45',
                'origen' => 'Origen',
                'destino' => 'Destino',
                'fecha_salida' => '2026-10-16',
                'transportista' => 'Transportes',
                'boleto' => UploadedFile::fake()->createWithContent('boleto.pdf', "%PDF-1.4\nprueba"),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('tb_viaticos', 0);
    }

    public function test_busqueda_de_empleados_respeta_secciones(): void
    {
        $usuario = $this->crearUsuarioSeccion('PLANTA');

        $this->actingAs($usuario)
            ->get(route('viaticos.crear', ['termino' => 'Empleado']))
            ->assertOk()
            ->assertSee('Empleado Planta')
            ->assertDontSee('Empleado Tajo');
    }

    public function test_aprobador_ve_boton_de_aprobacion_al_revisar_boleto(): void
    {
        $id = $this->crearViatico(100, 'VIA-APROBAR', 'boleto');

        $this->actingAs($this->usuario)
            ->get(route('viaticos.editar', $id))
            ->assertOk()
            ->assertSee('Aprobar viático')
            ->assertSee('action="/viaticos/aprobar"', false);
    }

    public function test_capturista_no_ve_boton_de_aprobacion_al_revisar_boleto(): void
    {
        $perfil = Perfil::query()->create([
            'nombre' => 'Capturistas sin aprobación',
            'slug' => 'capturistas-sin-aprobacion',
            'puede_gestionar_viaticos' => true,
            'puede_aprobar' => false,
        ]);
        $usuario = Usuario::query()->create([
            'name' => 'Usuario Capturista',
            'email' => 'capturista-revision@example.test',
            'password' => 'password',
            'perfil_id' => $perfil->id,
            'activo' => true,
        ]);
        $id = $this->crearViatico(100, 'VIA-CAPTURA', 'boleto');

        $this->actingAs($usuario)
            ->get(route('viaticos.editar', $id))
            ->assertOk()
            ->assertDontSee('Aprobar viático')
            ->assertDontSee('action="/viaticos/aprobar"', false);
    }

    public function test_filtro_de_area_muestra_solo_viaticos_asignados(): void
    {
        $this->crearViatico(100, 'VIA-PLANTA');
        $this->crearViatico(200, 'VIA-TAJO');
        $this->crearViatico(300, 'VIA-TALLER');

        $this->actingAs($this->usuario)
            ->get(route('viaticos.index', ['seccion' => 'PLANTA']))
            ->assertOk()
            ->assertSee('VIA-PLANTA')
            ->assertDontSee('VIA-TAJO')
            ->assertDontSee('VIA-TALLER');

        $this->actingAs($this->usuario)
            ->get(route('viaticos.index', ['seccion' => 'TALLER 1']))
            ->assertOk()
            ->assertSee('VIA-TALLER')
            ->assertDontSee('VIA-PLANTA')
            ->assertDontSee('VIA-TAJO');
    }

    public function test_filtro_de_error_muestra_numeros_sin_empleado(): void
    {
        $this->crearViatico(100, 'VIA-PLANTA');
        $this->crearViatico(999, 'VIA-ERROR');

        $this->actingAs($this->usuario)
            ->get(route('viaticos.index', ['seccion' => '__sin_asignacion__']))
            ->assertOk()
            ->assertSee('VIA-ERROR')
            ->assertSee('Error de asignación')
            ->assertDontSee('VIA-PLANTA');
    }

    public function test_perfil_restringido_no_puede_filtrar_errores_sin_seccion(): void
    {
        $usuario = $this->crearUsuarioSeccion('PLANTA');
        $this->crearViatico(999, 'VIA-ERROR');

        $this->actingAs($usuario)
            ->get(route('viaticos.index', ['seccion' => '__sin_asignacion__']))
            ->assertRedirect()
            ->assertSessionHasErrors('seccion');
    }

    public function test_listado_muestra_fecha_de_registro(): void
    {
        $this->crearViatico(100, 'VIA-FECHA', null, '2026-10-25 14:35:00');

        $this->actingAs($this->usuario)
            ->get(route('viaticos.index'))
            ->assertOk()
            ->assertSee('25/10/2026')
            ->assertSee('14:35');
    }

    public function test_filtros_se_mantienen_hasta_entrar_desde_el_menu_superior(): void
    {
        $pendiente = $this->crearViatico(100, 'VIA-PLANTA-PENDIENTE');
        $aprobado = $this->crearViatico(100, 'VIA-PLANTA-APROBADO');
        DB::table('tb_viaticos')->where('id_viatico', $aprobado)->update(['estatus_aprobacion' => 'APROBADO']);
        $this->crearViatico(200, 'VIA-TAJO-PENDIENTE');

        $this->actingAs($this->usuario)
            ->get(route('viaticos.index', [
                'estatus' => 'PENDIENTE_REVISION',
                'seccion' => 'PLANTA',
            ]))
            ->assertOk()
            ->assertSessionHas('viaticos.filtros', [
                'estatus' => 'PENDIENTE_REVISION',
                'seccion' => 'PLANTA',
            ]);

        $this->get(route('viaticos.editar', $pendiente))->assertOk();
        $this->get(route('viaticos.index'))
            ->assertOk()
            ->assertSee('VIA-PLANTA-PENDIENTE')
            ->assertDontSee('VIA-PLANTA-APROBADO')
            ->assertDontSee('VIA-TAJO-PENDIENTE')
            ->assertSee('value="PLANTA" selected', false)
            ->assertSee('value="PENDIENTE_REVISION" selected', false)
            ->assertSee(route('viaticos.index', ['reiniciar_filtros' => 1]), false);

        $this->get(route('viaticos.index', ['reiniciar_filtros' => 1]))
            ->assertRedirectToRoute('viaticos.index')
            ->assertSessionMissing('viaticos.filtros');

        $this->get(route('viaticos.index'))
            ->assertOk()
            ->assertSee('VIA-PLANTA-PENDIENTE')
            ->assertSee('VIA-PLANTA-APROBADO')
            ->assertSee('VIA-TAJO-PENDIENTE');
    }

    public function test_aprobar_desde_revision_ofrece_continuar_con_el_siguiente_pendiente(): void
    {
        $actual = $this->crearViatico(100, 'VIA-ACTUAL', null, '2026-10-25 11:00:00');
        $sinSemana = $this->crearViatico(100, 'VIA-SIN-SEMANA', null, '2026-10-25 10:30:00');
        $siguiente = $this->crearViatico(100, 'VIA-SIGUIENTE', null, '2026-10-25 10:00:00');
        $this->crearViatico(200, 'VIA-OTRA-AREA', null, '2026-10-25 09:00:00');
        DB::table('tb_viaticos')->where('id_viatico', $sinSemana)->update(['semana_pagar' => null]);

        $this->actingAs($this->usuario)->get(route('viaticos.index', [
            'estatus' => 'PENDIENTE_REVISION',
            'seccion' => 'PLANTA',
        ]));

        $this->get(route('viaticos.editar', $actual))
            ->assertOk()
            ->assertSee('name="desde_revision" value="1"', false);

        $this->post(route('viaticos.aprobar'), [
            'viaticos' => [$actual],
            'desde_revision' => 1,
        ])->assertRedirectToRoute('viaticos.index')
            ->assertSessionHas('continuar_revision', true);

        $this->get(route('viaticos.index'))
            ->assertOk()
            ->assertSee('Revisión terminada')
            ->assertSee('Seguir revisando pendientes')
            ->assertSee('Salir al visor')
            ->assertSee(route('viaticos.editar', $siguiente), false)
            ->assertDontSee('VIA-OTRA-AREA');
    }

    public function test_aprobador_recibe_aviso_si_solo_quedan_pendientes_sin_semana(): void
    {
        $actual = $this->crearViatico(100, 'VIA-ACTUAL', null, '2026-10-25 11:00:00');
        $sinSemana = $this->crearViatico(100, 'VIA-SIN-SEMANA', null, '2026-10-25 10:00:00');
        DB::table('tb_viaticos')->where('id_viatico', $sinSemana)->update(['semana_pagar' => null]);

        $this->actingAs($this->usuario)->get(route('viaticos.index', [
            'estatus' => 'PENDIENTE_REVISION',
            'seccion' => 'PLANTA',
        ]));

        $this->post(route('viaticos.aprobar'), [
            'viaticos' => [$actual],
            'desde_revision' => 1,
        ])->assertRedirectToRoute('viaticos.index');

        $this->get(route('viaticos.index'))
            ->assertOk()
            ->assertSee('Terminaste la revisión, pero hay boletos pendientes sin asignación de semana.')
            ->assertSee('Salir al visor')
            ->assertDontSee('Seguir revisando pendientes');
    }

    private function crearViatico(
        ?int $numeroEmpleado = null,
        string $folio = 'VIA-2026-PRUEBA',
        ?string $boleto = null,
        string $fechaIngreso = '2026-10-25 10:00:00',
    ): int {
        return DB::table('tb_viaticos')->insertGetId([
            'folio_ingreso' => $folio,
            'numero_empleado' => $numeroEmpleado,
            'estatus_aprobacion' => 'PENDIENTE_REVISION',
            'semana_pagar' => '2026-W41',
            'fecha_ingreso' => $fechaIngreso,
            'imagen_mime' => $boleto === null ? null : 'application/pdf',
            'imagen_nombre' => $boleto === null ? null : 'boleto.pdf',
            'imagen_boleto' => $boleto,
        ]);
    }

    private function crearEmpleado(int $clave, string $nombre, string $seccion): void
    {
        DB::table('HCBMS_ALL_OP')->insert([
            'clave' => $clave,
            'Nombre_completo' => $nombre,
            'categoria' => 'Operador',
            'dept_name' => 'Operaciones',
            'seccion' => $seccion,
        ]);
    }

    private function crearUsuarioSeccion(string $seccion): Usuario
    {
        $perfil = Perfil::query()->create([
            'nombre' => "Perfil {$seccion}",
            'slug' => 'perfil-'.strtolower($seccion).'-'.uniqid(),
            'puede_gestionar_viaticos' => true,
            'puede_aprobar' => true,
            'secciones' => [$seccion],
        ]);

        return Usuario::query()->create([
            'name' => "Usuario {$seccion}",
            'email' => strtolower($seccion).'-'.uniqid().'@example.test',
            'password' => 'password',
            'perfil_id' => $perfil->id,
            'activo' => true,
        ]);
    }
}
