-- log_correos_enviados.tipo es un ENUM que nunca incluyo 'reserva_pendiente' -- por eso
-- MailerService::enviarReservaPendiente() usaba 'confirmacion_reserva' (el unico valor del
-- ENUM que tenia sentido), rompiendo la deduplicacion entre el correo de "reserva pendiente"
-- y el de "reserva confirmada" (hallazgo Bajo de la auditoria). El codigo ya se corrigio
-- para usar 'reserva_pendiente'; esto agrega el valor que le faltaba al ENUM.

ALTER TABLE log_correos_enviados
  MODIFY COLUMN tipo ENUM('cotizacion_equipo','confirmacion_reserva','reserva_pendiente','recordatorio_viaje','reporte_periodico','otro') NOT NULL;
