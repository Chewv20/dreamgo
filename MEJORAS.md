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
   - Estado: **pendiente**

5. **Dashboard con métricas de negocio** — `DashboardController` (`app/Controllers/Admin/DashboardController.php`)
   solo cuenta filas. Agregar ingresos por periodo (`SUM(precio_total)` de reservas
   confirmadas), tasa de conversión cotización→reserva, y ocupación por salida
   (`cupo_disponible/cupo_maximo`).
   - Estado: **pendiente**

6. **Exportar a CSV** en listados de reservas/cotizaciones — para contabilidad o CRM externo.
   - Estado: **pendiente**

7. **Newsletter / alertas de ofertas** — captar email en el home, tabla nueva `suscriptores`
   con opt-in, reusar `MailerService` para mandar ofertas nuevas.
   - Estado: **pendiente**

## Bajo impacto / pulido

8. **Multi-moneda real** — el campo `moneda` existe por paquete pero no hay conversión ni
   selector. Solo vale la pena si venden a extranjeros.
   - Estado: **pendiente**

9. **Comparador de paquetes** (2-3 lado a lado) — útil si el catálogo crece; prematuro con
   pocos paquetes.
   - Estado: **pendiente**

## Orden de trabajo acordado

Se empieza por el grupo de **alto impacto**, en este orden (decidido 2026-08-24):

1. **#3 Buscador y filtros** — primero, es autocontenido y sin dependencias externas.
2. **#2 Consulta de reserva por código + email** — segundo.
3. **#1 Reserva y pago en línea** — al final. **Pasarela decidida: Mercado Pago** (Checkout Pro).

Progreso y decisiones de cada una se documentan aquí a medida que se implementan.

**Las 3 mejoras de alto impacto ya están implementadas** (2026-08-24). Sigue el grupo de
medio impacto (#4-#7) cuando se retome este backlog.
