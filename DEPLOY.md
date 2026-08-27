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
   - En despliegues futuros (la BD de produccion ya existe y no vas a reimportar
     `schema.sql`), corre `php database/migrate.php` desde la raiz del proyecto para aplicar
     los cambios de esquema pendientes. Ver `database/migrations/README.md`.

## 4. Variables de entorno (`.env`)

Copia `.env.example` a `.env` en la raiz del proyecto (junto a `composer.json`, **no** dentro de `public/`) y completa:

```
APP_ENV=production
APP_URL=https://dreamgooperadoraturistica.com

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

MP_ACCESS_TOKEN=<access token de produccion de tu cuenta de Mercado Pago>
MP_WEBHOOK_SECRET=<clave secreta de la notificacion webhook, ver paso 4.1>

GA4_MEASUREMENT_ID=<opcional: G-XXXXXXXXXX de Google Analytics 4>
META_PIXEL_ID=<opcional: ID numerico del pixel de Meta>
```

`GA4_MEASUREMENT_ID` / `META_PIXEL_ID` son opcionales. Al ponerlos, el sitio publico carga
GA4 / Meta Pixel **solo despues** de que el visitante acepte en el banner de cookies. Antes
de activarlos hay que completar los datos entre `[CORCHETES]` de la pagina
`/aviso-de-privacidad` (`app/Views/public/legal/aviso-privacidad.php`) y que la revise un
abogado.

### 4.1. Configurar Mercado Pago (reserva y pago en linea)

1. En [Mercado Pago Developers](https://www.mercadopago.com.mx/developers) → **Tus integraciones**,
   crea o abre tu aplicacion y copia el **Access Token de produccion** a `MP_ACCESS_TOKEN`.
2. En la seccion **Webhooks** de esa misma aplicacion, agrega una notificacion para el evento
   **Pagos** apuntando a `https://tudominio.com/webhooks/mercadopago`. Mercado Pago te da ahi
   una **clave secreta** — copiala a `MP_WEBHOOK_SECRET` (se usa para verificar la firma
   `x-signature` de cada notificacion; sin ella el webhook sigue funcionando pero sin poder
   confirmar que la notificacion viene realmente de Mercado Pago).
3. El porcentaje de anticipo que se cobra al reservar en linea se ajusta desde
   `/admin/configuracion` (campo "Porcentaje de anticipo al reservar en linea"), no en `.env`.
4. Sin `MP_ACCESS_TOKEN` configurado, el formulario publico de reserva sigue funcionando (crea
   la reserva y aparta el cupo igual), solo que sin pago en linea — el cliente queda con la
   reserva `pendiente` para coordinar el pago por otro medio.

## 5. Dependencias de Composer

Si tienes acceso SSH: `composer install --no-dev --optimize-autoloader` dentro de la carpeta del proyecto.
Si no tienes SSH, sube la carpeta `vendor/` ya generada localmente (excluida de git, pero necesaria en el servidor).

Al actualizar una instalacion ya desplegada, vuelve a correr `composer install` (o resube
`vendor/`) para traer dependencias nuevas — p. ej. `dompdf/dompdf`, que genera el comprobante
PDF de las reservas. Tambien corre `php database/migrate.php` para aplicar migraciones nuevas.

## 6. Permisos de carpetas

`storage/logs/`, `storage/backups/`, `public/uploads/paquetes/original/` y `public/uploads/paquetes/thumbs/`
deben ser escribibles por el proceso de PHP (normalmente ya lo son en Hostinger sin configuracion extra).

## 7. SSL / HTTPS

Activa el certificado SSL gratuito (Let's Encrypt) desde hPanel → **SSL**. Una vez activo, confirma que
`APP_URL` en `.env` use `https://` — esto activa las cookies de sesion `Secure` automaticamente
(ver `config/config.php`).

## 8. Cron Jobs

Sigue `cron/README.md` para las 9 tareas programadas. Ajusta las rutas de ejemplo a la ruta real de tu
cuenta de Hostinger (hPanel te la muestra al crear el primer cron job).

## 9. Primer acceso al panel

Usuario admin inicial (creado por el seed): `admin@dreamgooperadoraturistica.com`. La contrasena temporal esta en el comentario junto al `INSERT` de `usuarios_admin` en `database/seeds/seed_demo.sql` — no se documenta aqui para no duplicar el punto de exposicion.

El panel **obliga a cambiarla** en el primer login (`debe_cambiar_password = 1`): no es utilizable como contrasena real hasta reemplazarla desde el formulario que aparece automaticamente.

## 10. Checklist final antes de anunciar el sitio

- [ ] `.env` de produccion con credenciales reales de Hostinger (SMTP y BD), `APP_ENV=production`.
- [ ] Contrasena del usuario admin inicial cambiada.
- [ ] Numero de WhatsApp real configurado en `/admin/configuracion`.
- [ ] Correo del equipo (`email_equipo_reportes`) configurado en `/admin/configuracion`.
- [ ] Contenido placeholder (paquetes/categorias de ejemplo) reemplazado o eliminado por contenido real.
- [ ] Los 9 cron jobs configurados en hPanel.
- [ ] SSL activo y `APP_URL` con `https://`.
- [ ] (Opcional) `GA4_MEASUREMENT_ID` / `META_PIXEL_ID` en `.env` para activar Google
      Analytics 4 y/o Meta Pixel. Antes de activarlos: completar los `[CORCHETES]` de
      `/aviso-de-privacidad` y que un abogado lo revise (al activarlos se usan cookies de
      analitica/publicidad de terceros, previo consentimiento del visitante).
- [ ] Enviar una cotizacion de prueba real y confirmar que llega el correo (valida SMTP en produccion).
- [ ] `MP_ACCESS_TOKEN`/`MP_WEBHOOK_SECRET` configurados y una reserva de prueba pagada de
      principio a fin (checkout de Mercado Pago + webhook confirma la reserva) — ver punto 4.1.
