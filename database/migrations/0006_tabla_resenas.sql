-- Mejora #4 del backlog (MEJORAS.md): resenas verificadas de clientes. Reservas confirmadas ya
-- identifican viajes completados por cliente; esta tabla guarda la resena real que el cliente
-- deja post-viaje (moderada por un admin antes de mostrarse en la ficha del paquete).

CREATE TABLE resenas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reserva_id INT UNSIGNED NOT NULL,
  cliente_id INT UNSIGNED NOT NULL,
  paquete_id INT UNSIGNED NOT NULL,
  calificacion TINYINT UNSIGNED NOT NULL,
  comentario TEXT NOT NULL,
  estado ENUM('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  moderada_en DATETIME NULL,
  CONSTRAINT fk_resena_reserva FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE CASCADE,
  CONSTRAINT fk_resena_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
  CONSTRAINT fk_resena_paquete FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE CASCADE,
  CONSTRAINT uq_resena_reserva UNIQUE (reserva_id),
  CONSTRAINT chk_resena_calificacion CHECK (calificacion BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_resenas_paquete_estado ON resenas(paquete_id, estado);

INSERT INTO permisos (clave, modulo, descripcion) VALUES
  ('resenas.ver', 'resenas', 'Ver el listado de resenas de clientes'),
  ('resenas.gestionar', 'resenas', 'Aprobar o rechazar resenas de clientes');

INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT 1, id FROM permisos WHERE clave IN ('resenas.ver', 'resenas.gestionar');
