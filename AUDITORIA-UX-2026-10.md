# Auditoría visual y de UX — 2026-10

Revisión del sitio público y del panel de administración: navegador (escritorio + simulación
móvil a 390 px), análisis de DOM de todas las páginas y lectura de vistas/CSS. El panel se
recorrió con usuarios temporales de auditoría (rol Administrador y rol Editor).

> Las imágenes de paquetes (flyers) son de prueba y quedan fuera de esta auditoría.

## Veredicto general

La base visual es sólida: paleta terracota/pizarra coherente, tipografía serif + sans bien
emparejada, tarjetas consistentes, sin desbordes graves, foco visible y CSP limpia. Lo que más
daña la experiencia hoy **no es el diseño sino el contenido**: texto sin acentos en todo el
contenido sembrado y secciones vacías o con placeholders. En el panel, el mayor problema es que el contenido de los
paquetes se captura en **HTML crudo**, lo que explica buena parte de lo anterior.

| ID | Área | Severidad | Título |
|----|------|-----------|--------|
| PUB-02 | Contenido | **Alta** | Contenido de BD sin acentos ni ñ (home, destinos, paquetes, nosotros) — ✅ resuelto |
| PUB-03 | Visual | **Media** | Botón "Cotizar" del header con contraste 2.3:1 y texto invisible en hover — ✅ resuelto |
| PUB-04 | Visual | **Media** | Hero del inicio sin imagen (placeholder gris de montañas) |
| PUB-05 | UX | **Media** | Modal promocional a los 700 ms de cargar, tapa el hero — ✅ resuelto |
| PUB-06 | UX | **Media** | Ficha de paquete: panel de precio/CTA no es *sticky* — ✅ resuelto |
| PUB-07 | UX | **Media** | Formulario de reserva: estimado estático, sin total ni resumen — ✅ resuelto |
| PUB-08 | Visual | Media | Tarjetas de destino de alturas distintas (unas con imagen, otras no) — ✅ resuelto |
| PUB-09 | Visual | Media | Placeholder 16:9 dentro del marco 4:5 de las tarjetas de paquete — ✅ resuelto |
| PUB-10 | Visual | Baja | Casilla "Comparar" con doble sangría y flotando a media tarjeta — ✅ resuelto |
| PUB-11 | UX | Baja | Filtros del catálogo: "Duración" sola en una fila completa, select truncado — ✅ resuelto |
| PUB-12 | Contenido | Baja | Blog vacío pero enlazado en el menú principal — ✅ resuelto |
| PUB-13 | Contenido | Baja | Cifras infladas en Nosotros ("+50 destinos" con 5 publicados) |
| PUB-14 | Confianza | Baja | Testimonios genéricos ("Cliente Dream Go"); teléfono y redes sin capturar |
| PUB-15 | SEO | Baja | Inicio con `<title>` "Dream Go" y meta description "Excursiones" — ⚠️ parcial (guía en Configuración; falta capturar el texto) |
| PUB-16 | A11Y | Baja | Botón hamburguesa sin `aria-label` (lee "☰") — ✅ resuelto |
| PUB-17 | Responsive | Baja | Correo largo del footer desborda la columna en pantallas ≤ 375 px — ✅ resuelto |
| PUB-18 | UX | Baja | Cotizador genérico sin campo de destino/presupuesto; "Cotizar" y "Contacto" compiten — ✅ resuelto |
| PUB-19 | Visual | Baja | Sección de newsletter: título alineado a la izquierda, formulario centrado — ✅ resuelto |
| ADM-01 | UX | **Alta** | Descripción, itinerario e incluye/no incluye se capturan en HTML crudo — ✅ resuelto |
| ADM-02 | UX | **Media** | Listados de Reservas y Paquetes sin búsqueda ni filtros — ✅ resuelto |
| ADM-03 | UX | **Media** | Menú lateral plano de 16 entradas sin agrupar ni estado activo — ✅ resuelto |
| ADM-04 | UX | Media | Listado de paquetes sin miniatura ni enlace "Ver en el sitio" — ✅ resuelto |
| ADM-05 | UX | Media | Login sin "¿Olvidaste tu contraseña?" ni enlace al sitio — ✅ resuelto |
| ADM-06 | UX | Baja | No hay acceso a "Cambiar contraseña" desde el panel — ✅ resuelto |
| ADM-07 | UX | Baja | Confirmaciones destructivas con `window.confirm()` nativo — ✅ resuelto |
| ADM-08 | A11Y | Baja | Mensajes flash sin `role="status"` ni botón de cerrar — ✅ resuelto |
| ADM-09 | Texto | Baja | Textos del panel sin acento ("Iniciar sesion", "Por que viajar...") — ✅ resuelto |
| ADM-10 | UX | Baja | Formulario de paquete largo sin secciones; "Fechas y cupos" enterrado al final — ✅ resuelto |
| ADM-11 | Visual | **Media** | Jerarquía invertida: los `h2` del contenido (36.8 px) más grandes que el título de página (22.4 px) — ✅ resuelto |
| ADM-12 | UX | **Media** | Detalle de reserva: sin registro de pago manual, sin contacto rápido, "Cancelar" con estilo neutro — ✅ resuelto |
| ADM-13 | Visual | Media | Dashboard: KPIs de poco valor arriba, huecos de 48 px entre filas, filtro de fechas sobredimensionado — ✅ resuelto |
| ADM-14 | UX | Baja | "Nueva reserva" sólo existe dentro de Salidas; `/admin/reservas/crear` redirige a Paquetes con error — ✅ resuelto |
| ADM-15 | UX | Baja | Matriz de permisos con claves técnicas (`bitacora`, `paquetes.ver`) y descripciones sin acento — ✅ resuelto |
| ADM-16 | UX | Baja | Alta de usuario sin requisitos de contraseña visibles ni opción "forzar cambio al entrar" — ✅ resuelto |
| ADM-17 | Visual | Baja | Botones de alto inconsistente (32 / 38 / 46 px) y menú lateral con scroll propio — ✅ resuelto |
| ADM-18 | UX | Baja | 404 dentro del panel usa la página pública ("Volver al inicio" saca del panel) — ✅ resuelto |

