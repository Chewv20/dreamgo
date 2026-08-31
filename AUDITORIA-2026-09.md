# Auditoría 2026-09 — revisión completa desde cero

Revisión integral del proyecto (seguridad, calidad de código, rendimiento/BD y
accesibilidad/SEO), hecha **sin partir de las auditorías anteriores**: se releyó todo el
código de `core/`, `config/`, `app/`, `cron/`, `database/`, `public/` y los assets JS.

## Veredicto general

El código está en muy buen estado. Cuatro rondas de auditoría previas (`AUDITORIA.md`,
`AUDITORIA-2026-08.md`) cerraron los problemas de fondo: PDO preparado en el 100 % de las
consultas, CSRF en todos los formularios, RBAC sin roles fijos, rate-limiting por IP e
identificador, sanitización de HTML por lista blanca, CSP con nonce sin `unsafe-inline`,
verificación de firma + anti-replay del webhook de Mercado Pago, cabeceras de seguridad,
caducidad de sesión, dompdf con `isRemoteEnabled`/`isPhpEnabled` en `false`, uploads
reencodeados con GD + `.htaccess` anti-ejecución, retención automática de logs.

**No se encontró ningún hallazgo crítico ni alto.** Lo que sigue son mejoras de
defensa en profundidad, robustez y consistencia.

| ID | Área | Severidad | Título |
|----|------|-----------|--------|
| SEG-01 | Seguridad | **Media** | `roles.gestionar` permite auto-escalada de privilegios — ✅ resuelto |
| SEG-02 | Seguridad | Baja | CSRF verificado a mano en cada handler, sin red de seguridad |
| SEG-03 | Seguridad | Baja | `backup_bd.php`: detección de `mysqldump` con sintaxis de Windows |
| SEG-04 | Seguridad | Baja | `rol_id`/`permiso_id` sin validar antes del INSERT (500 en vez de error controlado) |
| SEG-05 | Seguridad | Baja | `HtmlSanitizer`: enlaces sin `rel="noreferrer nofollow"`, admite `http://` |
| SEG-06 | Seguridad | Info | CSP con `googletagmanager` / `connect.facebook.net` en `script-src` |
| SEG-07 | Seguridad | Info | `cambiarPassword` no regenera el ID de sesión |
| SEG-08 | Seguridad | Info | Enumeración sutil en `/mi-reserva` y `/resena/{codigo}` (ya en PENDIENTES) |
| CAL-01 | Calidad | **Media** | Sin pruebas de integración del camino de dinero (ya en PENDIENTES) — ✅ resuelto (harness + camino de dinero) |
| CAL-02 | Calidad | Baja | `ReservaPublicaController::crear` repite el render del formulario 4 veces |
| CAL-03 | Calidad | Baja | `config/routes.php` plano: `'auth' => true` repetido en cada ruta admin |
| CAL-04 | Calidad | Baja | Lectura directa de `$_SERVER['HTTP_REFERER']` en controladores |
| CAL-05 | Calidad | Trivial | 4 documentos de auditoría (~150 KB) en la raíz del repo |
| PERF-01 | Rendimiento | **Media** | Envío SMTP síncrono dentro del request, sin `Timeout` configurado — ✅ resuelto |
| PERF-02 | Rendimiento | Baja | Exportaciones CSV sin límite ni streaming |
| PERF-03 | Rendimiento | Info | `calendarioDatos`: `anio`/`mes` sin validar rango |
| A11Y-01 | Accesibilidad | Baja | Sin aviso de contraste WCAG al fijar la paleta (ya en PENDIENTES) |
| A11Y-02 | Accesibilidad | Baja | `alt_text` de imágenes de paquete rellenado con el slug |
| A11Y-03 | Accesibilidad | Baja | Banner de consentimiento sin `aria-modal` ni trampa de foco |
| SEO-01 | SEO | Baja | `robots.txt` bloquea `/uploads/` → imágenes no rastreables |
| SEO-02 | SEO | Baja | `robots.txt` con `Sitemap:` de producción hardcodeado (ya en PENDIENTES) |
| SEO-03 | SEO | Info | `meta description` puede quedar vacía en fichas sin resumen |

---

## Seguridad

### SEG-01 · `roles.gestionar` permite auto-escalada de privilegios — **Media**

`RolAdminController::guardarMatriz()` deja que cualquier usuario con el permiso
`roles.gestionar` edite la matriz de permisos de **todos** los roles no-sistema, sin
restringir qué permisos puede asignar. Un usuario cuyo rol tenga sólo `roles.gestionar`
puede añadir a su propio rol `usuarios.gestionar`, `configuracion.gestionar`,
`bitacora.ver`, `reservas.cancelar`, etc. Tras el cierre de sesión que fuerza
`sesionVigente()` (por el sello `permisos_actualizado_en`), al reautenticarse ya tiene los
permisos nuevos.

