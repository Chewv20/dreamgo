# Auditoría de seguridad — Dream Go

Auditoría completa (seguridad, base de datos, configuración/infraestructura y calidad de
código) realizada el 2026-08-23. Informe completo con todos los hallazgos y evidencia:

**Informe interactivo:** https://claude.ai/code/artifact/db2132a4-cffd-44c4-b8cf-30468a2e8350

Este archivo es el resumen de seguimiento: qué se corrigió, qué falta y cómo continuar.

## Corregido

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

6. **Sin sistema de migraciones de base de datos versionado** — `database/migrations/` +
   `database/migrate.php`. `schema.sql` sigue siendo la referencia para una instalación
   nueva; para llevar una base de datos ya existente al día se corre
   `php database/migrate.php`, que aplica en orden los `.sql` pendientes de esa carpeta y
   los registra en una tabla `schema_migrations` (evita reaplicar dos veces). El primer
   archivo (`0001_seguridad_login_y_email_unico.sql`) es justamente el cambio del punto 3 y
   el 5 de arriba, para no perder ese SQL suelto que antes solo vivía en este markdown.
   Detalle de la convención en `database/migrations/README.md`.

   Nota real de la prueba en vivo: la primera versión de `migrate.php` envolvía cada
   migración en una transacción PDO; con una sola sentencia `CREATE TABLE` reventó con
   "There is no active transaction", porque MySQL/MariaDB hace commit implícito en cada
   sentencia DDL y PDO ya no tenía una transacción activa que cerrar. Se quitó esa falsa
   protección — ver el comentario en `migrate.php`.

7. **Sin cabeceras de seguridad HTTP** — `config/config.php` ahora manda
   `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy` y
   `Content-Security-Policy` en toda respuesta (público, admin, y páginas de error 403/404/500,
   verificado con `curl -i` contra los tres). La CSP es `script-src 'self'` sin
   `'unsafe-inline'` ni `'unsafe-eval'` — el sitio no usa ningún CDN externo (JS, CSS y fuentes
   son todas same-origin), así que no hizo falta abrir excepciones.

   Para lograr `script-src 'self'` sin romper nada hubo que limpiar el JS inline que ya
   existía en las vistas admin, porque la CSP lo bloquea igual que a un script externo no
   confiable:
   - Los dos `<script>` inline (`layouts/admin.php`, `admin/reservas/calendario.php`) se
     movieron a `public/assets/js/admin.js` y `admin-calendario.js`.
   - Los 5 atributos `onclick`/`onsubmit`/`onchange` inline (confirmaciones de borrar/cancelar/
     archivar, un `<select>` con auto-submit, un `<select>` que mostraba/ocultaba un campo)
     se reemplazaron por atributos `data-confirm`, `data-autosubmit` y
     `data-toggle-target`/`data-toggle-value`, con los listeners genéricos correspondientes
     en `admin.js` (mismo patrón que ya usaba `data-admin-sidebar-toggle`).

   `style-src` sí quedó con `'unsafe-inline'` a propósito: hay `style=""` inline en 46
   archivos de vistas, y sacarlos todos es un refactor aparte que no entra en el alcance de
   "agregar cabeceras". Queda anotado como pendiente abajo.

8. **`public/uploads/` sin `.htaccess` anti-ejecución** — `public/uploads/.htaccess` nuevo
   (cubre `original/`, `thumbs/` y cualquier subcarpeta futura por herencia de Apache):
   bloquea `Require all denied` sobre extensiones tipo script (`.php`, `.phtml`, `.cgi`,
   `.pl`, `.py`, `.sh`, `.asp`, `.aspx`, `.jsp`, `.exe`), `php_flag engine off` para
   `mod_php` como capa extra, y `Options -Indexes` para que tampoco se pueda listar el
   directorio. Probado en vivo: una imagen real sigue sirviéndose (200), un `.php` de
   prueba metido a mano en la carpeta responde 403 en vez de ejecutarse, y el listado de
   directorio también da 403. Como ya dice el hallazgo original, hoy no era explotable
   porque `ImageUploadService` reencodea con GD y siempre guarda como `.jpg` — esto es
   puramente defensa en profundidad por si esa validación cambia en el futuro.

9. **Cron jobs sin try/catch** — en vez de duplicar try/catch en los 6 scripts, la red de
   seguridad se puso una sola vez en `cron/_bootstrap.php` (todos hacen
   `require __DIR__ . '/_bootstrap.php';`): un `set_exception_handler` para cualquier
   `Throwable` sin capturar, y un `register_shutdown_function` que revisa
   `error_get_last()` para los errores fatales reales de PHP (los que ni siquiera llegan
   como `Throwable`, ej. agotar `memory_limit`). Ambos caminos terminan en `cron_log()` con
   el nombre del script (sacado de `$argv[0]`) y `exit(1)`.

   Probado en vivo con tres fallos provocados a propósito (excepción sin capturar, función
   inexistente, y agotamiento real de memoria con `memory_limit` bajado a 16M) — los tres
   quedaron en `cron.log` en vez de morir en silencio. Después se corrieron los 6 crons
   reales para confirmar que el cambio no rompió nada (todos terminaron con su mensaje de
   éxito de siempre).

   Límite conocido y no resoluble desde este archivo: un *parse error* en el propio script
   de cron (no en `_bootstrap.php`) sigue sin quedar registrado, porque PHP compila el
   archivo completo antes de ejecutar la primera línea — ni siquiera el `require` del
   bootstrap llega a correr. Eso solo se cubre con un supervisor externo (ej. que el cron
   real de Hostinger mande el stderr por correo), no con código PHP.

