-- Mejora #4 del backlog (MEJORAS.md): dias despues de terminado el viaje para pedir una
-- resena al cliente, mismo patron que dias_recordatorio_viaje.

INSERT INTO configuracion_sitio (clave, valor, descripcion)
VALUES ('dias_solicitud_resena', '3', 'Dias despues de terminado el viaje (fecha_regreso o fecha_salida) para pedir una resena al cliente')
ON DUPLICATE KEY UPDATE clave = clave;
