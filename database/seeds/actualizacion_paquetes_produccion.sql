-- Actualización de paquetes en producción (2026-10-16).
--
-- Correr DESPUÉS de actualizacion_categorias_produccion.sql: la categoría de cada paquete se
-- busca por slug, no por id, porque en producción los ids de categorias pueden no coincidir
-- con los locales. Si falta una categoría, el INSERT falla (categoria_id es NOT NULL) y la
-- transacción no aplica nada.
--
-- Upsert sobre la llave única `slug`: actualiza el paquete si existe y lo crea si no.
-- `creado_por` y `creado_en` solo se fijan al crear; `actualizado_en` se actualiza solo.
-- Es idempotente: correrlo dos veces deja el mismo resultado.
--
-- Sin prefijo de base de datos: selecciona la BD de producción antes de correrlo.
-- Uso: phpMyAdmin > pestaña SQL, o  mysql --default-character-set=utf8mb4 <bd> < este_archivo
--
-- OJO: las portadas apuntan a /uploads/paquetes/original/; esos archivos deben existir en el
-- servidor (los uploads no viven en el repo).

SET NAMES utf8mb4;

START TRANSACTION;

SET @cat_playas    = (SELECT id FROM categorias WHERE slug = 'playas-del-caribe');
SET @cat_pueblos   = (SELECT id FROM categorias WHERE slug = 'pueblos-magicos');
SET @cat_aventura  = (SELECT id FROM categorias WHERE slug = 'aventura-y-naturaleza');
SET @cat_europa    = (SELECT id FROM categorias WHERE slug = 'europa-clasica');
SET @cat_sudamerica = (SELECT id FROM categorias WHERE slug = 'sudamerica-ancestral');
SET @admin         = (SELECT id FROM usuarios_admin WHERE email = 'admin@dreamgooperadoraturistica.com');