---

## Sitio público

### PUB-02 · Contenido de BD sin acentos ni ñ — **Alta**

El pipeline UTF-8 está bien; el problema es el texto de `database/seeds/seed_demo.sql` cargado
en la BD. Palabras detectadas en páginas públicas: *Mexico, asesoria, Por que, despues,
categorias, proxima, Magicos, tradicion, rincon, Clasica, Sudamerica, seleccion, Cancun,
Cristobal, Canon/canones, America, Peru, Paris, conversacion, cotizacion, Dia, arqueologica,
disenar, mas*.

- **Dónde:** bloques de contenido (inicio, nosotros), nombres/descripciones de destinos,
  títulos, resúmenes e itinerarios de paquetes, testimonios.
- **Qué hacer:** corregir el seed **y** los registros ya cargados (desde el panel o con una
  migración de datos). Los slugs pueden quedar igual.

### PUB-03 · Botón "Cotizar" del header: contraste 2.3:1 — **Media**

`.nav a { color: var(--color-texto-oscuro) }` (especificidad 0,1,1) gana a
`.btn-primario { color: #fff }` (0,1,0): el botón queda con texto pizarra sobre terracota
(**2.32:1**, mínimo 4.5:1). Peor aún, `.nav a:hover` pinta el texto `--color-primario-oscuro`,
**el mismo color que el fondo del botón**: en hover el texto desaparece.

- **Archivos:** `public/assets/css/site.css:155` y `:265-272`
- **Arreglo:** limitar la regla a enlaces que no son botón (`.nav a:not(.btn)`) o añadir
  `.nav a.btn-primario, .nav a.btn-primario:hover { color: var(--color-blanco); }`.

### PUB-04 · Hero del inicio sin imagen — **Media**

El hero muestra el placeholder SVG (montañas y círculo sobre gris). Es lo primero que se ve y
transmite "sitio en construcción". El código ya soporta varias imágenes con carrusel
(`app/Views/public/home/index.php:46-50`); falta cargar al menos una foto desde
*Contenido del sitio → Inicio → Hero*.

Además, el botón secundario "Ver paquetes" (rosa claro sobre gris) se ve lavado; sobre un
hero oscuro funciona mejor un botón contorno blanco.

### PUB-05 · Modal promocional a los 700 ms — **Media**

`site.js:440` abre el modal 700 ms después de cargar, en la primera visita de la sesión. El
visitante aún no vio nada del sitio y ya tiene un overlay encima (se ve en las capturas).

- **Recomendación:** dispararlo por intención — tras ~40-50 % de scroll, tras 15-20 s, o en
  *exit intent* en escritorio — y no mostrarlo en fichas de paquete ni en el flujo de reserva.
  Guardar el cierre en `localStorage` con caducidad (p. ej. 7 días) en vez de por sesión.

### PUB-06 · Ficha de paquete: panel de CTA no es *sticky* — **Media**

En `/paquetes/{slug}` el panel lateral (precio, salidas, "Solicitar cotización", WhatsApp)
tiene `position: static`. En cuanto el usuario baja al itinerario, la columna derecha queda
vacía y los CTA desaparecen.

- **Archivo:** `public/assets/css/site.css:1317` (`.paquete-aside`)
- **Arreglo:** en escritorio `position: sticky; top: calc(alto-del-header + 1rem)`. En móvil,
  una barra inferior fija con precio + "Reservar/Cotizar".
- **Relacionado:** el checkbox "Agregar a comparar" dentro del panel de precio distrae del CTA
  principal; moverlo debajo de los botones o a la cabecera.
- La ficha termina en "No incluye" sin cierre; añadir política de cancelación / preguntas
  frecuentes y un CTA final.

### PUB-07 · Formulario de reserva: estimado estático — **Media**

