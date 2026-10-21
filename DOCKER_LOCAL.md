# Revisión local con Docker

Este entorno sirve Laravel en `http://localhost:8080`. Usa el controlador nativo `pdo_sqlsrv` con ODBC de Microsoft, igual que producción, y se conecta a una instancia SQL Server externa. No incluye una base de datos ni ejecuta migraciones automáticamente.

`host.docker.internal` solo funciona si SQL Server está instalado en Windows y escucha en el puerto TCP `1433`. Si la base está en otro equipo o servidor, coloca en `DB_HOST` su nombre DNS o dirección IP.

## Primera ejecución

1. Edita `.env.docker` y configura `DB_HOST`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD` para una base de pruebas. No uses producción.
2. Construye y levanta el contenedor:

```powershell
docker compose -f docker-compose.local.yml up --build -d
```

3. Si la base es nueva, aplica las migraciones manualmente:

```powershell
docker compose -f docker-compose.local.yml exec app php artisan migrate --force
```

La migración `2024_01_01_000016_add_viaticos_permission_to_perfiles` agrega la columna `puede_gestionar_viaticos`. La migración `2024_01_01_000017_add_unique_folios_to_incapacidades` evita folios IMSS duplicados. Revisa ambas antes de aplicarlas en un entorno existente.

4. Para crear el usuario administrador local:

```powershell
docker compose -f docker-compose.local.yml exec app php artisan db:seed --class=Database\Seeders\PerfilAdminSeeder --force
```

5. Abre `http://localhost:8080/login`.

Las credenciales iniciales se toman de `ADMIN_EMAIL` y `ADMIN_PASSWORD` en `.env.docker`.

## Error de conexión SQL Server

Si aparece `Unable to connect: Adaptive Server is unavailable`, revisa lo siguiente:

- SQL Server está iniciado.
- TCP/IP está habilitado para la instancia.
- El puerto configurado en `DB_PORT` está abierto en el firewall.
- `DB_HOST` apunta al equipo correcto.

Después de cambiar `.env.docker`, recrea el contenedor:

```powershell
docker compose -f docker-compose.local.yml up -d --force-recreate
docker compose -f docker-compose.local.yml exec app php artisan config:clear
```

No ejecutes `migrate` contra producción. Para revisar con datos reales, usa una copia o base de pruebas que contenga `usuarios`, `perfiles`, `tb_viaticos` y las vistas operativas necesarias.

## Comandos útiles

```powershell
docker compose -f docker-compose.local.yml logs -f app
docker compose -f docker-compose.local.yml exec app php artisan route:list --path=viaticos
docker compose -f docker-compose.local.yml exec app vendor/bin/phpunit
docker compose -f docker-compose.local.yml down
```

Si modificas código, vuelve a ejecutar `up --build -d` porque la imagen contiene una copia del proyecto. La tabla `tb_viaticos` debe existir en la base configurada y sus cambios de esquema deben administrarse mediante el proceso autorizado para SQL Server.
