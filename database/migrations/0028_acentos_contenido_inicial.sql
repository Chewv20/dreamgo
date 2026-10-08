-- Acentos y ñ en el contenido inicial (seed_demo.sql se cargó sin ellos).
-- Cada UPDATE sólo cambia el valor si sigue idéntico al original sin acentos: el texto que
-- ya se haya editado desde el panel no se toca.
SET NAMES utf8mb4;

UPDATE categorias SET nombre = 'Pueblos Mágicos'
WHERE id = '2' AND nombre = 'Pueblos Magicos';

UPDATE categorias SET descripcion = 'Historia, arquitectura colonial y tradición en cada rincón.'
WHERE id = '2' AND descripcion = 'Historia, arquitectura colonial y tradicion en cada rincon.';

UPDATE categorias SET nombre = 'Europa Clásica'
WHERE id = '4' AND nombre = 'Europa Clasica';

UPDATE categorias SET descripcion = 'Las ciudades más emblemáticas del viejo continente.'
WHERE id = '4' AND descripcion = 'Las ciudades mas emblematicas del viejo continente.';

UPDATE categorias SET nombre = 'Sudamérica Ancestral'
WHERE id = '5' AND nombre = 'Sudamerica Ancestral';

UPDATE paquetes SET titulo = 'Cancún y Riviera Maya Todo Incluido'
WHERE id = '1' AND titulo = 'Cancun y Riviera Maya Todo Incluido';

UPDATE paquetes SET resumen = 'Playas turquesa, arrecifes y la energía de la Riviera Maya en un solo viaje.'
WHERE id = '1' AND resumen = 'Playas turquesa, arrecifes y la energia de la Riviera Maya en un solo viaje.';

UPDATE paquetes SET resumen = 'Desierto y mar se encuentran en uno de los destinos más exclusivos de México.'
WHERE id = '2' AND resumen = 'Desierto y mar se encuentran en uno de los destinos mas exclusivos de Mexico.';

UPDATE paquetes SET descripcion_larga = '<p>Una experiencia entre el Mar de Cortés y el Océano Pacífico, ideal para desconectar y disfrutar del lujo casual de Los Cabos.</p>'
WHERE id = '2' AND descripcion_larga = '<p>Una experiencia entre el Mar de Cortes y el Oceano Pacifico, ideal para desconectar y disfrutar del lujo casual de Los Cabos.</p>';

UPDATE paquetes SET itinerario = '<p><strong>Día 1:</strong> Llegada y tarde libre.</p><p><strong>Día 2:</strong> Paseo en catamarán al Arco de Cabo San Lucas.</p><p><strong>Día 3:</strong> Día libre.</p><p><strong>Día 4:</strong> Tour de vinos en Valle de Guadalupe (opcional).</p><p><strong>Día 5:</strong> Traslado al aeropuerto.</p>'
WHERE id = '2' AND itinerario = '<p><strong>Dia 1:</strong> Llegada y tarde libre.</p><p><strong>Dia 2:</strong> Paseo en catamaran al Arco de Cabo San Lucas.</p><p><strong>Dia 3:</strong> Dia libre.</p><p><strong>Dia 4:</strong> Tour de vinos en Valle de Guadalupe (opcional).</p><p><strong>Dia 5:</strong> Traslado al aeropuerto.</p>';

UPDATE paquetes SET incluye = '<ul><li>Traslados</li><li>4 noches de hospedaje</li><li>Paseo en catamarán</li></ul>'
WHERE id = '2' AND incluye = '<ul><li>Traslados</li><li>4 noches de hospedaje</li><li>Paseo en catamaran</li></ul>';

UPDATE paquetes SET titulo = 'San Cristóbal de las Casas y Cañón del Sumidero'
WHERE id = '3' AND titulo = 'San Cristobal de las Casas y Canon del Sumidero';

UPDATE paquetes SET resumen = 'Cultura viva, cafetales y uno de los cañones más espectaculares de América.'
WHERE id = '3' AND resumen = 'Cultura viva, cafetales y uno de los canones mas espectaculares de America.';

UPDATE paquetes SET descripcion_larga = '<p>Recorre las calles empedradas de San Cristóbal y navega el imponente Cañón del Sumidero en este viaje por Chiapas.</p>'
WHERE id = '3' AND descripcion_larga = '<p>Recorre las calles empedradas de San Cristobal y navega el imponente Canon del Sumidero en este viaje por Chiapas.</p>';

