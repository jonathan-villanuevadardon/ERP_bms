<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Crea el calendario y la vista diaria que servirá como fuente de nómina.
 *
 * La vista devuelve una fila por empleado y fecha. La aplicación siempre la
 * consulta con un rango máximo de 30 días para mantener tiempos predecibles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendario', function (Blueprint $table) {
            $table->date('fecha')->primary();
        });

        // Rango suficiente para históricos y crecimiento; puede ampliarse con
        // otra migración sin modificar la vista de nómina.
        DB::unprepared(<<<'SQL'
;WITH fechas AS (
    SELECT CAST('2020-01-01' AS DATE) AS fecha
    UNION ALL
    SELECT DATEADD(DAY, 1, fecha)
    FROM fechas
    WHERE fecha < '2040-12-31'
)
INSERT INTO dbo.calendario (fecha)
SELECT fecha FROM fechas
OPTION (MAXRECURSION 0)
SQL);

        $view = <<<'SQL'
CREATE VIEW dbo.VW_ERP_LISTA_ASISTENCIA_NOMINA
AS
WITH empleados_fuente AS (
    SELECT
        TRY_CONVERT(BIGINT, clave) AS clave,
        LTRIM(RTRIM(Nombre_completo)) AS nombre_completo,
        LTRIM(RTRIM(dept_name)) AS area,
        LTRIM(RTRIM(categoria)) AS cargo,
        LTRIM(RTRIM(seccion)) AS seccion,
        ROW_NUMBER() OVER (
            PARTITION BY TRY_CONVERT(BIGINT, clave)
            ORDER BY TRY_CONVERT(DATETIME2, create_time) DESC, LTRIM(RTRIM(Nombre_completo)) DESC
        ) AS numero_fila
    FROM dbo.HCBMS_ALL_OP
    WHERE TRY_CONVERT(BIGINT, clave) IS NOT NULL
),
empleados AS (
    SELECT clave, nombre_completo, area, cargo, seccion
    FROM empleados_fuente
    WHERE numero_fila = 1
)
SELECT
    empleados.clave,
    empleados.nombre_completo,
    empleados.area,
    empleados.cargo,
    empleados.seccion,
    calendario.fecha,
    CAST(CASE WHEN EXISTS (
        SELECT 1 FROM dbo.hechos_asistencia h
        WHERE h.clave = empleados.clave AND h.day_f = calendario.fecha
    ) THEN 1 ELSE 0 END AS BIT) AS tiene_asistencia,
    CAST(CASE WHEN EXISTS (
        SELECT 1 FROM dbo.descansos d
        WHERE d.clave = empleados.clave AND d.deleted_at IS NULL AND d.activo = 1
          AND calendario.fecha BETWEEN d.fecha_inicio AND ISNULL(d.fecha_fin, d.fecha_inicio)
    ) THEN 1 ELSE 0 END AS BIT) AS tiene_descanso,
    CAST(CASE WHEN EXISTS (
        SELECT 1 FROM dbo.vacaciones v
        WHERE v.clave = empleados.clave AND v.deleted_at IS NULL
          AND calendario.fecha BETWEEN v.fecha_inicio AND v.fecha_fin
    ) THEN 1 ELSE 0 END AS BIT) AS tiene_vacaciones,
    CAST(CASE WHEN EXISTS (
        SELECT 1 FROM dbo.permisos p
        WHERE p.clave = empleados.clave AND p.deleted_at IS NULL
          AND calendario.fecha BETWEEN p.fecha_inicio AND ISNULL(p.fecha_fin, p.fecha_inicio)
    ) THEN 1 ELSE 0 END AS BIT) AS tiene_permiso,
    (SELECT TOP 1 p.tipo FROM dbo.permisos p
     WHERE p.clave = empleados.clave AND p.deleted_at IS NULL
       AND calendario.fecha BETWEEN p.fecha_inicio AND ISNULL(p.fecha_fin, p.fecha_inicio)
     ORDER BY p.id DESC) AS tipo_permiso,
    CAST(CASE WHEN EXISTS (
        SELECT 1 FROM dbo.incapacidades i
        WHERE i.clave = empleados.clave AND i.deleted_at IS NULL
          AND calendario.fecha BETWEEN i.fecha_inicio AND i.fecha_fin
    ) THEN 1 ELSE 0 END AS BIT) AS tiene_incapacidad,
    (SELECT TOP 1 i.tipo FROM dbo.incapacidades i
     WHERE i.clave = empleados.clave AND i.deleted_at IS NULL
       AND calendario.fecha BETWEEN i.fecha_inicio AND i.fecha_fin
     ORDER BY i.id DESC) AS tipo_incapacidad,
    (SELECT TOP 1 i.folio FROM dbo.incapacidades i
     WHERE i.clave = empleados.clave AND i.deleted_at IS NULL
       AND calendario.fecha BETWEEN i.fecha_inicio AND i.fecha_fin
     ORDER BY i.id DESC) AS folio_incapacidad,
    CASE
        WHEN EXISTS (SELECT 1 FROM dbo.incapacidades i WHERE i.clave = empleados.clave AND i.deleted_at IS NULL AND calendario.fecha BETWEEN i.fecha_inicio AND i.fecha_fin) THEN 'INCAPACIDAD'
        WHEN EXISTS (SELECT 1 FROM dbo.vacaciones v WHERE v.clave = empleados.clave AND v.deleted_at IS NULL AND calendario.fecha BETWEEN v.fecha_inicio AND v.fecha_fin) THEN 'VACACIONES'
        WHEN EXISTS (SELECT 1 FROM dbo.permisos p WHERE p.clave = empleados.clave AND p.deleted_at IS NULL AND calendario.fecha BETWEEN p.fecha_inicio AND ISNULL(p.fecha_fin, p.fecha_inicio)) THEN 'PERMISO'
        WHEN EXISTS (SELECT 1 FROM dbo.descansos d WHERE d.clave = empleados.clave AND d.deleted_at IS NULL AND d.activo = 1 AND calendario.fecha BETWEEN d.fecha_inicio AND ISNULL(d.fecha_fin, d.fecha_inicio)) THEN 'DESCANSO'
        WHEN EXISTS (SELECT 1 FROM dbo.hechos_asistencia h WHERE h.clave = empleados.clave AND h.day_f = calendario.fecha) THEN 'ASISTENCIA'
        ELSE 'FALTA'
    END AS estatus_nomina
FROM empleados
CROSS JOIN dbo.calendario
SQL;

        DB::unprepared("IF OBJECT_ID('dbo.VW_ERP_LISTA_ASISTENCIA_NOMINA', 'V') IS NOT NULL DROP VIEW dbo.VW_ERP_LISTA_ASISTENCIA_NOMINA");
        DB::unprepared($view);
    }

    public function down(): void
    {
        DB::unprepared("IF OBJECT_ID('dbo.VW_ERP_LISTA_ASISTENCIA_NOMINA', 'V') IS NOT NULL DROP VIEW dbo.VW_ERP_LISTA_ASISTENCIA_NOMINA");
        Schema::dropIfExists('calendario');
    }
};
