# Despliegue a Hostinger — Dream Go

Esta guia asume un plan de hosting compartido de Hostinger con acceso a hPanel, PHP 8.1+ y MariaDB/MySQL.

## 1. Subir el proyecto

Sube **todo el proyecto** (no solo `public/`) a una carpeta fuera de `public_html`, por ejemplo:
`/home/USUARIO/dreamgo/` (via Administrador de Archivos de hPanel, FTP o Git).

No subas `.env` real por FTP sin cifrar si no es necesario; puedes crearlo directamente en el servidor en el paso 4.

## 2. Apuntar el dominio a `public/`

En hPanel → **Dominios** → tu dominio → **Administrar** → **Document Root** (o "Raiz del documento"),
cambia la raiz de `public_html` a `dreamgo/public` (la ruta completa segun tu cuenta).

Esto asegura que ninguna URL exponga `/public/` y que `app/`, `core/`, `config/`, `storage/`, `cron/`,
`.env` y `vendor/` **nunca sean accesibles por HTTP**, ya que quedan fuera del document root real.

## 3. Base de datos

1. hPanel → **Bases de datos MySQL** → crea una base de datos y un usuario dedicado (no uses el usuario root de Hostinger para la app).
2. Anota host, nombre de BD, usuario y contrasena.
3. Importa el esquema y los datos iniciales via **phpMyAdmin**:
   - `database/schema.sql` primero.
   - `database/seeds/seed_demo.sql` despues (o solo la parte de `roles`/`permisos` si no quieres el contenido de ejemplo en produccion).

## 4. Variables de entorno (`.env`)

Copia `.env.example` a `.env` en la raiz del proyecto (junto a `composer.json`, **no** dentro de `public/`) y completa:

```
APP_ENV=production
APP_URL=https://dreamgooperadoraturistica.com
APP_KEY=<genera uno nuevo, distinto al de desarrollo>

DB_HOST=<host de Hostinger, normalmente localhost>
DB_NAME=<nombre de la BD>
DB_USER=<usuario dedicado>
DB_PASS=<contrasena>

SMTP_HOST=<host SMTP de Hostinger, ej. smtp.hostinger.com>
SMTP_PORT=587
SMTP_USER=<correo completo, ej. no-reply@dreamgooperadoraturistica.com>
SMTP_PASS=<contrasena del correo>
SMTP_FROM_EMAIL=no-reply@dreamgooperadoraturistica.com
SMTP_FROM_NAME="Dream Go Operadora Turistica"
SMTP_SECURE=tls
```

Genera un `APP_KEY` nuevo con: `php -r "echo bin2hex(random_bytes(32));"`

## 5. Dependencias de Composer

Si tienes acceso SSH: `composer install --no-dev --optimize-autoloader` dentro de la carpeta del proyecto.
Si no tienes SSH, sube la carpeta `vendor/` ya generada localmente (excluida de git, pero necesaria en el servidor).

## 6. Permisos de carpetas

`storage/logs/`, `storage/backups/`, `public/uploads/paquetes/original/` y `public/uploads/paquetes/thumbs/`
deben ser escribibles por el proceso de PHP (normalmente ya lo son en Hostinger sin configuracion extra).

## 7. SSL / HTTPS

Activa el certificado SSL gratuito (Let's Encrypt) desde hPanel → **SSL**. Una vez activo, confirma que
`APP_URL` en `.env` use `https://` — esto activa las cookies de sesion `Secure` automaticamente
(ver `config/config.php`).

## 8. Cron Jobs

Sigue `cron/README.md` para las 5 tareas programadas. Ajusta las rutas de ejemplo a la ruta real de tu
cuenta de Hostinger (hPanel te la muestra al crear el primer cron job).

## 9. Primer acceso al panel

Usuario admin inicial (creado por el seed): `admin@dreamgooperadoraturistica.com` / `DreamGo2026!`

**Cambia esta contrasena inmediatamente** desde `/admin/usuarios` tras el primer login.

## 10. Checklist final antes de anunciar el sitio

- [ ] `.env` de produccion con credenciales reales de Hostinger (SMTP y BD), `APP_ENV=production`.
- [ ] Contrasena del usuario admin inicial cambiada.
- [ ] Numero de WhatsApp real configurado en `/admin/configuracion`.
- [ ] Correo del equipo (`email_equipo_reportes`) configurado en `/admin/configuracion`.
- [ ] Contenido placeholder (paquetes/categorias de ejemplo) reemplazado o eliminado por contenido real.
- [ ] Los 5 cron jobs configurados en hPanel.
- [ ] SSL activo y `APP_URL` con `https://`.
- [ ] Enviar una cotizacion de prueba real y confirmar que llega el correo (valida SMTP en produccion).
