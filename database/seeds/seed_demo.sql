-- Dream Go - Datos iniciales (roles, permisos, usuario admin, contenido placeholder)
SET NAMES utf8mb4;

-- ==========================================================
-- ROLES
-- ==========================================================
INSERT INTO roles (id, nombre, descripcion, es_sistema) VALUES
  (1, 'Administrador', 'Acceso total al sistema. No puede eliminarse.', 1),
  (2, 'Editor', 'Gestión de paquetes y fechas de salida únicamente.', 0);

-- ==========================================================
-- CATALOGO DE PERMISOS
-- ==========================================================
INSERT INTO permisos (clave, modulo, descripcion) VALUES
  ('paquetes.ver', 'paquetes', 'Ver el listado de paquetes'),
  ('paquetes.crear', 'paquetes', 'Crear nuevos paquetes'),
  ('paquetes.editar', 'paquetes', 'Editar paquetes existentes'),
  ('paquetes.eliminar', 'paquetes', 'Archivar o eliminar paquetes'),
  ('salidas.gestionar', 'salidas', 'Gestionar fechas de salida y cupos'),
  ('destinos.gestionar', 'destinos', 'Crear, editar y ordenar los destinos del sitio'),
  ('reservas.ver', 'reservas', 'Ver el listado y calendario de reservas'),
  ('reservas.crear', 'reservas', 'Crear reservas manuales desde el panel'),
  ('reservas.confirmar', 'reservas', 'Confirmar reservas pendientes'),
  ('reservas.cancelar', 'reservas', 'Cancelar reservas'),
  ('reservas.registrar_pago', 'reservas', 'Registrar pagos manuales (transferencia, efectivo...) en una reserva'),
  ('cotizaciones.ver', 'cotizaciones', 'Ver la bandeja de cotizaciones'),
  ('cotizaciones.gestionar', 'cotizaciones', 'Cambiar el estado de una cotización'),
  ('ofertas.gestionar', 'ofertas', 'Crear y editar códigos de descuento'),
  ('usuarios.gestionar', 'usuarios', 'Crear y editar usuarios del panel'),
  ('roles.gestionar', 'roles', 'Crear roles y asignar permisos'),
  ('configuracion.gestionar', 'configuracion', 'Editar la configuración global del sitio'),
  ('reportes.ver', 'reportes', 'Ver reportes y métricas del negocio'),
  ('contenido.gestionar', 'contenido', 'Editar textos, orden y colores de las páginas públicas'),
  ('resenas.ver', 'resenas', 'Ver el listado de reseñas de clientes'),
  ('resenas.gestionar', 'resenas', 'Aprobar o rechazar reseñas de clientes'),
  ('suscriptores.ver', 'suscriptores', 'Ver el listado de suscriptores al newsletter'),
  ('bitacora.ver', 'bitacora', 'Ver la bitácora de acciones del panel'),
  ('articulos.ver', 'blog', 'Ver el listado de artículos del blog'),
  ('articulos.crear', 'blog', 'Crear nuevos artículos del blog'),
  ('articulos.editar', 'blog', 'Editar artículos del blog existentes'),
  ('articulos.eliminar', 'blog', 'Archivar artículos del blog');

-- Administrador: todos los permisos
INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT 1, id FROM permisos;

-- Editor: solo paquetes y salidas
INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT 2, id FROM permisos WHERE clave IN ('paquetes.ver','paquetes.crear','paquetes.editar','paquetes.eliminar','salidas.gestionar');

-- ==========================================================
-- USUARIO ADMIN INICIAL
-- Email: admin@dreamgooperadoraturistica.com
-- Password temporal: 13449fae57f7ecbba0
-- El panel obliga a cambiarla en el primer login (debe_cambiar_password = 1);
-- no queda utilizable como password real hasta que se reemplace.
-- ==========================================================
INSERT INTO usuarios_admin (nombre, email, password_hash, debe_cambiar_password, rol_id, activo) VALUES
  ('Administrador Dream Go', 'admin@dreamgooperadoraturistica.com', '$2y$12$qzzgX4Ar1SjfbgC/EuxdLu3hSVDMWqYZQt71pyakB2S18pOF03h/y', 1, 1, 1);

