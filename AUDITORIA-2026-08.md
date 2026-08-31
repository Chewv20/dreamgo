# Auditoría completa — Dream Go Operadora Turística

**Fecha:** 2026-08-31
**Alcance:** seguridad, base de datos, rendimiento, SEO/accesibilidad, infraestructura/despliegue y calidad de código.
**Método:** revisión manual del código fuente completo (`core/`, `app/`, `config/`, `cron/`, `database/`, `public/`),
pase independiente (sin asumir nada de `AUDITORIA.md`); el contraste con la auditoría previa está en la última sección.
**No se modificó código.**

---

## 1. Resumen ejecutivo

El proyecto está **bien construido y por encima de la media** para un MVC propio en PHP. La superficie
clásica de ataque está esencialmente cubierta:

- PDO con sentencias preparadas en el 100% de las consultas y `ATTR_EMULATE_PREPARES = false`.
- CSRF en todos los POST (incluidos login y logout).
- Salida escapada con `htmlspecialchars(..., ENT_QUOTES)` en todas las vistas revisadas; los campos
  WYSIWYG pasan por un sanitizador de HTML por lista blanca sobre DOM.
- CSP fuerte con *nonce* por petición, **sin** `'unsafe-inline'` ni en `script-src` ni en `style-src`;
  `object-src 'none'`, `base-uri 'self'`, `frame-ancestors 'self'`.
- Login con rate-limiting por email e IP en ventana móvil, hash bcrypt, `password_verify` dummy
  anti-enumeración temporal, `session_regenerate_id(true)` y cambio de contraseña forzado en el primer acceso.
- Webhook de Mercado Pago que nunca confía en el payload: revalida contra la API con el token propio,
  verifica la firma HMAC y deduplica por `UNIQUE(referencia_pago)`.
- Concurrencia de cupo y de códigos de descuento resuelta con `SELECT ... FOR UPDATE` dentro de transacción.
- dompdf con `isRemoteEnabled` e `isPhpEnabled` en `false` (cierra SSRF/RCE del generador de PDF).
- Exportación CSV con defensa contra inyección de fórmulas.

**No se encontraron vulnerabilidades críticas ni altas.** Los hallazgos son de severidad **media a baja**
y de *hardening*, y varios dependen del entorno de despliegue (proxy/CDN, versión de PHP, document root).

| Severidad | Cantidad |
|-----------|----------|
| Alta      | 0 |
| Media     | 5 |
| Baja      | 9 |
| Hardening / calidad | 7 |

---

## 2. Hallazgos de severidad media

### SEG-01 · Rate-limiting evadible / envenenable si el sitio queda detrás de un proxy o CDN
**Ubicación:** `core/Request.php:75-78` (`ip()`), usado por `Core\Auth`, `App\Helpers\RateLimiter`,
`cotizaciones.ip_origen`, `suscriptores.ip_origen`.

`Request::ip()` devuelve únicamente `$_SERVER['REMOTE_ADDR']`. Es la decisión correcta **hoy** (no confía en
`X-Forwarded-For`), pero `config/config.php:85-86` ya contempla que el sitio pueda quedar detrás de un
terminador TLS externo (Cloudflare, balanceador). En ese escenario:

- Todas las peticiones llegan con la misma IP (la del proxy) → los contadores de `intentos_login` e
  `intentos_accion` se agrupan globalmente. Un atacante puede **agotar el umbral por IP (15 logins,
  10-30 acciones) para el resto de usuarios legítimos** (denegación de servicio) o, si el proxy rota
  IPs de salida, diluir su propio conteo.
- `bloqueado()` por email sigue funcionando (5 intentos), así que el login no queda totalmente abierto,
  pero el bloqueo por IP deja de tener sentido.

**Recomendación:** decidir explícitamente la topología. Si va a haber proxy de confianza, leer la IP real
de `X-Forwarded-For`/`CF-Connecting-IP` **solo** cuando `REMOTE_ADDR` esté en una lista blanca de rangos del
proxy; si no habrá proxy, dejarlo como está y documentarlo. Mismo criterio para `$isHttps` en `config.php`.

---

### SEG-02 · Falta cabecera HSTS y redirección a HTTPS
**Ubicación:** `config/config.php:52-76`, `public/.htaccess`, `.htaccess`.

