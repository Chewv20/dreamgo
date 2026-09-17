-- Corrige el bug historico de PaqueteAdminController::procesarImagenPortada(): cada
-- reemplazo de portada insertaba una fila nueva en imagenes_paquete con orden=0 sin borrar
-- la anterior. Hasta hoy ninguna fila de esta tabla viene de una galeria real gestionada por
-- el admin (esa funcionalidad no existia), asi que toda fila cuyo ruta_original no coincide
-- con la portada actual del paquete es un remanente de una subida vieja y se puede borrar
-- con seguridad.
DELETE ip FROM imagenes_paquete ip
INNER JOIN paquetes p ON p.id = ip.paquete_id
WHERE ip.ruta_original <> p.imagen_portada;

UPDATE imagenes_paquete SET orden = 0 WHERE orden <> 0;
