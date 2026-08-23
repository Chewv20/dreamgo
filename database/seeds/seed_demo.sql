-- Dream Go - Datos iniciales (roles, permisos, usuario admin, contenido placeholder)
SET NAMES utf8mb4;

-- ==========================================================
-- ROLES
-- ==========================================================
INSERT INTO roles (id, nombre, descripcion, es_sistema) VALUES
  (1, 'Administrador', 'Acceso total al sistema. No puede eliminarse.', 1),
  (2, 'Editor', 'Gestion de paquetes y fechas de salida unicamente.', 0);

-- ==========================================================
-- CATALOGO DE PERMISOS
-- ==========================================================
INSERT INTO permisos (clave, modulo, descripcion) VALUES
  ('paquetes.ver', 'paquetes', 'Ver el listado de paquetes'),
  ('paquetes.crear', 'paquetes', 'Crear nuevos paquetes'),
  ('paquetes.editar', 'paquetes', 'Editar paquetes existentes'),
  ('paquetes.eliminar', 'paquetes', 'Archivar o eliminar paquetes'),
  ('salidas.gestionar', 'salidas', 'Gestionar fechas de salida y cupos'),
  ('reservas.ver', 'reservas', 'Ver el listado y calendario de reservas'),
  ('reservas.confirmar', 'reservas', 'Confirmar reservas pendientes'),
  ('reservas.cancelar', 'reservas', 'Cancelar reservas'),
  ('cotizaciones.ver', 'cotizaciones', 'Ver la bandeja de cotizaciones'),
  ('cotizaciones.gestionar', 'cotizaciones', 'Cambiar el estado de una cotizacion'),
  ('ofertas.gestionar', 'ofertas', 'Crear y editar codigos de descuento'),
  ('usuarios.gestionar', 'usuarios', 'Crear y editar usuarios del panel'),
  ('roles.gestionar', 'roles', 'Crear roles y asignar permisos'),
  ('configuracion.gestionar', 'configuracion', 'Editar la configuracion global del sitio'),
  ('reportes.ver', 'reportes', 'Ver reportes y metricas del negocio');

-- Administrador: todos los permisos
INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT 1, id FROM permisos;

-- Editor: solo paquetes y salidas
INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT 2, id FROM permisos WHERE clave IN ('paquetes.ver','paquetes.crear','paquetes.editar','paquetes.eliminar','salidas.gestionar');

-- ==========================================================
-- USUARIO ADMIN INICIAL
-- Email: admin@dreamgooperadoraturistica.com
-- Password temporal: DreamGo2026!  (cambiar en el primer login)
-- ==========================================================
INSERT INTO usuarios_admin (nombre, email, password_hash, rol_id, activo) VALUES
  ('Administrador Dream Go', 'admin@dreamgooperadoraturistica.com', '$2y$12$SgLweP4n7uz6.wT8VSEOrOsaOJ.Ufgmn99AThl83gqRp8aX7b5Iim', 1, 1);

-- ==========================================================
-- CATEGORIAS / DESTINOS
-- ==========================================================
INSERT INTO categorias (id, nombre, slug, tipo, descripcion, orden, activo) VALUES
  (1, 'Playas del Caribe', 'playas-del-caribe', 'nacional', 'Sol, arena blanca y mar turquesa en el Caribe mexicano.', 1, 1),
  (2, 'Pueblos Magicos', 'pueblos-magicos', 'nacional', 'Historia, arquitectura colonial y tradicion en cada rincon.', 2, 1),
  (3, 'Aventura y Naturaleza', 'aventura-y-naturaleza', 'nacional', 'Cascadas, selva y experiencias al aire libre.', 3, 1),
  (4, 'Europa Clasica', 'europa-clasica', 'internacional', 'Las ciudades mas emblematicas del viejo continente.', 4, 1),
  (5, 'Sudamerica Ancestral', 'sudamerica-ancestral', 'internacional', 'Cultura e historia de las civilizaciones andinas.', 5, 1);