En la práctica **`roles.gestionar` es equivalente a administrador total**. El rol
`es_sistema` sí está protegido (no se puede asignar ni editar sin ser de sistema), pero la
frontera real de privilegio es más porosa de lo que sugiere la granularidad del RBAC.

- **Archivos:** `app/Controllers/Admin/RolAdminController.php:74-97`
- **Opciones:**
  - **A (documentar):** dejar claro en el panel y en `README.md` que `roles.gestionar` es
    un permiso de superadministrador y sólo debe darse a cuentas de plena confianza.
  - **B (restringir):** al guardar la matriz, filtrar la selección para que un editor sólo
    pueda asignar permisos que **él mismo posee** (`array_intersect` con
    `Auth::$SESSION_PERMISOS`). Un no-sistema deja de poder crear un rol más poderoso que
    el suyo.
  - Recomendación: **B**, es la que preserva el principio de menor privilegio que ya
    persigue el resto del diseño.
- **Resuelto (2026-09):** implementada la opción B. `guardarMatriz()` ahora, para un editor
  sin rol de sistema, sólo acepta de la selección los permisos que el propio editor tiene
  (`Permiso::idsPorClaves(Auth::permisos())`) y **conserva** los que ya tenía el rol y el
  editor no puede tocar. La matriz muestra esas casillas bloqueadas. Un Administrador (rol
  de sistema) sigue pudiendo asignar cualquiera. Nuevo getter `Auth::permisos()` y helper
  `Permiso::idsPorClaves()`.

### SEG-02 · CSRF verificado a mano en cada handler — Baja

La protección CSRF depende de que **cada** método que muta estado llame a
`$this->verifyCsrf()`. Hoy la cobertura es completa (se verificó ruta por ruta: 38
llamadas, todas las rutas POST cubiertas, más el webhook de MP correctamente excluido por
ser un callback externo). Pero no hay ninguna red de seguridad: una ruta POST nueva a la
que se le olvide la llamada queda sin protección y **ningún test lo detecta**.

- **Sugerencia:** mover la verificación a `Router::dispatch()` para todo método no seguro
  (`POST`/`PUT`/`DELETE`), con una marca explícita de exclusión en la definición de ruta
  para `/webhooks/mercadopago`. Alternativa más barata: un test que recorra
  `config/routes.php` y afirme que cada handler POST contiene `verifyCsrf` (o está en una
  lista blanca).
- **Archivos:** `core/Router.php:82-91`, `core/Controller.php:69-74`, `config/routes.php`

### SEG-03 · `backup_bd.php`: detección de `mysqldump` con sintaxis de Windows — Baja

`intentarMysqldump()` hace `shell_exec('where mysqldump 2>NUL')`. En el servidor de
producción documentado (Hostinger, Linux):

- `where` no existe → error silencioso.
- `2>NUL` redirige stderr a un **archivo llamado `NUL`** en el directorio de trabajo, que
  queda como basura en el repo/htdocs.
- Resultado: siempre cae al `backupPhpPuro()`, que es más lento, arma los `INSERT` con
  `PDO::quote()` (no idéntico byte a byte a un dump nativo para tipos binarios/`BIT`) y
  carga **todo el dump en memoria** (`file_get_contents` completo antes de `gzencode`).

- **Archivos:** `cron/backup_bd.php:35`, `:109-114`
- **Sugerencia:** usar `command -v mysqldump 2>/dev/null` en Linux (ya se intenta `which`
  como segundo `shell_exec`, pero el primero ensucia el cwd); comprimir con streaming
  (`gzopen`/`gzwrite` leyendo el `.sql` por bloques) en el fallback.

### SEG-04 · `rol_id` / `permiso_id` sin validar antes del INSERT — Baja

`UsuarioAdminController::crear()` / `::editar()` hacen `(int) $datos['rol_id']` y lo
insertan directo; `RolAdminController::guardarMatriz()` hace
`array_map('intval', $seleccion[...])`. Un `rol_id` o `permiso_id` inexistente rebota
contra la FK (`fk_usuario_rol`, `fk_rolpermiso_permiso`) y se convierte en un
`PDOException` → 500 genérico, en vez de un `Flash` de "rol inválido".

No hay bypass de seguridad (la FK protege la integridad), es sólo robustez y UX.

