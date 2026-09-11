<?php

namespace Database\Seeders;

use App\Models\Perfil;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder "PerfilAdminSeeder".
 *
 * Crea el perfil administrador "ADMIN" (con acceso total) y un usuario
 * administrador inicial para poder ingresar al sistema. Las credenciales se
 * leen de variables de entorno (ADMIN_EMAIL / ADMIN_PASSWORD) para no
 * exponerlas en el repositorio.
 */
class PerfilAdminSeeder extends Seeder
{
    /**
     * Ejecuta la siembra de datos.
     */
    public function run(): void
    {
        // 1) Crea (o recupera) el perfil ADMIN con todos los permisos.
        $admin = Perfil::firstOrCreate(
            ['slug' => 'admin'],
            [
                'nombre'                  => 'Administrador',
                'descripcion'             => 'Perfil administrador con acceso total y aprobación.',
                'puede_asignar_rol'       => true,
                'puede_gestionar_descansos' => true,
                'puede_gestionar_vacaciones' => true,
                'puede_gestionar_permisos' => true,
                'puede_aprobar'           => true,
                'es_admin'                => true,
                'puede_ver_visor'         => true,
                'secciones'               => null,
            ]
        );

        // 2) Crea un usuario administrador inicial si no existe.
        $email = env('ADMIN_EMAIL', 'admin@erpbms.local');
        $password = env('ADMIN_PASSWORD', 'Cambiar123!');

        Usuario::firstOrCreate(
            ['email' => $email],
            [
                'name'     => 'Administrador',
                'password' => Hash::make($password),
                'perfil_id' => $admin->id,
                'activo'   => true,
            ]
        );
    }
}