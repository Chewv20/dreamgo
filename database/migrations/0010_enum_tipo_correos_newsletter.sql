-- Mejora #7 del backlog (MEJORAS.md): el correo de confirmacion de suscripcion y el aviso de
-- ofertas nuevas registran su envio en log_correos_enviados.tipo, que no incluia estos valores.

ALTER TABLE log_correos_enviados
  MODIFY COLUMN tipo ENUM('cotizacion_equipo','confirmacion_reserva','reserva_pendiente','recordatorio_viaje','reporte_periodico','solicitud_resena','confirmacion_suscripcion','aviso_oferta','otro') NOT NULL;