- **Archivos:** `app/Controllers/Admin/UsuarioAdminController.php:55-66`, `:115-133`;
  `app/Controllers/Admin/RolAdminController.php:88-90`
- **Sugerencia:** validar contra `Rol::find()` / la lista de `Permiso::all()` antes de
  persistir.

### SEG-05 · `HtmlSanitizer`: enlaces sin `rel` completo, admite `http://` — Baja

`HtmlSanitizer::limpiarAtributos()` reescribe cada `<a>` con `href` normalizado y
`rel="noopener"`, pero:

- no añade `noreferrer nofollow` (contenido de un blog/paquete puede enlazar a sitios de
  terceros sin marcar el enlace como no confiable para SEO ni cortar el `Referer`);
- `Url::segura()` acepta `http://` además de `https://`.

Impacto bajo: el contenido lo controla un administrador autenticado. Es endurecimiento.

- **Archivos:** `app/Helpers/HtmlSanitizer.php:82-95`, `app/Helpers/Url.php:38`

### SEG-06 · CSP con `googletagmanager` / `connect.facebook.net` en `script-src` — Info

`config/config.php:78-89` permite esos dos orígenes en `script-src` junto al nonce. Google
Tag Manager puede cargar scripts arbitrarios definidos en el contenedor, así que la CSP no
acota lo que GTM inyecte. Es el compromiso habitual de usar analítica de terceros y sólo
se activa tras el consentimiento del visitante (`App\Helpers\Analytics`). Se documenta
como riesgo residual aceptado.

### SEG-07 · `cambiarPassword` no regenera el ID de sesión — Info

`AuthController::cambiarPassword()` llama a `Auth::forzarCierre()` (limpia las claves
`admin_*` de `$_SESSION`) pero no a `session_regenerate_id(true)`. El identificador de
cookie anónimo sobrevive hasta el siguiente `Auth::login()` (que sí regenera). Ventana
pequeña y sin datos sensibles en la sesión ya vaciada; conviene regenerar igual por
higiene.

- **Archivo:** `app/Controllers/Admin/AuthController.php:96`

### SEG-08 · Enumeración sutil en `/mi-reserva` y `/resena/{codigo}` — Info

Ya registrado en `PENDIENTES.md` (SEG-09): los mensajes difieren según exista o no la
combinación código+email. Mitigado por `RateLimiter`. Se cierra unificando el mensaje de
"no encontrada / no elegible".

---

## Calidad de código

### CAL-01 · Sin pruebas de integración del camino de dinero — **Media**

Reiteración de `PENDIENTES.md` #1 por su criticidad. El flujo
`POST /reservar` → `ReservaService::crear` (descuento de cupo con `FOR UPDATE`) → webhook
MP (`registrarPagoAprobado`, dedup por `UNIQUE(referencia_pago)`) → confirmación → correo
sólo tiene tests de lógica pura. Reposición de cupo en cancelación/expiración, rechazo
RBAC de punta a punta y condiciones de carrera (`Cliente::encontrarOCrear`,
`DescuentoService::validar` cerca de `uso_maximo`, doble reseña) no se ejercitan contra una
BD real. Ver el esbozo de `tests/Integration/` en PENDIENTES.

**Resuelto (2026-09) — parcial:** creado el harness de integración con BD real y las pruebas
del **camino de dinero** (lo prioritario). Nuevos:

- `phpunit.integration.xml` (suite `integration` separada de `unit`) + `composer test:integration`.
- `tests/Integration/bootstrap.php` — apunta la app a `dreamgo_test` (o `DB_NAME_TEST`)
  antes de abrir la conexión.
- `tests/Integration/IntegrationTestCase.php` — recrea `schema.sql` una vez, `TRUNCATE` +
  fixture determinista por test (sin transacción envolvente, porque el código bajo prueba
  abre las suyas con `FOR UPDATE`). Se marca *skipped* con instrucciones si no hay BD.
- `tests/Integration/ReservaFlujoDineroTest.php` — 18 pruebas: descuento de cupo, código +
  token, confirmación por anticipo server-side, dedup del webhook por
  `UNIQUE(referencia_pago)`, pago insuficiente / acumulado / de saldo, reposición de cupo en
  cancelación y expiración (y su tope en `cupo_maximo`), y códigos de descuento con límite
  de usos.

**Sigue pendiente:** RBAC de punta a punta contra BD, las condiciones de carrera de
`Cliente::encontrarOCrear` / doble reseña, y configurar CI.

### CAL-02 · `ReservaPublicaController::crear` repite el render 4 veces — Baja