-- ==========================================================
-- CATEGORIAS / DESTINOS
-- ==========================================================
INSERT INTO categorias (id, nombre, slug, tipo, descripcion, orden, activo) VALUES
  (1, 'Playas del Caribe', 'playas-del-caribe', 'nacional', 'Sol, arena blanca y mar turquesa en el Caribe mexicano.', 1, 1),
  (2, 'Pueblos Mágicos', 'pueblos-magicos', 'nacional', 'Historia, arquitectura colonial y tradición en cada rincón.', 2, 1),
  (3, 'Aventura y Naturaleza', 'aventura-y-naturaleza', 'nacional', 'Cascadas, selva y experiencias al aire libre.', 3, 1),
  (4, 'Europa Clásica', 'europa-clasica', 'internacional', 'Las ciudades más emblemáticas del viejo continente.', 4, 1),
  (5, 'Sudamérica Ancestral', 'sudamerica-ancestral', 'internacional', 'Cultura e historia de las civilizaciones andinas.', 5, 1);

-- ==========================================================
-- PAQUETES (contenido placeholder, listo para editarse desde el panel)
-- ==========================================================
INSERT INTO paquetes (id, categoria_id, titulo, slug, resumen, descripcion_larga, itinerario, incluye, no_incluye, precio_desde, duracion_dias, duracion_noches, imagen_portada, destacado, estado, creado_por) VALUES
(1, 1, 'Cancún y Riviera Maya Todo Incluido', 'cancun-riviera-maya-todo-incluido',
  'Playas turquesa, arrecifes y la energía de la Riviera Maya en un solo viaje.',
  '<p>Disfruta de un escape perfecto entre las playas de Cancún y la Riviera Maya, con hospedaje frente al mar y actividades para toda la familia.</p>',
  '<p><strong>Día 1:</strong> Llegada y traslado al hotel. Tarde libre en la playa.</p><p><strong>Día 2:</strong> Excursión a Xcaret o cenote a elección.</p><p><strong>Día 3:</strong> Día libre / tour opcional a Tulum.</p><p><strong>Día 4:</strong> Desayuno y traslado al aeropuerto.</p>',
  '<ul><li>Traslados aeropuerto-hotel-aeropuerto</li><li>3 noches de hospedaje todo incluido</li><li>Una excursión incluida</li></ul>',
  '<ul><li>Vuelos</li><li>Propinas</li><li>Actividades opcionales no listadas</li></ul>',
  8990.00, 4, 3, '/uploads/paquetes/original/cancun-riviera-maya-todo-incluido.png', 1, 'publicado', 1),

(2, 1, 'Los Cabos Experience', 'los-cabos-experience',
  'Desierto y mar se encuentran en uno de los destinos más exclusivos de México.',
  '<p>Una experiencia entre el Mar de Cortés y el Océano Pacífico, ideal para desconectar y disfrutar del lujo casual de Los Cabos.</p>',
  '<p><strong>Día 1:</strong> Llegada y tarde libre.</p><p><strong>Día 2:</strong> Paseo en catamarán al Arco de Cabo San Lucas.</p><p><strong>Día 3:</strong> Día libre.</p><p><strong>Día 4:</strong> Tour de vinos en Valle de Guadalupe (opcional).</p><p><strong>Día 5:</strong> Traslado al aeropuerto.</p>',
  '<ul><li>Traslados</li><li>4 noches de hospedaje</li><li>Paseo en catamarán</li></ul>',
  '<ul><li>Vuelos</li><li>Alimentos no especificados</li><li>Propinas</li></ul>',
  11500.00, 5, 4, '/uploads/paquetes/original/los-cabos-experience.png', 0, 'publicado', 1),

(3, 2, 'San Cristóbal de las Casas y Cañón del Sumidero', 'san-cristobal-canon-sumidero',
  'Cultura viva, cafetales y uno de los cañones más espectaculares de América.',
  '<p>Recorre las calles empedradas de San Cristóbal y navega el imponente Cañón del Sumidero en este viaje por Chiapas.</p>',
  '<p><strong>Día 1:</strong> Llegada a San Cristóbal, recorrido por el centro histórico.</p><p><strong>Día 2:</strong> Paseo en lancha por el Cañón del Sumidero.</p><p><strong>Día 3:</strong> Visita a San Juan Chamula y Zinacantán, traslado de salida.</p>',
  '<ul><li>Transporte terrestre durante el tour</li><li>2 noches de hospedaje</li><li>Paseo en lancha por el cañón</li></ul>',
  '<ul><li>Vuelos o transporte de llegada a Chiapas</li><li>Alimentos no especificados</li></ul>',
  5200.00, 3, 2, '/uploads/paquetes/original/san-cristobal-canon-sumidero.png', 1, 'publicado', 1),