Se emiten `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy` y CSP, pero
**no `Strict-Transport-Security`**, y no hay redirección `http → https` ni en la app ni en `.htaccess`. Un
visitante que llegue por `http://` viaja en claro (cookie de sesión incluida, aunque el flag `Secure`
dependa del esquema) y queda expuesto a *SSL stripping*.

**Recomendación:** en producción (`APP_ENV !== 'local'`) añadir
`Strict-Transport-Security: max-age=31536000; includeSubDomains; preload` y forzar el redirect a HTTPS
(preferible en `.htaccess`/servidor, antes de PHP).

---

### SEG-03 · Webhook de Mercado Pago sin validación de frescura del `ts`
**Ubicación:** `app/Services/MercadoPagoService.php:85-101`, `app/Controllers/Public/MercadoPagoWebhookController.php`.

`verificarFirmaWebhook()` valida el HMAC del *manifest* `id:...;request-id:...;ts:...;` pero **no comprueba
que `ts` sea reciente**. Una notificación válida capturada (headers + query) puede reproducirse
indefinidamente.

**Impacto real: bajo.** El controlador vuelve a consultar el pago a la API de MP (fuente de verdad) y
`pagos_reserva` tiene `UNIQUE(referencia_pago)`, así que un *replay* termina en `PAGO_DUPLICADO`. Aun así,
permite forzar repetidamente el trabajo del webhook (consulta a la API + transacción).

**Recomendación:** rechazar la notificación si `abs(now - ts) > 300s` (ventana de 5 min), como hace la
propia documentación de MP para la verificación de firma.

---

### SEG-04 · Generación de PDF sin límite: consumo de CPU con un token filtrado
**Ubicación:** `app/Controllers/Public/ComprobanteReservaController.php`, `app/Services/ComprobanteReservaService.php`.

`GET /reserva/{codigo}/comprobante?t={token}` **regenera el PDF con dompdf en cada petición**, sin
rate-limiting propio (comentario explícito: "el token de 32 hex ya hace el link no adivinable") ni caché.
dompdf es caro en CPU/memoria. Si un token se filtra (reenvío de correo, historial, logs de un proxy),
un atacante puede pedir el comprobante en bucle y saturar el worker de PHP.

**Recomendación:** aplicar `RateLimiter` también aquí (acción `comprobante`, límite bajo por IP y por
código), o cachear el PDF en `storage/` y servir el archivo ya generado, invalidándolo al cambiar el estado
o el monto de la reserva. Aplica igual a `/admin/reservas/{id}/comprobante`.

---

### BD-01 / CAL-01 · Validación incompleta en formularios de administración → error 500 en vez de mensaje
**Ubicación:**
- `app/Controllers/Admin/SalidaAdminController.php:33-60` y `73-94`: `fecha_salida` y `fecha_regreso` no
  se validan con `Validator::fecha()`; `precio_override` no se valida como número; en `editar()` ni
  siquiera se valida `cupo_maximo`.
- `app/Controllers/Admin/OfertaAdminController.php:122-137`: `tipo` y `alcance` se toman del input sin
  contrastar contra sus ENUM.
- `app/Controllers/Admin/ReservaAdminController.php:118-125`: `telefono` sin `Validator::telefono()`.

Estos formularios no capturan `PDOException`. Una fecha mal formada, un precio no numérico o un valor de
ENUM inválido llega al `INSERT`/`UPDATE`, viola un `CHECK`/tipo de columna y sale como **500 genérico**
(registrado en `storage/logs/php-error.log`) en lugar de un mensaje al operador. Es panel autenticado, por
lo que **no es un problema de seguridad**, pero sí de robustez, UX y ruido en logs.

**Recomendación:** completar `Validator` en estos controladores (whitelist de ENUM, `fecha()`, numérico) y,
como red de seguridad, capturar `PDOException` en los controladores admin igual que ya se hace en los
públicos.

---

## 3. Hallazgos de severidad baja

### SEG-05 · `<link rel="canonical">` refleja la URL completa con query string
**Ubicación:** `app/Views/layouts/public.php:12`
```php
<link rel="canonical" href="<?= htmlspecialchars(($_ENV['APP_URL'] ?? '') . $_SERVER['REQUEST_URI'], ...) ?>">
```
Está escapado (no hay XSS), pero: (a) el canonical **incluye el query string**, así que
`/paquetes?x=1`, `/paquetes?x=2`… se anuncian como URLs canónicas distintas → contenido duplicado y
dilución de señales SEO; (b) cualquiera puede fabricar el canonical de una página a partir de la ruta.
**Recomendación:** construir el canonical a partir de la ruta normalizada del router (sin query, o solo con
los parámetros que definan contenido, como `?pagina=`).

