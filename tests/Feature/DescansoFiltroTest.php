<?php

namespace Tests\Feature;

use App\Models\Descanso;
use App\Models\Perfil;
use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DescansoFiltroTest extends TestCase
{
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
            $table->boolean('puede_gestionar_descansos')->default(false);
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
        Schema::create('descansos', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('clave');
            $table->string('nombre_completo')->nullable();
            $table->string('area')->nullable();
            $table->string('seccion')->nullable();
            $table->string('tipo');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->string('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedBigInteger('pendiente_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('descansos');
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('perfiles');

        parent::tearDown();
    }

    public function test_filtra_descansos_por_numero_de_empleado_y_conserva_el_filtro(): void
    {
        $perfil = Perfil::query()->create([
            'nombre' => 'Recursos Humanos',
            'slug' => 'rh',
            'puede_gestionar_descansos' => true,
        ]);
        $usuario = Usuario::query()->create([
            'name' => 'Usuario RH',
            'email' => 'rh@example.test',
            'password' => 'password',
            'perfil_id' => $perfil->id,
            'activo' => true,
        ]);
        Descanso::query()->create([
            'clave' => 1001,
            'nombre_completo' => 'Empleado Encontrado',
            'area' => 'Operaciones',
            'seccion' => 'PLANTA',
            'tipo' => 'por_periodo',
            'fecha_inicio' => '2026-11-10',
            'fecha_fin' => '2026-11-11',
        ]);
        Descanso::query()->create([
            'clave' => 2002,
            'nombre_completo' => 'Empleado Excluido',
            'area' => 'Mina',
            'seccion' => 'TAJO',
            'tipo' => 'por_periodo',
            'fecha_inicio' => '2026-11-12',
            'fecha_fin' => '2026-11-13',
        ]);

        $this->actingAs($usuario)
            ->get(route('descansos.index', ['clave' => 1001]))
            ->assertOk()
            ->assertSee('Empleado Encontrado')
            ->assertDontSee('Empleado Excluido')
            ->assertSee('value="1001"', false);
    }
}
