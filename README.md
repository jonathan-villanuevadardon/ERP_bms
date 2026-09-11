# ERP BMS — Sistema de Gestión de Descansos, Vacaciones y Permisos

Aplicación web en **Laravel 11 (MVC)** para alojarse en **Hostinger**, conectada a
**SQL Server** (Microsoft) en `nominarc4.duckdns.org` (base `ERPBMS`).

## Descripción general

ERP modular para el área de Recursos Humanos de BMS. Permite:

1. **Asignación de Rol (descanso)** — asigna a cada empleado la regla
   "N días trabajados × M días de descanso", de manera **masiva (Excel)** o
   **individual**. Incluye plantilla descargable.
2. **Descansos** — periodos de descanso **fijos** (con aprobación del Admin) o
   **por periodo** (directo). Los fijos pasan por un buzón de aprobación antes
   de registrarse.
3. **Vacaciones** — periodos de vacaciones que pasan por **aprobación** antes de
   registrarse. Modificables solo antes de aprobarse.
4. **Permisos** — con o sin goce de sueldo. Los **con goce** requieren aprobación.
5. **Visor de cumplimiento** — consulta si cada empleado cumple su rol de
   descanso (días trabajados vs. descanso) a partir de una **tabla de hechos**
   alimentada por un procedimiento almacenado.

### Perfiles y permisos

- El perfil **ADMIN** tiene acceso total: todos los módulos, las aprobaciones y
  la gestión de **perfiles** (altas/bajas/modificaciones) y **usuarios**.
- Otros perfiles pueden restringirse a **secciones** específicas (`PLANTA`,
  `TAJO`, `TALLER 1`) y a ciertos módulos.
- Los módulos visibles en el menú dependen de los permisos del perfil.

## Estructura del proyecto (MVC)

```
app/
├── Console/Commands/       # Comandos artisan (refrescar hechos)
├── Exports/                # Plantilla Excel de roles
├── Http/
│   ├── Controllers/        # Controladores (Rol, Descanso, Vacacion, Permiso, Visor, Perfil, Usuario, Auth)
│   └── Middleware/         # VerificarPerfil, RequiereAdmin
├── Imports/                # Importación masiva Excel
├── Models/                 # Modelos Eloquent (dominio + vistas)
├── Providers/
└── Services/               # EmpleadoService, VisorService
bootstrap/                  # Arranque de Laravel
config/                     # Configuración (database.php para SQL Server)
database/
├── migrations/             # Esquema del dominio
├── seeders/                # Perfil/admin inicial
└── sql/                    # SP sp_refrescar_hechos_asistencia.sql
resources/views/            # Vistas Blade (Bootstrap 5)
routes/web.php              # Rutas del sistema
```

## Requisitos

- PHP **8.2+** con extensiones **`pdo_sqlsrv`** y **`sqlsrv`** habilitadas
  (Hostinger debe tenerlas; verificar en `phpinfo`). En su defecto, usar
  `pdo_sqlsrv`.
- Composer.
- Acceso de red al SQL Server en el puerto **1433**.

## Instalación local / despliegue

```bash
# 1) Clonar el repositorio
git clone https://github.com/jonathan-villanuevadardon/ERP_bms.git
cd ERP_bms

# 2) Instalar dependencias
composer install --no-dev --optimize-autoloader

# 3) Crear el archivo .env (FUERA del repositorio) con las credenciales reales
cp .env.example .env
#   - editar DB_USERNAME, DB_PASSWORD, APP_KEY, ADMIN_EMAIL, ADMIN_PASSWORD

# 4) Generar la clave de la aplicación
php artisan key:generate

# 5) Ejecutar las migraciones (crea tablas del dominio + Laravel)
php artisan migrate

# 6) Sembrar el perfil y usuario administrador inicial
php artisan db:seed

# 7) Crear el enlace simbólico de storage
php artisan storage:link
```

## Configuración de la base de datos SQL Server

1. Asegurar que la BD `ERPBMS` existe y que el usuario tiene permisos de
   **crear tablas** (las migraciones crean el esquema del dominio).
2. Ejecutar el procedimiento almacenado (una sola vez) para crearlo:

   ```sql
   -- Ejecutar el contenido de database/sql/sp_refrescar_hechos_asistencia.sql
   ```

   El SP hace una carga **incremental**: no trunca, solo inserta los días
   trabajados **nuevos** que aparezcan en `VW_Listas_asistencia_unic`. Así se
   respeta la "data viva" del checador (asistencias que se cargan con retraso
   o cuando el dispositivo recupera internet).

3. Programar el refresco periódico en el CRON de Hostinger:

   ```bash
   * * * * * php /ruta/a/artisan schedule:run
   ```

   El scheduler ejecuta `erp:refrescar-hechos` **cada 5 horas**
   (`->everyFiveHours()`). También existe el botón "Actualizar datos" en el
   visor para refrescar bajo demanda.

## Seguridad / variables de entorno

Las credenciales **no** se versionan. El archivo `.env` reside **fuera** del
repositorio (ver `.gitignore`). Variables sensibles:

| Variable          | Descripción                        |
|-------------------|------------------------------------|
| `DB_USERNAME`     | Usuario SQL Server (`Erp_admin`)   |
| `DB_PASSWORD`     | Contraseña SQL Server              |
| `ADMIN_EMAIL`     | Email del usuario administrador    |
| `ADMIN_PASSWORD`  | Contraseña inicial del admin       |

> **Importante:** cambiar la contraseña del admin inicial tras el primer inicio.

## Vista de datos externa (solo lectura)

La aplicación lee de la vista **`HCBMS_ALL_OP`** (maestro de empleados) y de
**`VW_Listas_asistencia_unic`** (días trabajados) sin modificarlas. Los datos
propios del ERP se guardan en tablas nuevas del dominio:
`roles_descanso`, `descansos`, `descansos_pendientes`, `vacaciones`,
`vacaciones_pendientes`, `permisos`, `permisos_pendientes`, `hechos_asistencia`
y tablas del framework.

## Módulos en detalle

- **Asignación de rol**: `GET /roles` — alta individual, edición, eliminación,
  carga masiva (`.xlsx`) y descarga de plantilla.
- **Descansos**: `GET /descansos` — alta (fijo→aprobación, por periodo→directo).
  Aprobaciones en `GET /descansos/pendientes/lista`.
- **Vacaciones**: `GET /vacaciones` — envío a aprobación y buzón en
  `GET /vacaciones/pendientes/lista`.
- **Permisos**: `GET /permisos` — sin goce (directo) y con goce (aprobación).
- **Visor**: `GET /visor` — cumplimiento de rol + refresco de hechos.

## Créditos

Desarrollado como ERP modular para BMS RH sobre Laravel 11 + SQL Server.