-- CRM ligero sobre cotizaciones: asignar un asesor, fijar una fecha de proximo contacto y
-- llevar un historial de notas de seguimiento. Antes la cotizacion solo cambiaba de estado.
-- ADD COLUMN IF NOT EXISTS / FOREIGN KEY IF NOT EXISTS / CREATE ... IF NOT EXISTS: re-ejecutable
-- (MariaDB 10.6, ver database/migrations/README.md).
ALTER TABLE cotizaciones
  ADD COLUMN IF NOT EXISTS asignado_a INT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS seguimiento_en DATE NULL;

ALTER TABLE cotizaciones
  ADD CONSTRAINT fk_cotizacion_asignado FOREIGN KEY IF NOT EXISTS (asignado_a) REFERENCES usuarios_admin(id) ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS idx_cotizaciones_asignado ON cotizaciones(asignado_a);
CREATE INDEX IF NOT EXISTS idx_cotizaciones_seguimiento ON cotizaciones(seguimiento_en);

-- Historial de notas de seguimiento. usuario_nombre va desnormalizado para que la nota siga
-- atribuida aunque despues se borre el usuario (mismo criterio que bitacora_admin).
CREATE TABLE IF NOT EXISTS cotizacion_notas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cotizacion_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NULL,
  usuario_nombre VARCHAR(150) NULL,
  nota TEXT NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_cotnotas_cotizacion (cotizacion_id),
  CONSTRAINT fk_cotnota_cotizacion FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE,
  CONSTRAINT fk_cotnota_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios_admin(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
