-- Auditoria 2026-08-25, hallazgos SEG-04/SEG-05/SEG-07: /reservar, /suscribir, /mi-reserva y
-- /resena/{codigo} no tenian ningun limite de intentos, a diferencia del login (intentos_login).
-- Generaliza el mismo patron (ventana movil calculada con NOW() de MySQL) a cualquier accion
-- publica sensible, en vez de crear una tabla por endpoint.

CREATE TABLE IF NOT EXISTS intentos_accion (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  accion VARCHAR(40) NOT NULL,
  identificador VARCHAR(190) NULL,
  ip VARCHAR(45) NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_intentos_accion_identificador ON intentos_accion(accion, identificador, creado_en);
CREATE INDEX idx_intentos_accion_ip ON intentos_accion(accion, ip, creado_en);