10. **N+1 en `RolAdminController::index`** — hacía una query a `rol_permiso` por cada rol
    dentro de un `foreach`. `Rol::permisosDelRol(int $rolId)` (que solo se usaba ahí) se
    reemplazó por `Rol::permisosPorRoles(int[] $rolIds)`: una sola query con
    `WHERE rol_id IN (...)` que agrupa el resultado en PHP con la misma forma que esperaba
    la vista (`array<rolId, int[]>`), así que `app/Views/admin/roles/index.php` no se tocó.

    Verificado contra la base de datos real comparando ambos caminos (el viejo, rol por
    rol, contra el nuevo en una sola query) para los 2 roles que existen localmente —
    mismo resultado, mismos permisos por rol, en 1 query en vez de N.

11. **Sin manejo centralizado de transacciones en `core/Model.php`** — el patrón
    `beginTransaction()`/`try`/`commit()`/`catch`+`rollBack()` estaba repetido en 4 lugares
    (`Rol::sincronizarPermisos()`, y `ReservaService::crear()`, `cancelar()` y el loop de
    `expirarVencidas()`). La implementación real quedó en `Core\Database::transaction(PDO
    $db, callable $callback)` (no en `Model` directamente) porque `ReservaService` no
    extiende `Model` — recibe su PDO por constructor. `Core\Model::transaction(callable
    $callback)` es un wrapper protegido que llama a `Database::transaction()` con
    `static::db()`, para que los modelos lo usen sin pasar el PDO a mano.

    `ReservaService::cancelar()` tenía un `rollBack()` manual en su camino de "reserva no
    encontrada o ya no cancelable" que devolvía `false` sin lanzar excepción; con el helper
    genérico (que solo hace rollback si hay una excepción) ese `return false;` deja que la
    transacción haga *commit* en vez de rollback. Es un cambio de comportamiento
    intencional y sin efecto real: esa rama solo hizo un `SELECT ... FOR UPDATE` de
    lectura, así que da igual confirmar o descartar la transacción — en ambos casos se
    libera el lock de fila sin haber escrito nada.

    Probado en vivo contra la base de datos real (con limpieza después de cada corrida):
    - `crear()` + `cancelar()` con una salida real: cupo se descuenta y se repone
      correctamente, ninguna transacción queda abierta al terminar.
    - `cancelar()` sobre una reserva ya cancelada devuelve `false` sin dejar una
      transacción colgada (el caso del punto anterior).
    - `crear()` con una salida inexistente lanza la excepción esperada, hace rollback real,
      y no deja ninguna reserva/cliente huérfano en la base.
    - `expirarVencidas()` repone el cupo correctamente (con margen suficiente para no
      toparse con `cupo_maximo`) y marca las reservas como `expirada`.
    - `Rol::sincronizarPermisos()` sigue dejando exactamente los permisos pedidos y
      restaurando el estado original del rol de prueba usado.

12. **Duplicación de CRUD/paginación entre controladores admin** — revisando los tres
    controladores del hallazgo (`CotizacionAdminController`, `ReservaAdminController`,
    `PaqueteAdminController`), lo único que estaba literalmente duplicado línea por línea
    era el armado del `Paginator` en `index()`:
    `new Paginator(Paginator::paginaDesde($this->request), self::POR_PAGINA,
    Modelo::contarTotal())`, repetido igual en los 3 (mismo `POR_PAGINA = 20` los tres).
    El resto de "CRUD" (crear/editar/archivar/cambiar estado) **no** es boilerplate
    repetido — cada uno tiene su propia validación, campos y efectos secundarios
    (`PaqueteAdminController` sube imagen y genera slug, `ReservaAdminController` delega en
    `ReservaService`, `CotizacionAdminController` solo cambia un estado) — forzar una base
    genérica de CRUD ahí habría sido una abstracción prematura sobre código que en realidad
    no es igual, así que no se tocó.

    Se agregó `AdminController::paginar(int $total, int $porPagina = 20): Paginator` (mismo
    patrón que ya existía para `encontrarO404()`), y los 3 controladores quedaron con
    `$paginador = $this->paginar(Modelo::contarTotal());` en vez de las 3 líneas
    repetidas. Se pudo quitar `private const POR_PAGINA = 20;` y
    `use Core\Paginator;` de los 3 (ya no instancian `Paginator` directamente).

    Se revisaron también `OfertaAdminController`, `UsuarioAdminController` y
    `SalidaAdminController` por si tenían el mismo patrón — no lo tienen, listan todo con
    `Modelo::all()` sin paginar, así que no aplicaba tocarlos.

    Probado en vivo: se invocaron los 3 controladores directamente en proceso (con sesión
    de PHP arrancada a mano, sin pasar por el middleware de auth) para confirmar que
    `index()` sigue renderizando el layout completo sin excepciones, y que
    `paginar()` respeta `?pagina=` y usa `porPagina=20` por defecto igual que antes. Aparte,
    se pidieron las 3 rutas reales por HTTP (`/admin/cotizaciones`, `/admin/reservas`,
    `/admin/paquetes`) para confirmar que el stack completo (router + `AuthMiddleware`)
    sigue respondiendo 302 a `/admin/login` en vez de un 500.

