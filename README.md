# Dream Go Operadora Turistica

Landing page y panel administrativo de Dream Go, agencia de viajes. PHP vanilla con
patron MVC propio, MariaDB, JS/CSS sin frameworks pesados, PWA instalable.

## Requisitos locales

- XAMPP (Apache + PHP 8.1+ + MariaDB)
- Composer
- Un Virtual Host apuntando a `public/` (ver abajo)

## Puesta en marcha local

No se usa Virtual Host de Apache (para evitar conflictos con otros proyectos en XAMPP). En su
lugar se corre con el servidor embebido de PHP, apuntando directo a `public/`:

1. **Dependencias**: `composer install`
2. **Variables de entorno**: copia `.env.example` a `.env`, ajusta `DB_USER`/`DB_PASS` con las
   credenciales de tu MariaDB local y deja `APP_URL=http://localhost:8090`.
3. **Base de datos**:
   ```
   mysql -u root -p -e "CREATE DATABASE dreamgo CHARACTER SET utf8mb4"
   mysql -u root -p dreamgo < database/schema.sql
   mysql -u root -p dreamgo < database/seeds/seed_demo.sql
   ```
4. **Servidor**: desde la raiz del proyecto, `php -S localhost:8090 -t public`
5. Abre `http://localhost:8090/` — deberias ver el sitio con el contenido de ejemplo.
6. Panel admin: `http://localhost:8090/admin/login` — usuario `admin@dreamgooperadoraturistica.com`, contrasena temporal en el comentario de `database/seeds/seed_demo.sql` (el panel te pedira cambiarla apenas inicies sesion).

## Estructura del proyecto

```
app/            Controladores, Modelos, Vistas, Services y Helpers (logica de negocio)
core/           "Framework" propio: Router, Database, Auth, Controller/Model base
config/         Bootstrap, rutas, credenciales de BD
cron/           9 tareas programadas (ver cron/README.md)
database/       Esquema SQL y datos iniciales
storage/        Logs y backups (nunca accesible por URL)
public/         Document root real (front controller, assets, uploads)
```

## Tests

`composer test` corre la suite de PHPUnit (`tests/Unit/`). Por ahora cubre las piezas de
logica pura mas sensibles: verificacion de firma del webhook de Mercado Pago
(`MercadoPagoService::verificarFirmaWebhook`), el guard de rango de `ReservaService::crear`,
el parser de `external_reference` (`ReservaService::parseReferenciaExterna`), la generacion
del comprobante PDF (`ComprobanteReservaService`), el saneo de atribucion de leads
(`App\Helpers\Atribucion`), la validacion de los IDs de analitica (`App\Helpers\Analytics`),
el manejo de errores de la bitacora (`App\Helpers\Auditoria`), el armado de filtros del
listado de cotizaciones (`Cotizacion::clausulaFiltros`) y `Validator`. No requiere base de
datos.

El comprobante PDF de reserva se genera con `dompdf/dompdf` (dependencia de produccion, en
`require`). Solo usa la fuente DejaVu que viene incluida en el paquete, asi que no necesita
permisos de escritura extra en el servidor.

## Documentacion relacionada

- `cron/README.md` — que hace cada tarea programada y como configurarla en Hostinger.
- `DEPLOY.md` — guia paso a paso para desplegar a Hostinger.

## Principios seguidos en el codigo

- Sin hardcodeo: parametros de negocio en `configuracion_sitio` (BD) o `.env`.
- Responsabilidad unica: `Services/` concentra logica reutilizable (correo, WhatsApp, cupos, descuentos, imagenes, sitemap).
- RBAC granular: roles y permisos 100% administrables desde `/admin/roles`, sin roles fijos en codigo.
- `app/`, `core/`, `config/`, `cron/`, `storage/`, `vendor/`, `.env` fuera del document root.
- PDO con sentencias preparadas en toda consulta, CSRF en formularios, contrasenas con `password_hash`.
