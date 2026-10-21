@echo off
setlocal EnableExtensions

set "TASK_NAME=ERP BMS Docker Autostart"
set "PROJECT_NAME=erpbms"
set "PROJECT_DIR=%~dp0"
set "COMPOSE_FILE=%~dp0docker-compose.local.yml"
set "DOCKER_EXE=%ProgramFiles%\Docker\Docker\resources\bin\docker.exe"
set "LOG_DIR=%LOCALAPPDATA%\ERP-BMS"
set "LOG_FILE=%LOG_DIR%\docker-autostart.log"

if /i "%~1"=="start" goto start
if /i "%~1"=="install" goto install
if /i "%~1"=="uninstall" goto uninstall
if /i "%~1"=="status" goto status
if not "%~1"=="" goto usage

:install
fltmc >nul 2>&1
if errorlevel 1 (
    echo Este instalador necesita permisos de administrador.
    echo Haz clic derecho sobre este archivo y selecciona "Ejecutar como administrador".
    pause
    exit /b 1
)

if not exist "%COMPOSE_FILE%" (
    echo No se encontro %COMPOSE_FILE%
    pause
    exit /b 1
)

if not exist "%DOCKER_EXE%" (
    echo No se encontro Docker CLI en %DOCKER_EXE%
    pause
    exit /b 1
)

powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ^
    "$ErrorActionPreference='Stop';" ^
    "$taskName='%TASK_NAME%';" ^
    "$batPath='%~f0';" ^
    "$user=[Security.Principal.WindowsIdentity]::GetCurrent().Name;" ^
    "$startupCommand='& '+[char]39+$batPath+[char]39+' start';" ^
    "$encodedCommand=[Convert]::ToBase64String([Text.Encoding]::Unicode.GetBytes($startupCommand));" ^
    "$action=New-ScheduledTaskAction -Execute (Join-Path $PSHOME 'powershell.exe') -Argument ('-NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -EncodedCommand '+$encodedCommand);" ^
    "$trigger=New-ScheduledTaskTrigger -AtLogOn -User $user;" ^
    "$trigger.Delay='PT30S';" ^
    "$principal=New-ScheduledTaskPrincipal -UserId $user -LogonType Interactive -RunLevel Highest;" ^
    "$settings=New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -RestartCount 3 -RestartInterval (New-TimeSpan -Minutes 1) -ExecutionTimeLimit (New-TimeSpan -Minutes 10);" ^
    "Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Principal $principal -Settings $settings -Description 'Inicia ERP BMS en Docker al iniciar sesion.' -Force | Out-Null;"

if errorlevel 1 (
    echo No se pudo registrar la tarea de inicio.
    pause
    exit /b 1
)

echo Tarea "%TASK_NAME%" instalada correctamente.
echo ERP BMS se iniciara automaticamente al entrar a Windows.
schtasks /Run /TN "%TASK_NAME%" >nul 2>&1
echo Inicio solicitado. La aplicacion estara disponible en http://localhost:8080
if /i "%~1"=="install" exit /b 0
pause
exit /b 0

:start
if not exist "%LOG_DIR%" mkdir "%LOG_DIR%" >nul 2>&1
call :log Iniciando ERP BMS.

if not exist "%COMPOSE_FILE%" (
    call :log ERROR: no se encontro %COMPOSE_FILE%
    exit /b 1
)

if not exist "%DOCKER_EXE%" (
    call :log ERROR: no se encontro Docker CLI en %DOCKER_EXE%
    exit /b 1
)

"%DOCKER_EXE%" desktop status >nul 2>&1
if errorlevel 1 (
    call :log Docker no esta disponible; abriendo Docker Desktop.
    "%DOCKER_EXE%" desktop start --detach --timeout 120 >>"%LOG_FILE%" 2>&1
)

set /a WAIT_COUNT=0
:wait_docker
"%DOCKER_EXE%" desktop status >nul 2>&1
if not errorlevel 1 goto docker_ready
set /a WAIT_COUNT+=1
if %WAIT_COUNT% GEQ 60 (
    call :log ERROR: Docker no estuvo disponible despues de 5 minutos.
    exit /b 1
)
%SystemRoot%\System32\ping.exe 127.0.0.1 -n 6 >nul
goto wait_docker

:docker_ready
call :log Docker disponible; levantando el proyecto %PROJECT_NAME%.
"%DOCKER_EXE%" compose -p "%PROJECT_NAME%" -f "%COMPOSE_FILE%" up -d >>"%LOG_FILE%" 2>&1
if errorlevel 1 (
    call :log ERROR: Docker Compose no pudo levantar ERP BMS.
    exit /b 1
)

call :log ERP BMS disponible en http://localhost:8080
exit /b 0

:uninstall
fltmc >nul 2>&1
if errorlevel 1 (
    echo La desinstalacion necesita permisos de administrador.
    echo Ejecuta este archivo como administrador con el argumento uninstall.
    pause
    exit /b 1
)

schtasks /Delete /TN "%TASK_NAME%" /F >nul 2>&1
if errorlevel 1 (
    echo La tarea "%TASK_NAME%" no existe o no pudo eliminarse.
    exit /b 1
)
echo Tarea "%TASK_NAME%" eliminada.
exit /b 0

:status
schtasks /Query /TN "%TASK_NAME%" /FO LIST /V
exit /b %errorlevel%

:usage
echo Uso: %~nx0 [start^|status^|uninstall]
exit /b 1

:log
>>"%LOG_FILE%" echo [%date% %time%] %*
exit /b 0
