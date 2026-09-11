<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Crea el SP que sincroniza incrementalmente los días del checador.
 *
 * La carga conserva el historial y agrega asistencias tardías en la siguiente
 * ejecución. Un bloqueo de aplicación evita dos refrescos simultáneos entre
 * el cron y el botón de emergencia del administrador.
 */
return new class extends Migration
{
    public function up(): void
    {
        $procedure = <<<'SQL'
CREATE PROCEDURE dbo.sp_refrescar_hechos_asistencia
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;

    DECLARE @lock_result INT;
    DECLARE @filas_insertadas INT = 0;

    EXEC @lock_result = sys.sp_getapplock
        @Resource = 'erp_bms_refrescar_hechos_asistencia',
        @LockMode = 'Exclusive',
        @LockOwner = 'Session',
        @LockTimeout = 0;

    -- Otro proceso ya está sincronizando; no se duplica el trabajo.
    IF @lock_result < 0
    BEGIN
        SELECT @filas_insertadas AS filas_insertadas;
        RETURN;
    END;

    BEGIN TRY
        ;WITH fuente AS (
            SELECT DISTINCT
                TRY_CONVERT(BIGINT, clave) AS clave,
                day_F AS day_f
            FROM dbo.VW_Listas_asistencia_unic
            WHERE TRY_CONVERT(BIGINT, clave) IS NOT NULL
              AND day_F IS NOT NULL
        )
        INSERT INTO dbo.hechos_asistencia (clave, day_f, created_at, updated_at)
        SELECT
            fuente.clave,
            fuente.day_f,
            GETDATE(),
            GETDATE()
        FROM fuente
        WHERE NOT EXISTS (
            SELECT 1
            FROM dbo.hechos_asistencia AS hechos
            WHERE hechos.clave = fuente.clave
              AND hechos.day_f = fuente.day_f
        );

        SET @filas_insertadas = @@ROWCOUNT;

        EXEC sys.sp_releaseapplock
            @Resource = 'erp_bms_refrescar_hechos_asistencia',
            @LockOwner = 'Session';

        SELECT @filas_insertadas AS filas_insertadas;
    END TRY
    BEGIN CATCH
        EXEC sys.sp_releaseapplock
            @Resource = 'erp_bms_refrescar_hechos_asistencia',
            @LockOwner = 'Session';
        THROW;
    END CATCH;
END
SQL;

        // SQL Server 2016 RTM no admite CREATE OR ALTER. DROP y CREATE se
        // envían como lotes separados para que CREATE sea la primera sentencia.
        DB::unprepared(<<<'SQL'
IF OBJECT_ID('dbo.sp_refrescar_hechos_asistencia', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_refrescar_hechos_asistencia
SQL);
        DB::unprepared($procedure);
    }

    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS dbo.sp_refrescar_hechos_asistencia');
    }
};
