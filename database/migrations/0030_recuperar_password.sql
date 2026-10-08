-- Recuperación de contraseña del panel por correo (auditoría UX 2026-10, ADM-05).
-- Se guarda sólo el SHA-256 del token: el token en claro viaja únicamente en el correo,
-- así que una copia de la BD no sirve para restablecer contraseñas.
-- CREATE ... IF NOT EXISTS / MODIFY de ENUM: re-ejecutable (ver database/migrations/README.md).
CREATE TABLE IF NOT EXISTS password_resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expira_en DATETIME NOT NULL,
  usado_en DATETIME NULL,
  ip VARCHAR(45) NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_password_resets_token (token_hash),
  INDEX idx_password_resets_usuario (usuario_id),
  CONSTRAINT fk_password_reset_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios_admin(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE log_correos_enviados
  MODIFY COLUMN tipo ENUM('cotizacion_equipo','confirmacion_reserva','reserva_pendiente','recordatorio_viaje','reporte_periodico','solicitud_resena','confirmacion_suscripcion','aviso_oferta','recordatorio_saldo','pago_recibido','recuperar_password','otro') NOT NULL;
