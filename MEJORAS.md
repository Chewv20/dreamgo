# Mejoras funcionales propuestas — Dream Go

Backlog de mejoras funcionales (no seguridad — ver `AUDITORIA.md` para eso) resultado de un
análisis del estado del proyecto el 2026-08-24. Se guarda aquí para no perder el contexto
entre sesiones y para llevar seguimiento de qué se implementó.

## Alto impacto — aprovechan infraestructura que ya existe pero está sin usar

1. **Reserva y pago en línea (self-service)** — `App\Services\ReservaService::crear()` ya
   maneja cupos con `FOR UPDATE`, códigos de descuento y expiración automática por cron, pero
   no hay ninguna ruta pública que lo use: hoy toda reserva la teclea un admin a mano en
   `/admin/reservas/crear`. Falta un flujo público (`/paquetes/{slug}` → elegir salida → datos
   del cliente → pago) y una pasarela de pago para el anticipo (MXN → Stripe / Conekta /
   Mercado Pago, a decidir).
   - Estado: **hecho** (2026-08-24), con una salvedad de prueba explicada abajo. Decisiones
     tomadas con el usuario: se cobra un **anticipo configurable** (no el 100%) vía **Mercado
     Pago** (Checkout Pro, redirect completo — sin SDK de JS en el cliente); el usuario todavía
     no tiene cuenta de Mercado Pago, así que el flujo queda implementado y probado en todo lo
     que no depende de credenciales reales. Plan completo guardado como referencia en
     `C:\Users\artur\.claude\plans\tender-soaring-pillow.md` (histórico de diseño).

     **Piezas nuevas:**
     - `App\Services\MercadoPagoService` (nuevo) — llama la API REST de Mercado Pago con
       `curl` (sin SDK de Composer, mismo criterio "sin frameworks pesados" del resto del
       proyecto): `crearPreferencia()` (Checkout Pro), `obtenerPago()` (el webhook SIEMPRE
       vuelve a pedir el pago a la API en vez de confiar en el cuerpo de la notificación) y
       `verificarFirmaWebhook()` (HMAC-SHA256 sobre el header `x-signature`, formula
       documentada por Mercado Pago).
     - `ReservaService::registrarPagoAprobado()` (nuevo, lo único que se tocó de
       `ReservaService` — `crear/confirmar/cancelar/expirarVencidas` no cambiaron): confirma la
       reserva si seguía `pendiente` y graba `metodo_pago`/`referencia_pago`/`monto_pagado`,
       todo en una transacción con `FOR UPDATE`. Devuelve `true` solo si esa llamada fue la que
       confirmó, para no duplicar el correo de confirmación si Mercado Pago reintenta la
       notificación.
     - `App\Controllers\Public\ReservaPublicaController` (nuevo): `formulario()` (
       `GET /paquetes/{slug}/reservar/{salidaId}`), `crear()` (`POST /reservar`, reutiliza
       `ReservaService::crear()` sin tocarlo) y `gracias()` (`GET /reservar/{codigo}/gracias`).
       Si Mercado Pago no está configurado o la llamada a su API falla, **la reserva y el cupo
       ya quedaron creados igual** (no dependen de Mercado Pago) y se avisa al cliente que se
       le contactará para coordinar el pago — el pago en línea se activa solo con poner las
       credenciales en `.env`, sin tocar código.
     - `App\Controllers\Public\MercadoPagoWebhookController` (nuevo): `POST
       /webhooks/mercadopago`, sin auth ni CSRF (lo llama Mercado Pago, no un navegador). Si
       `MP_WEBHOOK_SECRET` está configurado, rechaza con 401 cualquier notificación sin firma
       válida; si no, deja advertencia en el log pero sigue funcionando (para no bloquear antes
       de tener cuenta). Ignora tópicos que no sean `payment` (ej. `merchant_order`).
     - Nueva clave `porcentaje_anticipo_reserva` en `configuracion_sitio` (migración
       `database/migrations/0004_porcentaje_anticipo_reserva.sql`, default `30`), editable
       desde `/admin/configuracion` junto a `horas_expiracion_reserva`.
     - `.env.example`/`.env`: `MP_ACCESS_TOKEN` y `MP_WEBHOOK_SECRET` (vacíos por defecto).
       `DEPLOY.md` documenta dónde conseguirlos y qué URL registrar como webhook.
     - Botón "Reservar" agregado en `app/Views/public/paquetes/ficha.php` junto a cada salida
       con cupo disponible.

     **Bug real encontrado y corregido durante la prueba:** el formato actual de webhook de
     Mercado Pago manda el id del pago como `data.id` en la query string
     (`?type=payment&data.id=123`), pero PHP convierte automáticamente los puntos de los
     nombres de parámetros de query string en guiones bajos — `$_GET` nunca tiene una clave
     `data.id`, siempre `data_id`. El primer intento leía `$this->request->query('data.id',
     ...)`, que jamás iba a encontrar nada (se hubiera ido siempre por la rama del formato
     viejo/vacío). Se corrigió a leer `data_id`. Confirmado con `parse_str()` de forma aislada
     y luego con una llamada HTTP real al webhook.

     **Probado en vivo contra la base de datos real y contra la API real de Mercado Pago**
     (con `php -S localhost:8090 -t public`):
     - Firma HMAC del webhook: firma correcta aceptada, payment id alterado rechazado, secreto
       incorrecto rechazado, header malformado rechazado (probado de forma aislada, sin red).
     - Formulario público (`GET /paquetes/{slug}/reservar/{salidaId}`) con una salida real:
       200 y monto de anticipo calculado correctamente; con salida de otro paquete o paquete
       inexistente: 404.
     - `POST /reservar` con datos válidos: crea la reserva real (cupo descontado, código
       `DG-2026-0000NN` generado), envía el correo de "reserva pendiente" (ya existente).
     - Con `MP_ACCESS_TOKEN` vacío: cae en el aviso "no pudimos iniciar el pago en línea (aun
       no esta configurado)" sin perder la reserva.
     - Con un `MP_ACCESS_TOKEN` inválido: `crearPreferencia()` hace una llamada real a
       `api.mercadopago.com`, Mercado Pago la rechaza, y el flujo cae en el aviso "no pudimos
       iniciar el pago en linea en este momento" — igual sin perder la reserva.
     - Webhook: tópico `merchant_order` ignorado (200); `payment` sin `MP_ACCESS_TOKEN` (200,
       `ok:false`, log de advertencia); `payment` sin firma con `MP_WEBHOOK_SECRET` configurado
       (401); `payment` con firma válida pero token de Mercado Pago inválido (llega hasta
       `obtenerPago()`, la API real lo rechaza, degrada a 200 `ok:false`).
     - Smoke test de todo el sitio (`/`, `/paquetes`, `/destinos`, `/nosotros`, `/contacto`,
       `/cotizador`, `/mi-reserva`, ficha de paquete, formulario de reserva, `/admin/login`):
       200 en todas, sin regresiones.
     - Limpieza: se borraron las 4 reservas y clientes de prueba creados durante estas pruebas
       y se restauró `cupo_disponible` de la salida usada a su valor original (20/20). `.env`
       restaurado a `MP_ACCESS_TOKEN=`/`MP_WEBHOOK_SECRET=` vacíos.

     **Lo único que NO se pudo probar** (documentado explícitamente, no se finge que se hizo):
     el camino "pago real aprobado → webhook con firma real de Mercado Pago →
     `obtenerPago()` contra un pago real → `registrarPagoAprobado()` → correo de confirmación"
     completo, porque el usuario todavía no tiene cuenta/credenciales de Mercado Pago (ni
     siquiera de sandbox). Cada pieza de ese camino se probó por separado contra la API real o
     de forma aislada (ver arriba), pero falta la prueba end-to-end con un pago genuino.
     **Pendiente:** repetirla en cuanto el usuario tenga credenciales de prueba — crear una
     reserva real, pagar con una tarjeta de prueba de Mercado Pago, y confirmar que la reserva
     pasa a `confirmada` y llega el correo.

