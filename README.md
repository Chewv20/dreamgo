# Dream Go Operadora Turistica

Landing page y panel administrativo de Dream Go, agencia de viajes. PHP vanilla con
patron MVC propio, MariaDB, JS/CSS sin frameworks pesados, PWA instalable.

## Requisitos locales

- XAMPP (Apache + PHP 8.1+ + MariaDB)
- Composer
- Un Virtual Host apuntando a `public/` (ver abajo)

## Puesta en marcha local

1. **Virtual Host**: agrega el contenido de `vhost-dreamgo.local.conf` a
   `C:\xampp\apache\conf\extra\httpd-vhosts.conf` y reinicia Apache desde el XAMPP Control Panel.
2. **Archivo hosts**: agrega `127.0.0.1 dreamgo.local` a `C:\Windows\System32\drivers\etc\hosts`
   (requiere abrir el editor como administrador).
3. **Dependencias**: `composer install`
4. **Variables de entorno**: copia `.env.example` a `.env` y ajusta `DB_USER`/`DB_PASS` con las
   credenciales de tu MariaDB local.
5. **Base de datos**:
   ```
   mysql -u root -p -e "CREATE DATABASE dreamgo CHARACTER SET utf8mb4"
   mysql -u root -p dreamgo < database/schema.sql
   mysql -u root -p dreamgo < database/seeds/seed_demo.sql
   ```
6. Abre `http://dreamgo.local/` — deberias ver el sitio con el contenido de ejemplo.
7. Panel admin: `http://dreamgo.local/admin/login` — `admin@dreamgooperadoraturistica.com` / `DreamGo2026!`
   (cambiala en tu primer login desde `/admin/usuarios`).

## Estructura del proyecto

```
app/            Controladores, Modelos, Vistas, Services y Helpers (logica de negocio)
core/           "Framework" propio: Router, Database, Auth, Controller/Model base
config/         Bootstrap, rutas, credenciales de BD
cron/           5 tareas programadas (ver cron/README.md)
database/       Esquema SQL y datos iniciales
storage/        Logs y backups (nunca accesible por URL)
public/         Document root real (front controller, assets, uploads)
```

## Documentacion relacionada

- `cron/README.md` — que hace cada tarea programada y como configurarla en Hostinger.
- `DEPLOY.md` — guia paso a paso para desplegar a Hostinger.

## Principios seguidos en el codigo

- Sin hardcodeo: parametros de negocio en `configuracion_sitio` (BD) o `.env`.
- Responsabilidad unica: `Services/` concentra logica reutilizable (correo, WhatsApp, cupos, descuentos, imagenes, sitemap).
- RBAC granular: roles y permisos 100% administrables desde `/admin/roles`, sin roles fijos en codigo.
- `app/`, `core/`, `config/`, `cron/`, `storage/`, `vendor/`, `.env` fuera del document root.
- PDO con sentencias preparadas en toda consulta, CSRF en formularios, contrasenas con `password_hash`.