### SEG-06 · Sin timeout absoluto ni de inactividad para sesiones de administración
**Ubicación:** `config/config.php:88-95` (`'lifetime' => 0`), `core/Auth.php`.
La sesión admin dura hasta que se cierre el navegador. La invalidación por cambio de `actualizado_en` o de
permisos mitiga el caso de "usuario alterado", pero una cookie de sesión robada sigue siendo válida
indefinidamente si nadie toca la cuenta. **Recomendación:** guardar `last_activity` en sesión e invalidar
tras N minutos de inactividad y/o un máximo absoluto (p. ej. 8 h).

### SEG-07 · Token CSRF único por sesión, nunca rotado
**Ubicación:** `app/Helpers/Csrf.php`.
Se genera una vez por sesión y no se regenera al iniciar sesión ni al cambiar la contraseña. Es aceptable,
pero rotarlo en cambios de privilegio es buena práctica (defensa frente a *session fixation* de token).

### SEG-08 · `Slugify::generar()` puede devolver cadena vacía y depende del locale del SO
**Ubicación:** `app/Helpers/Slugify.php`.
`iconv('UTF-8', 'ASCII//TRANSLIT', ...)` es dependiente de `LC_CTYPE` (resultados distintos entre Windows y
Linux, puede emitir `?`). Un título íntegramente no latino o de emojis produce `slug = ''`; entonces la
ficha (`/paquetes/{slug}`, `/blog/{slug}`) queda **inalcanzable** y `slugUnico()` empieza a numerar desde
`-2`. **Recomendación:** si el slug base queda vacío, usar un fallback (`'paquete'` / `'articulo'` + id o
hash corto) y fijar `setlocale(LC_CTYPE, 'C.UTF-8')` antes del `iconv`, o usar un transliterador propio.

### SEG-09 · Enumeración sutil de reservas por mensajes diferenciados
**Ubicación:** `app/Controllers/Public/ReservaConsultaController.php`, `app/Controllers/Public/ResenaPublicaController.php:52-71`.
Los códigos de reserva son correlativos (`DG-2026-000001`). Los formularios de "Mi reserva" y de reseña
devuelven mensajes distintos según si el par código+email existe y según el estado de la reserva. Con el
email de una víctima se puede confirmar la existencia de su reserva y su estado. Está **mitigado** por
`RateLimiter` (8 por email, 30 por IP / 15 min). Informativo; si se quiere cerrar, unificar el mensaje de
"no encontrada / no elegible".

### PERF-01 · `SitemapService::regenerar()` se ejecuta síncrono en cada alta/edición de contenido
**Ubicación:** `app/Controllers/Admin/PaqueteAdminController.php:80,142`,
`app/Controllers/Admin/ArticuloAdminController.php:65,115,129`.
Cada guardado de paquete o artículo dispara 3-4 consultas y una escritura de `public/sitemap.xml` dentro de
la request. Es barato al volumen actual, pero acopla el tiempo de respuesta del panel a I/O de disco.
**Recomendación:** regenerar el sitemap en un cron (diario) o con un flag "sucio" que consuma el siguiente cron.

### PERF-02 · Subconsulta correlacionada de rating por fila en el catálogo
**Ubicación:** `app/Models/Paquete.php:53-59` (`(SELECT AVG(r.calificacion) FROM resenas r WHERE r.paquete_id = p.id ...)`).
Se evalúa una vez por paquete listado y además se ordena por su alias con `orden=mejor_valorados`. Correcto
y aceptable hoy; si el catálogo crece a cientos de paquetes conviene materializar el promedio (columna
`rating_promedio` / `rating_total` actualizada al moderar reseñas, o `LEFT JOIN` con agregación).

### PERF-03 · `log_correos_enviados` crece sin purga
**Ubicación:** `app/Services/MailerService.php:231-253`, `database/schema.sql:382`.
A diferencia de `intentos_login`, `intentos_accion` y `bitacora_admin` (que se purgan en
`cron/limpiar_intentos_login.php`), `log_correos_enviados` no tiene ninguna política de retención.
**Recomendación:** añadir un `DELETE ... WHERE enviado_en < NOW() - INTERVAL 6 MONTH` al mismo cron.

