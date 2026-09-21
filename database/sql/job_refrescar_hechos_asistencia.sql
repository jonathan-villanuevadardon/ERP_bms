/* =====================================================================
   SQL Server Agent Job: refresco incremental de asistencia

   Ejecutar conectado a SQL Server con permisos de SQL Server Agent.
   El job reemplaza el cron de Laravel/PHP y corre cada 5 horas.
   Si ya existe, se elimina y se recrea con la misma configuracion.
   ===================================================================== */

USE msdb;
GO

IF EXISTS (
    SELECT 1
    FROM msdb.dbo.sysjobs
    WHERE name = N'ERP BMS - Refrescar hechos asistencia'
)
BEGIN
    EXEC msdb.dbo.sp_delete_job
        @job_name = N'ERP BMS - Refrescar hechos asistencia',
        @delete_unused_schedule = 1;
END;
GO

DECLARE @job_id UNIQUEIDENTIFIER;

EXEC msdb.dbo.sp_add_job
    @job_name = N'ERP BMS - Refrescar hechos asistencia',
    @enabled = 1,
    @description = N'Refresco incremental de hechos_asistencia desde VW_Listas_asistencia_unic.',
    @owner_login_name = SUSER_SNAME(),
    @job_id = @job_id OUTPUT;
GO

EXEC msdb.dbo.sp_add_jobstep
    @job_name = N'ERP BMS - Refrescar hechos asistencia',
    @step_name = N'Ejecutar procedimiento incremental',
    @subsystem = N'TSQL',
    @database_name = N'ERPBMS',
    @command = N'EXEC dbo.sp_refrescar_hechos_asistencia;',
    @on_success_action = 1,
    @on_fail_action = 2;
GO

EXEC msdb.dbo.sp_add_schedule
    @schedule_name = N'ERP BMS - Cada 5 horas',
    @enabled = 1,
    @freq_type = 4,
    @freq_interval = 1,
    @freq_subday_type = 8,
    @freq_subday_interval = 5,
    @active_start_time = 0;
GO

EXEC msdb.dbo.sp_attach_schedule
    @job_name = N'ERP BMS - Refrescar hechos asistencia',
    @schedule_name = N'ERP BMS - Cada 5 horas';
GO

EXEC msdb.dbo.sp_add_jobserver
    @job_name = N'ERP BMS - Refrescar hechos asistencia';
GO

SELECT
    jobs.name AS job_name,
    jobs.enabled,
    schedules.name AS schedule_name,
    schedules.enabled AS schedule_enabled
FROM msdb.dbo.sysjobs AS jobs
LEFT JOIN msdb.dbo.sysjobschedules AS job_schedules
    ON job_schedules.job_id = jobs.job_id
LEFT JOIN msdb.dbo.sysschedules AS schedules
    ON schedules.schedule_id = job_schedules.schedule_id
WHERE jobs.name = N'ERP BMS - Refrescar hechos asistencia';