`app/Views/public/reservar/formulario.php:14-27` calcula el anticipo en el servidor con el
número de personas inicial. Si el usuario cambia "Número de personas", el texto no se
actualiza; tampoco se muestra el **total**.

- **Arreglo:** caja-resumen a la derecha (o arriba en móvil) con precio por persona × personas
  = total, anticipo y saldo, recalculada en JS al cambiar el campo (el servidor sigue siendo
  la fuente de verdad).
- Añadir: enlace de regreso a la ficha, indicador de pasos (Datos → Pago → Confirmación),
  mención de Mercado Pago como medio de pago, enlace al aviso de privacidad junto al botón, y
  errores ligados con `aria-describedby` + `aria-invalid`.

### PUB-08 · Tarjetas de destino de alturas distintas — **Media**

En "Explora por destino" sólo *Playas del Caribe* tiene imagen; las demás son tarjetas de
texto que se estiran al alto de la fila y dejan un gran hueco blanco. Además la imagen de
Playas del Caribe es de 225 px reescalada a ~364 px (se ve borrosa).

- **Arreglo:** cuando el destino no tiene imagen, usar el mismo placeholder ilustrado que los
  paquetes para que todas las tarjetas tengan la misma estructura; y exigir un ancho mínimo
  (p. ej. 800 px) al subir imágenes de destino.

### PUB-09 · Placeholder 16:9 dentro del marco 4:5 — **Media**

En `/paquetes`, los paquetes sin imagen (*Europa Clásica*, etc.) muestran el placeholder
apaisado centrado en el marco vertical, con bandas desenfocadas arriba y abajo. Se ve como un
error de carga. Hacer el placeholder en proporción 4:5 (o `object-fit: cover`).

### PUB-10 · Casilla "Comparar" desalineada — **Baja**

`.tarjeta__comparar` lleva `padding: 0 1.25rem 1.25rem` pero está dentro de un cuerpo que ya
tiene padding → queda con doble sangría respecto al título y precio. Y como no está anclada
abajo, en tarjetas de distinto largo queda a distinta altura, con hueco blanco debajo.

- **Archivo:** `public/assets/css/site.css:572`
- **Arreglo:** quitar el padding horizontal y usar `margin-top: auto` en un cuerpo
  `display:flex; flex-direction:column`.

### PUB-11 · Filtros del catálogo — **Baja**

- "Duración" ocupa una fila entera sola, con el botón "Filtrar" a la derecha: parece otro
  formulario.
- El select "Tipo" se trunca ("Nacional e internacior").
- "Precio máx." tiene placeholder "$" (sin ejemplo útil).
- "Ordenar por" se autoenvía pero los filtros no: dos comportamientos distintos en la misma
  barra.

Sugerencia: una sola fila de 6 columnas en escritorio (o "Más filtros" desplegable) y, en
móvil, un botón "Filtros" que abra un panel.

### PUB-12 · Blog vacío enlazado en el menú — **Baja**

`/blog` dice "Todavía no publicamos artículos". Ocultar el enlace del menú y del footer
mientras no haya artículos publicados.

### PUB-13 · Cifras infladas en Nosotros — **Baja**

"+50 Destinos disponibles" cuando el sitio muestra 5 destinos y 8 paquetes. Una cifra que el
propio sitio contradice resta credibilidad; usar cifras reales o quitar el bloque.

### PUB-14 · Señales de confianza débiles — **Baja**

- Testimonios firmados como "Cliente Dream Go" con inicial en círculo: parecen inventados.
  Usar nombre + ciudad + paquete y, cuando haya, las reseñas reales del sistema de reseñas.
- Footer sin teléfono, dirección ni redes: **el panel ya lo soporta** (*Configuración →
  Contacto / Redes sociales*), sólo falta capturarlos. Valorar añadir RNT/registro de operador.
- Sin enlace a términos y condiciones / política de cancelación.

### PUB-15 · SEO del inicio — **Baja**

El inicio tiene `<title>Dream Go</title>` y `meta description` "Excursiones". El resto de
páginas sí usa "Página | Dream Go Operadora Turística". Sugerido:
"Dream Go Operadora Turística | Excursiones nacionales e internacionales" y una descripción de
140-160 caracteres.

### PUB-16 · Hamburguesa sin `aria-label` — **Baja**

`app/Views/layouts/public.php:66`: el botón sólo contiene `&#9776;`. Añadir
`aria-label="Abrir menú"` y marcar el carácter con `aria-hidden="true"` (o usar un SVG).

### PUB-17 · Desborde del footer en móviles pequeños — **Baja**

`contacto@dreamgooperadoraturistica.com` no se parte y fuerza el ancho mínimo de la columna
`1fr` del footer (desborda ~11 px a 375 px). Añadir `overflow-wrap: anywhere` al enlace o
`grid-template-columns: minmax(0, 1fr)` en `.footer__grid` (`site.css:725`).

### PUB-18 · Cotizador genérico — **Baja**

