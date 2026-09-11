<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Evita truncamientos cuando el catálogo dinámico incorpore nombres mayores.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX roles_descanso_seccion_index ON dbo.roles_descanso');

        foreach ([
            'roles_descanso',
            'descansos',
            'descansos_pendientes',
            'vacaciones',
            'vacaciones_pendientes',
            'permisos',
            'permisos_pendientes',
        ] as $tabla) {
            DB::statement("ALTER TABLE dbo.{$tabla} ALTER COLUMN seccion NVARCHAR(100) NULL");
        }

        DB::statement('CREATE INDEX roles_descanso_seccion_index ON dbo.roles_descanso (seccion)');
    }

    public function down(): void
    {
        // No se reduce para evitar truncar secciones creadas después del cambio.
    }
};
