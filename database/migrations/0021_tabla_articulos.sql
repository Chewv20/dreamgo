-- Blog de destinos: articulos editoriales para trafico organico. Mismo patron que paquetes
-- (slug unico, estado borrador/publicado/archivado, HTML sanitizado con HtmlSanitizer,
-- imagen opcional, meta SEO). categoria_id opcional enlaza el articulo con un destino para
-- cross-linking.
CREATE TABLE IF NOT EXISTS articulos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(180) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  resumen VARCHAR(300) NULL,
  contenido LONGTEXT NULL,
  imagen VARCHAR(255) NULL,
  categoria_id INT UNSIGNED NULL,
  estado ENUM('borrador','publicado','archivado') NOT NULL DEFAULT 'borrador',
  meta_title VARCHAR(180) NULL,
  meta_description VARCHAR(300) NULL,
  publicado_en DATETIME NULL,
  creado_por INT UNSIGNED NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_articulos_estado_pub (estado, publicado_en),
  CONSTRAINT fk_articulo_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
  CONSTRAINT fk_articulo_autor FOREIGN KEY (creado_por) REFERENCES usuarios_admin(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO permisos (clave, modulo, descripcion) VALUES
  ('articulos.gestionar', 'blog', 'Crear y editar articulos del blog');

INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT 1, id FROM permisos WHERE clave = 'articulos.gestionar';