2. **Consulta de reserva/cotización por código + email** — página pública tipo "Mi reserva"
   (`codigo_reserva` + email) para ver estado, saldo pendiente y fecha de salida sin llamar.
   No requiere portal de cuenta completo, solo una consulta puntual.
   - Estado: **hecho** (2026-08-24), solo para reservas — las cotizaciones no tienen un
     código público (`cotizaciones.id` es interno), así que se descartó incluirlas en esta
     consulta para no exponer/inventar un identificador nuevo sin que se pidiera.
     `Reserva::porCodigoYEmail()` (nuevo) exige código **y** email exacto de la tabla
     `clientes` — decisión deliberada: `codigo_reserva` es correlativo
     (`DG-2026-000001`, `000002`...) y por sí solo sería adivinable por fuerza bruta
     secuencial; exigir también el email exacto lo evita sin tener que montar rate-limiting
     nuevo para un endpoint de solo lectura.
     Nuevo `App\Controllers\Public\ReservaConsultaController` (`mostrar`/`buscar`) + rutas
     `GET /mi-reserva` y `POST /mi-reserva` (con CSRF, mismo patrón que `CotizadorController`)
     + vista `app/Views/public/reserva-consulta/formulario.php` + enlace agregado al footer
     (`app/Views/layouts/public.php`).
     Probado en vivo end-to-end contra la base de datos real: se creó una reserva real con
     `ReservaService::crear()` (salida con cupo real, sin tocar código de producción), se
     probaron los 3 casos por HTTP con `php -S localhost:8090 -t public` — código+email
     correctos (200, muestra estado/fecha/monto pagado), código inexistente con email válido
     (200, mensaje "no encontramos"), y campos inválidos (200, errores de validación por
     campo) — y al final se borró la reserva/cliente de prueba y se restauró el
     `cupo_disponible` de la salida usada, dejando la base como estaba antes de la prueba.

3. **Buscador y filtros reales en el catálogo** — `App\Models\Paquete::publicadosConFiltros()`
   solo filtra por categoría/tipo hoy (`app/Controllers/Public/PaqueteController.php`).
   Agregar texto libre (título/resumen), rango de precio y duración es una extensión del mismo
   método.
   - Estado: **hecho** (2026-08-24). `Paquete::clausulaFiltrosPublicados()` ahora acepta `q`
     (LIKE sobre `titulo`/`resumen`), `precio_min`/`precio_max` (sobre `precio_desde`) y
     `duracion` (rangos fijos `1-3`/`4-7`/`8-14`/`15+` sobre `duracion_dias`).
     `PaqueteController::catalogo()` valida/sanea los query params (`ctype_digit` para precios,
     whitelist para `duracion`) y los pasa a la vista; `catalogo.php` agrega los inputs al
     formulario y `queryExtra` de paginación los conserva entre páginas.
     Nota real encontrada al probar: el primer intento de `q` usaba el mismo placeholder
     `:q` dos veces en el `LIKE ... OR ... LIKE`, y `Database` usa `PDO::ATTR_EMULATE_PREPARES
     => false` (prepares nativos), que no permite repetir un placeholder con nombre — daba
     `SQLSTATE[HY093]: Invalid parameter number` en cualquier búsqueda por texto. Se corrigió
     usando dos placeholders (`:q_titulo`, `:q_resumen`) con el mismo valor. Probado por CLI
     contra la base de datos real (combinaciones de los 4 filtros) y por HTTP contra
     `php -S localhost:8090 -t public` (catálogo simple y con cada filtro, valores reflejados
     correctamente en el formulario tras el submit).

## Medio impacto

4. **Reseñas verificadas de clientes** — los "testimonios" del home hoy son contenido estático
   editado a mano en `bloques_pagina`. Con `reservas.estado = 'confirmada'` ya identificado por
   cliente, se puede pedir una reseña real post-viaje (reusando el cron de recordatorio) y
   mostrarla ligada al paquete.
   - Estado: **hecho** (2026-08-24). Decisiones tomadas con el usuario: requiere **aprobación de
     un admin** antes de publicarse, el nombre público se muestra como "Nombre + inicial de
     apellido" (privacidad), y el formato es **estrellas (1-5) + texto**.
     Tabla nueva `resenas` (migraciones `0006`-`0008`: tabla+permisos, enum de
     `log_correos_enviados`, config `dias_solicitud_resena`) ligada a `reserva_id` (`UNIQUE`,
     una reseña por reserva), `cliente_id`, `paquete_id`. Acceso público sin cuenta reusando el
     mismo patrón de `Reserva::porCodigoYEmail()` que ya usa `/mi-reserva` (código+email, sin
     inventar un sistema de tokens nuevo) vía `App\Controllers\Public\ResenaPublicaController`
     (`GET`/`POST /resena/{codigo}`). Nuevo cron `cron/solicitar_resena.php` (mismo patrón que
     `recordatorio_viaje.php`) pide la reseña N días después de terminado el viaje
     (`COALESCE(fecha_regreso, fecha_salida)`), con doble guarda anti-duplicado (log de correos
     + `NOT EXISTS` contra `resenas`). Moderación en `/admin/resenas`
     (`ResenaAdminController`, permisos `resenas.ver`/`resenas.gestionar`, nuevos). Reseñas
     aprobadas se muestran en `app/Views/public/paquetes/ficha.php` reusando el markup de
     `tarjeta-testimonio` ya existente en el home.
     Probado en vivo end-to-end: cron con datos de prueba (confirma que no reenvía si ya hay
     reseña o si el correo ya se envió), formulario público (código+email inválido → error
     genérico; válido pero viaje no terminado o reserva no confirmada → error explicativo;
     reenvío tras ya dejar reseña → idempotente sin duplicar), moderación admin, visualización
     en la ficha del paquete, y gateo de permisos con un rol sin `resenas.ver`. Datos de prueba
     borrados al terminar.

