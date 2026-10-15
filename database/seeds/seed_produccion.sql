-- Dream Go - Datos iniciales para PRODUCCION: roles, permisos, usuario admin, configuracion y
-- bloques de pagina. Generado de seed_demo.sql SIN el contenido de ejemplo (categorias,
-- paquetes, imagenes y salidas). Mantener sincronizado con seed_demo.sql.
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
  ('modales.gestionar', 'modales', 'Crear y editar los modales promocionales del sitio'),
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