13. **Contraseñas de admin solo exigían 8 caracteres, sin complejidad** — `strlen($x) < 8`
    estaba repetido en 3 lugares (`AuthController::cambiarPassword()`,
    `UsuarioAdminController::crear()` y `::editar()`). Se centralizó en
    `App\Helpers\PasswordPolicy::esValida()` (8 caracteres mínimo + al menos una letra y un
    número) y `::mensaje()` para el texto de error, usado en los 3 call sites. Probado con
    casos sueltos (`abc`, `12345678`, `abcdefgh`, `abcdefg1`, `Password123`, `short1`) contra
    el resultado esperado de cada uno — todos correctos.

14. **Cookie `Secure` dependía solo de `$_SERVER['HTTPS']`** — en `config/config.php` ahora
    también se acepta `X-Forwarded-Proto: https` como señal de que el visitante llegó por
    HTTPS aunque el TLS se haya terminado antes de PHP (proxy/balanceador). Probado en vivo
    con `curl`: sin el header, la cookie sale sin `secure`; con
    `-H "X-Forwarded-Proto: https"`, sale con `secure`. Este fix es preventivo — el deploy
    documentado en `DEPLOY.md` (Hostinger, PHP recibe el TLS directo) no tiene ese proxy de
    por medio hoy, pero si el sitio se pone algún día detrás de Cloudflare u otro balanceador,
    esto ya queda cubierto sin tener que acordarse.

15. **Faltaban `CHECK` constraints para precios y fechas** — nueva migración
    `database/migrations/0002_check_constraints_precios_fechas.sql` (+ replicada en
    `schema.sql` para instalaciones nuevas): `paquetes.precio_desde >= 0`,
    `salidas.precio_override >= 0` (si no es NULL), `salidas.fecha_regreso >=
    fecha_salida` (si no es NULL), `codigos_descuento.valor > 0` y `<= 100` cuando
    `tipo = 'porcentaje'`, `codigos_descuento.fecha_fin >= fecha_inicio`,
    `reservas.precio_total >= 0`, `reservas.monto_pagado >= 0`. Antes de escribirla se
    verificó que ninguna fila real violara estas reglas.

    **Bug real encontrado al aplicar esta migración con `database/migrate.php` (el sistema
    del punto 6): el parser se comía sentencias enteras sin avisar.** El archivo empieza
    con comentarios `--` seguidos de la primera sentencia real, todo antes del primer `;`.
    El filtro viejo (`!str_starts_with($s, '--')`) solo miraba si el *bloque completo*
    (comentarios + sentencia) empezaba con `--` — como sí empezaba, descartaba el bloque
    entero, sentencia real incluida, **sin ningún error**: `migrate.php` decía "Aplicada"
    igual. Así fue como `chk_paquete_precio` quedó sin crearse la primera vez, y solo se
    encontró porque se probó insertando un precio negativo después de correr la migración
    y el `INSERT` no fallo cuando debía. Esto pudo pasar en cualquier migración futura que
    siguiera la convención de `database/migrations/README.md` (empezar con un comentario
    explicando el cambio) — el bug estaba en el sistema, no en el contenido de esta
    migración puntual.

    Arreglado en `database/migrate.php`: ahora se quitan las líneas 100% comentario con un
    regex multilinea (`preg_replace('/^\s*--.*$/m', '', $sql)`) *antes* de partir por `;`,
    así que una sentencia con comentarios pegados arriba ya no desaparece. Verificado con
    una base de datos desechable, recreando el estado "antes de la migración" (mismo
    `schema.sql` con los 8 `CHECK` nuevamente quitados) y volviendo a parsear/ejecutar el
    archivo 0002 con la lógica corregida: esta vez salieron las 4 sentencias reales (antes
    solo 3) y las 8 restricciones quedaron creadas, incluyendo `chk_paquete_precio`. También
    se re-parseó la migración 0001 con la lógica nueva para confirmar que sigue dando las
    mismas 6 sentencias de siempre (esa migración se había aplicado a mano, antes de que
    existiera `migrate.php`, así que el bug nunca la afectó en la práctica — pero de haberse
    corrido por el sistema viejo, también le habría faltado la primera sentencia).

    En la base de datos real de este equipo: se borró a mano la fila de prueba con precio
    negativo que sí logró insertarse mientras faltaba la restricción, se agregó
    `chk_paquete_precio` directamente, y se confirmaron las 8 restricciones con
    `information_schema.TABLE_CONSTRAINTS`. Se reintentó el mismo `INSERT` que antes había
    pasado sin error — esta vez lo rechazó. También se importó `schema.sql` completo en una
    base nueva desechable para confirmar que sigue importándose sin errores con los `CHECK`
    incluidos desde el arranque.