-- ==========================================================
-- PAQUETES (contenido placeholder, listo para editarse desde el panel)
-- ==========================================================
INSERT INTO paquetes (id, categoria_id, titulo, slug, resumen, descripcion_larga, itinerario, incluye, no_incluye, precio_desde, duracion_dias, duracion_noches, imagen_portada, destacado, estado, creado_por) VALUES
(1, 1, 'Cancun y Riviera Maya Todo Incluido', 'cancun-riviera-maya-todo-incluido',
  'Playas turquesa, arrecifes y la energia de la Riviera Maya en un solo viaje.',
  '<p>Disfruta de un escape perfecto entre las playas de Cancun y la Riviera Maya, con hospedaje frente al mar y actividades para toda la familia.</p>',
  '<p><strong>Dia 1:</strong> Llegada y traslado al hotel. Tarde libre en la playa.</p><p><strong>Dia 2:</strong> Excursion a Xcaret o cenote a eleccion.</p><p><strong>Dia 3:</strong> Dia libre / tour opcional a Tulum.</p><p><strong>Dia 4:</strong> Desayuno y traslado al aeropuerto.</p>',
  '<ul><li>Traslados aeropuerto-hotel-aeropuerto</li><li>3 noches de hospedaje todo incluido</li><li>Una excursion incluida</li></ul>',
  '<ul><li>Vuelos</li><li>Propinas</li><li>Actividades opcionales no listadas</li></ul>',
  8990.00, 4, 3, '/uploads/paquetes/original/cancun-riviera-maya-todo-incluido.png', 1, 'publicado', 1),

(2, 1, 'Los Cabos Experience', 'los-cabos-experience',
  'Desierto y mar se encuentran en uno de los destinos mas exclusivos de Mexico.',
  '<p>Una experiencia entre el Mar de Cortes y el Oceano Pacifico, ideal para desconectar y disfrutar del lujo casual de Los Cabos.</p>',
  '<p><strong>Dia 1:</strong> Llegada y tarde libre.</p><p><strong>Dia 2:</strong> Paseo en catamaran al Arco de Cabo San Lucas.</p><p><strong>Dia 3:</strong> Dia libre.</p><p><strong>Dia 4:</strong> Tour de vinos en Valle de Guadalupe (opcional).</p><p><strong>Dia 5:</strong> Traslado al aeropuerto.</p>',
  '<ul><li>Traslados</li><li>4 noches de hospedaje</li><li>Paseo en catamaran</li></ul>',
  '<ul><li>Vuelos</li><li>Alimentos no especificados</li><li>Propinas</li></ul>',
  11500.00, 5, 4, '/uploads/paquetes/original/los-cabos-experience.png', 0, 'publicado', 1),

(3, 2, 'San Cristobal de las Casas y Canon del Sumidero', 'san-cristobal-canon-sumidero',
  'Cultura viva, cafetales y uno de los canones mas espectaculares de America.',
  '<p>Recorre las calles empedradas de San Cristobal y navega el imponente Canon del Sumidero en este viaje por Chiapas.</p>',
  '<p><strong>Dia 1:</strong> Llegada a San Cristobal, recorrido por el centro historico.</p><p><strong>Dia 2:</strong> Paseo en lancha por el Canon del Sumidero.</p><p><strong>Dia 3:</strong> Visita a San Juan Chamula y Zinacantan, traslado de salida.</p>',
  '<ul><li>Transporte terrestre durante el tour</li><li>2 noches de hospedaje</li><li>Paseo en lancha por el canon</li></ul>',
  '<ul><li>Vuelos o transporte de llegada a Chiapas</li><li>Alimentos no especificados</li></ul>',
  5200.00, 3, 2, '/uploads/paquetes/original/san-cristobal-canon-sumidero.png', 1, 'publicado', 1),