### SEO-01 · `meta description` puede quedar vacía
**Ubicación:** `app/Controllers/Public/BlogController.php:105` (`$articulo['meta_description'] ?: $articulo['resumen']`
con ambos potencialmente `null`), y fichas de paquete.
**Recomendación:** *fallback* final a la descripción global del sitio (`meta_description_default` en
`configuracion_sitio`) o a un texto derivado del contenido.

---

## 4. Hardening y calidad de código

### INFRA-01 · Mínimo de PHP en fin de soporte
`composer.json` pide `"php": ">=8.1"`. PHP 8.1 salió de soporte de seguridad a finales de 2025. Subir el
mínimo a **8.2** (idealmente probar en 8.3/8.4) y fijarlo también en el `.env`/panel de Hostinger.

### INFRA-02 · La protección de las carpetas fuera de `public/` depende de dos capas; verificar en cada deploy
El árbol sensible (`.env`, `storage/`, `app/`, `config/`, `vendor/`) queda protegido por (a) el
`Require all denied` de `.htaccess` raíz — que necesita `AllowOverride` y Apache 2.4 — y (b) que el
*document root* apunte a `dreamgo/public`. Si un hosting sirviera el proyecto desde la raíz e ignorara
`.htaccess`, todo quedaría expuesto. **Recomendación:** tras cada despliegue, verificar con
`curl -i https://dominio/.env`, `/composer.json`, `/config/database.php`, `/storage/logs/php-error.log`
(deben dar 403/404). Considerar mover `storage/` y `.env` aún más arriba del árbol del proyecto.

### INFRA-03 · `curl` a Mercado Pago sin `CURLOPT_CONNECTTIMEOUT`
**Ubicación:** `app/Services/MercadoPagoService.php:114-120`. Solo se fija `CURLOPT_TIMEOUT => 15`. Un
handshake colgado puede acercarse al `max_execution_time` justo cuando `crear()` ya abrió una transacción.
Añadir `CURLOPT_CONNECTTIMEOUT => 5`.

### CAL-02 · `Core\Model` interpola identificadores y `ORDER BY` sin binding
**Ubicación:** `core/Model.php` (`insert`, `update`, `where`, `all`).
Los nombres de columna y el `ORDER BY` se concatenan. **Hoy es seguro**: todos los llamadores pasan arrays
literales o filtrados con `Request::only([...])`, y el `ORDER BY` del catálogo pasa por el mapa
`Paquete::ORDENES`. Pero es un *footgun*: un futuro `Modelo::insert($request->all())` sería inyección SQL +
*mass assignment* a la vez. **Recomendación:** validar las claves contra una lista blanca de columnas en el
modelo base (o al menos `assert`/comentario de contrato).

### CAL-03 · Cobertura de pruebas limitada a lógica pura
Hay 13 archivos de test (helpers, `ReservaService` parcial, verificación de firma del webhook,
`ComprobanteReservaService`, `Validator`, JSON-LD…), todos sin base de datos. **No hay** pruebas de
integración/HTTP: ni del flujo completo de dinero (creación → webhook → confirmación → correo), ni de
CSRF/auth, ni de RBAC (que una ruta con `permiso` rechaza a quien no lo tiene). Es la parte más frágil del
proyecto ante cambios futuros. **Recomendación:** añadir tests de integración contra una BD de prueba para
el camino crítico de reservas y para el middleware de permisos.

### CAL-04 · Tope de personas inconsistente
`num_personas` admite 1-30 en la reserva (`ReservaService::MAX_PERSONAS_POR_RESERVA` y el formulario) pero
1-60 en el cotizador (`CotizadorController::enviar`). Unificar o documentar la diferencia.

### CAL-05 · `Validator::telefono` acepta 7-20 caracteres de `[0-9+\s()-]`
Es laxo (permite `-------`), pero suficiente para un campo de contacto. Sin acción; anotado para contexto.

### SEO-02 · `robots.txt` estático con dominio de producción fijo
`public/robots.txt` apunta a `https://dreamgooperadoraturistica.com/sitemap.xml`. En un *staging* con otro
`APP_URL` referencia el sitemap de producción. Es una decisión consciente (el archivo se sirve estático,
nunca pasa por PHP); anotado por si se monta un entorno de pruebas indexable.