(4, 2, 'Guanajuato y San Miguel de Allende', 'guanajuato-san-miguel-allende',
  'Callejones, leyendas y la mejor arquitectura colonial del Bajío.',
  '<p>Un recorrido por dos de los pueblos mágicos más queridos de México, entre callejones, plazas y miradores.</p>',
  '<p><strong>Día 1:</strong> Llegada a Guanajuato, tour de leyendas nocturno.</p><p><strong>Día 2:</strong> Visita a San Miguel de Allende y regreso.</p><p><strong>Día 3:</strong> Museo y mirador, traslado de salida.</p>',
  '<ul><li>Transporte terrestre entre ciudades</li><li>2 noches de hospedaje</li><li>Tour de leyendas</li></ul>',
  '<ul><li>Alimentos no especificados</li><li>Entradas a museos opcionales</li></ul>',
  4800.00, 3, 2, '/uploads/paquetes/original/guanajuato-san-miguel-allende.png', 0, 'publicado', 1),

(5, 2, 'Oaxaca Mágico: Mezcal, Playas y Cultura', 'oaxaca-magico',
  'Gastronomía, mezcal y la calidez oaxaqueña en un solo itinerario.',
  '<p>Descubre la riqueza cultural y gastronómica de Oaxaca, con visita a una fábrica de mezcal artesanal y sus zonas arqueológicas.</p>',
  '<p><strong>Día 1:</strong> Llegada, recorrido por el centro histórico.</p><p><strong>Día 2:</strong> Monte Albán y Árbol del Tule.</p><p><strong>Día 3:</strong> Fábrica de mezcal y mercado local.</p><p><strong>Día 4:</strong> Traslado de salida.</p>',
  '<ul><li>Transporte durante el tour</li><li>3 noches de hospedaje</li><li>Entrada a Monte Albán</li></ul>',
  '<ul><li>Vuelos</li><li>Bebidas alcohólicas</li><li>Propinas</li></ul>',
  6700.00, 4, 3, '/uploads/paquetes/original/oaxaca-magico.png', 0, 'publicado', 1),

(6, 4, 'Europa Clásica: París, Roma y Barcelona', 'europa-clasica-paris-roma-barcelona',
  'Tres capitales icónicas en un solo recorrido inolvidable.',
  '<p>El viaje soñado por Europa: arte, historia y arquitectura en París, Roma y Barcelona.</p>',
  '<p><strong>Días 1-3:</strong> París: Torre Eiffel, Louvre y Montmartre.</p><p><strong>Días 4-6:</strong> Roma: Coliseo, Vaticano y Fontana di Trevi.</p><p><strong>Días 7-9:</strong> Barcelona: Sagrada Familia y Barrio Gótico.</p>',
  '<ul><li>Vuelos entre ciudades europeas</li><li>8 noches de hospedaje</li><li>Tours guiados en cada ciudad</li></ul>',
  '<ul><li>Vuelo internacional de llegada</li><li>Alimentos no especificados</li><li>Propinas</li></ul>',
  42900.00, 9, 8, '/uploads/paquetes/original/europa-clasica-paris-roma-barcelona.png', 1, 'publicado', 1),

(7, 5, 'Perú Ancestral: Lima, Cusco y Machu Picchu', 'peru-ancestral-machu-picchu',
  'El imperio inca cobra vida en uno de los viajes más buscados de Sudamérica.',
  '<p>Un recorrido completo por Perú: la gastronomía limeña, el centro histórico de Cusco y la majestuosidad de Machu Picchu.</p>',
  '<p><strong>Días 1-2:</strong> Lima: centro histórico y gastronomía.</p><p><strong>Días 3-5:</strong> Cusco y Valle Sagrado.</p><p><strong>Días 6-7:</strong> Machu Picchu y regreso.</p>',
  '<ul><li>Vuelos internos Lima-Cusco</li><li>6 noches de hospedaje</li><li>Entrada a Machu Picchu con guía</li></ul>',
  '<ul><li>Vuelo internacional de llegada</li><li>Alimentos no especificados</li></ul>',
  35800.00, 7, 6, '/uploads/paquetes/original/peru-ancestral-machu-picchu.png', 0, 'publicado', 1),