16. **`robots.txt` confirmaba la existencia de `/admin/`** — se quitó la línea
    `Disallow: /admin/`. La protección real de indexación ya existía por otro lado:
    `app/Views/layouts/admin.php` manda `<meta name="robots" content="noindex, nofollow">`
    en cada página del panel (verificado en vivo con `curl` contra `/admin/login`), que es
    el mecanismo recomendado hoy para esto — a diferencia de `Disallow`, evita que la
    página aparezca en resultados de búsqueda aunque algo la enlace desde afuera, y no
    depende de que el bot en cuestión respete `robots.txt`. La única función que cumplía
    `Disallow: /admin/` era publicar la ruta a cualquiera que leyera el archivo (que es
    público, no solo para bots) — la seguridad real de `/admin/` la da `AuthMiddleware`, no
    `robots.txt`. Se dejó `Disallow: /uploads/` (no revela nada sensible) sin tocar.

    De paso, sin relación con el hallazgo: la línea `Sitemap:` apuntaba a
    `http://dreamgo.local/sitemap.xml` (dominio de desarrollo hardcodeado, `public/robots.txt`
    es un archivo estático que Apache sirve directo — nunca pasa por PHP, así que no puede
    leer `APP_URL` del `.env`). Se corrigió al dominio real de producción
    (`https://dreamgooperadoraturistica.com/sitemap.xml`), que es el único dominio que un
    crawler real va a usar para pedir este archivo de todas formas.

17. **`MailerService::enviarReservaPendiente` registraba el log con el tipo equivocado** —
    usaba `confirmacion_reserva` en vez de `reserva_pendiente`, lo que hacía que un correo
    de "tu reserva está en revisión" y uno de "tu reserva fue confirmada" quedaran
    indistinguibles en `log_correos_enviados` (mismo `tipo`, mismo `referencia_id`) — una
    futura dedup por tipo+referencia jamás distinguiría cuál de los dos ya se mandó.

    **Al corregirlo apareció la razón real por la que estaba mal desde el principio:**
    `log_correos_enviados.tipo` es un `ENUM('cotizacion_equipo','confirmacion_reserva',
    'recordatorio_viaje','reporte_periodico','otro')` que **nunca incluyó**
    `'reserva_pendiente'`. Probar el fix ingenuo (solo cambiar el string en el código) tronó
    en vivo con `SQLSTATE[01000]: Data truncated for column 'tipo'` — MySQL/MariaDB
    silenciosamente trunca/rechaza un valor de ENUM que no existe en la lista. Osea que el
    bug original no fue un simple "se equivocaron de string": el ENUM le faltaba una opción,
    y usar `confirmacion_reserva` para ambos casos era la única forma de que el `INSERT` no
    fallara. Se agregó `database/migrations/0003_enum_tipo_reserva_pendiente.sql` (+
    reflejado en `schema.sql`) para meter `reserva_pendiente` al `ENUM`, y ya con eso el
    código se corrigió a usar ese valor.

    Probado en vivo end-to-end (con SMTP real sin configurar en local, que es justo el
    escenario donde `registrarLog()` sí corre igual aunque el envío falle):
    `enviarReservaPendiente()` ahora graba `tipo = 'reserva_pendiente'`,
    `enviarConfirmacionReserva()` sigue grabando `tipo = 'confirmacion_reserva'` como
    siempre (no se tocó, solo se verificó que seguía bien). También se reimportó
    `schema.sql` completo en una base desechable para confirmar que el `ENUM` ampliado no
    rompe nada, y se corrió `migrate.php` de nuevo para confirmar que no queda nada
    pendiente.

### De paso, sin relación con la auditoría

La base de datos local tenía dos tablas/columnas de una migración anterior que nunca se
aplicaron (`bloques_pagina` completa, `roles.permisos_actualizado_en`) — causaban un 500 en
el sitio público y en el login. Ya están en `database/schema.sql`; si vas a importar en un
equipo nuevo con `schema.sql` + `seed_demo.sql` frescos, no necesitas hacer nada extra.

## Aplicar estos cambios en una base de datos YA existente

Superado por el punto 6 (sistema de migraciones). Si en el otro equipo ya tienes una base
de datos poblada (no vas a reimportar `schema.sql` desde cero), corre:

```
php database/migrate.php
```

Aplica `database/migrations/0001_seguridad_login_y_email_unico.sql` (los mismos `ALTER`
que antes vivían copiados aquí a mano) y deja registro en la tabla `schema_migrations`
para no reaplicarlo. Si esa base de datos también le falta `roles.permisos_actualizado_en`
o la tabla `bloques_pagina` (ver nota de "De paso" arriba, de una migración previa a este
sistema), agrégalas a mano — `migrate.php` solo sabe de cambios hechos desde que existe.

Nota: la contraseña de admin de **esta** base de datos local ya fue cambiada a mano
durante las pruebas (no es la del seed) — no quedó documentada aquí a propósito. Si vas a
compartir el acceso con alguien más, cámbiala de nuevo desde `/admin/usuarios` o pide que
te la pasen por un canal aparte.

## Pendiente (del informe original, sin tocar todavía)

### Alto
- ~~Sin sistema de migraciones de base de datos versionado~~ — corregido, punto 6 arriba.
- ~~`storage/backups/*.sql.gz` reales en disco~~ — revisado: ya están fuera del webroot
  (punto 1), en `.gitignore`, y `cron/backup_bd.php::rotarBackupsAntiguos()` ya borra los de
  más de 14 días. No había nada más que hacer en el código; era un hallazgo sobre el
  servidor de producción real (que no está accesible desde aquí), no sobre este repo.
- Confirmar que `APP_ENV=production` en cualquier `.env` real de producción — no verificable
  desde este entorno local (no hay acceso al `.env` de Hostinger); sigue como paso manual en
  el checklist de `DEPLOY.md` (punto 10).

