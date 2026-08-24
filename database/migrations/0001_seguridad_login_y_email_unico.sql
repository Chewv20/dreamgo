-- Hallazgos de la auditoria de seguridad del 2026-08-23 (ver AUDITORIA.md).
-- Ya incluido en schema.sql para instalaciones nuevas; este archivo es para
-- aplicar el mismo cambio a una base de datos existente.

ALTER TABLE usuarios_admin
  ADD COLUMN debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash;

CREATE TABLE IF NOT EXISTS intentos_login (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  exitoso TINYINT(1) NOT NULL DEFAULT 0,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE INDEX idx_intentos_login_email ON intentos_login(email, creado_en);
CREATE INDEX idx_intentos_login_ip ON intentos_login(ip, creado_en);

DROP INDEX idx_clientes_email ON clientes;
ALTER TABLE clientes
  ADD UNIQUE KEY clientes_email_unique (email);
