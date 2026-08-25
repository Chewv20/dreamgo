-- Mejora #7 del backlog (MEJORAS.md): newsletter / alertas de ofertas. Captura de email en el
-- home con doble opt-in (confirmacion por correo antes de quedar activo).

CREATE TABLE suscriptores (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL UNIQUE,
  estado ENUM('pendiente','confirmado','baja') NOT NULL DEFAULT 'pendiente',
  token VARCHAR(64) NOT NULL UNIQUE,
  ip_origen VARCHAR(45) NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  confirmado_en DATETIME NULL,
  baja_en DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_suscriptores_estado ON suscriptores(estado);

INSERT INTO permisos (clave, modulo, descripcion) VALUES
  ('suscriptores.ver', 'suscriptores', 'Ver el listado de suscriptores al newsletter');

INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT 1, id FROM permisos WHERE clave = 'suscriptores.ver';
