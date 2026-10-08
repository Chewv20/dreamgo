-- Pagos registrados a mano desde el panel (transferencia, efectivo, depósito...) y notas
-- internas por reserva (auditoría UX 2026-10, ADM-12). pagos_reserva ya es aditivo
-- (monto_pagado = SUM(monto)) y metodo_pago es VARCHAR, así que sólo faltan la nota del
-- pago y quién lo registró.
-- ADD COLUMN IF NOT EXISTS / FOREIGN KEY IF NOT EXISTS / CREATE ... IF NOT EXISTS: re-ejecutable
-- (MariaDB 10.6, ver database/migrations/README.md).
ALTER TABLE pagos_reserva
  ADD COLUMN IF NOT EXISTS nota VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS registrado_por INT UNSIGNED NULL;

ALTER TABLE pagos_reserva
  ADD CONSTRAINT fk_pago_registrado_por FOREIGN KEY IF NOT EXISTS (registrado_por) REFERENCES usuarios_admin(id) ON DELETE SET NULL;

-- Notas internas de seguimiento de una reserva (mismo diseño que cotizacion_notas).
CREATE TABLE IF NOT EXISTS reserva_notas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reserva_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NULL,
  usuario_nombre VARCHAR(150) NULL,
  nota TEXT NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_resnotas_reserva (reserva_id),
  CONSTRAINT fk_resnota_reserva FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE CASCADE,
  CONSTRAINT fk_resnota_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios_admin(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO permisos (clave, modulo, descripcion) VALUES
  ('reservas.registrar_pago', 'reservas', 'Registrar pagos manuales (transferencia, efectivo...) en una reserva');

-- Lo reciben los roles de sistema y los que ya podían confirmar reservas.
INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT DISTINCT r.id, p.id
FROM roles r
CROSS JOIN permisos p
WHERE p.clave = 'reservas.registrar_pago'
  AND (r.es_sistema = 1 OR r.id IN (
    SELECT rp.rol_id FROM rol_permiso rp INNER JOIN permisos pc ON pc.id = rp.permiso_id WHERE pc.clave = 'reservas.confirmar'
  ));

UPDATE roles SET permisos_actualizado_en = NOW()
WHERE es_sistema = 1 OR id IN (
  SELECT rp.rol_id FROM rol_permiso rp INNER JOIN permisos pc ON pc.id = rp.permiso_id WHERE pc.clave = 'reservas.registrar_pago'
);

-- Abonos del cliente al saldo desde su link de pago: monto mínimo por abono.
INSERT IGNORE INTO configuracion_sitio (clave, valor, descripcion) VALUES
  ('abono_minimo_saldo', '500', 'Monto mínimo de cada abono al saldo que el cliente paga en línea (si el saldo es menor, paga el saldo completo)');
