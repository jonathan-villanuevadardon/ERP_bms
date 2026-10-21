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

    private function crearViatico(?int $numeroEmpleado = null, string $folio = 'VIA-2026-PRUEBA', ?string $boleto = null): int
    {
        return DB::table('tb_viaticos')->insertGetId([
            'folio_ingreso' => $folio,
            'numero_empleado' => $numeroEmpleado,
            'estatus_aprobacion' => 'PENDIENTE_REVISION',
            'semana_pagar' => '2026-W41',
            'fecha_ingreso' => now(),
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