(8, 3, 'Chiapas Aventura: Cascadas y Selva', 'chiapas-aventura-cascadas-selva',
  'Adrenalina y naturaleza en las cascadas y selvas más impresionantes del sureste.',
  '<p>Un viaje pensado para los amantes de la naturaleza: cascadas de Agua Azul, Misol-Ha y la selva lacandona.</p>',
  '<p><strong>Día 1:</strong> Llegada y tarde libre.</p><p><strong>Día 2:</strong> Cascadas de Agua Azul y Misol-Ha.</p><p><strong>Día 3:</strong> Palenque y zona arqueológica.</p><p><strong>Día 4:</strong> Traslado de salida.</p>',
  '<ul><li>Transporte durante el tour</li><li>3 noches de hospedaje</li><li>Entradas a cascadas y zona arqueológica</li></ul>',
  '<ul><li>Vuelos</li><li>Alimentos no especificados</li></ul>',
  5900.00, 4, 3, '/uploads/paquetes/original/chiapas-aventura-cascadas-selva.png', 1, 'publicado', 1);

-- Miniaturas de galeria (portada tambien como primera imagen de la galeria)
INSERT INTO imagenes_paquete (paquete_id, ruta_original, ruta_thumb, alt_text, orden) VALUES
(1, '/uploads/paquetes/original/cancun-riviera-maya-todo-incluido.png', '/uploads/paquetes/thumbs/cancun-riviera-maya-todo-incluido.png', 'Cancún y Riviera Maya', 1),
(2, '/uploads/paquetes/original/los-cabos-experience.png', '/uploads/paquetes/thumbs/los-cabos-experience.png', 'Los Cabos', 1),
(3, '/uploads/paquetes/original/san-cristobal-canon-sumidero.png', '/uploads/paquetes/thumbs/san-cristobal-canon-sumidero.png', 'San Cristóbal de las Casas', 1),
(4, '/uploads/paquetes/original/guanajuato-san-miguel-allende.png', '/uploads/paquetes/thumbs/guanajuato-san-miguel-allende.png', 'Guanajuato y San Miguel de Allende', 1),
(5, '/uploads/paquetes/original/oaxaca-magico.png', '/uploads/paquetes/thumbs/oaxaca-magico.png', 'Oaxaca Mágico', 1),
(6, '/uploads/paquetes/original/europa-clasica-paris-roma-barcelona.png', '/uploads/paquetes/thumbs/europa-clasica-paris-roma-barcelona.png', 'Europa Clásica', 1),
(7, '/uploads/paquetes/original/peru-ancestral-machu-picchu.png', '/uploads/paquetes/thumbs/peru-ancestral-machu-picchu.png', 'Perú Ancestral', 1),
(8, '/uploads/paquetes/original/chiapas-aventura-cascadas-selva.png', '/uploads/paquetes/thumbs/chiapas-aventura-cascadas-selva.png', 'Chiapas Aventura', 1);