Desde el menú, `/cotizador` sólo pide nombre, correo, teléfono, personas, fecha y mensaje. Sin
campo de destino/paquete de interés ni presupuesto, el asesor recibe poca información.
Además "Cotizar" (botón) y "Contacto" están juntos en el menú con propósitos casi iguales;
valorar dejar sólo "Cotizar" como CTA y mover "Contacto" al footer.

### PUB-19 · Newsletter desalineada — **Baja**

En "No te pierdas nuestras ofertas" el título y subtítulo van a la izquierda y el formulario
centrado. Alinear todo a la izquierda o todo al centro.

---

## Panel de administración

### ADM-01 · Contenido de paquete en HTML crudo — **Alta**

`app/Views/admin/paquetes/_form.php`: *Descripción*, *Itinerario*, *Incluye* y *No incluye*
son `<textarea>` donde se escribe HTML a mano ("lista HTML: &lt;ul&gt;&lt;li&gt;..."). Para
personal no técnico es propenso a errores y explica el itinerario plano de la ficha pública.

- **Opción A (rápida):** *Incluye/No incluye* como "un elemento por línea" y el servidor
  genera la `<ul>`. *Itinerario* como repetidor de días (título + descripción).
- **Opción B:** editor enriquecido ligero (p. ej. Trix/Quill) limitado a negritas, listas y
  enlaces, que respete la lista blanca del `HtmlSanitizer`.
- Recomendación: **A** para incluye/itinerario (dato estructurado, permite mejor diseño en
  la ficha) y **B** sólo para la descripción.

### ADM-02 · Reservas y Paquetes sin búsqueda ni filtros — **Media**

- `admin/reservas/index.php`: no se puede buscar por código ni cliente, ni filtrar por estado
  o salida. Tampoco hay columna de monto total/pagado.
- `admin/paquetes/index.php`: sin búsqueda ni filtro por estado/categoría.
- Cotizaciones sí tiene filtros: replicar ese patrón.

### ADM-03 · Menú lateral plano y sin estado activo — **Media**

`app/Views/layouts/admin.php:24-39`: 16 enlaces seguidos y ninguno marca la sección actual
(no hay `aria-current` ni clase activa). Propuesta de grupos:

- **Ventas:** Reservas, Cotizaciones
- **Catálogo:** Paquetes, Destinos
- **Marketing:** Ofertas, Modales, Suscriptores, Reseñas, Blog
- **Sitio:** Contenido, Colores
- **Sistema:** Usuarios, Roles y permisos, Configuración, Bitácora

Añadir también "Ver sitio ↗" y contadores (cotizaciones nuevas, reservas pendientes).

### ADM-04 · Listado de paquetes sin miniatura — **Media**

Sin miniatura de portada no se nota a simple vista qué imagen tiene cada paquete. Añadir miniatura,
enlace "Ver en el sitio" y acceso directo a "Salidas" en cada fila.

### ADM-05 · Login sin recuperación de contraseña — **Media**

No hay ruta de restablecimiento; si un asesor olvida su contraseña depende de un
administrador. Añadir "¿Olvidaste tu contraseña?" (token por correo, ya hay `MailerService` y
rate limiting) y un enlace "← Volver al sitio".

### ADM-06 · Sin acceso a "Cambiar contraseña" — **Baja**

`/admin/cambiar-password` sólo se alcanza cuando se fuerza el cambio. Convertir el nombre de
la barra superior en un menú con "Cambiar contraseña" y "Cerrar sesión".

### ADM-07 · `window.confirm()` nativo — **Baja**

`admin.js` usa `confirm()` para eliminar/archivar/cancelar. Funciona, pero no se puede
estilizar ni explicar consecuencias. Un `<dialog>` propio permitiría resaltar la acción
destructiva en rojo y, para lo irreversible, pedir escribir el nombre.

### ADM-08 · Mensajes flash — **Baja**

`layouts/admin.php:52-56`: añadir `role="status"` (éxito/info) o `role="alert"` (error) para
lectores de pantalla, y un botón de cerrar o desvanecido automático en los de éxito.

### ADM-09 · Textos del panel sin acento — **Baja**

El commit de acentos corrigió las vistas, pero no los `title`/`heading` que fijan los
controladores ni algunos rótulos:

- `AuthController.php:22,44,56`: "Iniciar sesion", "Cambiar contrasena".
- `ArticuloAdminController.php:28,36,78`: "Articulos del blog", "Nuevo/Editar articulo".
- `ResenaAdminController.php:20`: "Resenas de clientes".
- `OfertaAdminController.php:24,31,68`: "codigos de descuento".
- `BitacoraController.php:23`, `ConfiguracionController.php:44`, `CotizacionAdminController.php:50`.
- `ContenidoController.php:28` ("Por que viajar…") y la lista de páginas de *Contenido*
  ("Pagina de inicio", "Paquetes (catalogo)").
- Seed: descripción del rol Editor ("Gestion… unicamente"), módulos de permisos
  ("bitacora"), configuración ("reporte periodico", "cotizacion").