### Medio
- ~~Sin cabeceras de seguridad HTTP~~ — corregido, punto 7 arriba.
- **Nuevo, de baja prioridad:** la CSP quedó con `style-src 'self' 'unsafe-inline'` en vez de
  bloquear también los estilos inline, porque hay `style=""` inline en 46 archivos de vistas.
  Si se quiere cerrar del todo, hay que mover esos estilos a clases CSS (en `admin.css` /
  `site.css`) o usar un nonce por request para `style-src`; no se tocó porque es un refactor
  grande sin relación directa con "agregar cabeceras".
- ~~Cron jobs sin try/catch~~ — corregido, punto 9 arriba.
- ~~Duplicación de CRUD/paginación entre controladores admin~~ — corregido (solo la parte de
  paginación, ver por qué en el punto 12 arriba).
- ~~N+1 en `RolAdminController::index`~~ — corregido, punto 10 arriba.
- ~~Sin manejo centralizado de transacciones en `core/Model.php`~~ — corregido, punto 11
  arriba.

### Bajo / informativo
- ~~Contraseñas de admin solo exigen 8 caracteres, sin complejidad~~ — corregido, punto 13
  arriba.
- ~~Cookie `Secure` depende de `$_SERVER['HTTPS']`~~ — corregido, punto 14 arriba.
- ~~`core/Exceptions/ValidationException.php` nunca se usa~~ — corregido: se borró el
  archivo (no había otro sitio en el código que lo referenciara).
- ~~`APP_KEY` definido en `.env.example` pero no referenciado~~ — corregido: se quitó de
  `.env.example`, `.env` local y `DEPLOY.md` (nada en el código lo leía; no había ninguna
  funcionalidad de cifrado/firma que lo necesitara).
- ~~Faltan `CHECK` constraints para precios y fechas~~ — corregido, punto 15 arriba.
- ~~`robots.txt` confirma la existencia de `/admin/`~~ — corregido, punto 16 arriba.
- ~~`MailerService::enviarReservaPendiente` registra el log con el tipo equivocado~~ —
  corregido, punto 17 arriba.

No queda nada pendiente de la lista original de este archivo. Detalle completo de cada
hallazgo (archivo:línea, severidad, escenario de explotación) en el informe interactivo
enlazado arriba.

## Auditoría 2026-08-25 — sitio completo (seguimiento)

Segunda auditoría completa, sobre el estado del repositorio tras `01ee0b0` y `f48d71d`
(pago en línea con Mercado Pago, búsqueda/comparador de paquetes, reserva y consulta
pública, reseñas, suscripción a newsletter). Cuatro revisiones independientes en paralelo
(seguridad, base de datos, configuración/infraestructura, calidad de código), contrastadas
contra la auditoría del 23-08 — nada de lo corregido ahí se reabrió con el código nuevo.

**Informe interactivo:** https://claude.ai/code/artifact/e8e17720-98d3-4852-b673-665e0b4c328c

21 hallazgos: 2 Alto, 10 Medio, 7 Bajo, 2 informativo. Se corrigieron los 2 Alto y, a
continuación en la misma sesión, todo el resto salvo un hallazgo Medio (BD-01) que no se
tocó a propósito por una regla explícita del propio proyecto. Detalle completo de cada
hallazgo (archivo:línea, escenario de explotación) en el informe interactivo enlazado
arriba; acá el resumen de qué se hizo y cómo se probó.

### Corregido

1. **DoS de inventario + validación de `num_personas` insuficiente** —
   `Validator::entero()` no exigía rango, así que `ReservaPublicaController::crear()`
   aceptaba cualquier entero. Con un valor grande, una sola petición anónima a `/reservar`
   podía vaciar el cupo real de cualquier salida (bloqueándola 48h, sin CAPTCHA ni límite de
   tasa). Con un valor negativo, el `UPDATE cupo_disponible = cupo_disponible - :personas`
   se invertía, y el único freno era el `CHECK` de precio de la BD (auditoría anterior,
   punto 15) — cuyo `SQLSTATE` se mostraba crudo al visitante porque `PDOException` extiende
   `RuntimeException` en PHP 8 y caía en el mismo `catch`.

   Arreglado en dos capas: `ReservaService::crear()` ahora rechaza `num_personas` fuera de
   `[1, 30]` *antes* de tocar la base de datos (`self::MAX_PERSONAS_POR_RESERVA`, defensa en
   profundidad — cubre a cualquier llamador presente o futuro, no solo al controlador
   público), y `enRango('num_personas', 1, 30, ...)` se agregó tanto en
   `ReservaPublicaController` como en `ReservaAdminController` para dar el error al usuario
   sin llegar al servicio. Se agregó además un `catch (PDOException)` separado del
   `catch (RuntimeException)` en ambos controladores (`PDOException` va primero, por la
   jerarquía de PHP 8) que loggea el detalle real y muestra un mensaje genérico en vez del
   `SQLSTATE` crudo.

   Probado en vivo con `php -S localhost:8090 -t public` + MariaDB real, contra la salida
   `id=9` (cupo inicial 20): `num_personas=-5` → rechazado con "debe estar entre 1 y 30",
   cupo sin cambios. `num_personas=999` → mismo rechazo, cupo sin cambios.
   `num_personas=2` → reserva `DG-2026-000010` creada correctamente, cupo bajó a 18. Datos
   de prueba (reserva, cliente, cupo) limpiados después de verificar.

