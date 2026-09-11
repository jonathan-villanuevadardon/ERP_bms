<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder "DatabaseSeeder".
 *
 * Orquestra la siembra de datos inicial del sistema. Ejecuta el seeder que
 * crea el perfil y usuario administrador.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Ejecuta los seeders del sistema.
     */
    public function run(): void
    {
        $this->call([
            PerfilAdminSeeder::class,
        ]);
    }
}