### A11Y-01 · Revisión pendiente de accesibilidad a fondo
Lo revisado va bien: `lang` presente, `alt` en imágenes, `loading="lazy"` + `width/height` en tarjetas
(evita CLS), `aria-*` en el menú y el lightbox, foco gestionado en el lightbox, `role="status"` en flashes.
Pendiente de una pasada dedicada: contraste de la paleta configurable (el admin puede fijar colores que no
cumplan WCAG AA), *skip link* al contenido, y foco visible en todos los interactivos. Menor.

---

## 5. Base de datos — notas

- **Esquema sólido:** InnoDB en todo, FKs con `ON DELETE` deliberado (`RESTRICT`/`CASCADE`/`SET NULL`),
  `CHECK` de precios/fechas/cupos, `UNIQUE` en `clientes.email`, `usuarios_admin.email`,
  `reservas.codigo_reserva`, `reservas.token_publico`, `pagos_reserva.referencia_pago`,
  `resenas.reserva_id`, `suscriptores.email`/`token`. Índices razonables para los listados y filtros.
- **Migraciones versionadas** (`database/migrations/` + `migrate.php` + tabla `schema_migrations`), con la
  limitación asumida y documentada de que un archivo con varias sentencias DDL puede quedar aplicado a
  medias si una falla (mitigado con la convención de migraciones pequeñas).
- `migrate.php` parte por `;` tras quitar líneas de comentario: frágil ante un `;` dentro de una cadena o
  de un `DELIMITER` (triggers/procedimientos). Hoy no hay ninguno; anotarlo si se añaden.
- Falta índice util para `Paquete::tieneReservas` (`reservas` → `salidas` por `paquete_id`): el `JOIN`
  usa `idx_reservas_salida` + PK de `salidas`, aceptable.
- `codigo_reserva` = `DG-{año}-{id con str_pad(6)}`: rompe el formato (no la unicidad) a partir de 10^6
  reservas. Cosmético.

---

## 6. Contraste con `AUDITORIA.md` (auditoría previa, 2026-08-23/25/29)

Se verificó en el código cada punto que el seguimiento previo da por corregido. **Todos están
efectivamente implementados**, y en algunos casos el código fue más allá de lo que describe el markdown:

| Punto previo | Estado real en el código |
|---|---|
| `.htaccess` raíz + `public/` + `public/uploads/` | Presentes y coherentes (`Require all denied` / `granted` / anti-ejecución). |
| Rate-limiting de login | `IntentoLogin` + `Auth::bloqueado()` (5/email, 15/IP, ventana con `NOW()` de MySQL). Confirmado. |
| Contraseña de fábrica + cambio forzado | `debe_cambiar_password` + `AuthMiddleware` + `/admin/cambiar-password`. Confirmado. |
| Condición de carrera en descuentos | `DescuentoService::validar()` con `SELECT ... FOR UPDATE` dentro de la transacción de `ReservaService::crear()`. Confirmado. |
| `clientes.email UNIQUE` + carrera | `UNIQUE` en el esquema + captura `SQLSTATE 23000` en `Cliente::encontrarOCrear()`. Confirmado. |
| Migraciones versionadas | `database/migrate.php` + `schema_migrations`. Confirmado. |
| Cabeceras de seguridad + CSP | En `config/config.php` para toda respuesta. **Avanzó:** el markdown deja `style-src 'unsafe-inline'` como pendiente ("46 vistas con `style=""`"); el código ya está en `style-src 'self' 'nonce-...'` **sin** `unsafe-inline`, y no queda ningún `style=""` inline. Pendiente cerrado. |
| Cron sin try/catch | `cron/_bootstrap.php` con `set_exception_handler` + `register_shutdown_function`. Confirmado. |
| N+1 en `RolAdminController::index` | `Rol::permisosPorRoles(int[])` (una query con `IN`). Confirmado. |
| Transacciones centralizadas | `Database::transaction()` usado por `ReservaService`, `DescuentoService` (indirecto), etc. Confirmado. |
| SW cacheando `/admin` y documentos con token | `public/sw.js` excluye `/admin` y cualquier URL con `?t=` (network-only). Confirmado. |
| Rotación de `cron.log` / `php-error.log` | En `_bootstrap.php` y `config.php` (rotación de 1 generación a 5 MB). Confirmado. |

**Conclusión del contraste:** la auditoría previa se ejecutó de verdad y el estado del código es
consistente (o mejor) que su documento de seguimiento. Los hallazgos de este informe son **nuevos** o de
menor prioridad que no se habían listado.

