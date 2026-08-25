# Tareas programadas (cron) — Dream Go

Los 7 scripts de esta carpeta se ejecutan por PHP CLI y son independientes del servidor web.
Todos registran su actividad en `storage/logs/cron.log`.

## Prueba manual en local (XAMPP / Windows)

Desde la raiz del proyecto:

```
php cron/liberar_reservas_expiradas.php
php cron/desactivar_ofertas_vencidas.php
php cron/recordatorio_viaje.php
php cron/solicitar_resena.php
php cron/reporte_periodico.php --periodo=diario
php cron/backup_bd.php
php cron/limpiar_intentos_login.php
```

## Configuracion en Hostinger (hPanel > Avanzado > Cron Jobs)

Sustituye `USUARIO` y el dominio por los datos reales de la cuenta de Hostinger.
La ruta base normalmente es `/home/USUARIO/domains/dreamgooperadoraturistica.com/`.

```
*/15 * * * * /usr/bin/php /home/USUARIO/domains/dreamgooperadoraturistica.com/cron/liberar_reservas_expiradas.php >> /home/USUARIO/domains/dreamgooperadoraturistica.com/storage/logs/cron-output.log 2>&1

0 3 * * * /usr/bin/php /home/USUARIO/domains/dreamgooperadoraturistica.com/cron/desactivar_ofertas_vencidas.php >> /home/USUARIO/domains/dreamgooperadoraturistica.com/storage/logs/cron-output.log 2>&1

0 8 * * * /usr/bin/php /home/USUARIO/domains/dreamgooperadoraturistica.com/cron/recordatorio_viaje.php >> /home/USUARIO/domains/dreamgooperadoraturistica.com/storage/logs/cron-output.log 2>&1

0 9 * * * /usr/bin/php /home/USUARIO/domains/dreamgooperadoraturistica.com/cron/solicitar_resena.php >> /home/USUARIO/domains/dreamgooperadoraturistica.com/storage/logs/cron-output.log 2>&1

0 7 * * * /usr/bin/php /home/USUARIO/domains/dreamgooperadoraturistica.com/cron/reporte_periodico.php --periodo=diario >> /home/USUARIO/domains/dreamgooperadoraturistica.com/storage/logs/cron-output.log 2>&1
0 7 * * 1 /usr/bin/php /home/USUARIO/domains/dreamgooperadoraturistica.com/cron/reporte_periodico.php --periodo=semanal >> /home/USUARIO/domains/dreamgooperadoraturistica.com/storage/logs/cron-output.log 2>&1

30 2 * * * /usr/bin/php /home/USUARIO/domains/dreamgooperadoraturistica.com/cron/backup_bd.php >> /home/USUARIO/domains/dreamgooperadoraturistica.com/storage/logs/cron-output.log 2>&1

0 4 * * * /usr/bin/php /home/USUARIO/domains/dreamgooperadoraturistica.com/cron/limpiar_intentos_login.php >> /home/USUARIO/domains/dreamgooperadoraturistica.com/storage/logs/cron-output.log 2>&1
```

## Que hace cada script

| Script | Frecuencia sugerida | Que hace |
|---|---|---|
| `liberar_reservas_expiradas.php` | cada 15 min | Libera el cupo de reservas `pendiente` cuyo `expira_en` ya paso, y las marca `expirada`. |
| `desactivar_ofertas_vencidas.php` | diario (madrugada) | Desactiva codigos de descuento cuya `fecha_fin` ya paso. |
| `recordatorio_viaje.php` | diario | Envia un correo recordatorio a clientes con reserva `confirmada` cuya salida es en N dias (configurable en `/admin/configuracion`). Evita duplicados revisando `log_correos_enviados`. |
| `solicitar_resena.php` | diario | Envia un correo pidiendo una resena a clientes con reserva `confirmada` cuyo viaje termino hace N dias (configurable en `/admin/configuracion`). El link lleva a `/resena/{codigo}`. Evita duplicados revisando `log_correos_enviados` y la tabla `resenas`. |
| `reporte_periodico.php` | diario y/o semanal | Envia un resumen de cotizaciones y reservas nuevas al correo del equipo. Usa `--periodo=diario` o `--periodo=semanal`. |
| `backup_bd.php` | diario (madrugada) | Genera un dump comprimido (`.sql.gz`) de la base de datos en `storage/backups/`, con `mysqldump` si esta disponible o un respaldo 100% PHP como respaldo. Conserva los ultimos 14 dias. |
| `limpiar_intentos_login.php` | diario | Purga de `intentos_login` (rate limiting del login admin, ver `core/Auth.php`) los registros con mas de 30 dias. |

`storage/` nunca es accesible por URL (queda fuera de `public/`), asi que los backups y logs no estan expuestos publicamente.
