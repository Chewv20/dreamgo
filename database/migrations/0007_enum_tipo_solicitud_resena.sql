-- Mejora #4 del backlog (MEJORAS.md): el cron de solicitud de resena registra su envio en
-- log_correos_enviados.tipo, que no incluia este valor todavia.

ALTER TABLE log_correos_enviados
  MODIFY COLUMN tipo ENUM('cotizacion_equipo','confirmacion_reserva','reserva_pendiente','recordatorio_viaje','reporte_periodico','solicitud_resena','otro') NOT NULL;