UPDATE paquetes SET itinerario = '<p><strong>Día 1:</strong> Llegada a San Cristóbal, recorrido por el centro histórico.</p><p><strong>Día 2:</strong> Paseo en lancha por el Cañón del Sumidero.</p><p><strong>Día 3:</strong> Visita a San Juan Chamula y Zinacantán, traslado de salida.</p>'
WHERE id = '3' AND itinerario = '<p><strong>Dia 1:</strong> Llegada a San Cristobal, recorrido por el centro historico.</p><p><strong>Dia 2:</strong> Paseo en lancha por el Canon del Sumidero.</p><p><strong>Dia 3:</strong> Visita a San Juan Chamula y Zinacantan, traslado de salida.</p>';

UPDATE paquetes SET incluye = '<ul><li>Transporte terrestre durante el tour</li><li>2 noches de hospedaje</li><li>Paseo en lancha por el cañón</li></ul>'
WHERE id = '3' AND incluye = '<ul><li>Transporte terrestre durante el tour</li><li>2 noches de hospedaje</li><li>Paseo en lancha por el canon</li></ul>';

UPDATE paquetes SET resumen = 'Callejones, leyendas y la mejor arquitectura colonial del Bajío.'
WHERE id = '4' AND resumen = 'Callejones, leyendas y la mejor arquitectura colonial del Bajio.';

UPDATE paquetes SET descripcion_larga = '<p>Un recorrido por dos de los pueblos mágicos más queridos de México, entre callejones, plazas y miradores.</p>'
WHERE id = '4' AND descripcion_larga = '<p>Un recorrido por dos de los pueblos magicos mas queridos de Mexico, entre callejones, plazas y miradores.</p>';

UPDATE paquetes SET itinerario = '<p><strong>Día 1:</strong> Llegada a Guanajuato, tour de leyendas nocturno.</p><p><strong>Día 2:</strong> Visita a San Miguel de Allende y regreso.</p><p><strong>Día 3:</strong> Museo y mirador, traslado de salida.</p>'
WHERE id = '4' AND itinerario = '<p><strong>Dia 1:</strong> Llegada a Guanajuato, tour de leyendas nocturno.</p><p><strong>Dia 2:</strong> Visita a San Miguel de Allende y regreso.</p><p><strong>Dia 3:</strong> Museo y mirador, traslado de salida.</p>';

UPDATE paquetes SET titulo = 'Oaxaca Mágico: Mezcal, Playas y Cultura'
WHERE id = '5' AND titulo = 'Oaxaca Magico: Mezcal, Playas y Cultura';

UPDATE paquetes SET resumen = 'Gastronomía, mezcal y la calidez oaxaqueña en un solo itinerario.'
WHERE id = '5' AND resumen = 'Gastronomia, mezcal y la calidez oaxaquena en un solo itinerario.';

UPDATE paquetes SET descripcion_larga = '<p>Descubre la riqueza cultural y gastronómica de Oaxaca, con visita a una fábrica de mezcal artesanal y sus zonas arqueológicas.</p>'
WHERE id = '5' AND descripcion_larga = '<p>Descubre la riqueza cultural y gastronomica de Oaxaca, con visita a una fabrica de mezcal artesanal y sus zonas arqueologicas.</p>';

UPDATE paquetes SET itinerario = '<p><strong>Día 1:</strong> Llegada, recorrido por el centro histórico.</p><p><strong>Día 2:</strong> Monte Albán y Árbol del Tule.</p><p><strong>Día 3:</strong> Fábrica de mezcal y mercado local.</p><p><strong>Día 4:</strong> Traslado de salida.</p>'
WHERE id = '5' AND itinerario = '<p><strong>Dia 1:</strong> Llegada, recorrido por el centro historico.</p><p><strong>Dia 2:</strong> Monte Alban y Arbol del Tule.</p><p><strong>Dia 3:</strong> Fabrica de mezcal y mercado local.</p><p><strong>Dia 4:</strong> Traslado de salida.</p>';

