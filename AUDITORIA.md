# Auditoría de seguridad — Dream Go

Auditoría completa (seguridad, base de datos, configuración/infraestructura y calidad de
código) realizada el 2026-08-23. Informe completo con todos los hallazgos y evidencia:

**Informe interactivo:** https://claude.ai/code/artifact/db2132a4-cffd-44c4-b8cf-30468a2e8350

Este archivo es el resumen de seguimiento: qué se corrigió, qué falta y cómo continuar.

## Corregido (plan de acción, pasos 1-5)

1. **Exposición de carpetas fuera de `public/`** — `.htaccess` en la raíz del proyecto
   (`Require all denied`) + `public/.htaccess` revierte el bloqueo explícitamente
   (`Require all granted`). El sitio ahora solo es accesible vía `/public/...`. **No se usa
   virtual host de Apache para esto en ningún entorno** (decisión explícita, ver nota abajo).

2. **Credenciales de admin de fábrica** — `database/seeds/seed_demo.sql` ya no trae la
   contraseña filtrada; trae una nueva generada al azar y `debe_cambiar_password = 1`.
   `core/Auth.php` + `core/Middleware/AuthMiddleware.php` fuerzan el cambio en el primer
   login (`/admin/cambiar-password`, nuevo en `AuthController`) antes de dejar usar
   cualquier otra ruta del panel.

3. **Sin límite de intentos en el login** — nueva tabla `intentos_login` +
   `app/Models/IntentoLogin.php`. `Auth::bloqueado()` corta el login tras 5 fallos por
   email o 15 por IP en una ventana móvil de 15 minutos (calculada con `NOW()` de MySQL,
   no en PHP, para evitar desfases de zona horaria entre el servidor web y la BD). Cron
   nuevo `cron/limpiar_intentos_login.php` purga registros de más de 30 días.

4. **Condición de carrera en códigos de descuento** — `DescuentoService::validar()` ahora
   bloquea la fila con `SELECT ... FOR UPDATE` (mismo patrón que `ReservaService` ya usaba
   con `salidas`), dentro de la misma transacción que abre `ReservaService::crear()`.

5. **`clientes.email` sin `UNIQUE`** — restricción agregada en `schema.sql`.
   `Cliente::encontrarOCrear()` captura el conflicto (`SQLSTATE 23000`) si pierde la
   carrera entre el `SELECT` y el `INSERT`, y recupera el id que ganó en vez de fallar.

Todo lo anterior fue probado en vivo (login, cambio de password forzado, bloqueo tras 5
intentos, reserva con código de descuento agotado, inserción concurrente de cliente) contra
una base de datos real antes de darse por cerrado.

### De paso, sin relación con la auditoría

La base de datos local tenía dos tablas/columnas de una migración anterior que nunca se
aplicaron (`bloques_pagina` completa, `roles.permisos_actualizado_en`) — causaban un 500 en
el sitio público y en el login. Ya están en `database/schema.sql`; si vas a importar en un
equipo nuevo con `schema.sql` + `seed_demo.sql` frescos, no necesitas hacer nada extra.

## Aplicar estos cambios en una base de datos YA existente

Si en el otro equipo ya tienes una base de datos poblada (no vas a reimportar
`schema.sql` desde cero), corre esto una sola vez:

```sql
ALTER TABLE usuarios_admin
  ADD COLUMN debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash;

ALTER TABLE roles
  ADD COLUMN permisos_actualizado_en DATETIME NULL AFTER es_sistema;

CREATE TABLE IF NOT EXISTS intentos_login (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  exitoso TINYINT(1) NOT NULL DEFAULT 0,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE INDEX idx_intentos_login_email ON intentos_login(email, creado_en);
CREATE INDEX idx_intentos_login_ip ON intentos_login(ip, creado_en);

ALTER TABLE clientes ADD CONSTRAINT uniq_clientes_email UNIQUE (email);
```

Si tu tabla `clientes` ya tiene emails duplicados, el último `ALTER` fallará — revisa
primero con `SELECT email, COUNT(*) FROM clientes GROUP BY email HAVING COUNT(*) > 1;`
y decide cómo fusionarlos antes de aplicar la restricción.

Nota: la contraseña de admin de **esta** base de datos local ya fue cambiada a mano
durante las pruebas (no es la del seed) — no quedó documentada aquí a propósito. Si vas a
compartir el acceso con alguien más, cámbiala de nuevo desde `/admin/usuarios` o pide que
te la pasen por un canal aparte.

## Pendiente (del informe original, sin tocar todavía)

### Alto
- Sin sistema de migraciones de base de datos versionado (solo `schema.sql` estático).
- `storage/backups/*.sql.gz` reales en disco (mitigado por el punto 1, pero conviene no
  dejarlos ahí indefinidamente).
- Confirmar que `APP_ENV=production` en cualquier `.env` real de producción.

### Medio
- Sin cabeceras de seguridad HTTP (`CSP`, `X-Frame-Options`, `X-Content-Type-Options`, etc.).
- `public/uploads/` sin `.htaccess` anti-ejecución (defensa en profundidad; hoy no es
  explotable porque `ImageUploadService` revalida MIME y reencodifica con GD).
- Cron jobs sin try/catch — un fallo termina en fatal error sin pasar por `cron_log()`.
- Duplicación de CRUD/paginación entre controladores admin
  (`CotizacionAdminController`, `ReservaAdminController`, `PaqueteAdminController`).
- N+1 en `RolAdminController::index` (una query por rol para traer sus permisos).
- Sin manejo centralizado de transacciones en `core/Model.php`.

### Bajo / informativo
- Contraseñas de admin solo exigen 8 caracteres, sin complejidad.
- Cookie `Secure` depende de `$_SERVER['HTTPS']`; puede fallar tras un proxy con TLS
  terminado antes de PHP.
- `core/Exceptions/ValidationException.php` nunca se usa (código muerto).
- `APP_KEY` definido en `.env.example` pero no referenciado en ningún archivo PHP.
- Faltan `CHECK` constraints para precios y fechas (solo existe uno, para cupo de salidas).
- `robots.txt` confirma la existencia de `/admin/` (reconocimiento gratuito).
- `MailerService::enviarReservaPendiente` registra el log con el tipo
  `confirmacion_reserva` en vez de `reserva_pendiente`, rompiendo la deduplicación de
  recordatorios por cron.

Detalle completo de cada hallazgo (archivo:línea, severidad, escenario de explotación) en
el informe interactivo enlazado arriba.