2. **Cero tests automatizados** (142 archivos PHP, ~8300 líneas) — se instaló PHPUnit 10.5
   como dependencia de desarrollo (`composer require --dev`, ya excluida de producción por
   el `--no-dev` que documenta `DEPLOY.md`) y se escribieron 21 tests (`composer test`,
   todos en verde):
   - `tests/Unit/Services/MercadoPagoServiceTest.php` — 10 casos sobre
     `verificarFirmaWebhook()`, la única barrera contra webhooks de pago falsificados: firma
     válida, `data.id`/`request-id` alterados, secreto incorrecto, mayúsculas/minúsculas,
     headers malformados o incompletos, replay con `ts` alterado.
   - `tests/Unit/Services/ReservaServiceTest.php` — el guard de `num_personas` del punto 1,
     con un PDO simulado que falla el test si el servicio llega a invocar `prepare()`/
     `query()` (confirma que el guard corta *antes* de tocar la base de datos).
   - `tests/Unit/Helpers/ValidatorTest.php` — 9 casos sobre `Validator`, incluyendo
     `enRango()` y el comportamiento de `entero()` (documentado a propósito: acepta
     negativos, por eso hacía falta `enRango()` además).

   Cobertura inicial, no exhaustiva — se priorizó la lógica pura de mayor riesgo (firma de
   pagos, validación de reservas) sobre cobertura total. Sin cobertura todavía:
   `Database::transaction()` y los flujos completos de `ReservaService` contra una base de
   datos real (necesitan fixture/BD de pruebas, fuera de este alcance). Documentado en una
   sección nueva de `README.md`.

3. **Permiso `reservas.ver` alcanzaba para crear reservas manuales** (SEG-06) — faltaba un
   permiso `reservas.crear` separado (el esquema ya distinguía `.ver`/`.confirmar`/
   `.cancelar`). Agregado a `database/seeds/seed_demo.sql` (instalación nueva) y a la
   migración `0012_permiso_reservas_crear.sql` (BD ya existente, `INSERT IGNORE` +
   asignado al rol Administrador). `config/routes.php` ahora exige `reservas.crear` en
   `GET/POST /admin/reservas/crear`. Migración aplicada y verificada contra la BD local
   (`rol_permiso` correcto para el rol 1).

4. **Monto/moneda del pago de Mercado Pago no se validaba contra el anticipo esperado**
   (SEG-02) — `ReservaService::registrarPagoAprobado()` ahora calcula el anticipo esperado
   server-side (`precio_total × porcentaje_anticipo_reserva`, con 1 centavo de tolerancia
   por redondeo) y solo confirma la reserva si el monto pagado lo alcanza; si no, la reserva
   queda `pendiente` (no hay estado intermedio en el esquema) con el pago igual registrado y
   un `error_log` para revisión manual. Probado en vivo con una reserva real
   (`precio_total=1000`, anticipo 30% = 300): pago de 200 → no confirma, queda pendiente,
   log de advertencia; pago de 300 → confirma. Reserva de prueba limpiada después.

5. **Webhook de Mercado Pago sin `MP_WEBHOOK_SECRET` aceptaba cualquier origen** (SEG-03) —
   `MercadoPagoWebhookController` ahora rechaza (401) si el secreto no está configurado,
   *salvo* en `APP_ENV=local` (para poder probar el webhook sin configurarlo en desarrollo,
   como ya documentaba `.env.example`). Revisado por lectura de código + lint; no se pudo
   probar en vivo el camino de rechazo real sin mockear la API de Mercado Pago, fuera de
   alcance razonable para esta sesión.

6. **Sin límite de intentos en `/reservar`, `/mi-reserva`, `/resena/{codigo}` y
   `/suscribir`** (SEG-04, SEG-05, SEG-07) — se generalizó el mismo patrón de ventana móvil
   que ya usaba el login (`intentos_login` / `Core\Auth::bloqueado()`) a cualquier acción
   pública sensible: tabla nueva `intentos_accion` (migración `0013`) + modelo
   `App\Models\IntentoAccion` + helper `App\Helpers\RateLimiter` con límites por acción
   (`reservar`: 20/IP/30min sin límite por email — el email lo pone el propio atacante;
   `reserva_consulta` y `resena`: 8 por email + 30 por IP/15min; `suscribir`: 15/IP/30min).
   Nuevo código de estado 429 en `Core\Controller::abort()` + vista `errors/429.php`.

   Probado en vivo contra el servidor real: 9 intentos seguidos a `POST /mi-reserva` con el
   mismo email → los primeros 8 pasan, el 9º da 429 con el mensaje esperado (verificado
   contra `intentos_accion`: exactamente 8 filas). 21 intentos a `POST /reservar` con
   `num_personas` inválido (para no tocar cupo real) desde la misma IP → los primeros 20
   pasan (validación normal), el 21º da 429; `cupo_disponible` de la salida de prueba sin
   cambios en ningún momento. Filas de prueba limpiadas de `intentos_accion` después.