5. **Dashboard con métricas de negocio** — `DashboardController` (`app/Controllers/Admin/DashboardController.php`)
   solo cuenta filas. Agregar ingresos por periodo (`SUM(precio_total)` de reservas
   confirmadas), tasa de conversión cotización→reserva, y ocupación por salida
   (`cupo_disponible/cupo_maximo`).
   - Estado: **hecho** (2026-08-24). Ingresos por periodo se filtra por `confirmada_en` (no
     `creado_en`): el ingreso se realiza cuando la reserva se confirma, no cuando se solicita
     (nuevo índice `idx_reservas_estado_confirmada`, migración `0005`). Tasa de conversión se
     aproxima como `COUNT(estado='convertida') / COUNT(*)` sobre `cotizaciones` en el periodo —
     no hay FK real entre `cotizaciones` y `reservas`, así que es una aproximación, documentada
     en el código. Filtro de periodo (`?desde=&hasta=`) validado con `preg_match` en el
     controller, con fallback al mes actual si la entrada es inválida o `desde > hasta`. Sin
     librerías de gráficos nuevas (el proyecto no tenía ninguna): tarjetas numéricas + tabla de
     ocupación de próximas salidas con badges de color (verde/ámbar/rojo por % de ocupación).
     Probado en vivo contra la base de datos real: filtro con basura en la URL y `desde > hasta`
     caen al fallback sin error 500, badges calculan bien, y un rol sin `reservas.ver`/
     `cotizaciones.ver` no ve las tarjetas nuevas.

