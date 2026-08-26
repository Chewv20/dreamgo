-- Auditoria 2026-08-25, hallazgo CFG-02: OfertaAdminController::enviarSuscriptores() mandaba
-- el correo a cada suscriptor confirmado uno por uno, en el hilo de la propia request admin,
-- sin loteo ni cola -- riesgo real de agotar max_execution_time a mitad del envio con una
-- lista mediana. Esta tabla es la cola: la request admin solo encola (INSERT rapido) y un
-- cron nuevo (cron/enviar_avisos_oferta.php) procesa en lotes.

CREATE TABLE IF NOT EXISTS ofertas_envio_cola (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  oferta_id INT UNSIGNED NOT NULL,
  suscriptor_id INT UNSIGNED NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ofertas_cola_oferta FOREIGN KEY (oferta_id) REFERENCES codigos_descuento(id) ON DELETE CASCADE,
  CONSTRAINT fk_ofertas_cola_suscriptor FOREIGN KEY (suscriptor_id) REFERENCES suscriptores(id) ON DELETE CASCADE,
  CONSTRAINT uq_ofertas_cola UNIQUE (oferta_id, suscriptor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