7. **El service worker cacheaba páginas de `/admin/` sin exclusión** (CFG-01) — `public/sw.js`
   (bump a `dreamgo-v5`, fuerza que los navegadores con una versión vieja instalada
   descarten esa cache) ahora sirve cualquier ruta bajo `/admin/` con `fetch(request)`
   directo (network-only, sin `cache.put`). Capa extra en
   `Core\Middleware\AuthMiddleware::handle()`: manda `Cache-Control: no-store, private` en
   toda respuesta autenticada. Probado en vivo con `curl` (GET real, no HEAD): `/admin/reservas`
   sin sesión responde 302 con `Cache-Control: no-store, private` en las cabeceras.

8. **Envío de newsletter 100% síncrono dentro de una request admin** (CFG-02) —
   `OfertaAdminController::enviarSuscriptores()` ya no manda correos en el hilo de la
   request: solo encola (`ofertas_envio_cola`, migración `0014`, `UNIQUE(oferta_id,
   suscriptor_id)` para no reencolar ni duplicar). Cron nuevo
   `cron/enviar_avisos_oferta.php` (sugerido cada 5 min en `cron/README.md` y `DEPLOY.md`,
   ahora 8 tareas en vez de 7) procesa la cola en lotes de 50 y saca cada fila
   independientemente de si el envío tuvo éxito (un problema de SMTP persistente no debe
   dejar la cola creciendo para siempre).

   Probado en vivo: se creó una oferta y un suscriptor confirmado de prueba,
   `OfertaEnvioCola::encolarParaOferta()` encoló 1 fila la primera vez y 0 la segunda
   (dedup funcionando), y `php cron/enviar_avisos_oferta.php` la procesó (falló el envío
   real por no haber SMTP configurado en local, igual que el resto de los crons de correo
   probados en la auditoría anterior — quedó registrado en `log_correos_enviados` como
   fallido) y la sacó de la cola. Datos de prueba limpiados después.

9. **Logs sin rotación ni purga** (CFG-03) — rotación de una sola generación (a los 5MB, el
   archivo actual pasa a `.1` y se pisa la rotación anterior) agregada en
   `cron_log()` (`cron/_bootstrap.php`) para `cron.log`, y en `config/config.php` para
   `php-error.log` (se revisa en cada request fuera de `local`, un `stat()` es barato).
   Además, `cron/limpiar_intentos_login.php` ahora también purga `intentos_accion` (mismo
   tipo de tabla de solo-crecimiento que `intentos_login`, no ameritaba un cron aparte).
   Probado en vivo: se generó un `cron.log` de más de 5MB a mano y se corrió el cron — el
   archivo viejo quedó como `cron.log.1` (5MB) y el nuevo arrancó limpio con la línea del
   propio cron. Archivos de prueba limpiados después.

10. **Sin cache headers ni compresión para assets estáticos** (CFG-04) — bloques
    `mod_expires`/`mod_headers`/`mod_deflate` en `public/.htaccess`: CSS/JS con cache corto
    (1 día, sin `immutable`) porque `site.css`/`site.js` se referencian por nombre fijo sin
    versión ni hash — un cache largo dejaría a los visitantes con la versión vieja hasta que
    expirara sola en cada deploy; fuentes e imágenes sí llevan cache largo (1 año), son
    estables entre deploys. **No verificado en vivo**: el flujo local de este proyecto usa
    `php -S -t public` (ver README), que no procesa `.htaccess` — son directivas de Apache
    puro, solo se pueden confirmar en un entorno con Apache real (Hostinger en producción).
    Revisado por lectura, sintaxis estándar.

11. **Doble envío concurrente en reseñas/suscripciones daba un 500 genérico** (BD-02) —
    mismo patrón que ya se había corregido para `Cliente::encontrarOCrear()` en la auditoría
    anterior, replicado ahora en `ResenaPublicaController::guardar()` y
    `SuscripcionController::suscribir()`: `catch (PDOException)` con `getCode() === '23000'`
    que recupera la fila que ganó la carrera (en suscripciones) o muestra el mismo mensaje
    de "ya existe" (en reseñas) en vez de dejar subir la excepción como 500.

12. **Índice ausente `(estado, creado_en)` en `suscriptores`** (BD-03) — migración `0011`:
    se borró el índice viejo (solo `estado`, redundante frente al compuesto) y se creó
    `idx_suscriptores_estado_creado`. Aplicada y confirmada con `SHOW INDEX` contra la BD
    local.

13. **Duplicación sustancial entre `ReservaAdminController::crear()` y
    `ReservaPublicaController::crear()`** (CAL-02) — extraído `ReservaService::
    crearYNotificar()`: crea la reserva, trae el detalle y manda el correo de "reserva
    pendiente", los tres pasos que antes estaban repetidos línea por línea en ambos
    controladores. Lo que sigue siendo distinto entre ambos (cómo le muestran el error al
    usuario si falla: re-renderizar el formulario vs. redirigir con flash) se quedó en cada
    controlador a propósito — mismo criterio que ya usó este archivo en el punto 12 de la
    auditoría anterior para no forzar una abstracción sobre lógica que en realidad difiere.

    Probado en vivo end-to-end en ambos flujos contra la salida `id=9` (cupo 20): reserva
    pública con `num_personas=2` → cupo bajó a 18, reserva creada, sin pago en línea
    configurado localmente se comportó como antes. Reserva admin (con un usuario de prueba
    y su sesión real) con `num_personas=1` → cupo bajó a 19, reserva `DG-2026-000012`
    creada, redirige a su detalle. Ambas reservas, cliente y usuario de prueba, y el cupo,
    limpiados después.