### ADM-10 · Formulario de paquete sin secciones — **Baja**

`_form.php` es una sola columna larga sin agrupar; la galería y "Gestionar fechas y cupos"
quedan al final, debajo del botón Guardar. Agrupar en fieldsets o pestañas (Datos básicos ·
Contenido · Imágenes · Fechas y cupos · SEO), con barra de guardar fija.

### ADM-11 · Jerarquía tipográfica invertida — **Media**

El título de página del topbar (`.admin-topbar h1`) mide 22.4 px, pero los `h2` dentro de los
paneles heredan el tamaño del sitio público (36.8 px). En el dashboard, "Cotizaciones por origen
(periodo)" grita más que "Panel". Definir en `admin.css` una escala propia:
`h1` ≈ 1.5rem, `.admin-panel h2` ≈ 1.15rem, `h3` ≈ 1rem.

### ADM-12 · Detalle de reserva incompleto — **Media**

`app/Views/admin/reservas/detalle.php`:

- No hay forma de **registrar un pago manual** (transferencia, efectivo): sólo entran pagos por
  Mercado Pago, aunque el sitio dice "el resto se liquida directamente con nuestro equipo".
- Correo y teléfono son texto plano: convertirlos en `mailto:` y enlace de WhatsApp.
- "Cancelar reserva" usa el mismo `btn-secundario` que acciones neutras. No existe un estilo
  de botón de peligro en el CSS; crear `.btn-peligro` y usarlo en cancelar/eliminar.
- Sin notas internas ni historial de cambios de estado (cotizaciones sí tiene notas).

### ADM-13 · Dashboard — **Media**

- Los dos primeros KPI son "Paquetes totales" y "Paquetes publicados", que casi no cambian.
  Arriba deberían ir los accionables: cotizaciones nuevas, seguimientos vencidos, reservas
  pendientes (con enlace a cada listado filtrado).
- Entre filas de tarjetas hay 48 px (gap 24 px + `margin-bottom` 24 px del `.admin-panel`):
  quitar el margen dentro de `.grid-tarjetas`.
- El filtro de fechas ocupa todo el ancho con inputs gigantes; basta una fila compacta a la
  derecha del título, con atajos ("Este mes", "Últimos 30 días").
- Sin comparación con el periodo anterior: un "0" no dice si es bueno o malo.

### ADM-14 · Crear reserva manual — **Baja**

El listado de Reservas no tiene "Nueva reserva"; hay que ir a Paquetes → editar → Gestionar
fechas → Nueva reserva. Entrar directo a `/admin/reservas/crear` redirige a Paquetes con un
error. Añadir el botón en Reservas con un selector paquete → salida.

### ADM-15 · Matriz de permisos técnica — **Baja**

La matriz agrupa por el slug del módulo (`bitacora`, `paquetes`) y muestra claves. Usar
nombres legibles por grupo ("Bitácora", "Paquetes") y la descripción del permiso como etiqueta
principal; añadir "marcar todo el grupo".

### ADM-16 · Alta de usuario — **Baja**

`/admin/usuarios/crear` pide nombre, correo, contraseña y rol, sin indicar los requisitos de
`PasswordPolicy` (se descubren al fallar), sin botón de mostrar/generar contraseña y sin la
opción de forzar el cambio en el primer inicio de sesión (la columna `debe_cambiar_password`
ya existe).

### ADM-17 · Consistencia de botones y menú — **Baja**

- En el dashboard "Filtrar" mide 32 px y "Restablecer" 38 px; "Cerrar sesión" 46 px y con
  el mismo color de acento que un CTA. Unificar alturas y dejar "Cerrar sesión" como enlace
  discreto (o dentro del menú de usuario, ver ADM-06).
- El menú lateral es más alto que la ventana (936 px) y tiene su propio scroll. Con la
  agrupación de ADM-03 cabe sin scroll.
- "Colores del sitio" aparece en el menú y también dentro de *Contenido*: dejar una sola
  entrada.

### ADM-18 · 404 dentro del panel — **Baja**

Una URL inexistente bajo `/admin` muestra la 404 pública con "Volver al inicio" (sitio
público). Para rutas `/admin/*`, renderizar la 404 dentro del layout del panel con enlace al
dashboard.

---

## Estado de los arreglos (2026-10-07)

Aplicados los arreglos rápidos de CSS/HTML. Cambios principales:

- `site.css`: `.nav a:not(.btn)` (el CTA recupera texto blanco, 4.76:1); `.paquete-aside`
  *sticky* desde 900 px; `.tarjeta--paquete` en columna con pie anclado abajo y
  "Comparar" sin doble sangría; `.footer__grid` con `minmax(0, 1fr)` y enlaces con
  `overflow-wrap: anywhere`; newsletter alineada a la izquierda.
