-- Actualización de bloques_pagina en producción (2026-10-16).
--
-- Upsert sobre la llave única (pagina, clave): actualiza el bloque si existe y lo crea si no.
-- `actualizado_en` se actualiza solo (ON UPDATE CURRENT_TIMESTAMP).
-- Es idempotente: correrlo dos veces deja el mismo resultado.
--
-- Sin prefijo de base de datos: selecciona la BD de producción antes de correrlo.
-- Uso: phpMyAdmin > pestaña SQL, o  mysql --default-character-set=utf8mb4 <bd> < este_archivo
--
-- OJO: el hero apunta a imágenes en /uploads/hero/original/; esos archivos deben existir en
-- el servidor (los uploads no viven en el repo).

SET NAMES utf8mb4;

START TRANSACTION;

INSERT INTO bloques_pagina (pagina, clave, orden, visible, titulo, subtitulo, contenido, color_fondo) VALUES
  ('home', 'hero', 1, 1,
   'Descubre el mundo con la tranquilidad de viajar acompañado',
   'Diseñamos excursiones y paquetes a los mejores destinos de México y el mundo, con asesoría personalizada de principio a fin.',
   '{"cta_primario_texto":"Cotiza tu viaje","cta_primario_link":"/cotizador","cta_secundario_texto":"Ver paquetes","cta_secundario_link":"/paquetes","imagenes":["/uploads/hero/original/hero-7ebce71f66ed.jpg","/uploads/hero/original/hero-ddbbbaa84304.jpg","/uploads/hero/original/hero-b446e4a9c2a3.jpg","/uploads/hero/original/hero-224bc6fba031.jpg","/uploads/hero/original/hero-c8dee70688f6.jpg"]}',
   NULL),

  ('home', 'ventajas', 2, 1,
   'Por qué viajar con Dream Go',
   'Somos profesionales, con experiencia de más de 10 años en turismo, viajes y organización.\nEntendemos tus necesidades y hacemos que disfrutes al 100%',
   '{"items":[{"icono":"escudo","titulo":"Asesoría personalizada","texto":"Te acompañamos desde la elección del destino hasta el regreso a casa, resolviendo cada duda en el camino."},{"icono":"chat","titulo":"Itinerarios claros","texto":"Sin sorpresas: sabes exactamente qué incluye cada paquete antes de reservar."},{"icono":"etiqueta","titulo":"Precios exactos","texto":"Sin sorpresas de letras chiquitas"},{"icono":"estrella","titulo":"Experiencia","texto":"Años de experiencia, con las mejores agencias del país y respaldos al 100%."}]}',
   '#0f2744'),

  ('home', 'destinos', 3, 1,
   'Explora por destino',
   'Recorre nuestras categorías nacionales e internacionales y encuentra tu próxima escapada.',
   NULL,
   '#0f2744'),

  ('home', 'paquetes_destacados', 4, 1,
   'Paquetes destacados',
   'Una selección de las excursiones favoritas de nuestros viajeros.',
   NULL,
   '#0f2744'),

  ('home', 'testimonios', 5, 1,
   'Lo que dicen quienes ya viajaron',
   '',
   '{"items":[{"inicial":"M","texto":"La asesoría fue clave: nos ayudaron a armar el itinerario perfecto para el tiempo que teníamos y todo salió como lo planeamos.","autor":"Cliente Dream Go — Paquete nacional"},{"inicial":"R","texto":"Viajamos en grupo y el equipo resolvió cada duda por WhatsApp casi al instante. Se siente el acompañamiento real.","autor":"Cliente Dream Go — Paquete internacional"},{"inicial":"L","texto":"Cotización clara desde el inicio, sin cargos escondidos. Ya vamos por nuestro segundo viaje con ellos.","autor":"Cliente Dream Go — Paquete nacional"}]}',
   '#0f2744'),

  ('home', 'cta_final', 6, 1,
   'Tu próximo viaje empieza con una conversación',
   'Cuéntanos qué tienes en mente y te preparamos una cotización a la medida, sin compromiso.',
   '{"boton_texto":"Solicita tu cotización","boton_link":"/cotizador"}',
   '#0f2744'),

  ('nosotros', 'intro', 1, 1,
   'Sobre Dream Go',
   'En Dream Go Operadora Turística creemos que viajar debe ser una experiencia sin complicaciones. Somos un equipo de asesores de viaje dedicados a diseñar excursiones nacionales e internacionales con itinerarios claros, acompañamiento real y la seguridad de trabajar con un operador establecido.',
   '{"parrafo_2":"Viajes seguros, disfrutar al 100%. Maletas listas, documentos también."}',
   '#d99e8e'),

  ('nosotros', 'estadisticas', 2, 1,
   NULL,
   NULL,
   '{"items":[{"numero":"+50","etiqueta":"Destinos disponibles"},{"numero":"+5,000","etiqueta":"Viajeros felices"},{"numero":"+10","etiqueta":"Años de experiencia"}]}',
   '#faf3ef'),

  ('nosotros', 'cta_final', 3, 1,
   '¿Listo para tu próximo viaje?',
   '',
   '{"boton_texto":"Cotiza con nosotros","boton_link":"/cotizador"}',
   '#d99e8e'),

  ('contacto', 'intro', 1, 1,
   'Hablemos de tu próximo viaje',
   'Escríbenos por el canal que prefieras, nuestro equipo te responderá a la brevedad.',
   NULL,
   NULL),

  ('destinos', 'intro', 1, 1,
   'Nuestros destinos',
   'Elige entre experiencias nacionales e internacionales diseñadas por nuestro equipo.',
   NULL,
   NULL),

  ('paquetes', 'intro', 1, 1,
   'Paquetes y excursiones',
   'Filtra por destino o por tipo de viaje para encontrar tu próxima excursión.',
   NULL,
   '#0f2744')
ON DUPLICATE KEY UPDATE
  orden = VALUES(orden),
  visible = VALUES(visible),
  titulo = VALUES(titulo),
  subtitulo = VALUES(subtitulo),
  contenido = VALUES(contenido),
  color_fondo = VALUES(color_fondo);

COMMIT;

-- Verificación
SELECT pagina, clave, orden, visible, titulo, color_fondo, actualizado_en
FROM bloques_pagina
ORDER BY pagina, orden;
