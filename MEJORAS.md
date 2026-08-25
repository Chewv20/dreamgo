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