El bloque `$this->view('public/reservar/formulario', [...])` con `paquete`, `salida`,
`precioUnitario`, `porcentajeAnticipo`, `errores`, `valores` y el mismo `title` aparece
idéntico en 4 ramas (validación, `PDOException`, `RuntimeException`, éxito sin MP).
Extraer un `private function renderFormulario(array $errores, array $valores): void`.

- **Archivo:** `app/Controllers/Public/ReservaPublicaController.php:90-144`

### CAL-03 · `config/routes.php` plano — Baja

~130 rutas sin agrupar; `['auth' => true, 'permiso' => '...']` se repite a mano en cada
línea admin. Es fácil olvidar `'auth' => true` en una ruta nueva (de hecho `/admin` sólo
lleva `auth` sin `permiso`, que es correcto, pero la diferencia no salta a la vista).
Un helper `admin(prefix, fn)` o un `group()` en el `Router` que aplique `auth` por defecto
reduce ese riesgo.

### CAL-04 · Lectura directa de `$_SERVER['HTTP_REFERER']` — Baja

`ReservaPublicaController`, `CotizadorController` y `ContactoController` leen
`$_SERVER['HTTP_REFERER']` directo en vez de a través de `Core\Request`. `Atribucion` ya lo
sanea, pero rompe la abstracción que el resto del proyecto respeta. Añadir
`Request::referer(): ?string`.

### CAL-05 · Documentos de auditoría en la raíz — Trivial

`AUDITORIA.md` (63 KB), `AUDITORIA-2026-08.md`, `MEJORAS.md`, `PENDIENTES.md` y este
archivo viven en la raíz. Mover a `docs/` para dejar la raíz limpia (dejar sólo
`README.md`, `DEPLOY.md`).

---

## Rendimiento y base de datos

### PERF-01 · Envío SMTP síncrono dentro del request, sin `Timeout` — **Media**

`MailerService::enviar()` se ejecuta dentro del ciclo de petición en varias rutas
públicas y, sobre todo, dentro de `MercadoPagoWebhookController::notificar()`.
`configurarPhpMailer()` no fija `$mail->Timeout` ni `$mail->SMTPTimeout`: el valor por
defecto de PHPMailer es **300 s**. Un SMTP lento o caído:

- bloquea la respuesta al visitante que acaba de reservar/cotizar;
- en el webhook, puede superar el tiempo que Mercado Pago espera por el `200` y provocar
  **reintentos de la notificación** (aunque el dedup por `UNIQUE(referencia_pago)` evita
  el doble cobro, genera ruido y trabajo repetido).

`ReservaService::crearYNotificar()` manda el correo después del `commit`, lo cual es
correcto, pero sigue siendo dentro del request.

- **Archivos:** `app/Services/MailerService.php:211-229`,
  `app/Controllers/Public/MercadoPagoWebhookController.php:99-105`
- **Sugerencia mínima:** `$mail->Timeout = 8; $mail->SMTPKeepAlive = false;` y responder
  el `200` del webhook **antes** de enviar el correo (registrar el pago, responder, y
  disparar el correo con `fastcgi_finish_request()` o dejarlo a un cron que recorra
  reservas confirmadas sin correo de confirmación en `log_correos_enviados`).
- **Resuelto (2026-09):**
  - `MailerService::configurarPhpMailer()` fija `$mail->Timeout = 10`.
  - Nuevo `Core\Response::jsonYContinuar()`: envía el cuerpo, `Content-Length` + `flush()` y
    `fastcgi_finish_request()` / `litespeed_finish_request()` si existen, y **devuelve el
    control** sin cortar el proceso.
  - `MercadoPagoWebhookController::notificar()` responde el `200` a Mercado Pago con
    `Response::jsonYContinuar()` y sólo después envía el correo de confirmación / pago
    recibido. Las demás rutas públicas quedan acotadas por el `Timeout` de 10 s.

### PERF-02 · Exportaciones CSV sin límite ni streaming — Baja

`Reserva::todasAdmin()`, y las equivalentes de cotizaciones y suscriptores, hacen
`SELECT ... ORDER BY creado_en DESC` **sin `LIMIT`**, materializan todo en un array y lo
mapean en memoria antes de `Response::csv()`. Crece linealmente con el histórico. Para el
volumen de una agencia probablemente aguante años, pero conviene paginar la escritura del
CSV (cursor no bufferizado + `fputcsv` fila a fila) antes de que sea un problema.

- **Archivos:** `app/Models/Reserva.php:40-51`, `core/Response.php:50-67`

### PERF-03 · `calendarioDatos`: `anio`/`mes` sin validar — Info

