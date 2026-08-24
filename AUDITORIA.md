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