UPDATE paquetes SET incluye = '<ul><li>Transporte durante el tour</li><li>3 noches de hospedaje</li><li>Entrada a Monte Albán</li></ul>'
WHERE id = '5' AND incluye = '<ul><li>Transporte durante el tour</li><li>3 noches de hospedaje</li><li>Entrada a Monte Alban</li></ul>';

UPDATE paquetes SET no_incluye = '<ul><li>Vuelos</li><li>Bebidas alcohólicas</li><li>Propinas</li></ul>'
WHERE id = '5' AND no_incluye = '<ul><li>Vuelos</li><li>Bebidas alcoholicas</li><li>Propinas</li></ul>';

UPDATE paquetes SET titulo = 'Europa Clásica: París, Roma y Barcelona'
WHERE id = '6' AND titulo = 'Europa Clasica: Paris, Roma y Barcelona';

UPDATE paquetes SET resumen = 'Tres capitales icónicas en un solo recorrido inolvidable.'
WHERE id = '6' AND resumen = 'Tres capitales iconicas en un solo recorrido inolvidable.';

UPDATE paquetes SET descripcion_larga = '<p>El viaje soñado por Europa: arte, historia y arquitectura en París, Roma y Barcelona.</p>'
WHERE id = '6' AND descripcion_larga = '<p>El viaje sonado por Europa: arte, historia y arquitectura en Paris, Roma y Barcelona.</p>';

UPDATE paquetes SET itinerario = '<p><strong>Días 1-3:</strong> París: Torre Eiffel, Louvre y Montmartre.</p><p><strong>Días 4-6:</strong> Roma: Coliseo, Vaticano y Fontana di Trevi.</p><p><strong>Días 7-9:</strong> Barcelona: Sagrada Familia y Barrio Gótico.</p>'
WHERE id = '6' AND itinerario = '<p><strong>Dias 1-3:</strong> Paris: Torre Eiffel, Louvre y Montmartre.</p><p><strong>Dias 4-6:</strong> Roma: Coliseo, Vaticano y Fontana di Trevi.</p><p><strong>Dias 7-9:</strong> Barcelona: Sagrada Familia y Barrio Gotico.</p>';

UPDATE paquetes SET titulo = 'Perú Ancestral: Lima, Cusco y Machu Picchu'
WHERE id = '7' AND titulo = 'Peru Ancestral: Lima, Cusco y Machu Picchu';

UPDATE paquetes SET resumen = 'El imperio inca cobra vida en uno de los viajes más buscados de Sudamérica.'
WHERE id = '7' AND resumen = 'El imperio inca cobra vida en uno de los viajes mas buscados de Sudamerica.';

UPDATE paquetes SET descripcion_larga = '<p>Un recorrido completo por Perú: la gastronomía limeña, el centro histórico de Cusco y la majestuosidad de Machu Picchu.</p>'
WHERE id = '7' AND descripcion_larga = '<p>Un recorrido completo por Peru: la gastronomia limena, el centro historico de Cusco y la majestuosidad de Machu Picchu.</p>';

UPDATE paquetes SET itinerario = '<p><strong>Días 1-2:</strong> Lima: centro histórico y gastronomía.</p><p><strong>Días 3-5:</strong> Cusco y Valle Sagrado.</p><p><strong>Días 6-7:</strong> Machu Picchu y regreso.</p>'
WHERE id = '7' AND itinerario = '<p><strong>Dias 1-2:</strong> Lima: centro historico y gastronomia.</p><p><strong>Dias 3-5:</strong> Cusco y Valle Sagrado.</p><p><strong>Dias 6-7:</strong> Machu Picchu y regreso.</p>';

UPDATE paquetes SET incluye = '<ul><li>Vuelos internos Lima-Cusco</li><li>6 noches de hospedaje</li><li>Entrada a Machu Picchu con guía</li></ul>'
WHERE id = '7' AND incluye = '<ul><li>Vuelos internos Lima-Cusco</li><li>6 noches de hospedaje</li><li>Entrada a Machu Picchu con guia</li></ul>';

UPDATE paquetes SET resumen = 'Adrenalina y naturaleza en las cascadas y selvas más impresionantes del sureste.'
WHERE id = '8' AND resumen = 'Adrenalina y naturaleza en las cascadas y selvas mas impresionantes del sureste.';

