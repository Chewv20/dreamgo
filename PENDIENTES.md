# Pendientes — auditoría 2026-08

Trabajo abierto tras aplicar la auditoría completa (`AUDITORIA-2026-08.md`). De los 16 hallazgos,
14 quedaron cerrados y estos 2 quedaron **parciales** porque necesitan una decisión previa, no
solo más código. El resto de esta lista son puntos menores que se anotaron "sin acción" en el
informe y se dejan aquí para no perderlos.

---

## 1. Harness de pruebas de integración con base de datos  (CAL-03, parcial)

**Hecho:** pruebas unitarias de `PermissionMiddleware` (RBAC por ruta) y de
`Auth::sesionCaducada()` / `registrarActividad()` — `tests/Unit/Core/`.

**Falta:** pruebas que ejerciten el camino crítico contra una BD real, no lógica pura:

- Flujo de dinero completo: `POST /reservar` → `ReservaService::crear` (descuento de cupo con
  `FOR UPDATE`) → webhook de Mercado Pago (`registrarPagoAprobado`, dedup por
  `UNIQUE(referencia_pago)`) → confirmación → correo (`log_correos_enviados`).
- Reposición de cupo en cancelación y en expiración (`cron/liberar_reservas_expiradas.php`).
- Que una ruta admin con `['permiso' => 'x']` rechaza a un usuario sin ese permiso de punta a
  punta (no solo el middleware aislado).
- Condiciones de carrera ya cubiertas por código pero sin test: `Cliente::encontrarOCrear`,
  `DescuentoService::validar` cerca de `uso_maximo`, doble submit de reseña.

**Qué hay que decidir antes:**
- ¿BD de pruebas dedicada (p. ej. `dreamgo_test`) que se crea desde `database/schema.sql` +
  `database/seeds/`? ¿O SQLite en memoria? (Hay SQL específico de MySQL: `FOR UPDATE`,
  `INSERT IGNORE`, `ON DUPLICATE KEY`, `DATE_ADD` — SQLite no sirve sin reescribir; lo
  realista es MySQL/MariaDB de prueba).
- Aislamiento entre tests: envolver cada test en una transacción y hacer `rollback` en
  `tearDown` (rápido, pero no cubre código que hace su propio `COMMIT`), o `TRUNCATE` de las
  tablas tocadas.
- ¿Corre en CI? Hoy no hay CI configurado.

**Esbozo:**
- Nuevo suite `tests/Integration/` + sección en `phpunit.xml` (`<testsuite name="integration">`),
  separada de `unit` para poder correr solo una.
- `tests/Integration/IntegrationTestCase.php`: `setUpBeforeClass()` conecta a la BD de prueba
  (vars `DB_*` de un `.env.testing` o `phpunit.xml` `<php><env>`), `setUp()` abre transacción,
  `tearDown()` `rollBack()`.
- Documentar en `README.md` cómo levantar la BD de prueba y correr `composer test:integration`.

---

## 2. Contraste de la paleta configurable  (A11Y-01, parcial)

**Hecho:** `a:not(.btn):focus-visible` global y *skip link* admin (`public/assets/css/site.css`,
`app/Views/layouts/admin.php`).

**Falta:** el panel deja fijar cualquier color hex válido para el sitio
(`/admin/colores` → `ContenidoController::guardarColores`, validación solo de formato en
`ContenidoController::esColorHexValido`) y para el fondo de cada bloque
(`ContenidoController::editar`). Un administrador puede elegir combinaciones que no cumplan
WCAG AA (texto sobre fondo con ratio < 4.5:1) sin que nada lo avise.

**Qué hay que decidir antes:**
- **Opción A — advertir, no bloquear:** al guardar, calcular el ratio de contraste de cada par
  relevante (`color_texto_oscuro` sobre `color_fondo` / `color_fondo_alterno`, texto blanco
  sobre `color_primario` / `color_error` / `color_exito`, etc.) y mostrar un `Flash` de aviso
  con los pares que no llegan a 4.5:1, pero guardar igual. Menos intrusivo; el admin manda.
- **Opción B — bloquear:** rechazar el guardado de los colores que fallen, como ya se hace con
  el formato hex. Garantiza accesibilidad pero puede frustrar a quien quiere un look concreto.
- Recomendación: **A** (advertir). Es reversible y no encierra al usuario.

**Esbozo (opción A):**
- Nuevo `App\Helpers\Contraste`: `ratio(string $hex1, string $hex2): float` (luminancia
  relativa WCAG) y `cumpleAA(string $texto, string $fondo): bool`.
- En `ContenidoController::guardarColores()` y `::editar()` (bloque con `color_fondo`), tras
  validar el formato, evaluar los pares y, si alguno falla, añadir al `Flash` de éxito una
  línea "⚠ Estos colores tienen poco contraste: …".
- Pruebas unitarias del helper con pares conocidos (negro/blanco = 21, etc.).

---

## 3. Menores anotados en el informe (sin acción, para contexto)

| Ref | Punto | Nota |
|-----|-------|------|
| CAL-04 | `num_personas` admite 1–30 en la reserva y 1–60 en el cotizador | Unificar o documentar la diferencia a propósito. |
| CAL-05 | `Validator::telefono` acepta 7–20 chars de `[0-9+\s()-]` (permite `-------`) | Suficiente para un campo de contacto; endurecer solo si aparece spam. |
| SEO-02 | `public/robots.txt` estático con dominio de producción fijo | En un *staging* indexable apuntaría al `sitemap.xml` de producción. Decisión consciente (archivo estático). |
| SEG-09 | Enumeración sutil de reservas en `/mi-reserva` y `/resena/{codigo}` (mensajes distintos según exista código+email) | Mitigado por `RateLimiter`. Cerrar solo si se unifica el mensaje de "no encontrada / no elegible". |
| BD-nota | `database/migrate.php` parte por `;` tras quitar comentarios | Frágil ante un `;` dentro de una cadena o un `DELIMITER`. Hoy no hay ninguno; revisar si se añaden triggers/procedimientos. |
| PERF-nota | `codigo_reserva` = `DG-{año}-{id con str_pad(6)}` | Rompe el *formato* (no la unicidad) a partir de 10^6 reservas. Cosmético. |