- `ficha.php`: "Agregar a comparar" movido debajo de los CTA.
- `layouts/public.php` + `site.js`: hamburguesa con `aria-label` que alterna Abrir/Cerrar.
- `home/index.php`: el campo de la newsletter tiene `aria-label`.
- `configuracion/index.php`: guía de longitud para título y descripción SEO.
- `layouts/admin.php` + `admin.css`: menú agrupado (Ventas, Catálogo, Marketing, Sitio,
  Sistema) con `aria-current`, una sola entrada "Contenido y colores", "Ver sitio ↗" y
  "Cerrar sesión" discretos al pie; cabe sin scroll en 911 px de alto. Escala propia de
  `h2`/`h3`; `line-height` común para `.btn` (todos a 33 px); sin margen doble en la rejilla
  del dashboard; flashes con `role="status"` / `role="alert"`.
- Controladores: títulos y etiquetas con acentos (`Auth`, `Articulo`, `Resena`, `Oferta`,
  `Bitacora`, `Configuracion`, `Cotizacion`, `Contenido`, `core/Controller.php`).

### Acentos del contenido (PUB-02, ADM-09, ADM-15)

- `database/seeds/seed_demo.sql`: 91 líneas corregidas (textos visibles; slugs, rutas,
  claves de permisos, claves JSON y nombres de columna intactos). Incluye "Anos de
  experiencia" → "Años de experiencia".
- `database/migrations/0028_acentos_contenido_inicial.sql`: 87 `UPDATE` sobre categorías,
  paquetes, alt de imágenes, roles, permisos, configuración y bloques de página. Cada uno
  sólo aplica si el valor sigue idéntico al original sin acentos, así que en producción no
  pisa texto editado desde el panel. Aplicada en local.
- `admin/roles/index.php`: los grupos de la matriz muestran nombres legibles
  ("Bitácora", "Reseñas", "Fechas de salida"…) en vez de la clave del módulo.
- Barrido posterior de todas las páginas públicas, fichas de paquete/destino y pantallas
  del panel: sin palabras sin acento detectadas.

### Bloque 1 · Público: conversión (PUB-05, 06, 07, 12, 18)

- **Modal promocional:** se abre al 50 % de scroll, a los 20 s o por *exit intent*; tras
  mostrarse no vuelve en 7 días (`localStorage`). No se imprime en cotizador, contacto,
  reserva, pago de saldo, consulta de reserva ni reseña. **Bug corregido:** el layout
  comparaba la URI cruda con `/`, así que en una instalación en subcarpeta los modales
  "solo inicio" nunca aparecían; ahora usa `$this->request->uri()`.
- **Ficha en móvil:** barra fija inferior con precio y "Ver fechas" (ancla al panel) o
  "Cotizar" si no hay salidas abiertas; el botón de WhatsApp sube y la barra cede el
  lugar al comparador si está abierto.
- **Reserva:** resumen lateral (arriba en móvil) con precio × personas, total, anticipo y
  saldo recalculados en vivo; "← Volver al paquete", "Paso 1 de 2", nota de Mercado Pago,
  enlace al aviso de privacidad y errores con `aria-invalid` + `aria-describedby`.
- **Blog:** el enlace del menú y del footer sólo aparece si hay artículos publicados.
- **Cotizador:** sin paquete de origen, muestra "Paquete de interés" (agrupado por destino,
  usa el campo `paquete_slug` existente, sin migración) y un *placeholder* que sugiere
  destino, presupuesto y hospedaje. Si la validación falla viniendo de una ficha, se
  conserva el aviso "Estás cotizando".
- **Extra:** etiquetas de validación y mensajes visibles con acento ("El teléfono",
  "La contraseña", "Token de seguridad inválido", mensajes de pago en línea, etc.).

### Bloque 2 · Público: visual (PUB-08, 09, 11)

- **Tarjetas sin imagen:** nuevo partial `_tarjeta_media_vacia.php` (degradado de la paleta +
  ícono de ubicación) en el mismo marco que una tarjeta con foto. Se usa en destinos (inicio
  y `/destinos`) y en paquetes, que antes caían al logo. La rejilla queda pareja.
- **Fotos horizontales en marco 4:5:** `initOrientacionMedia()` mide la imagen real y, si es
  apaisada (≥ 1.15:1), le pone `.is-apaisada` → `object-fit: cover`. Los flyers verticales
  siguen en `contain` para no cortar texto. Un flyer horizontal con texto sí se recortaría:
  conviene subirlos en vertical.
- **Filtros del catálogo:** rejilla de 4 columnas en escritorio (búsqueda ancha + destino +
  tipo / precios + duración + Filtrar), 2 en tableta. En móvil queda la búsqueda y
  "Más filtros", abierto de entrada si ya hay un filtro aplicado; sin JS se ve todo.
  "Tipo" ya no se trunca ("Todos") y "Precio máx." dice "Sin límite".

### Bloque 3 · Panel: navegación y listados (ADM-02, 04, 06, 08, 14, 18)