-- ==========================================================
-- SALIDAS (fechas y cupos de ejemplo, futuras respecto a hoy)
-- ==========================================================
INSERT INTO salidas (paquete_id, fecha_salida, fecha_regreso, cupo_maximo, cupo_disponible, estado) VALUES
(1, DATE_ADD(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 23 DAY), 20, 20, 'abierta'),
(1, DATE_ADD(CURDATE(), INTERVAL 50 DAY), DATE_ADD(CURDATE(), INTERVAL 53 DAY), 20, 14, 'abierta'),
(2, DATE_ADD(CURDATE(), INTERVAL 35 DAY), DATE_ADD(CURDATE(), INTERVAL 39 DAY), 16, 16, 'abierta'),
(3, DATE_ADD(CURDATE(), INTERVAL 15 DAY), DATE_ADD(CURDATE(), INTERVAL 17 DAY), 25, 9, 'abierta'),
(4, DATE_ADD(CURDATE(), INTERVAL 25 DAY), DATE_ADD(CURDATE(), INTERVAL 27 DAY), 20, 20, 'abierta'),
(5, DATE_ADD(CURDATE(), INTERVAL 40 DAY), DATE_ADD(CURDATE(), INTERVAL 43 DAY), 18, 5, 'abierta'),
(6, DATE_ADD(CURDATE(), INTERVAL 70 DAY), DATE_ADD(CURDATE(), INTERVAL 78 DAY), 15, 15, 'abierta'),
(7, DATE_ADD(CURDATE(), INTERVAL 60 DAY), DATE_ADD(CURDATE(), INTERVAL 66 DAY), 12, 3, 'abierta'),
(8, DATE_ADD(CURDATE(), INTERVAL 18 DAY), DATE_ADD(CURDATE(), INTERVAL 21 DAY), 20, 20, 'abierta');

-- ==========================================================
-- CONFIGURACION DEL SITIO (parametros editables, sin hardcodear en el codigo)
-- ==========================================================
INSERT INTO configuracion_sitio (clave, valor, descripcion) VALUES
  ('direccion', '', 'Dirección física mostrada en el pie de página'),
  ('telefono_contacto', '', 'Teléfono de contacto mostrado en el pie de página'),
  ('email_contacto', 'contacto@dreamgooperadoraturistica.com', 'Correo público mostrado en el pie de página'),
  ('whatsapp_numero', '5215500000000', 'Número de WhatsApp destino para cotizaciones (formato internacional sin +)'),
  ('facebook_url', '', 'URL de la página de Facebook (vacío = icono oculto)'),
  ('instagram_url', '', 'URL del perfil de Instagram (vacío = icono oculto)'),
  ('tiktok_url', '', 'URL del perfil de TikTok (vacío = icono oculto)'),
  ('youtube_url', '', 'URL del canal de YouTube (vacío = icono oculto)'),
  ('horas_expiracion_reserva', '48', 'Horas antes de liberar automáticamente una reserva pendiente'),
  ('porcentaje_anticipo_reserva', '30', 'Porcentaje del precio total que se cobra como anticipo en línea al reservar (1-100)'),
  ('dias_recordatorio_viaje', '3', 'Días de anticipación para enviar el recordatorio de viaje'),
  ('abono_minimo_saldo', '500', 'Monto mínimo de cada abono al saldo que el cliente paga en línea (si el saldo es menor, paga el saldo completo)'),
  ('dias_recordatorio_saldo', '7', 'Días antes de la salida para enviar el recordatorio de saldo pendiente al cliente'),
  ('dias_solicitud_resena', '3', 'Días después de terminado el viaje (fecha_regreso o fecha_salida) para pedir una reseña al cliente'),
  ('email_equipo_reportes', 'admin@dreamgooperadoraturistica.com', 'Correo que recibe el reporte periódico y notificaciones de cotización'),
  ('meta_title_default', 'Dream Go Operadora Turística | Excursiones y paquetes de viaje', 'Título SEO por defecto'),
  ('meta_description_default', 'Excursiones y paquetes a los mejores destinos de México y el mundo, con la confianza de un equipo experto.', 'Descripción SEO por defecto'),
  ('color_primario', '#d99e8e', 'Color primario (acentos, botones secundarios)'),
  ('color_primario_oscuro', '#a85f4d', 'Color primario oscuro (botones principales, enlaces)'),
  ('color_texto_oscuro', '#2f3e46', 'Color de texto principal y fondos oscuros (footer, hero)'),
  ('color_fondo', '#fdfaf8', 'Color de fondo general del sitio'),
  ('color_fondo_alterno', '#faf3ef', 'Color de fondo alterno (secciones destacadas)'),
  ('color_exito', '#4f7a5c', 'Color para mensajes de éxito'),
  ('color_error', '#b3453a', 'Color para mensajes de error');

