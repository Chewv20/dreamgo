-- Modales promocionales: un aviso que se abre al entrar al sitio, promociona un paquete y
-- lleva a su ficha con un boton. Todo el contenido (texto, imagen, paquete, vigencia,
-- visibilidad) se administra desde /admin/modales; mismo patron que destinos/paquetes.
--
-- DDL + DML idempotente (CREATE TABLE IF NOT EXISTS / INSERT IGNORE): re-ejecutable aunque
-- migrate.php no llegue a marcarlo como aplicado (ver database/migrations/README.md).

CREATE TABLE IF NOT EXISTS modales_promocion (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(120) NOT NULL,
  cuerpo VARCHAR(500) NULL,
  texto_boton VARCHAR(40) NOT NULL DEFAULT 'Ver paquete',
  paquete_id INT UNSIGNED NOT NULL,
  imagen VARCHAR(255) NULL,
  mostrar_en ENUM('inicio','todas') NOT NULL DEFAULT 'inicio',
  fecha_inicio DATE NULL,
  fecha_fin DATE NULL,
  prioridad SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_modal_paquete FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE CASCADE,
  CONSTRAINT chk_modal_fechas CHECK (fecha_fin IS NULL OR fecha_inicio IS NULL OR fecha_fin >= fecha_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_modales_activo ON modales_promocion(activo, prioridad);

INSERT IGNORE INTO permisos (clave, modulo, descripcion) VALUES
  ('modales.gestionar', 'modales', 'Crear y editar los modales promocionales del sitio');

-- Rol Administrador (es_sistema = 1): hereda el permiso nuevo.
INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permisos p
WHERE r.es_sistema = 1 AND p.clave = 'modales.gestionar';

-- Refresca el sello de permisos de los roles de sistema para que las sesiones admin ya
-- abiertas recarguen el permiso nuevo en el proximo request (mismo mecanismo que la
-- migracion de destinos). Sin esto, un admin logueado no veria el modulo Modales hasta
-- volver a iniciar sesion.
UPDATE roles SET permisos_actualizado_en = NOW() WHERE es_sistema = 1;