- **Reservas:** buscador por código, nombre o correo del cliente; filtros por estado y por
  salida (próximas / pasadas); columnas Total y Pagado (con saldo); conteo de resultados y
  mensaje propio cuando la búsqueda no encuentra nada. `Reserva::clausulaFiltros()`.
- **Paquetes:** buscador por título y filtros por estado y destino; miniatura de portada,
  destino bajo el título, número de próximas salidas y accesos "Fechas" y "Ver ↗" (sólo
  publicados). `Paquete::clausulaFiltrosAdmin()`.
- Ambos armadores de WHERE con pruebas unitarias (`ListadosAdminFiltrosTest`, 7 casos:
  parámetros por columna, escape de `%`/`_`, valores inválidos ignorados).
- **Nueva reserva:** botón en el listado (con permiso `reservas.crear`); sin `salida_id`,
  `/admin/reservas/crear` muestra la lista de fechas abiertas con cupo en vez de redirigir
  con error. El formulario muestra el paquete y "← Elegir otra fecha".
- **404 / 403 en el panel:** con sesión iniciada, una ruta inexistente o sin permiso bajo
  `/admin` se muestra dentro del layout del panel con "Volver al panel".
- **Menú de usuario:** el nombre de la barra superior abre "Cambiar contraseña" y "Cerrar
  sesión" (se cierra con clic fuera o Escape). La pantalla de cambio distingue el cambio
  forzado del voluntario (texto, botón "Cancelar y volver al panel") y muestra la política
  de contraseñas.
- **Mensajes flash:** botón de cerrar; los de éxito/info se desvanecen a los 6 s, los de
  error se quedan.
- **Extra:** las celdas `td.admin-acciones` usaban `display:flex`, lo que las sacaba del
  modelo de tabla (borde de fila desfasado en 8 listados); botones del panel con tamaño
  compacto uniforme; "Dólar estadounidense".

### Bloque 4 · Panel: reservas y dashboard (ADM-12, 13) + abonos del cliente

- **Migración `0029_pagos_manuales_y_notas_reserva.sql`** (y `schema.sql`/seed): columnas
  `pagos_reserva.nota` y `registrado_por`, tabla `reserva_notas`, permiso
  `reservas.registrar_pago` (lo reciben los roles de sistema y los que ya podían confirmar)
  y configuración `abono_minimo_saldo` (500 por defecto).
- **Pagos manuales:** `ReservaService::registrarPagoManual()` (transferencia, depósito,
  efectivo, terminal, otro). Comparte con Mercado Pago el mismo `registrarPago()` aditivo:
  si cubre el anticipo de una reserva pendiente la confirma. Con referencia del banco,
  registrar dos veces la misma transferencia devuelve duplicado en vez de sumarla. Opción
  de avisar al cliente por correo; queda en bitácora.
- **Detalle de reserva:** resumen Total / Pagado / Saldo, historial de pagos con método,
  referencia, nota y quién lo registró, notas internas, columna de cliente con `mailto:`,
  `tel:` y WhatsApp (lada 52 si el número tiene 10 dígitos), "Cancelar" con estilo de
  peligro (`.btn-peligro`) separado de "Confirmar" y confirmación que dice cuántos lugares
  se liberan.
- **Link de pago del cliente en el panel:** con saldo pendiente, el detalle muestra el link
  `/reserva/{código}/pagar-saldo?t=…` con botón "Copiar" y "Enviar link de pago por
  WhatsApp" (mensaje con el saldo y el link).
- **Abonos del cliente:** la página de pago de saldo ofrece "Liquidar el saldo" o "Abonar
  otra cantidad" (desde el mínimo configurable; si el saldo es menor que el mínimo, se
  cobra completo). `ReservaService::resolverMontoSaldo()` acota en el servidor lo que
  escriba el cliente a [mínimo, saldo]; la preferencia de Mercado Pago se titula "Abono al
  saldo". El webhook no cambia: cada abono entra como un pago "saldo" más. El correo de
  "pago recibido" incluye el botón "Abonar o liquidar saldo" mientras quede saldo.
- **Dashboard:** "Por atender" (cotizaciones nuevas, seguimientos vencidos, reservas
  pendientes, saldos por cobrar) como tarjetas enlazadas a su listado filtrado y resaltadas
  si hay algo; "Resultados del periodo" con Cobrado (pagos realmente recibidos), Ventas
  confirmadas, Reservas confirmadas y Conversión, cada una con variación contra el periodo
  anterior de la misma duración (`Fecha::periodoAnterior()`); atajos Este mes / Mes pasado
  / Últimos 30 días / Este año y "Otro rango" plegable.
- **Pruebas:** `MontoSaldoTest` (11 casos) y 5 casos nuevos de integración (abonos
  acumulados, pago manual que confirma, referencia duplicada, efectivo sin referencia,
  reserva cancelada / método inválido). La suite de integración pasa completa (23).
- **Extra:** la prueba `testDescuentoAgotadoRechazaLaSegundaReserva` seguía buscando
  "limite" tras el commit de acentos; ahora busca "límite".