-- ==========================================================
-- BLOQUES DE CONTENIDO: HOME
-- ==========================================================
INSERT INTO bloques_pagina (pagina, clave, orden, visible, titulo, subtitulo, contenido, color_fondo) VALUES
  ('home', 'hero', 1, 1,
   'Descubre el mundo con la tranquilidad de viajar acompañado',
   'Diseñamos excursiones y paquetes a los mejores destinos de México y el mundo, con asesoría personalizada de principio a fin.',
   '{"cta_primario_texto":"Cotiza tu viaje","cta_primario_link":"/cotizador","cta_secundario_texto":"Ver paquetes","cta_secundario_link":"/paquetes"}',
   NULL),
  ('home', 'ventajas', 2, 1,
   'Por qué viajar con Dream Go',
   'Nos ocupamos de los detalles para que tú solo te preocupes de disfrutar el viaje.',
   '{"items":[{"icono":"chat","titulo":"Asesoría personalizada","texto":"Te acompañamos desde la elección del destino hasta el regreso a casa, resolviendo cada duda en el camino."},{"icono":"mapa","titulo":"Itinerarios claros","texto":"Sin sorpresas: sabes exactamente qué incluye cada paquete antes de reservar."},{"icono":"reloj","titulo":"Soporte en todo momento","texto":"Resolvemos tus dudas antes, durante y después del viaje: nunca estás solo en el camino."},{"icono":"etiqueta","titulo":"Precios transparentes","texto":"Cotizaciones claras y sin cargos ocultos, para que planees tu presupuesto con confianza."}]}',
   NULL),
  ('home', 'destinos', 3, 1,
   'Explora por destino',
   'Recorre nuestras categorías nacionales e internacionales y encuentra tu próxima escapada.',
   NULL,
   NULL),
  ('home', 'paquetes_destacados', 4, 1,
   'Paquetes destacados',
   'Una selección de las excursiones favoritas de nuestros viajeros.',
   NULL,
   '#faf3ef'),
  ('home', 'testimonios', 5, 1,
   'Lo que dicen quienes ya viajaron',
   NULL,
   '{"items":[{"inicial":"M","texto":"La asesoría fue clave: nos ayudaron a armar el itinerario perfecto para el tiempo que teníamos y todo salió como lo planeamos.","autor":"Cliente Dream Go — Paquete nacional"},{"inicial":"R","texto":"Viajamos en grupo y el equipo resolvió cada duda por WhatsApp casi al instante. Se siente el acompañamiento real.","autor":"Cliente Dream Go — Paquete internacional"},{"inicial":"L","texto":"Cotización clara desde el inicio, sin cargos escondidos. Ya vamos por nuestro segundo viaje con ellos.","autor":"Cliente Dream Go — Paquete nacional"}]}',
   NULL),
  ('home', 'cta_final', 6, 1,
   'Tu próximo viaje empieza con una conversación',
   'Cuéntanos qué tienes en mente y te preparamos una cotización a la medida, sin compromiso.',
   '{"boton_texto":"Solicita tu cotización","boton_link":"/cotizador"}',
   NULL);

-- ==========================================================
-- BLOQUES DE CONTENIDO: NOSOTROS, CONTACTO, DESTINOS, PAQUETES
-- ==========================================================
INSERT INTO bloques_pagina (pagina, clave, orden, visible, titulo, subtitulo, contenido, color_fondo) VALUES
  ('nosotros', 'intro', 1, 1,
   'Sobre Dream Go',
   'En Dream Go Operadora Turística creemos que viajar debe ser una experiencia sin complicaciones. Somos un equipo de asesores de viaje dedicados a diseñar excursiones nacionales e internacionales con itinerarios claros, acompañamiento real y la seguridad de trabajar con un operador establecido.',
   '{"parrafo_2":"Desde nuestros primeros grupos hasta hoy, nuestro compromiso ha sido el mismo: que cada viajero se sienta acompañado antes, durante y después de su viaje."}',
   NULL),
  ('nosotros', 'estadisticas', 2, 1,
   NULL,
   NULL,
   '{"items":[{"numero":"+50","etiqueta":"Destinos disponibles"},{"numero":"+5,000","etiqueta":"Viajeros felices"},{"numero":"+10","etiqueta":"Años de experiencia"}]}',
   '#faf3ef'),
  ('nosotros', 'cta_final', 3, 1,
   '¿Listo para tu próximo viaje?',
   NULL,
   '{"boton_texto":"Cotiza con nosotros","boton_link":"/cotizador"}',
   NULL),
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
   NULL);
