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

    -- Elimina duplicados históricos por si un refresco anterior dejó filas
    -- repetidas (protección idempotente antes de la deduplicación final).
    DELETE dup
    FROM dbo.hechos_asistencia AS dup
    WHERE EXISTS (
        SELECT 1
        FROM dbo.hechos_asistencia AS d
        WHERE d.clave = dup.clave
          AND d.day_f = dup.day_f
          AND d.id < dup.id
    );

    -- Inserta únicamente los días trabajados que aún no existen en la tabla.
    -- Esto respeta la llegada diferida de datos del checador: los registros
    -- nuevos se agregan sin borrar los anteriores.
    INSERT INTO dbo.hechos_asistencia (clave, day_f, created_at, updated_at)
    SELECT
        CAST(a.clave AS BIGINT) AS clave,
        a.day_F,
        GETDATE(),
        GETDATE()
    FROM dbo.VW_Listas_asistencia_unic AS a
    WHERE ISNUMERIC(a.clave) = 1
      AND a.day_F IS NOT NULL
      AND NOT EXISTS (
          SELECT 1
          FROM dbo.hechos_asistencia AS h
          WHERE h.clave = CAST(a.clave AS BIGINT)
            AND h.day_f = a.day_F
      );

    -- Reporta cuántas filas NUEVAS se añadieron en este refresco.
    SELECT @@ROWCOUNT AS filas_insertadas;
END;
GO