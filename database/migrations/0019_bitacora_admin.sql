-- Bitacora de acciones sensibles del panel (quien confirmo/cancelo una reserva, cambio
-- permisos, edito la configuracion, etc.). Antes solo existia intentos_login para el acceso.
-- usuario_nombre va desnormalizado para que el registro siga siendo legible aunque despues
-- se borre el usuario (usuario_id queda NULL por el ON DELETE SET NULL).
CREATE TABLE IF NOT EXISTS bitacora_admin (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NULL,
  usuario_nombre VARCHAR(150) NULL,
  accion VARCHAR(50) NOT NULL,
  entidad_tipo VARCHAR(30) NULL,
  entidad_id INT UNSIGNED NULL,
  detalle VARCHAR(500) NULL,
  ip VARCHAR(45) NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_bitacora_creado (creado_en),
  INDEX idx_bitacora_accion (accion),
  INDEX idx_bitacora_entidad (entidad_tipo, entidad_id),
  CONSTRAINT fk_bitacora_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios_admin(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Permiso para ver la bitacora (solo lectura). Se asigna al rol Administrador (id 1).
INSERT IGNORE INTO permisos (clave, modulo, descripcion) VALUES
  ('bitacora.ver', 'bitacora', 'Ver la bitacora de acciones del panel');

INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT 1, id FROM permisos WHERE clave = 'bitacora.ver';