(4, 2, 'Guanajuato y San Miguel de Allende', 'guanajuato-san-miguel-allende',
  'Callejones, leyendas y la mejor arquitectura colonial del Bajio.',
  '<p>Un recorrido por dos de los pueblos magicos mas queridos de Mexico, entre callejones, plazas y miradores.</p>',
  '<p><strong>Dia 1:</strong> Llegada a Guanajuato, tour de leyendas nocturno.</p><p><strong>Dia 2:</strong> Visita a San Miguel de Allende y regreso.</p><p><strong>Dia 3:</strong> Museo y mirador, traslado de salida.</p>',
  '<ul><li>Transporte terrestre entre ciudades</li><li>2 noches de hospedaje</li><li>Tour de leyendas</li></ul>',
  '<ul><li>Alimentos no especificados</li><li>Entradas a museos opcionales</li></ul>',
  4800.00, 3, 2, '/uploads/paquetes/original/guanajuato-san-miguel-allende.png', 0, 'publicado', 1),

(5, 2, 'Oaxaca Magico: Mezcal, Playas y Cultura', 'oaxaca-magico',
  'Gastronomia, mezcal y la calidez oaxaquena en un solo itinerario.',
  '<p>Descubre la riqueza cultural y gastronomica de Oaxaca, con visita a una fabrica de mezcal artesanal y sus zonas arqueologicas.</p>',
  '<p><strong>Dia 1:</strong> Llegada, recorrido por el centro historico.</p><p><strong>Dia 2:</strong> Monte Alban y Arbol del Tule.</p><p><strong>Dia 3:</strong> Fabrica de mezcal y mercado local.</p><p><strong>Dia 4:</strong> Traslado de salida.</p>',
  '<ul><li>Transporte durante el tour</li><li>3 noches de hospedaje</li><li>Entrada a Monte Alban</li></ul>',
  '<ul><li>Vuelos</li><li>Bebidas alcoholicas</li><li>Propinas</li></ul>',
  6700.00, 4, 3, '/uploads/paquetes/original/oaxaca-magico.png', 0, 'publicado', 1),

(6, 4, 'Europa Clasica: Paris, Roma y Barcelona', 'europa-clasica-paris-roma-barcelona',
  'Tres capitales iconicas en un solo recorrido inolvidable.',
  '<p>El viaje sonado por Europa: arte, historia y arquitectura en Paris, Roma y Barcelona.</p>',
  '<p><strong>Dias 1-3:</strong> Paris: Torre Eiffel, Louvre y Montmartre.</p><p><strong>Dias 4-6:</strong> Roma: Coliseo, Vaticano y Fontana di Trevi.</p><p><strong>Dias 7-9:</strong> Barcelona: Sagrada Familia y Barrio Gotico.</p>',
  '<ul><li>Vuelos entre ciudades europeas</li><li>8 noches de hospedaje</li><li>Tours guiados en cada ciudad</li></ul>',
  '<ul><li>Vuelo internacional de llegada</li><li>Alimentos no especificados</li><li>Propinas</li></ul>',
  42900.00, 9, 8, '/uploads/paquetes/original/europa-clasica-paris-roma-barcelona.png', 1, 'publicado', 1),

(7, 5, 'Peru Ancestral: Lima, Cusco y Machu Picchu', 'peru-ancestral-machu-picchu',
  'El imperio inca cobra vida en uno de los viajes mas buscados de Sudamerica.',
  '<p>Un recorrido completo por Peru: la gastronomia limena, el centro historico de Cusco y la majestuosidad de Machu Picchu.</p>',
  '<p><strong>Dias 1-2:</strong> Lima: centro historico y gastronomia.</p><p><strong>Dias 3-5:</strong> Cusco y Valle Sagrado.</p><p><strong>Dias 6-7:</strong> Machu Picchu y regreso.</p>',
  '<ul><li>Vuelos internos Lima-Cusco</li><li>6 noches de hospedaje</li><li>Entrada a Machu Picchu con guia</li></ul>',
  '<ul><li>Vuelo internacional de llegada</li><li>Alimentos no especificados</li></ul>',
  35800.00, 7, 6, '/uploads/paquetes/original/peru-ancestral-machu-picchu.png', 0, 'publicado', 1),

