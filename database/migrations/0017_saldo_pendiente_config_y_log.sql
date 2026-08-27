-- Parametro de negocio para el recordatorio de saldo pendiente (cron/recordatorio_saldo.php).
INSERT IGNORE INTO configuracion_sitio (clave, valor, descripcion) VALUES
  ('dias_recordatorio_saldo', '7', 'Dias antes de la salida para enviar el recordatorio de saldo pendiente al cliente');

-- Nuevos tipos de correo: recordatorio de saldo (cron) y aviso de pago recibido (webhook,
-- cuando entra un pago sobre una reserva ya confirmada). MODIFY de un ENUM es idempotente.
ALTER TABLE log_correos_enviados
  MODIFY COLUMN tipo ENUM(
    'cotizacion_equipo','confirmacion_reserva','reserva_pendiente','recordatorio_viaje',
    'reporte_periodico','solicitud_resena','confirmacion_suscripcion','aviso_oferta',
    'recordatorio_saldo','pago_recibido','otro'
  ) NOT NULL;
