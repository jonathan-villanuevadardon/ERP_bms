<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Programación de tareas (cron)
|--------------------------------------------------------------------------
| Programa el refresco INCREMENTAL de la tabla de hechos de asistencia cada
| 5 horas para absorber la "data viva" del checador (asistencias que llegan
| con retraso o cuando el dispositivo recupera internet).
|
| Nota: en Hostinger, agregar en el CRON del panel:
|   * * * * * php /ruta/a/artisan schedule:run
| Laravel interpreta la expresión cron dentro del scheduler.
*/

Schedule::command('erp:refrescar-hechos')->cron('0 */5 * * *');