---

## 7. Plan de acción priorizado

**Ahora (bajo esfuerzo, valor claro) — IMPLEMENTADO 2026-08-31:**
1. ✅ HSTS (`config/config.php`, solo HTTPS + no-local) + redirección HTTP→HTTPS con guard de proxy
   (`public/.htaccess`) (SEG-02).
2. ✅ `MercadoPagoService::tsWebhookEsReciente()` (ventana de 10 min) llamado desde el webhook tras validar
   la firma; 6 pruebas nuevas (SEG-03).
3. ✅ `CURLOPT_CONNECTTIMEOUT => 5` en `MercadoPagoService::httpJson()` (INFRA-03).
4. ✅ `DELETE FROM log_correos_enviados` (>6 meses) en `cron/limpiar_intentos_login.php` + `cron/README.md` (PERF-03).
5. ✅ `SalidaAdminController` y `OfertaAdminController`: validación completa (fechas, numérico, ENUM) +
   `catch (PDOException)` → mensaje al operador; `ReservaAdminController` con `telefono()`/`maxLength()` (BD-01).
6. ✅ `composer.json` → `"php": ">=8.2"`; `composer.lock`, `README.md`, `DEPLOY.md` actualizados. `composer audit`: sin avisos (INFRA-01).

Verificado: `composer test` → 112 pruebas OK; `php -l` en los 8 archivos; smoke con servidor embebido
(home / `/admin/login` / `/paquetes` → 200, CSP intacta, HSTS ausente en local como se espera).

**Siguiente iteración — IMPLEMENTADO 2026-08-31:**
7. ✅ `App\Helpers\ProxyConfianza` (opt-in vía `TRUSTED_PROXIES` en `.env`): `Request::ip()` y el
   `$isHttps` de `config.php` solo miran `X-Forwarded-For`/`-Proto` si la conexión entra por un
   proxy declarado; por defecto (sin proxy) siguen usando `REMOTE_ADDR`/`$_SERVER['HTTPS']`. 7 pruebas (SEG-01).
8. ✅ `RateLimiter` acción `comprobante` (30/IP/10 min) aplicada en `ComprobanteReservaController::descargar` (SEG-04).
9. ✅ Canonical en `layouts/public.php`: ruta normalizada + solo `?pagina=`; sin reflejar el query string arbitrario (SEG-05).
10. ✅ `Auth::sesionCaducada()` / `registrarActividad()` + comprobación en `AuthMiddleware`: cierre
    tras 2 h de inactividad o 12 h absolutas (SEG-06).
11. ✅ `Slugify::generar()` con mapa de transliteración fijo (sin `iconv//TRANSLIT` locale-dependiente)
    y `$fallback` que garantiza slug no vacío; callers pasan `'paquete'`/`'articulo'`. 7 pruebas (SEG-08).
12. ✅ `cron/regenerar_sitemap.php` (horario) + eliminadas las 5 llamadas síncronas a `SitemapService`
    en `PaqueteAdminController` / `ArticuloAdminController`; `cron/README.md` y crontab actualizados (PERF-01).

Verificado: `composer test` → 125 pruebas OK; `php -l` en todos los archivos; smoke con servidor
embebido (canonical sin `utm_*`, `?pagina=2` auto-referencial, cron de sitemap escribe el XML).

**Cuando haya margen:**
13. Tests de integración del flujo de reservas y del middleware de permisos (CAL-03).
14. Lista blanca de columnas en `Core\Model` (CAL-02).
15. Pasada dedicada de accesibilidad (A11Y-01) y de contraste de la paleta configurable.
16. Materializar el rating de paquetes si crece el catálogo (PERF-02).

---

## 8. Inventario revisado

`core/` (Router, Database, Model, Controller, Auth, Request, Response, Paginator, middlewares) ·
`config/` (config, database, routes) · `app/Helpers/` (15) · `app/Services/` (8) ·
`app/Controllers/` (público + admin, ~35) · `app/Models/` (~25) · vistas públicas y layout admin ·
`database/schema.sql` + 22 migraciones + `migrate.php` · `cron/` (bootstrap + limpieza) ·
`public/` (`index.php`, `.htaccess` ×3, `sw.js`, `site.js`, `analytics.php`, `robots.txt`) ·
`composer.json`, `.env.example`, `.gitignore`, `DEPLOY.md`, `README.md`.