`ReservaAdminController::calendarioDatos()` hace `(int)` sobre la query sin acotar
`mes` a 1-12 ni `anio` a un rango razonable. `Reserva::calendarioMes()` va parametrizado y
un valor absurdo sólo devuelve un array vacío, pero conviene validar por prolijidad y para
no habilitar escaneos triviales.

- **Archivo:** `app/Controllers/Admin/ReservaAdminController.php:60-66`

---

## Accesibilidad y SEO

### A11Y-01 · Sin aviso de contraste WCAG al fijar la paleta — Baja

Ya en `PENDIENTES.md` (A11Y-01). `/admin/colores` y el fondo de bloque
(`ContenidoController`) aceptan cualquier hex válido; un administrador puede dejar
combinaciones con ratio < 4.5:1 sin que nada lo advierta. Recomendación de PENDIENTES:
opción A (advertir con un `Flash`, no bloquear) + helper `App\Helpers\Contraste`.

### A11Y-02 · `alt_text` de imágenes de paquete rellenado con el slug — Baja

`PaqueteAdminController::procesarImagenPortada()` inserta en `imagenes_paquete` con
`'alt' => $slug` (p. ej. `alt="cancun-playa-del-carmen-2"`). Para un lector de pantalla
eso es peor que un `alt` vacío: lee un identificador con guiones. Pedir un texto
alternativo real en el formulario de imagen, o guardar `alt=""` si la portada es
decorativa (el `<h1>` ya nombra el paquete).

- **Archivo:** `app/Controllers/Admin/PaqueteAdminController.php:236-244`

### A11Y-03 · Banner de consentimiento sin `aria-modal` ni trampa de foco — Baja

`app/Views/layouts/public.php:193` usa `role="dialog"` pero no `aria-modal="true"` y no
hay retención de foco mientras está visible; con teclado se puede tabular "detrás" del
banner. Como no bloquea la interacción con la página quizá sea deliberado, pero entonces
`role="region"` con `aria-label` es más honesto que `dialog`.

### SEO-01 · `robots.txt` bloquea `/uploads/` — Baja

`public/robots.txt` tiene `Disallow: /uploads/`. Todas las imágenes servidas al público
(portadas de paquete, imágenes de artículos, y el `og:image` de las fichas cuando apunta a
`/uploads/...`) viven bajo esa ruta. Consecuencias:

- no aparecen en Google Images;
- el validador de resultados enriquecidos y los depuradores de Open Graph (Facebook,
  LinkedIn) no pueden descargar la imagen del `<meta property="og:image">`.

- **Sugerencia:** quitar la línea (el `.htaccess` de `public/uploads/` ya impide ejecutar
  nada y no hay contenido sensible ahí) o acotarla a rutas que no existan.

### SEO-02 · `robots.txt` con `Sitemap:` de producción hardcodeado — Baja

Ya en `PENDIENTES.md` (SEO-02). En un `staging` indexable, el `Sitemap:` apunta al de
producción. Decisión consciente (archivo estático); se deja anotado.

### SEO-03 · `meta description` puede quedar vacía — Info

`PaqueteController::ficha()` y `BlogController::articulo()` pasan
`'description' => $paquete['meta_description'] ?: $paquete['resumen']`. Si ambos son
`NULL`, el layout imprime `<meta name="description" content="">`. `Core\Controller::view()`
sólo aplica el default global cuando la clave está ausente, no cuando llega `null`.
Sugerencia: `?: $meta_description_default` de `configuracion_sitio` como último recurso.

---

## Menores confirmados sin acción (contexto)

- `Validator::telefono` acepta 7-20 caracteres de `[0-9+\s()-]`, incluido `-------` sin
  ningún dígito (ya en PENDIENTES, CAL-05).
- `num_personas`: 1-30 en la reserva, 1-60 en el cotizador (ya en PENDIENTES, CAL-04).
- `codigo_reserva` = `DG-{año}-{id con str_pad(6)}` rompe el *formato* a partir de 10^6
  reservas, no la unicidad (ya en PENDIENTES).
- `database/migrate.php` parte el SQL por `;`: frágil ante `DELIMITER`/triggers; hoy no
  hay ninguno (ya en PENDIENTES).
- `MercadoPagoService::verificarFirmaWebhook`: si llegara una notificación en el formato
  antiguo `?topic=payment&id=X`, el manifiesto de la firma se arma con ese `id`; Mercado
  Pago ya usa siempre `data.id`, así que es un caso muerto.
- `Core\Model::update()` usa el placeholder `:__id`; `assertColumnas` permitiría una clave
  `__id` en `$data` y habría colisión. Ningún llamador lo hace.