UPDATE paquetes SET itinerario = '<p><strong>Día 1:</strong> Llegada y tarde libre.</p><p><strong>Día 2:</strong> Cascadas de Agua Azul y Misol-Ha.</p><p><strong>Día 3:</strong> Palenque y zona arqueológica.</p><p><strong>Día 4:</strong> Traslado de salida.</p>'
WHERE id = '8' AND itinerario = '<p><strong>Dia 1:</strong> Llegada y tarde libre.</p><p><strong>Dia 2:</strong> Cascadas de Agua Azul y Misol-Ha.</p><p><strong>Dia 3:</strong> Palenque y zona arqueologica.</p><p><strong>Dia 4:</strong> Traslado de salida.</p>';

UPDATE paquetes SET incluye = '<ul><li>Transporte durante el tour</li><li>3 noches de hospedaje</li><li>Entradas a cascadas y zona arqueológica</li></ul>'
WHERE id = '8' AND incluye = '<ul><li>Transporte durante el tour</li><li>3 noches de hospedaje</li><li>Entradas a cascadas y zona arqueologica</li></ul>';

UPDATE imagenes_paquete SET alt_text = 'Oaxaca Mágico'
WHERE id = '5' AND alt_text = 'Oaxaca Magico';

UPDATE imagenes_paquete SET alt_text = 'Europa Clásica'
WHERE id = '6' AND alt_text = 'Europa Clasica';

UPDATE imagenes_paquete SET alt_text = 'Perú Ancestral'
WHERE id = '7' AND alt_text = 'Peru Ancestral';

UPDATE roles SET descripcion = 'Gestión de paquetes y fechas de salida únicamente.'
WHERE id = '2' AND descripcion = 'Gestion de paquetes y fechas de salida unicamente.';

UPDATE permisos SET descripcion = 'Cambiar el estado de una cotización'
WHERE clave = 'cotizaciones.gestionar' AND descripcion = 'Cambiar el estado de una cotizacion';

UPDATE permisos SET descripcion = 'Crear y editar códigos de descuento'
WHERE clave = 'ofertas.gestionar' AND descripcion = 'Crear y editar codigos de descuento';

UPDATE permisos SET descripcion = 'Editar la configuración global del sitio'
WHERE clave = 'configuracion.gestionar' AND descripcion = 'Editar la configuracion global del sitio';

UPDATE permisos SET descripcion = 'Ver reportes y métricas del negocio'
WHERE clave = 'reportes.ver' AND descripcion = 'Ver reportes y metricas del negocio';

UPDATE permisos SET descripcion = 'Ver el listado de reseñas de clientes'
WHERE clave = 'resenas.ver' AND descripcion = 'Ver el listado de resenas de clientes';

UPDATE permisos SET descripcion = 'Aprobar o rechazar reseñas de clientes'
WHERE clave = 'resenas.gestionar' AND descripcion = 'Aprobar o rechazar resenas de clientes';

UPDATE permisos SET descripcion = 'Ver la bitácora de acciones del panel'
WHERE clave = 'bitacora.ver' AND descripcion = 'Ver la bitacora de acciones del panel';

UPDATE permisos SET descripcion = 'Ver el listado de artículos del blog'
WHERE clave = 'articulos.ver' AND descripcion = 'Ver el listado de articulos del blog';

UPDATE permisos SET descripcion = 'Crear nuevos artículos del blog'
WHERE clave = 'articulos.crear' AND descripcion = 'Crear nuevos articulos del blog';

UPDATE permisos SET descripcion = 'Editar artículos del blog existentes'
WHERE clave = 'articulos.editar' AND descripcion = 'Editar articulos del blog existentes';

UPDATE permisos SET descripcion = 'Archivar artículos del blog'
WHERE clave = 'articulos.eliminar' AND descripcion = 'Archivar articulos del blog';

UPDATE permisos SET descripcion = 'Editar textos, orden y colores de las páginas públicas'
WHERE clave = 'contenido.gestionar' AND descripcion = 'Editar textos, orden y colores de las paginas publicas';

UPDATE configuracion_sitio SET descripcion = 'Días antes de la salida para enviar el recordatorio de saldo pendiente al cliente'
WHERE clave = 'dias_recordatorio_saldo' AND descripcion = 'Dias antes de la salida para enviar el recordatorio de saldo pendiente al cliente';