14. **Enlace roto en el calendario admin** (CAL-03) — `Reserva::calendarioMes()` ahora
    selecciona `s.paquete_id`; `admin-calendario.js` arma el href real
    (`/admin/paquetes/{id}/salidas`) en vez de concatenar con un string vacío.

15. **Magic number del límite del comparador (3) duplicado en JS y PHP** (CAL-04) — fuente
    única en `PaqueteController::MAX_COMPARAR`, expuesta al JS vía
    `data-comparar-max="…"` en el `<body>` del layout público (mismo patrón `data-*` que ya
    usa el resto del sitio, ver auditoría anterior punto 7) en vez de repetir el número a
    mano en `site.js`.

16. **Búsqueda de texto libre no escapaba comodines de `LIKE`** (CAL-05) — `Paquete::
    clausulaFiltrosPublicados()` ahora escapa `%`/`_` del término de búsqueda antes de
    envolverlo, con `ESCAPE '\\'` explícito en el `LIKE`.

17. **`config/database.php` caía a credenciales de desarrollo si faltaban variables de
    entorno** (CAL-06, informativo) — fuera de `APP_ENV=local`, ahora lanza una
    `RuntimeException` clara si falta `DB_NAME` o `DB_USER` en vez de conectar en silencio
    con los valores por defecto de desarrollo (`host`/`port`/`password` sí mantienen
    default, son razonables aunque falten). Probado: con `.env` local normal
    (`APP_ENV=local`) sigue arrancando igual que antes.

### No corregido (a propósito)

- **Migraciones 0006 y 0009 no son re-ejecutables tras un fallo parcial** (BD-01) — mismo
  tipo de trampa que ya se había corregido para la 0002 en la auditoría anterior, pero
  corregirla ahora requeriría editar esos dos archivos, y `database/migrations/README.md`
  prohíbe explícitamente editar una migración ya commiteada/aplicada ("agrega una nueva,
  igual que con cualquier sistema de migraciones"). En vez de romper esa regla, se agregó
  la recomendación al propio `README.md` de migraciones para que ninguna migración nueva
  repita el problema (`CREATE TABLE IF NOT EXISTS` + `INSERT IGNORE` en permisos).

### Confirmado sin cambios necesarios

- **Condición de carrera entre el webhook de pago y la consulta pública** (BD-04) —
  `registrarPagoAprobado()` ya bloqueaba la fila con `FOR UPDATE` igual que el resto de
  `ReservaService`; se revisó explícitamente por ser el punto de mayor riesgo del código
  nuevo y no hizo falta ningún cambio.

No queda pendiente ningún hallazgo Alto o Medio salvo BD-01 (documentado arriba). Detalle
completo de cada hallazgo con archivo:línea y escenario de explotación en el informe
interactivo enlazado al principio de esta sección.

## Cambio deliberado en la CSP — GA4 / Meta Pixel (2026-08-27)

Al implementar la analítica (`MEJORAS.md`, segunda ronda), la CSP del punto 7 se relajó **a
propósito**, con el trade-off explícito:

- `script-src` pasó de `'self'` puro a `'self' 'nonce-<aleatorio-por-petición>'
  https://www.googletagmanager.com https://connect.facebook.net`. El nonce se genera en
  `config/config.php` (`CSP_NONCE`, `random_bytes(16)`) y lo usan los pocos `<script>` inline
  propios (bootstrap de GA4/Pixel en `app/Views/partials/analytics.php` y los eventos de
  conversión en las páginas de "gracias"). **No** se agregó `'unsafe-inline'`.
- Nuevas entradas en `img-src` (`googletagmanager`, `*.google-analytics.com`, `facebook.com`)
  y una directiva `connect-src` explícita (antes heredaba `default-src 'self'`).
- Los hosts de terceros están en la CSP siempre, pero los `<script>` solo se emiten si están
  configuradas las variables de entorno `GA4_MEASUREMENT_ID` / `META_PIXEL_ID` en `.env`
  (validadas por formato en `App\Helpers\Analytics`; un valor con comillas o `<>` se
  descarta y no llega al HTML) **y** el visitante acepta en el banner de cookies.

**Riesgo residual asumido:** un nonce por petición es más débil que `'self'` puro — si un
atacante lograra inyectar HTML en la *misma* respuesta podría copiar el nonce y ejecutar
script. Sigue siendo bastante más fuerte que `'unsafe-inline'`, y el proyecto no tiene hoy
ningún punto de inyección de HTML conocido (todas las salidas pasan por
`htmlspecialchars`).

**Consentimiento (agregado el mismo día):** GA4 y Meta Pixel ya **no se cargan** hasta que
el visitante pulsa "Aceptar" en un banner de cookies (`layouts/public.php` +
`initConsentimiento()` en `site.js`); la decisión se guarda en `localStorage`. El
`app/Views/partials/analytics.php` solo expone un inyector y lo llama si hay consentimiento
previo. Hay una página `/aviso-de-privacidad` (plantilla LFPDPPP con marcadores
`[CORCHETES]`) enlazada desde el footer y el banner. **Pendiente antes de producción:**
completar los `[CORCHETES]` del aviso con los datos reales de la empresa y que lo revise un
abogado (ver `MEJORAS.md`, ítem 3-ter).