(8, 3, 'Chiapas Aventura: Cascadas y Selva', 'chiapas-aventura-cascadas-selva',
  'Adrenalina y naturaleza en las cascadas y selvas mas impresionantes del sureste.',
  '<p>Un viaje pensado para los amantes de la naturaleza: cascadas de Agua Azul, Misol-Ha y la selva lacandona.</p>',
  '<p><strong>Dia 1:</strong> Llegada y tarde libre.</p><p><strong>Dia 2:</strong> Cascadas de Agua Azul y Misol-Ha.</p><p><strong>Dia 3:</strong> Palenque y zona arqueologica.</p><p><strong>Dia 4:</strong> Traslado de salida.</p>',
  '<ul><li>Transporte durante el tour</li><li>3 noches de hospedaje</li><li>Entradas a cascadas y zona arqueologica</li></ul>',
  '<ul><li>Vuelos</li><li>Alimentos no especificados</li></ul>',
  5900.00, 4, 3, '/uploads/paquetes/original/chiapas-aventura-cascadas-selva.png', 1, 'publicado', 1);

-- Miniaturas de galeria (portada tambien como primera imagen de la galeria)
INSERT INTO imagenes_paquete (paquete_id, ruta_original, ruta_thumb, alt_text, orden) VALUES
(1, '/uploads/paquetes/original/cancun-riviera-maya-todo-incluido.png', '/uploads/paquetes/thumbs/cancun-riviera-maya-todo-incluido.png', 'Cancun y Riviera Maya', 1),
(2, '/uploads/paquetes/original/los-cabos-experience.png', '/uploads/paquetes/thumbs/los-cabos-experience.png', 'Los Cabos', 1),
(3, '/uploads/paquetes/original/san-cristobal-canon-sumidero.png', '/uploads/paquetes/thumbs/san-cristobal-canon-sumidero.png', 'San Cristobal de las Casas', 1),
(4, '/uploads/paquetes/original/guanajuato-san-miguel-allende.png', '/uploads/paquetes/thumbs/guanajuato-san-miguel-allende.png', 'Guanajuato y San Miguel de Allende', 1),
(5, '/uploads/paquetes/original/oaxaca-magico.png', '/uploads/paquetes/thumbs/oaxaca-magico.png', 'Oaxaca Magico', 1),
(6, '/uploads/paquetes/original/europa-clasica-paris-roma-barcelona.png', '/uploads/paquetes/thumbs/europa-clasica-paris-roma-barcelona.png', 'Europa Clasica', 1),
(7, '/uploads/paquetes/original/peru-ancestral-machu-picchu.png', '/uploads/paquetes/thumbs/peru-ancestral-machu-picchu.png', 'Peru Ancestral', 1),
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
  ('direccion', '', 'Direccion fisica mostrada en el pie de pagina'),
  ('telefono_contacto', '', 'Telefono de contacto mostrado en el pie de pagina'),
  ('email_contacto', 'contacto@dreamgooperadoraturistica.com', 'Correo publico mostrado en el pie de pagina'),
  ('whatsapp_numero', '5215500000000', 'Numero de WhatsApp destino para cotizaciones (formato internacional sin +)'),
  ('facebook_url', '', 'URL de la pagina de Facebook (vacio = icono oculto)'),
  ('instagram_url', '', 'URL del perfil de Instagram (vacio = icono oculto)'),
  ('tiktok_url', '', 'URL del perfil de TikTok (vacio = icono oculto)'),
  ('youtube_url', '', 'URL del canal de YouTube (vacio = icono oculto)'),
  ('horas_expiracion_reserva', '48', 'Horas antes de liberar automaticamente una reserva pendiente'),
  ('dias_recordatorio_viaje', '3', 'Dias de anticipacion para enviar el recordatorio de viaje'),
  ('email_equipo_reportes', 'admin@dreamgooperadoraturistica.com', 'Correo que recibe el reporte periodico y notificaciones de cotizacion'),
  ('meta_title_default', 'Dream Go Operadora Turistica | Excursiones y paquetes de viaje', 'Titulo SEO por defecto'),
  ('meta_description_default', 'Excursiones y paquetes a los mejores destinos de Mexico y el mundo, con la confianza de un equipo experto.', 'Descripcion SEO por defecto');