UPDATE configuracion_sitio SET descripcion = 'Días de anticipación para enviar el recordatorio de viaje'
WHERE clave = 'dias_recordatorio_viaje' AND descripcion = 'Dias de anticipacion para enviar el recordatorio de viaje';

UPDATE configuracion_sitio SET descripcion = 'Días después de terminado el viaje (fecha_regreso o fecha_salida) para pedir una reseña al cliente'
WHERE clave = 'dias_solicitud_resena' AND descripcion = 'Dias despues de terminado el viaje (fecha_regreso o fecha_salida) para pedir una resena al cliente';

UPDATE configuracion_sitio SET descripcion = 'Dirección física mostrada en el pie de página'
WHERE clave = 'direccion' AND descripcion = 'Direccion fisica mostrada en el pie de pagina';

UPDATE configuracion_sitio SET descripcion = 'Correo público mostrado en el pie de página'
WHERE clave = 'email_contacto' AND descripcion = 'Correo publico mostrado en el pie de pagina';

UPDATE configuracion_sitio SET descripcion = 'Correo que recibe el reporte periódico y notificaciones de cotización'
WHERE clave = 'email_equipo_reportes' AND descripcion = 'Correo que recibe el reporte periodico y notificaciones de cotizacion';

UPDATE configuracion_sitio SET descripcion = 'URL de la página de Facebook (vacío = icono oculto)'
WHERE clave = 'facebook_url' AND descripcion = 'URL de la pagina de Facebook (vacio = icono oculto)';

UPDATE configuracion_sitio SET descripcion = 'Horas antes de liberar automáticamente una reserva pendiente'
WHERE clave = 'horas_expiracion_reserva' AND descripcion = 'Horas antes de liberar automaticamente una reserva pendiente';

UPDATE configuracion_sitio SET descripcion = 'URL del perfil de Instagram (vacío = icono oculto)'
WHERE clave = 'instagram_url' AND descripcion = 'URL del perfil de Instagram (vacio = icono oculto)';

UPDATE configuracion_sitio SET descripcion = 'Descripción SEO por defecto'
WHERE clave = 'meta_description_default' AND descripcion = 'Descripcion SEO por defecto';

UPDATE configuracion_sitio SET descripcion = 'Título SEO por defecto'
WHERE clave = 'meta_title_default' AND descripcion = 'Titulo SEO por defecto';

UPDATE configuracion_sitio SET descripcion = 'Porcentaje del precio total que se cobra como anticipo en línea al reservar (1-100)'
WHERE clave = 'porcentaje_anticipo_reserva' AND descripcion = 'Porcentaje del precio total que se cobra como anticipo en linea al reservar (1-100)';

UPDATE configuracion_sitio SET descripcion = 'Teléfono de contacto mostrado en el pie de página'
WHERE clave = 'telefono_contacto' AND descripcion = 'Telefono de contacto mostrado en el pie de pagina';

UPDATE configuracion_sitio SET descripcion = 'URL del perfil de TikTok (vacío = icono oculto)'
WHERE clave = 'tiktok_url' AND descripcion = 'URL del perfil de TikTok (vacio = icono oculto)';

UPDATE configuracion_sitio SET descripcion = 'Número de WhatsApp destino para cotizaciones (formato internacional sin +)'
WHERE clave = 'whatsapp_numero' AND descripcion = 'Numero de WhatsApp destino para cotizaciones (formato internacional sin +)';

UPDATE configuracion_sitio SET descripcion = 'URL del canal de YouTube (vacío = icono oculto)'
WHERE clave = 'youtube_url' AND descripcion = 'URL del canal de YouTube (vacio = icono oculto)';

UPDATE bloques_pagina SET subtitulo = 'Diseñamos excursiones y paquetes a los mejores destinos de México y el mundo, con asesoría personalizada de principio a fin.'
WHERE pagina = 'home' AND clave = 'hero' AND subtitulo = 'Diseñamos excursiones y paquetes a los mejores destinos de Mexico y el mundo, con asesoria personalizada de principio a fin.';

UPDATE bloques_pagina SET titulo = 'Por qué viajar con Dream Go'
WHERE pagina = 'home' AND clave = 'ventajas' AND titulo = 'Por que viajar con Dream Go';