6. **Exportar a CSV** en listados de reservas/cotizaciones — para contabilidad o CRM externo.
   - Estado: **hecho** (2026-08-24). Nuevo `Core\Response::csv()` (BOM UTF-8 para que Excel no
     rompa acentos, headers `Content-Disposition` correctos) reusado luego por la exportación de
     suscriptores (#7). Exporta el listado completo (no solo la página visible) vía
     `Reserva::todasAdmin()`/`Cotizacion::todasAdmin()`. Bug real encontrado y corregido durante
     la prueba: PHP 8.4+ marca como deprecated el `$escape` implícito de `fputcsv()`, y ese aviso
     se filtraba dentro del propio archivo CSV descargado, corrompiéndolo — se corrigió pasando
     el parámetro explícito. Probado en vivo con datos reales (incluyendo texto con comas y
     comillas, para validar el escapado) y confirmando que la ruta `/admin/reservas/{id}` sigue
     funcionando tras agregar la ruta de exportación antes en el router.

7. **Newsletter / alertas de ofertas** — captar email en el home, tabla nueva `suscriptores`
   con opt-in, reusar `MailerService` para mandar ofertas nuevas.
   - Estado: **hecho** (2026-08-24). Decisiones tomadas con el usuario: **doble opt-in**
     (confirmación por correo antes de quedar activo, evita que alguien suscriba el correo de
     otra persona sin su consentimiento) y el formulario vive en una **sección propia del home**
     (no en el footer global). Tabla nueva `suscriptores` (migraciones `0009`-`0010`) con
     `token` único que sirve tanto para el link de confirmación (`/suscribir/confirmar/{token}`)
     como para el de baja (`/suscribir/baja/{token}`, incluido en cada correo de oferta — buena
     práctica básica de correo masivo, mismo costo que ya requería el token). "Ofertas" en el
     código es literalmente `App\Models\CodigoDescuento`/`OfertaAdminController`: se agregó un
     botón manual "Enviar a suscriptores" en `/admin/ofertas` (no automático al guardar, para
     evitar disparos accidentales), reusando el permiso `ofertas.gestionar` ya existente; solo
     nuevo permiso agregado: `suscriptores.ver` para el listado admin
     (`SuscriptorAdminController`, con exportación CSV reusando `Core\Response::csv()` de #6).
     Probado en vivo end-to-end: alta con email nuevo (normalizado a minúsculas), confirmación
     por token, reenvío con email ya confirmado (idempotente, sin duplicar), baja, envío manual
     de aviso de oferta registrado en `log_correos_enviados`, y gateo de permisos. Datos de
     prueba borrados al terminar.

## Bajo impacto / pulido

8. **Multi-moneda real** — el campo `moneda` existe por paquete pero no hay conversión ni
   selector. Solo vale la pena si venden a extranjeros.
   - Estado: **hecho** (2026-08-24). El catálogo tenía 8 paquetes, todos en MXN — el propio
     escenario que este ítem marcaba como "no vale la pena todavía" — pero el usuario pidió
     implementarlo igual. Decisión tomada con el usuario entre 2 interpretaciones muy distintas:
     esto es **moneda real por paquete** (el admin elige USD/EUR/etc. al crear un paquete), no
     un conversor de moneda de visualización para visitantes con tipo de cambio en vivo (esa
     alternativa habría requerido una API externa nueva y tenía riesgo real de discrepancia
     entre el precio "convertido" que ve el visitante y lo que Mercado Pago cobra realmente).
     Hallazgo clave: `MercadoPagoService` ya leía `$paquete['moneda']` dinámicamente y cobraba
     en esa moneda — el hueco real era que el admin no podía asignarla
     (`PaqueteAdminController::datosFormulario()` nunca incluía `moneda`, y el label del form
     decía literalmente "Precio desde (MXN)" a las patadas). No hizo falta ninguna migración: la
     columna `moneda CHAR(3)` ya existía en `paquetes`.
     Riesgo real encontrado y mitigado durante el diseño (no en el pedido original):
     `reservas`/`salidas` no guardan su propia moneda, la heredan vía join a `paquetes.moneda` —
     si un admin cambiara la moneda de un paquete después de que ya existieran reservas, esas
     reservas viejas empezarían a mostrarse con el código de moneda nuevo sobre un monto
     histórico en la moneda vieja. Se agregó `Paquete::tieneReservas()` y el campo moneda queda
     bloqueado (`disabled` + servidor ignora cualquier valor manipulado) en el form de edición
     una vez que el paquete tiene al menos una reserva.
     También se corrigió una inconsistencia existente: varias vistas mostraban el total de una
     reserva sin el código de moneda (`admin/reservas/detalle.php`, `/mi-reserva`, los 2 correos
     transaccionales, el export CSV) — se agregó `p.moneda AS paquete_moneda` a los 3 métodos de
     `Reserva` que hacen join a `paquetes` y alimentan esas vistas.
     Probado en vivo end-to-end: paquete creado en USD se ve correcto en ficha/catálogo/admin;
     selector sigue editable sin reservas; tras crear una reserva de prueba el campo se bloquea
     y un POST manipulado a mano intentando cambiarlo es ignorado por el servidor (verificado
     que la moneda en BD no cambió); una moneda fuera de la whitelist es rechazada; el detalle
     admin, `/mi-reserva` y el CSV ya muestran "USD" junto al monto. Datos de prueba borrados al
     terminar.

9. **Comparador de paquetes** (2-3 lado a lado) — útil si el catálogo crece; prematuro con
   pocos paquetes.
   - Estado: **hecho** (2026-08-24), con una salvedad de prueba explicada abajo. Mismo caso que
     #8: el catálogo tiene 8 paquetes (cabrían todos en una sola página), pero el usuario pidió
     implementarlo igual. Decisión tomada con el usuario sobre cómo se elige qué comparar:
     **checkboxes + `localStorage` + barra flotante** (en vez de un formulario GET sin JS
     limitado a una sola página de resultados) — esto es, con conocimiento explícito del
     trade-off, la primera vez que el sitio público usa JavaScript con estado y `localStorage`
     (antes `site.js` solo tenía menú móvil, scroll-reveal y service worker).
     `Paquete::porSlugsPublicados()` (nuevo, no existía ningún método batch, solo el singular
     `porSlugPublicado()`). Ruta `GET /comparar?paquetes=slug1,slug2,slug3`
     (`PaqueteController::comparar()`), tope defensivo de 3 y descarte de slugs
     inválidos/no publicados tanto en cliente como en servidor; con menos de 2 válidos muestra un
     mensaje en vez de la tabla (sin error 500). Tabla comparativa con `incluye`/`no_incluye`
     aplanados vía `strip_tags()` (son HTML libre en la ficha, un bloque de HTML variable
     rompería la alineación de columnas).
     **Bug real encontrado y corregido durante la prueba:** la vista `comparar.php` declaraba
     una función PHP con nombre global para aplanar texto. `Controller::view()` usa `require`
     (no `require_once`), y el servidor embebido de PHP no forkea por request — una segunda
     visita a `/comparar` en el mismo proceso habría fallado con "Cannot redeclare function". Se
     corrigió usando un closure local en vez de una función con nombre; confirmado con dos
     requests consecutivas al mismo proceso que ya no rompe.
     Probado por HTTP: filtro sin parámetros, con slugs válidos, con duplicados/espacios, con un
     slug inexistente mezclado con uno válido, y con más de 3 slugs (recorta a 3) — todos sin
     errores PHP. **Lo que no se pudo probar:** la interacción real en navegador (marcar
     checkboxes, que la barra flotante aparezca/actualice, que la selección persista entre
     páginas via `localStorage`, quitar chips) — no había herramienta de navegador/Playwright
     disponible en el entorno para hacer clicks reales; la lógica de `initComparador()` se
     revisó a mano con cuidado pero queda pendiente que el usuario la pruebe en su navegador.

## Orden de trabajo acordado

Se empieza por el grupo de **alto impacto**, en este orden (decidido 2026-08-24):

1. **#3 Buscador y filtros** — primero, es autocontenido y sin dependencias externas.
2. **#2 Consulta de reserva por código + email** — segundo.
3. **#1 Reserva y pago en línea** — al final. **Pasarela decidida: Mercado Pago** (Checkout Pro).

Progreso y decisiones de cada una se documentan aquí a medida que se implementan.

**Las 3 mejoras de alto impacto ya están implementadas** (2026-08-24), en el orden: #5
Dashboard → #6 Exportar CSV → #4 Reseñas verificadas → #7 Newsletter (decidido con el usuario:
Dashboard primero por ser autocontenido y sin tablas nuevas; el resto se abordó una mejora a la
vez, probando y revisando cada una antes de seguir con la siguiente).

**Las 4 mejoras de medio impacto ya están implementadas** (2026-08-24). El grupo de bajo
impacto (#8 y #9) se implementó también por decisión explícita del usuario, pese a que ambos
ítems ya estaban marcados como "no vale la pena todavía" con el catálogo actual (8 paquetes,
todos en MXN) — orden: #9 Comparador de paquetes → #8 Multi-moneda real.

**Las 9 mejoras del backlog están implementadas.** No queda ningún ítem pendiente en
`MEJORAS.md`; próximos cambios funcionales requieren un análisis nuevo del estado del proyecto.

## Segunda ronda — análisis 2026-08-26

Revisión nueva del estado del proyecto (funciones actuales y dónde extender). Prioridades
detectadas: cerrar el ciclo de venta (comprobante PDF, cobro del saldo pendiente), tracking
de origen de lead (UTM), CRM ligero de cotizaciones + bitácora de acciones admin, y
SEO/contenido (reseñas agregadas + schema.org, blog de destinos).

**Hechos en esta ronda:** #1 Comprobante PDF, #2 Cobro del saldo pendiente (2026-08-26),
#3 Atribución de leads / UTM, #3-bis GA4 + Meta Pixel, #3-ter banner de consentimiento +
Aviso de Privacidad, #4-a Bitácora de acciones admin, #4-b CRM ligero de cotizaciones,
#5-a Reseñas agregadas + schema.org y #5-b Blog de destinos (2026-08-27).
**La segunda ronda está completa.** Próximos cambios funcionales requieren un análisis nuevo
del estado del proyecto.
— detallados en la sección "Pendiente de la segunda ronda" más abajo.

### 1. Comprobante / voucher PDF de la reserva

- Estado: **hecho** (2026-08-26). Decisión tomada con el usuario: librería **`dompdf/dompdf`**
  (PHP puro, MIT, en `require` porque `DEPLOY.md` usa `composer install --no-dev`), frente a
  la alternativa "sin librería" (página imprimible), que no permitía adjuntar el PDF al correo.

  **Piezas nuevas:**
  - `App\Services\ComprobanteReservaService` — `generarPdf(array $reserva)` (bytes del PDF) y
    `nombreArchivo()`. Render server-side de `app/Views/comprobantes/reserva.php` con estilos
    inline propios (dompdf no comparte la CSP del sitio) y fuente DejaVu (incluida en dompdf,
    sin escritura extra en disco). `isRemoteEnabled` off.
  - Migración `0015_token_publico_reservas.sql` — `reservas.token_publico CHAR(32) NULL UNIQUE`
    + backfill de filas existentes; reflejada en `schema.sql`. `ReservaService::crear()` lo
    genera con `bin2hex(random_bytes(16))`. Sirve para que el link de descarga en el correo no
    sea enumerable a partir del `codigo_reserva` correlativo (mismo criterio que el token de
    `suscriptores`/`resenas`).
  - `Reserva::porCodigoYToken()` (nuevo) — exige código + token de 32 hex; misma forma que
    `conDetalle()`.
  - `Core\Response::archivo(nombre, contenido, mime)` — descarga de bytes crudos (precedente:
    `Response::csv()`); descarta buffers pendientes para no arriesgar el binario con el rewrite
    de rutas de `public/index.php`.
  - Rutas: `GET /reserva/{codigo}/comprobante?t={token}` (público, 404 si no coincide o la
    reserva no está `confirmada`; sin CSRF por ser GET, sin rate-limit propio porque el token
    ya hace el link no adivinable) y `GET /admin/reservas/{id}/comprobante`
    (`reservas.ver`).
  - `MailerService::enviarConfirmacionReserva()` adjunta el PDF (`addStringAttachment`) y el
    cuerpo del correo incluye el link con token; si dompdf falla, el correo se manda igual
    (log de advertencia). `enviar()` recibió un parámetro opcional `$adjunto`.
  - Botón "Descargar comprobante" en `app/Views/admin/reservas/detalle.php` (si `confirmada`)
    y en el resultado de `/mi-reserva` (ahí el email ya está verificado; el link lleva token).
  - Test `tests/Unit/Services/ComprobanteReservaServiceTest.php` (3 casos, sin BD: precarga la
    cache de `ConfiguracionSitio` por reflexión). Suite: 24 tests en verde.

  **Nota real de la prueba:** `pdftotext` en modo `-layout`/plano reordenaba el texto (dompdf
  emite primero toda la columna de etiquetas y luego la de valores), lo que hacía *parecer*
  que la tabla estaba desalineada. Con `pdftotext -table` (reconstrucción por coordenadas) se
  confirmó que el PDF renderiza bien: etiqueta a la izquierda, valor a la derecha en la misma
  línea.

  **Probado en vivo** (`php -S localhost:8090 -t public` + MariaDB real): reserva de prueba
  creada y confirmada, PDF válido (`%PDF-1.7`, ~24 KB) con código, titular, total, anticipo y
  **saldo pendiente** (= total − pagado); ruta pública con token válido (200,
  `application/pdf`, `Content-Disposition: attachment`), token inválido / sin token / código
  inexistente / reserva `pendiente` → 404; ruta admin sin sesión → 302 a login, y con llamada
  directa al controlador → PDF y camino 404 correctos; `/mi-reserva` muestra el botón solo si
  `confirmada`; `MailerService` genera y adjunta el PDF sin error (el envío falla solo por no
  haber SMTP en local, como el resto de los correos). Smoke test del sitio: 200 en todas.
  Datos de prueba borrados y `cupo_disponible` de la salida restaurado al terminar.

  **Lo que NO se pudo probar en local:** el correo real llegando con el adjunto (no hay SMTP
  configurado — misma limitación que todos los correos del proyecto, ver `AUDITORIA.md`).

### 2. Cobro del saldo pendiente

- Estado: **hecho** (2026-08-26). Continuación natural del comprobante (#1): ahora que el
  comprobante muestra el saldo, el cliente puede pagarlo en línea.

  **Piezas nuevas:**
  - Tabla `pagos_reserva` (migración `0016`, + `schema.sql`) — historial de pagos de una
    reserva (anticipo + saldo) con `UNIQUE(referencia_pago)` como guarda anti-duplicado ante
    los reintentos de notificación de Mercado Pago. `reservas.monto_pagado` pasa de un
    *overwrite* con el último pago a `SUM(pagos_reserva.monto)`.
  - `ReservaService::registrarPagoAprobado()` reescrito: aditivo, con dedup, acepta `concepto`
    (`anticipo`|`saldo`) y devuelve un string (`confirmada` / `registrada` / `duplicada` /
    `insuficiente`) en vez de `bool`, para que el webhook sepa qué correo mandar. `crear()`
    ya no cambió. Nuevo helper puro `ReservaService::parseReferenciaExterna()` (unit-testeado,
    6 casos): descompone el `external_reference` de Mercado Pago — `"13"` (anticipo,
    retrocompatible con lo ya emitido) o `"13:saldo"`.
  - `MercadoPagoService::crearPreferencia()` acepta `$concepto`: cambia el título del ítem, el
    `external_reference` y el `back_url` (`?concepto=saldo`).
  - `App\Controllers\Public\PagoSaldoController` (nuevo): `GET /reserva/{codigo}/pagar-saldo?t={token}`
    (página con el saldo + botón) y `POST` (crea la preferencia MP del saldo y redirige a
    Checkout Pro). Gateado por código + `token_publico` (mismo criterio que el comprobante),
    CSRF en el POST, rate-limit `pagar_saldo` (20/IP/30min, agregado a `RateLimiter`). El
    monto lo calcula el servidor (`precio_total - monto_pagado`), nunca viene del cliente.
    Degradación elegante si MP no está configurado o su API falla (igual que el flujo de
    anticipo).
  - `MercadoPagoWebhookController` parsea el concepto y, según el resultado de
    `registrarPagoAprobado()`, manda `enviarConfirmacionReserva` (confirmó ahora) o
    `enviarPagoRecibido` (pago sobre reserva ya confirmada, típico del saldo).
  - `MailerService::enviarRecordatorioSaldo()` y `::enviarPagoRecibido()` (con comprobante
    actualizado adjunto). Vistas `emails/recordatorio_saldo.php` y `emails/pago_recibido.php`.
    Enum de `log_correos_enviados` +`recordatorio_saldo`/`pago_recibido` (migración `0017`).
  - Cron `cron/recordatorio_saldo.php` (patrón de `recordatorio_viaje.php`): reservas
    `confirmada` con `monto_pagado < precio_total` cuya salida es en N días
    (`dias_recordatorio_saldo`, default 7, migración `0017` + `seed_demo.sql` + editable en
    `/admin/configuracion`), con dedup por `log_correos_enviados`. Sube a **9 crons**
    (`cron/README.md`, `DEPLOY.md`, `README.md`).
  - `/mi-reserva` muestra el saldo pendiente + botón "Pagar saldo"; `admin/reservas/detalle.php`
    muestra pagado/saldo + la tabla de `pagos_reserva`.

  **Probado en vivo** (`php -S` + MariaDB real): con una reserva de prueba (total 23 000,
  anticipo 30 % = 6 900, saldo 16 100) —
  - `registrarPagoAprobado` con el anticipo → `confirmada`, `monto_pagado=6900`;
  - reintento del mismo `referencia_pago` → `duplicada`, `monto_pagado` sin cambios, sin fila
    nueva en `pagos_reserva`;
  - pago del saldo → `registrada`, `monto_pagado=23000` (suma), estado sigue `confirmada`,
    2 filas en `pagos_reserva` (anticipo + saldo);
  - pago menor al anticipo → `insuficiente`, reserva queda `pendiente`, pago igual registrado,
    línea en el log.
  - HTTP: `GET /reserva/{codigo}/pagar-saldo` con token válido → 200 con saldo y botón; token
    inválido → 404; reserva pendiente → "aún no confirmada"; reserva sin saldo → "todo
    listo". `POST` sin CSRF → 403; `POST` con CSRF y MP sin configurar → 200 con mensaje de
    error, sin romperse.
  - `/mi-reserva` muestra el botón "Pagar saldo" solo cuando hay saldo; webhook con
    `merchant_order` sigue ignorado (200).
  - `cron/recordatorio_saldo.php`: encuentra la reserva `confirmada` con saldo cuya salida
    cae en N días y excluye las ya pagadas por completo. Smoke test del sitio: 200 en todo.
    Suite: 30 tests en verde. Datos de prueba borrados y cupo restaurado al terminar.

  **Lo que NO se pudo probar en local** (sin credenciales de Mercado Pago): el redirect real
  a Checkout Pro y el webhook real de un pago de saldo aprobado end-to-end. Cada pieza se
  probó por separado (parser unit-testeado, `registrarPagoAprobado` contra la BD real, la
  degradación elegante cuando MP no responde).

  **Follow-ups anotados** (no en este alcance): registrar a mano un pago de saldo offline
  (efectivo/transferencia) desde el panel; pagos parciales de importe libre; reembolsos.

### 3. Atribución de leads / UTM

- Estado: **hecho** (2026-08-27). Objetivo: saber de qué canal viene cada lead
  (cotización) y cada conversión (reserva) para poder priorizar el resto del backlog con
  datos.

  **Piezas nuevas:**
  - 7 columnas `NULL` en `cotizaciones` **y** `reservas`: `utm_source`, `utm_medium`,
    `utm_campaign`, `utm_term`, `utm_content`, `referrer`, `landing_page` (migración `0018`
    con `ADD COLUMN IF NOT EXISTS` — MariaDB 10.6, re-ejecutable — + `schema.sql` +
    `idx_cotizaciones_utm_source`).
  - `App\Helpers\Atribucion`: `sanitizar(array)` (quita control chars, colapsa espacios,
    recorta a 100/255, vacío→null, ignora claves ajenas) y `desdeFormulario(array,
    ?refererHeader)` (usa el header `Referer` como respaldo si el form no trajo `referrer`).
  - `site.js` → `initAtribucion()`: en la primera carga con `utm_*` en la URL, guarda el set
    (utm + `referrer` externo + `landing_page`) en `sessionStorage` **una sola vez por
    sesión** (first-touch); al cargar un `form[data-atribucion]` (cotizador y reservar)
    inyecta esos valores como `<input hidden>` si el form no los trae ya. `sw.js` → `dreamgo-v6`
    (precachea `site.js`).
  - `CotizadorController::enviar()` mete la atribución saneada en el `insert`;
    `ReservaPublicaController::crear()` la pasa por `$datos['atribucion']` a
    `ReservaService::crear()`, que la agrega al `INSERT` de `reservas` (reservas creadas
    desde el panel quedan con las 7 en `null`).
  - Panel: `/admin/cotizaciones` con columna "Origen" (`utm_source` + medium/campaign, o
    "Directo") y filtro `?origen=` (dropdown de fuentes distintas; valor especial
    `Cotizacion::ORIGEN_DIRECTO` = sin UTM). CSV con las 7 columnas nuevas. Dashboard: bloque
    "Cotizaciones por origen (periodo)" (`Cotizacion::porOrigenPeriodo`), gateado por
    `cotizaciones.ver`.
  - `Cotizacion`: `adminListado`/`contarTotal` con filtro `?string $origen`;
    `fuentesDistintas()`; `porOrigenPeriodo()`.

  **Probado en vivo** (`php -S` + MariaDB real): `POST /cotizador` con los 7 campos → se
  guardan todos; sin UTM pero con header `Referer` → `referrer` = header, resto null;
  `landing_page` con `/ruta?query` se guarda tal cual; `utm_campaign` con `\n`/`\t`/espacios
  → saneado a una línea. `POST /reservar` con UTM → la reserva los guarda. Filtro admin
  `origen=google` / `__directo__` / sin filtro devuelven los conteos correctos;
  `porOrigenPeriodo` agrupa "Directo" + fuentes. Vistas admin y dashboard renderizan.
  `site.js` servido incluye `initAtribucion`; smoke test 200 en todo. Suite: **36 tests**
  (+6 de `AtribucionTest`). Datos de prueba borrados y cupo restaurado.

  **Limitación conocida:** sin JS, los `utm_*` se pierden al navegar; solo se captura
  `referrer` (header).

### 3-bis. GA4 + Meta Pixel

- Estado: **hecho** (2026-08-27). Era lo que #3 había dejado "fuera de alcance"; el usuario
  pidió hacerlo a continuación.

  **Piezas nuevas:**
  - CSP relajada a propósito en `config/config.php`: `script-src` ahora lleva
    `'nonce-<aleatorio-por-petición>'` (constante `CSP_NONCE`, `random_bytes(16)`) +
    `https://www.googletagmanager.com https://connect.facebook.net`; `img-src`/`connect-src`
    ampliados. **Sin** `'unsafe-inline'`. Detalle y riesgo residual en `AUDITORIA.md`
    ("Cambio deliberado en la CSP", 2026-08-27).
  - `App\Helpers\Analytics`: `ga4Id()` / `metaPixelId()` validan el formato al leer
    (`/^G-[A-Z0-9]{4,20}$/`, `/^[0-9]{6,20}$/`) — un valor con comillas o `<>` se descarta y
    nunca llega al `<script>`. `habilitado()`.
  - `app/Views/partials/analytics.php` (incluido en `<head>` desde `layouts/public.php`):
    emite el loader de `gtag.js` + bootstrap nonce'd, y el snippet de `fbevents.js` + `<img>`
    `<noscript>`. No emite nada si no hay IDs configurados. Solo en el layout público (el
    panel usa `layouts/admin.php` / `blank.php`, sin analítica).
  - Eventos de conversión (inline nonce'd): `generate_lead` + `Lead` en
    `public/cotizador/gracias.php`; `purchase` + `Purchase` (con `transaction_id` = código,
    **sin valor monetario** para no exponer importes por código adivinable) en
    `public/reservar/gracias.php` solo cuando el back_url de Mercado Pago trae
    `status=approved` (`ReservaPublicaController::gracias()` pasa `$esAprobado`).
  - Config: `GA4_MEASUREMENT_ID` / `META_PIXEL_ID` en **`.env`** (`.env.example` documentado).
    `App\Helpers\Analytics` los lee de `$_ENV` y los valida por formato. Vacío = desactivado.
    Se eligió `.env` sobre `configuracion_sitio` a pedido del usuario: se cambia una sola vez
    y queda junto a las otras credenciales de integración (`MP_*`, `SMTP_*`).
  - De paso se agregó `dias_recordatorio_saldo` a `ConfiguracionController` / la vista de
    `/admin/configuracion`, que la ronda #2 daba por editable pero no estaba en la lista.

  **Probado en vivo** (`php -S` + MariaDB real): con IDs vacíos, la home no trae ninguna
  referencia a gtm/facebook y la CSP igual incluye el nonce y los hosts; al configurar
  `G-TEST12345` / `1234567890123456` aparecen el loader de GA4, `fbq('init', ...)` y el
  bootstrap con `nonce="..."` **idéntico** al `nonce-...` de la cabecera CSP de la *misma*
  respuesta; con IDs de formato inválido no se emite nada; `cotizador/gracias` dispara
  `generate_lead`/`Lead`; `reservar/gracias?status=approved` dispara `purchase`/`Purchase` y
  sin `status` no; `/admin/login` nunca carga analítica. Smoke test 200 en todo. Suite:
  **50 tests** (+14 de `AnalyticsTest`, incluyen casos de inyección en los IDs). Config
  restaurada a vacío y datos de prueba borrados.

### 3-ter. Banner de consentimiento + Aviso de Privacidad

- Estado: **hecho** (2026-08-27). Cierra el follow-up no técnico de 3-bis.

  **Piezas nuevas:**
  - `app/Views/partials/analytics.php` reescrito: ya **no carga** gtag.js ni fbevents.js al
    renderizar. Define `window.dreamgoAnalitica` (IDs) y `window.dreamgoCargarAnalitica()`
    (inyector). Solo carga de una vez si `localStorage['dreamgo_consentimiento'] === 'granted'`
    de una visita anterior. Se quitó el `<noscript><img>` del pixel (sin JS no se puede
    consentir).
  - Banner en `layouts/public.php` (solo si `Analytics::habilitado()`), estilos
    `.banner-consentimiento` en `site.css`, lógica `initConsentimiento()` en `site.js`:
    muestra el banner si no hay decisión guardada; "Aceptar" → guarda `granted` + llama al
    inyector; "Rechazar" → guarda `denied`. La decisión persiste en `localStorage`; para
    cambiarla, borrar los datos del sitio.
  - Los eventos `purchase` / `generate_lead` de las páginas de "gracias" ya estaban
    guardados con `if (typeof gtag/fbq...)`, así que sin consentimiento simplemente no se
    disparan.
  - `App\Controllers\Public\LegalController` + `GET /aviso-de-privacidad` + vista
    `public/legal/aviso-privacidad.php`: **plantilla genérica LFPDPPP** (responsable, datos
    recabados, finalidades primarias/secundarias, transferencias —incluidas Google/Meta—,
    cookies, derechos ARCO, conservación, cambios, aceptación). Toma dirección/correo/teléfono
    de `configuracion_sitio`; el resto son marcadores `[CORCHETES]`. Enlazada desde el footer
    y desde el banner. Agregada a `SitemapService::urlsEstaticas` (priority 0.2). `sw.js` →
    `dreamgo-v7`.

  **IMPORTANTE antes de producción:** la vista `aviso-privacidad.php` es una plantilla. Hay
  que (1) reemplazar todos los `[CORCHETES]` con los datos reales de la empresa (razón social,
  RFC, domicilio fiscal, correo del departamento de datos, fecha) y (2) que un abogado la
  revise contra la operación real. Comentario recordatorio al inicio del archivo de la vista.

  **Probado en vivo:** con IDs vacíos no hay banner ni partial; con IDs configurados el
  partial NO emite ninguna petición a Google/Meta hasta pulsar "Aceptar" (verificado con una
  simulación en Node del flujo `partial + initConsentimiento + click`: 0 scripts inyectados
  al cargar, 2 tras aceptar, `localStorage=granted`, banner oculto). `/aviso-de-privacidad`
  200, en el footer y en el sitemap (tras regenerar). Suite: 50 tests. Config restaurada.

  **Pendiente para la UE (si aplica algún día):** el banner es aceptar/rechazar simple; para
  RGPD haría falta Google Consent Mode v2 y granularidad por finalidad.

### 4-a. Bitácora de acciones admin

- Estado: **hecho** (2026-08-27). Primera mitad del ítem #4 (la otra mitad, el CRM ligero de
  cotizaciones, queda pendiente como 4-b abajo). Se decidió con el usuario: bitácora primero
  (menos riesgo, solo escribe registros), CRM después.

  **Piezas nuevas:**
  - Tabla `bitacora_admin` (migración `0019` + `schema.sql`): `usuario_id` (FK
    `ON DELETE SET NULL`), `usuario_nombre` desnormalizado (el registro sigue legible aunque
    se borre el usuario), `accion`, `entidad_tipo`, `entidad_id`, `detalle` (texto corto),
    `ip`, `creado_en`. Decisión: tabla propia (no log a archivo).
  - Permiso `bitacora.ver` (migración `INSERT IGNORE` + asignado al rol Administrador;
    también en `seed_demo.sql`).
  - `App\Helpers\Auditoria::registrar(accion, entidadTipo?, entidadId?, detalle?)`: toma
    `usuario_id`/`usuario_nombre` de `Core\Auth` y la IP de `$_SERVER`; trunca a los largos
    de columna; **todo en try/catch** — una bitácora rota nunca tumba la acción auditada.
  - `App\Models\Bitacora` + `BitacoraController` + `GET /admin/bitacora` (`bitacora.ver`) +
    vista con filtro por `accion` y paginación. Enlace en el sidebar (`layouts/admin.php`).
  - Cableado en: `reserva.crear` / `reserva.confirmar` / `reserva.cancelar`,
    `cotizacion.estado` (con "anterior -> nuevo"), `rol.crear` / `rol.eliminar` /
    `rol.permisos`, `usuario.crear` / `usuario.editar`, `configuracion.guardar` (solo si
    cambió alguna clave, y lista cuáles), `paquete.crear` / `paquete.editar` /
    `paquete.archivar`, `resena.estado`, `oferta.enviar_suscriptores`.
  - `cron/limpiar_intentos_login.php` ahora también purga `bitacora_admin` de más de 12
    meses (se conserva más que los intentos por ser info de auditoría). `cron/README.md`
    actualizado.
  - **No se audita el login** (exitoso o fallido): ya está en `intentos_login` (auditoría
    2026-08-25) y duplicarlo no aporta.

  **Probado en vivo** (BD real): la tabla y el permiso quedan bien; `Auditoria::registrar()`
  escribe la fila con usuario/entidad/detalle/IP correctos y trunca `accion` a 50 /
  `detalle` a 500; end-to-end (cambio de estado de cotización) deja el registro y la vista
  lo muestra con su filtro; `/admin/bitacora` sin sesión → 302 login; el cron de purga corre
  y reporta el conteo. Suite: **52 tests** (+2 de `AuditoriaTest`, que verifican que
  `registrar()` no lanza aunque no haya BD).

### 4-b. CRM ligero de cotizaciones

- Estado: **hecho** (2026-08-27). Segunda mitad de #4.

  **Piezas nuevas:**
  - Migración `0020` + `schema.sql`: `cotizaciones.asignado_a` (FK `usuarios_admin`
    `ON DELETE SET NULL`) y `cotizaciones.seguimiento_en` (DATE); tabla `cotizacion_notas`
    (`cotizacion_id` con `ON DELETE CASCADE`, `usuario_id`/`usuario_nombre` desnormalizado,
    `nota`, `creado_en`). Nota real: `ADD CONSTRAINT IF NOT EXISTS ... FOREIGN KEY` da
    error de sintaxis en MariaDB 10.6 — el `IF NOT EXISTS` va **después** de `FOREIGN KEY`
    (`ADD CONSTRAINT x FOREIGN KEY IF NOT EXISTS (...)`); se corrigió y la migración se
    reaplicó sobre el estado parcial sin problema (las columnas ya llevaban `IF NOT EXISTS`).
  - `Cotizacion::conDetalle()`, `seguimientosVencidos()`, y `adminListado()`/`contarTotal()`
    refactorizados para tomar un `array $filtros` (`origen` + `asignado` + `seguimiento`).
    `clausulaFiltros(array): array` es pública y pura (5 tests nuevos en
    `tests/Unit/Models/CotizacionFiltrosTest.php`, incluido un caso de intento de inyección
    en `asignado`). Constante nueva `Cotizacion::SIN_ASIGNAR`.
  - `App\Models\CotizacionNota` (`porCotizacion`, `agregar` — atribuye al usuario en sesión).
  - `CotizacionAdminController`: `detalle()` (`GET /admin/cotizaciones/{id}`, permiso
    `cotizaciones.ver`; la ruta va **después** de `/exportar` para no capturarla), `asignar()`
    / `seguimiento()` / `agregarNota()` (`POST .../asignar|seguimiento|nota`, permiso
    `cotizaciones.gestionar`). `cambiarEstado()` acepta `volver=detalle` para redirigir al
    detalle en vez del listado. Cada acción registra en la bitácora
    (`cotizacion.asignar` / `cotizacion.seguimiento` / `cotizacion.nota`, además del ya
    existente `cotizacion.estado`).
  - Vista `admin/cotizaciones/detalle.php` (datos de contacto, mensaje, origen, y los 3
    formularios de gestión + historial de notas). El listado suma columna "Asesor", enlace
    "Ver", filtros por asesor / "sin asignar" y checkbox "solo seguimientos vencidos", y una
    fila con seguimiento vencido se marca en rojo.
  - Dashboard: tile "Seguimientos vencidos" (bajo `cotizaciones.ver`) con enlace a
    `/admin/cotizaciones?seguimiento=vencidos`.

  **Decisión:** el cron opcional que avisa por correo al asesor de seguimientos vencidos
  **no** se hizo — para un equipo chico rinde más la señal in-panel (tile + filtro + fila en
  rojo). Queda anotado como follow-up si más adelante quieren el nudge por correo.

  **Probado en vivo** (BD real): asignar, fijar seguimiento (fecha de ayer) y agregar nota
  dejan los datos correctos y las 3 líneas en la bitácora; `seguimientosVencidos()` y el
  filtro `seguimiento=vencidos` devuelven la cotización de prueba; el detalle renderiza con
  las 3 secciones y el historial; borrar la cotización arrastra sus notas (CASCADE);
  `/admin/cotizaciones/exportar` sigue siendo el CSV (no lo captura la ruta de detalle);
  rutas nuevas → 302 login sin sesión. Suite: **57 tests**. Datos de prueba borrados.

### 5-a. Reseñas agregadas + schema.org

- Estado: **hecho** (2026-08-27). Primera mitad de #5 (el blog queda como 5-b abajo).

  **Piezas nuevas:**
  - `Resena::resumenPorPaquete(int)` → `['promedio' => float, 'total' => int]` (AVG/COUNT
    sobre `estado='aprobada'`) y `resumenPorPaquetes(array $ids)` (versión en lote para las
    tarjetas, evita N+1). `Resena::nombrePublico(string)` extraído de la vista ("Juan Pérez"
    → "Juan P.", por privacidad) y reusado en el JSON-LD.
  - `App\Helpers\PaqueteJsonLd::construir(paquete, resumen, resenas, appUrl)`: arma un array
    de nodos JSON-LD para la ficha — `TouristTrip` + `BreadcrumbList` siempre, y un nodo
    `Product` con `AggregateRating` + `Review` **solo si hay reseñas aprobadas** (Google no
    muestra el rich snippet de estrellas sobre `TouristTrip`, sí sobre `Product`). Reemplaza
    el `json_encode` inline que tenía `PaqueteController::ficha()`. El `<script
    type="application/ld+json">` ya existía en el layout y convive con la CSP estricta.
  - Estrellas + "N reseñas" en `paquetes/ficha.php` (con ancla `#resenas`) y en
    `paquetes/_tarjeta.php`. Se pasa `$resumenes` (lote) desde `PaqueteController::catalogo`,
    `HomeController::index` (destacados) y `DestinoController::mostrar` — la tarjeta se
    protege si no llega (`isset`).
  - Tests: `tests/Unit/Helpers/PaqueteJsonLdTest.php` (8 casos: sin reseñas no hay
    `Product`/`AggregateRating`; con reseñas el `Product` trae `reviewCount`/`review[]` con
    nombres públicos y fechas; salida es JSON válido y sin `<`/`>` crudos; `nombrePublico`).

  **Probado en vivo** (BD real, con 2 reseñas aprobadas + 1 pendiente en el paquete 1): la
  pendiente no cuenta (promedio 4.5, total 2); la ficha muestra las estrellas y emite 3
  nodos JSON-LD válidos (`TouristTrip, Product, BreadcrumbList`) con `ratingValue "4.5"` /
  `reviewCount 2`; catálogo y home destacados muestran "4.5 · 2 reseñas" en la tarjeta; un
  paquete sin reseñas no emite `Product` ni la sección `#resenas`. Sin warnings en el log.
  Suite: **65 tests**. Datos de prueba borrados.

  **No se tocó** Open Graph / Twitter cards (el `og:image` por paquete ya sale bien vía
  `meta['ogImage']`; no hacía falta más).

### 5-b. Blog de destinos

- Estado: **hecho** (2026-08-27). Segunda mitad de #5 y **último ítem de la segunda ronda**.

  **Piezas nuevas:**
  - Tabla `articulos` (migración `0021` + `schema.sql`): `titulo`, `slug` único, `resumen`,
    `contenido LONGTEXT`, `imagen`, `categoria_id` (FK `categorias` `ON DELETE SET NULL`,
    para el cross-link con un destino), `estado` borrador/publicado/archivado, `meta_title`/
    `meta_description`, `publicado_en`, `creado_por`. Permiso `articulos.gestionar` (migración
    `INSERT IGNORE` + rol Administrador; también en `seed_demo.sql`).
  - `App\Models\Articulo` (`publicadosPaginados`, `contarPublicados`, `porSlugPublicado`,
    `publicadosDeCategoria`, `adminListado`, `contarTotal`). El orden público es por
    `COALESCE(publicado_en, creado_en)`.
  - `App\Helpers\ArticuloJsonLd::construir()` — nodos `Article` + `BreadcrumbList`
    (Inicio > Blog > título). 3 tests.
  - `App\Controllers\Public\BlogController` — `GET /blog` (listado paginado, 9/pág) y
    `GET /blog/{slug}` (404 si no publicado; JSON-LD; meta desde `meta_*` o fallback;
    artículos relacionados de la misma categoría).
  - `App\Controllers\Admin\ArticuloAdminController` — CRUD calcado de `PaqueteAdminController`
    (contenido saneado con `HtmlSanitizer`, slug con `Slugify` + desambiguación, imagen
    opcional vía `ImageUploadService`, `SitemapService::regenerar()` tras guardar, auditoría
    `articulo.crear|editar|archivar`). `publicado_en` se fija **la primera vez** que pasa a
    `publicado` y no se vuelve a tocar. Editar como **HTML libre** (mismo criterio que
    paquetes; el proyecto no tiene editor WYSIWYG).
  - `SitemapService` agrega `/blog` + cada artículo publicado.
  - Enlaces "Blog" en el header y footer públicos y en el sidebar admin. `destinos/mostrar.php`
    lista los artículos publicados de esa categoría ("Artículos sobre …"); la ficha del
    artículo enlaza de vuelta a `/destinos/{slug}` de su categoría.
  - `.contenido-articulo` en `site.css` (interlínea/espaciado de la prosa); `sw.js` → `dreamgo-v8`.

  **Probado en vivo** (BD real): tabla + permiso OK; artículo creado como `borrador` con
  `publicado_en` NULL, `HtmlSanitizer` descarta el `<script>` y conserva p/strong/h3/ul/li;
  al pasar a `publicado` se fija `publicado_en`; `/blog` lista, `/blog/{slug}` renderiza el
  HTML y emite `Article` + `BreadcrumbList` válidos, `/blog/inexistente` → 404; el sitemap
  incluye la URL; la página del destino muestra el cross-link; `/admin/articulos` y
  `/admin/articulos/crear` → 302 login sin sesión (y `crear` no lo captura la ruta
  `{id}/editar`). Smoke 200 en todo. Suite: **68 tests** (+3 `ArticuloJsonLdTest`). Datos de
  prueba borrados; sitemap/robots restaurados.

## Candidatos para una tercera ronda (del análisis, sin priorizar)

- Registrar pago offline del saldo desde el panel (follow-up del #2 de esta ronda).
- CI (GitHub Actions corriendo `composer test`), observabilidad (Sentry), caché del
  catálogo/home, CAPTCHA (Turnstile) en formularios públicos como capa sobre el rate-limit.
- Datos por pasajero en la reserva (hoy solo `num_personas`) para manifiestos de salida.
