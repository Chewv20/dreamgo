-- Mejora funcional: reserva y pago en linea con Mercado Pago (ver MEJORAS.md, punto 1).
-- El cliente paga un anticipo configurable (no el 100%) al reservar en linea; el resto se
-- liquida despues como ya se hacia. El porcentaje se guarda en configuracion_sitio en vez de
-- hardcodearlo, mismo patron que el resto de parametros de negocio del sitio.

INSERT INTO configuracion_sitio (clave, valor, descripcion)
VALUES ('porcentaje_anticipo_reserva', '30', 'Porcentaje del precio total que se cobra como anticipo en linea al reservar (1-100)')
ON DUPLICATE KEY UPDATE clave = clave;