### Bloque 5 · Panel: formularios (ADM-01, 07, 10, 15, 16)

- **Contenido estructurado del paquete** (`App\Helpers\ContenidoPaquete`): la descripción
  se escribe como texto normal (línea en blanco = párrafo nuevo), el itinerario como filas
  día por día (título opcional + actividades, con "+ Agregar día" / quitar; sin título se
  numera solo) e Incluye / No incluye como un elemento por línea. **En la BD no cambia
  nada**: se sigue guardando el mismo HTML y la ficha pública lo pinta igual. Cada campo
  sólo pasa a modo estructurado si su HTML actual va y vuelve sin cambios; si tiene
  formato avanzado (enlaces, negritas en medio del texto, títulos) se queda con el editor
  HTML y un aviso, así que convertir nunca pierde contenido. Los 8 paquetes actuales van
  y vuelven idénticos (verificado: guardar sin cambios no altera el HTML). 9 pruebas
  unitarias (`ContenidoPaqueteTest`).
- **Formulario de paquete por secciones:** Datos básicos · Precio y duración (en una fila)
  · Contenido de la ficha · Imágenes · SEO (plegable), con textos de ayuda y barra
  "Guardar paquete" fija abajo. Si la validación falla, lo capturado ya no se pierde (antes
  el formulario volvía vacío). En la edición, "Fechas y cupos", "Ver en el sitio ↗" y
  "Archivar paquete" pasan arriba.
- **Alta y edición de usuarios:** la política de contraseñas se ve antes de enviar; botones
  "Mostrar" y "Generar" (12 caracteres con letras y números, sin caracteres ambiguos);
  casilla "Pedir que la cambie al iniciar sesión" (marcada por defecto al crear) que usa la
  columna `debe_cambiar_password`; lo capturado (sin la contraseña) vuelve tras un error.
- **Matriz de permisos:** botón "Todo" por grupo y rol (marca el grupo; si ya estaba todo
  marcado, lo desmarca; respeta las casillas bloqueadas) y `aria-label` en cada casilla.
- **Confirmaciones:** `<dialog>` propio en lugar de `window.confirm()`: el botón dice la
  acción ("Eliminar", "Archivar paquete", "Cancelar reserva"), en rojo si es destructiva;
  "Volver" y Escape cancelan y devuelven el foco. Sin soporte de `<dialog>` cae a
  `window.confirm()`. Los botones de eliminar del panel usan `.btn-peligro`.

### Bloque 6 · Recuperación de contraseña (ADM-05)

- **Login:** enlaces "¿Olvidaste tu contraseña?" y "← Volver al sitio"; `autocomplete`
  correcto en los campos.
- **`/admin/recuperar`:** pide el correo y responde **siempre lo mismo**, exista o no la
  cuenta. La redirección sale antes del envío (`Response::redirectYContinuar()`), así que
  el tiempo de respuesta tampoco revela si el correo existe (medido: ~115 ms en ambos
  casos). Límite: 3 solicitudes por correo y 10 por IP cada 30 min (`RateLimiter`).
- **Token:** 32 bytes aleatorios; en la BD sólo se guarda su SHA-256 (tabla
  `password_resets`, migración `0030`). Vence a los 60 min, sirve una vez y pedir otro
  invalida los anteriores. Usuarios inactivos no reciben enlace.
- **`/admin/restablecer/{token}`:** muestra la cuenta y la política de contraseñas; un
  enlace vencido, usado o malformado muestra "Solicitar un enlace nuevo". Al guardar se
  cambia la contraseña, se quita `debe_cambiar_password`, se consumen todos los tokens del
  usuario y, como cambia `usuarios_admin.actualizado_en`, `Auth::sesionVigente()` cierra
  cualquier sesión abierta con la contraseña anterior. Queda en bitácora.
- **Correo:** plantilla `emails/recuperar_password.php` (botón + enlace en texto); nuevo
  tipo `recuperar_password` en `log_correos_enviados`.
- **Pruebas:** `RecuperacionPasswordTest` (7 casos de integración: sólo hash en BD, correo
  inexistente / inactivo, un solo uso, invalidación del anterior, vencimiento, validación
  sin consumir el token, formato inválido).
- **Requisito en producción:** SMTP configurado en `.env` (en local el envío falla con
  "Could not connect to SMTP host" y queda registrado en el log, que es lo esperado).

## Prioridad sugerida

1. **Contenido, sin código:** PUB-02, PUB-04, PUB-14 — corregir acentos en BD, cargar
   imagen del hero, capturar teléfono y redes en Configuración.
2. **Rápidos de CSS/HTML (< 1 h):** PUB-03, PUB-06, PUB-10, PUB-15, PUB-16, PUB-17, PUB-19,
   ADM-03 (estado activo), ADM-08, ADM-09, ADM-11, ADM-17.
3. **Siguiente iteración:** PUB-05, PUB-07, ADM-01, ADM-02, ADM-04, ADM-12, ADM-13.
4. **Backlog:** el resto.
