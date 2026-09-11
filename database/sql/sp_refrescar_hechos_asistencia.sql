/* =====================================================================
   Script SQL: materialización INCREMENTAL de la tabla de hechos.

   Propósito:
     Convertir la vista VW_Listas_asistencia_unic (que proviene de un
     checador con carga DIFERIDA y no siempre en línea) en una tabla de
     hechos "hechos_asistencia" con una fila por (empleado, día trabajado).

   Estrategia de "data viva":
     - NO trunca: conserva todo el historial ya procesado.
     - Inserta SOLO los días que aún NO existen en la tabla (incremental).
       Si el checador sube datos con retraso (sin internet), en el siguiente
       refresco simplemente aparecen como filas nuevas.
     - Es seguro reejecutar cuantas veces se quiera (idempotente por la
       cláusula NOT EXISTS y el índice único/imputado de deduplicación).

   Convención: la vista usa `clave` como nvarchar; aquí se normaliza a
   entero (bigint) descartando valores no numéricos.
   ===================================================================== */

IF OBJECT_ID('dbo.sp_refrescar_hechos_asistencia', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_refrescar_hechos_asistencia;
GO

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
        SELECT fuente.clave, fuente.day_f, GETDATE(), GETDATE()
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
END;
GO
