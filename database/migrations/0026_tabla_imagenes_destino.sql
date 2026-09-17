-- Los destinos (tabla `categorias`) solo admitian una imagen (imagen_portada). Se agrega una
-- tabla de galeria identica en forma a imagenes_paquete para poder subir varias imagenes por
-- destino y mostrarlas en un carrusel, igual que ya existe para paquetes.
CREATE TABLE IF NOT EXISTS imagenes_destino (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categoria_id INT UNSIGNED NOT NULL,
  ruta_original VARCHAR(255) NOT NULL,
  ruta_thumb VARCHAR(255) NOT NULL,
  alt_text VARCHAR(180) NULL,
  orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_imgdestino_categoria (categoria_id),
  CONSTRAINT fk_img_destino FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Backfill: los destinos que ya tienen imagen_portada (subida por el flujo viejo) pasan a
-- tener esa misma imagen como primer elemento de su galeria nueva. El thumb nunca se guardo
-- en una columna, pero ImageUploadService::procesar() siempre genera original+thumb con el
-- mismo nombre de archivo, asi que se deriva reemplazando la carpeta en la ruta.
INSERT INTO imagenes_destino (categoria_id, ruta_original, ruta_thumb, alt_text, orden)
SELECT c.id, c.imagen_portada, REPLACE(c.imagen_portada, '/original/', '/thumbs/'), c.nombre, 0
FROM categorias c
WHERE c.imagen_portada IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM imagenes_destino WHERE categoria_id = c.id);