UPDATE bloques_pagina SET subtitulo = 'Nos ocupamos de los detalles para que tú solo te preocupes de disfrutar el viaje.'
WHERE pagina = 'home' AND clave = 'ventajas' AND subtitulo = 'Nos ocupamos de los detalles para que tu solo te preocupes de disfrutar el viaje.';

UPDATE bloques_pagina SET contenido = '{\"items\":[{\"icono\":\"chat\",\"titulo\":\"Asesoría personalizada\",\"texto\":\"Te acompañamos desde la elección del destino hasta el regreso a casa, resolviendo cada duda en el camino.\"},{\"icono\":\"mapa\",\"titulo\":\"Itinerarios claros\",\"texto\":\"Sin sorpresas: sabes exactamente qué incluye cada paquete antes de reservar.\"},{\"icono\":\"reloj\",\"titulo\":\"Soporte en todo momento\",\"texto\":\"Resolvemos tus dudas antes, durante y después del viaje: nunca estás solo en el camino.\"},{\"icono\":\"etiqueta\",\"titulo\":\"Precios transparentes\",\"texto\":\"Cotizaciones claras y sin cargos ocultos, para que planees tu presupuesto con confianza.\"}]}'
WHERE pagina = 'home' AND clave = 'ventajas' AND contenido = '{\"items\":[{\"icono\":\"chat\",\"titulo\":\"Asesoria personalizada\",\"texto\":\"Te acompañamos desde la eleccion del destino hasta el regreso a casa, resolviendo cada duda en el camino.\"},{\"icono\":\"mapa\",\"titulo\":\"Itinerarios claros\",\"texto\":\"Sin sorpresas: sabes exactamente que incluye cada paquete antes de reservar.\"},{\"icono\":\"reloj\",\"titulo\":\"Soporte en todo momento\",\"texto\":\"Resolvemos tus dudas antes, durante y despues del viaje: nunca estas solo en el camino.\"},{\"icono\":\"etiqueta\",\"titulo\":\"Precios transparentes\",\"texto\":\"Cotizaciones claras y sin cargos ocultos, para que planees tu presupuesto con confianza.\"}]}';

UPDATE bloques_pagina SET subtitulo = 'Recorre nuestras categorías nacionales e internacionales y encuentra tu próxima escapada.'
WHERE pagina = 'home' AND clave = 'destinos' AND subtitulo = 'Recorre nuestras categorias nacionales e internacionales y encuentra tu proxima escapada.';

UPDATE bloques_pagina SET subtitulo = 'Una selección de las excursiones favoritas de nuestros viajeros.'
WHERE pagina = 'home' AND clave = 'paquetes_destacados' AND subtitulo = 'Una seleccion de las excursiones favoritas de nuestros viajeros.';

UPDATE bloques_pagina SET contenido = '{\"items\":[{\"inicial\":\"M\",\"texto\":\"La asesoría fue clave: nos ayudaron a armar el itinerario perfecto para el tiempo que teníamos y todo salió como lo planeamos.\",\"autor\":\"Cliente Dream Go — Paquete nacional\"},{\"inicial\":\"R\",\"texto\":\"Viajamos en grupo y el equipo resolvió cada duda por WhatsApp casi al instante. Se siente el acompañamiento real.\",\"autor\":\"Cliente Dream Go — Paquete internacional\"},{\"inicial\":\"L\",\"texto\":\"Cotización clara desde el inicio, sin cargos escondidos. Ya vamos por nuestro segundo viaje con ellos.\",\"autor\":\"Cliente Dream Go — Paquete nacional\"}]}'
WHERE pagina = 'home' AND clave = 'testimonios' AND contenido = '{\"items\":[{\"inicial\":\"M\",\"texto\":\"La asesoria fue clave: nos ayudaron a armar el itinerario perfecto para el tiempo que teniamos y todo salio como lo planeamos.\",\"autor\":\"Cliente Dream Go — Paquete nacional\"},{\"inicial\":\"R\",\"texto\":\"Viajamos en grupo y el equipo resolvio cada duda por WhatsApp casi al instante. Se siente el acompañamiento real.\",\"autor\":\"Cliente Dream Go — Paquete internacional\"},{\"inicial\":\"L\",\"texto\":\"Cotizacion clara desde el inicio, sin cargos escondidos. Ya vamos por nuestro segundo viaje con ellos.\",\"autor\":\"Cliente Dream Go — Paquete nacional\"}]}';

