-- Acuse por correo al cliente cuando envía el cotizador o el formulario de contacto
-- (MailerService::enviarConfirmacionCotizacion). Se registra en log_correos_enviados.tipo,
-- que no incluía este valor.
-- MODIFY de ENUM: re-ejecutable (ver database/migrations/README.md).
ALTER TABLE log_correos_enviados
  MODIFY COLUMN tipo ENUM('cotizacion_equipo','cotizacion_cliente','confirmacion_reserva','reserva_pendiente','recordatorio_viaje','reporte_periodico','solicitud_resena','confirmacion_suscripcion','aviso_oferta','recordatorio_saldo','pago_recibido','recuperar_password','otro') NOT NULL;