INSERT INTO paquetes (categoria_id, titulo, slug, resumen, descripcion_larga, itinerario, incluye, no_incluye,
                      precio_desde, moneda, duracion_dias, duracion_noches, imagen_portada, destacado,
                      meta_title, meta_description, estado, creado_por) VALUES
  (@cat_playas,
   'Cancún y Riviera Maya',
   'cancun-y-riviera-maya',
   'Playas turquesa, arrecifes y la energía de la Riviera Maya en un solo viaje.',
   '<p>Disfruta de un escape perfecto entre las playas de Cancún y la Riviera Maya, con hospedaje frente al mar y actividades para toda la familia.</p>',
   '<p><strong>Día 1:</strong> Llegada y traslado al hotel. Tarde libre en la playa.</p><p><strong>Día 2:</strong> Excursión a Xcaret o cenote a elección.</p><p><strong>Día 3:</strong> Día libre / tour opcional a Tulum.</p><p><strong>Día 4:</strong> Desayuno y traslado al aeropuerto.</p>',
   '<ul><li>Traslados aeropuerto-hotel-aeropuerto</li><li>3 noches de hospedaje todo incluido</li><li>Una excursión incluida</li></ul>',
   '<ul><li>Vuelos</li><li>Propinas</li><li>Actividades opcionales no listadas</li></ul>',
   8763.00, 'MXN', 4, 3, '/uploads/paquetes/original/cancun-y-riviera-maya-e72cdfe9a6c3.jpg', 1,
   NULL, NULL, 'publicado', @admin),

  (@cat_playas,
   'Los Cabos Experience',
   'los-cabos-experience',
   'Desierto y mar se encuentran en uno de los destinos más exclusivos de México.',
   '<p>Una experiencia entre el Mar de Cortés y el Océano Pacífico, ideal para desconectar y disfrutar del lujo casual de Los Cabos.</p>',
   '<p><strong>Día 1:</strong> Llegada y tarde libre.</p><p><strong>Día 2:</strong> Paseo en catamarán al Arco de Cabo San Lucas.</p><p><strong>Día 3:</strong> Día libre.</p><p><strong>Día 4:</strong> Tour de vinos en Valle de Guadalupe (opcional).</p><p><strong>Día 5:</strong> Traslado al aeropuerto.</p>',
   '<ul><li>Traslados</li><li>4 noches de hospedaje</li><li>Paseo en catamarán</li></ul>',
   '<ul><li>Vuelos</li><li>Alimentos no especificados</li><li>Propinas</li></ul>',
   11500.00, 'MXN', 5, 4, '/uploads/paquetes/original/los-cabos-experience-2acd29b0abf9.jpg', 0,
   NULL, NULL, 'archivado', @admin),

  (@cat_pueblos,
   'Explora Chiapas',
   'explora-chiapas',
   'Cultura, arqueología, cascadas y uno de los cañones más espectaculares.',
   '<p>Recorre las calles empedradas de San Cristóbal y navega el imponente Cañón del Sumidero.</p>',
   '<p><strong>Día 1:</strong> Tuxtla-San Cristóbal.</p><p><strong>Día 2:</strong> Lagos de Colón + El Chiflón.</p><p><strong>Día 3:</strong> San Cristóbal-Agua Azul-Palenque.</p><p><strong>Día 4:</strong> Palenque-Misol-Ha-San Cristóbal.</p><p><strong>Día 5:</strong> Cañón del Sumidero-Chiapa de Corzo.</p><p><strong>Día 6:</strong> San Cristóbal-Aeropuerto de Tuxtla.</p>',
   '<ul><li>Hospedaje</li><li>Tours</li><li>Traslados</li></ul>',
   '<ul><li>Avión desde tu ciudad de origen</li><li>Propinas</li></ul>',
   11500.00, 'MXN', 6, 5, '/uploads/paquetes/original/san-cristobal-canon-sumidero-fd98780e912b.jpg', 1,
   NULL, NULL, 'publicado', @admin),

  (@cat_pueblos,
   'Guanajuato y San Miguel de Allende',
   'guanajuato-san-miguel-allende',
   'Callejones, leyendas y la mejor arquitectura colonial del Bajío.',
   '<p>Un recorrido por dos de los pueblos mágicos más queridos de México, entre callejones, plazas y miradores.</p>',
   '<p><strong>Día 1:</strong> Llegada a Guanajuato, tour de leyendas nocturno.</p><p><strong>Día 2:</strong> Visita a San Miguel de Allende y regreso.</p><p><strong>Día 3:</strong> Museo y mirador, traslado de salida.</p>',
   '<ul><li>Transporte terrestre entre ciudades</li><li>2 noches de hospedaje</li><li>Tour de leyendas</li></ul>',
   '<ul><li>Alimentos no especificados</li><li>Entradas a museos opcionales</li></ul>',
   4800.00, 'MXN', 3, 2, NULL, 0,
   NULL, NULL, 'archivado', @admin),

  (@cat_pueblos,
   'Oaxaca Mágico: Mezcal, Playas y Cultura',
   'oaxaca-magico',
   'Gastronomía, mezcal y la calidez oaxaqueña en un solo itinerario.',
   '<p>Descubre la riqueza cultural y gastronómica de Oaxaca, con visita a una fábrica de mezcal artesanal y sus zonas arqueológicas.</p>',
   '<p><strong>Día 1:</strong> Llegada, recorrido por el centro histórico.</p><p><strong>Día 2:</strong> Monte Albán y Árbol del Tule.</p><p><strong>Día 3:</strong> Fábrica de mezcal y mercado local.</p><p><strong>Día 4:</strong> Traslado de salida.</p>',
   '<ul><li>Transporte durante el tour</li><li>3 noches de hospedaje</li><li>Entrada a Monte Albán</li></ul>',
   '<ul><li>Vuelos</li><li>Bebidas alcohólicas</li><li>Propinas</li></ul>',
   6700.00, 'MXN', 4, 3, '/uploads/paquetes/original/oaxaca-magico.png', 0,
   NULL, NULL, 'archivado', @admin),

  (@cat_europa,
   'Europa Clásica: París, Roma y Barcelona',
   'europa-clasica-paris-roma-barcelona',
   'Tres capitales icónicas en un solo recorrido inolvidable.',
   '<p>El viaje soñado por Europa: arte, historia y arquitectura en París, Roma y Barcelona.</p>',
   '<p><strong>Días 1-3:</strong> París: Torre Eiffel, Louvre y Montmartre.</p><p><strong>Días 4-6:</strong> Roma: Coliseo, Vaticano y Fontana di Trevi.</p><p><strong>Días 7-9:</strong> Barcelona: Sagrada Familia y Barrio Gótico.</p>',
   '<ul><li>Vuelos entre ciudades europeas</li><li>8 noches de hospedaje</li><li>Tours guiados en cada ciudad</li></ul>',
   '<ul><li>Vuelo internacional de llegada</li><li>Alimentos no especificados</li><li>Propinas</li></ul>',
   42900.00, 'MXN', 9, 8, '/uploads/paquetes/original/europa-clasica-paris-roma-barcelona-cf6490a0e620.jpg', 1,
   NULL, NULL, 'publicado', @admin),

  (@cat_sudamerica,
   'Perú Ancestral: Lima, Cusco y Machu Picchu',
   'peru-ancestral-machu-picchu',
   'El imperio inca cobra vida en uno de los viajes más buscados de Sudamérica.',
   '<p>Un recorrido completo por Perú: la gastronomía limeña, el centro histórico de Cusco y la majestuosidad de Machu Picchu.</p>',
   '<p><strong>Días 1-2:</strong> Lima: centro histórico y gastronomía.</p><p><strong>Días 3-5:</strong> Cusco y Valle Sagrado.</p><p><strong>Días 6-7:</strong> Machu Picchu y regreso.</p>',
   '<ul><li>Vuelos internos Lima-Cusco</li><li>6 noches de hospedaje</li><li>Entrada a Machu Picchu con guía</li></ul>',
   '<ul><li>Vuelo internacional de llegada</li><li>Alimentos no especificados</li></ul>',
   35800.00, 'MXN', 7, 6, '/uploads/paquetes/original/peru-ancestral-machu-picchu.png', 0,
   NULL, NULL, 'publicado', @admin),

  (@cat_aventura,
   'Chiapas Aventura: Cascadas y Selva',
   'chiapas-aventura-cascadas-selva',
   'Adrenalina y naturaleza en las cascadas y selvas más impresionantes del sureste.',
   '<p>Un viaje pensado para los amantes de la naturaleza: cascadas de Agua Azul, Misol-Ha y la selva lacandona.</p>',
   '<p><strong>Día 1:</strong> Llegada y tarde libre.</p><p><strong>Día 2:</strong> Cascadas de Agua Azul y Misol-Ha.</p><p><strong>Día 3:</strong> Palenque y zona arqueológica.</p><p><strong>Día 4:</strong> Traslado de salida.</p>',
   '<ul><li>Transporte durante el tour</li><li>3 noches de hospedaje</li><li>Entradas a cascadas y zona arqueológica</li></ul>',
   '<ul><li>Vuelos</li><li>Alimentos no especificados</li></ul>',
   5900.00, 'MXN', 4, 3, '/uploads/paquetes/original/chiapas-aventura-cascadas-selva.png', 1,
   NULL, NULL, 'archivado', @admin)
ON DUPLICATE KEY UPDATE
  categoria_id = VALUES(categoria_id),
  titulo = VALUES(titulo),
  resumen = VALUES(resumen),
  descripcion_larga = VALUES(descripcion_larga),
  itinerario = VALUES(itinerario),
  incluye = VALUES(incluye),
  no_incluye = VALUES(no_incluye),
  precio_desde = VALUES(precio_desde),
  moneda = VALUES(moneda),
  duracion_dias = VALUES(duracion_dias),
  duracion_noches = VALUES(duracion_noches),
  imagen_portada = VALUES(imagen_portada),
  destacado = VALUES(destacado),
  meta_title = VALUES(meta_title),
  meta_description = VALUES(meta_description),
  estado = VALUES(estado);

COMMIT;

-- Verificación
SELECT p.id, p.titulo, p.slug, c.slug AS categoria, p.precio_desde, p.estado, p.destacado, p.imagen_portada
FROM paquetes p
JOIN categorias c ON c.id = p.categoria_id
ORDER BY p.id;