UPDATE bloques_pagina SET titulo = 'Tu próximo viaje empieza con una conversación'
WHERE pagina = 'home' AND clave = 'cta_final' AND titulo = 'Tu proximo viaje empieza con una conversacion';

UPDATE bloques_pagina SET subtitulo = 'Cuéntanos qué tienes en mente y te preparamos una cotización a la medida, sin compromiso.'
WHERE pagina = 'home' AND clave = 'cta_final' AND subtitulo = 'Cuentanos que tienes en mente y te preparamos una cotizacion a la medida, sin compromiso.';

UPDATE bloques_pagina SET contenido = '{\"boton_texto\":\"Solicita tu cotización\",\"boton_link\":\"/cotizador\"}'
WHERE pagina = 'home' AND clave = 'cta_final' AND contenido = '{\"boton_texto\":\"Solicita tu cotizacion\",\"boton_link\":\"/cotizador\"}';

UPDATE bloques_pagina SET subtitulo = 'En Dream Go Operadora Turística creemos que viajar debe ser una experiencia sin complicaciones. Somos un equipo de asesores de viaje dedicados a diseñar excursiones nacionales e internacionales con itinerarios claros, acompañamiento real y la seguridad de trabajar con un operador establecido.'
WHERE pagina = 'nosotros' AND clave = 'intro' AND subtitulo = 'En Dream Go Operadora Turistica creemos que viajar debe ser una experiencia sin complicaciones. Somos un equipo de asesores de viaje dedicados a disenar excursiones nacionales e internacionales con itinerarios claros, acompañamiento real y la seguridad de trabajar con un operador establecido.';

UPDATE bloques_pagina SET contenido = '{\"parrafo_2\":\"Desde nuestros primeros grupos hasta hoy, nuestro compromiso ha sido el mismo: que cada viajero se sienta acompañado antes, durante y después de su viaje.\"}'
WHERE pagina = 'nosotros' AND clave = 'intro' AND contenido = '{\"parrafo_2\":\"Desde nuestros primeros grupos hasta hoy, nuestro compromiso ha sido el mismo: que cada viajero se sienta acompañado antes, durante y despues de su viaje.\"}';

UPDATE bloques_pagina SET contenido = '{\"items\":[{\"numero\":\"+50\",\"etiqueta\":\"Destinos disponibles\"},{\"numero\":\"+5,000\",\"etiqueta\":\"Viajeros felices\"},{\"numero\":\"+10\",\"etiqueta\":\"Años de experiencia\"}]}'
WHERE pagina = 'nosotros' AND clave = 'estadisticas' AND contenido = '{\"items\":[{\"numero\":\"+50\",\"etiqueta\":\"Destinos disponibles\"},{\"numero\":\"+5,000\",\"etiqueta\":\"Viajeros felices\"},{\"numero\":\"+10\",\"etiqueta\":\"Anos de experiencia\"}]}';

UPDATE bloques_pagina SET titulo = '¿Listo para tu próximo viaje?'
WHERE pagina = 'nosotros' AND clave = 'cta_final' AND titulo = '¿Listo para tu proximo viaje?';

UPDATE bloques_pagina SET titulo = 'Hablemos de tu próximo viaje'
WHERE pagina = 'contacto' AND clave = 'intro' AND titulo = 'Hablemos de tu proximo viaje';

UPDATE bloques_pagina SET subtitulo = 'Escríbenos por el canal que prefieras, nuestro equipo te responderá a la brevedad.'
WHERE pagina = 'contacto' AND clave = 'intro' AND subtitulo = 'Escribenos por el canal que prefieras, nuestro equipo te respondera a la brevedad.';

UPDATE bloques_pagina SET subtitulo = 'Elige entre experiencias nacionales e internacionales diseñadas por nuestro equipo.'
WHERE pagina = 'destinos' AND clave = 'intro' AND subtitulo = 'Elige entre experiencias nacionales e internacionales disenadas por nuestro equipo.';

UPDATE bloques_pagina SET subtitulo = 'Filtra por destino o por tipo de viaje para encontrar tu próxima excursión.'
WHERE pagina = 'paquetes' AND clave = 'intro' AND subtitulo = 'Filtra por destino o por tipo de viaje para encontrar tu proxima excursion.';

