-- Hallazgo "Bajo" de la auditoria: faltaban CHECK constraints para precios y fechas
-- (solo existia chk_cupo, para el cupo de las salidas). Verificado antes de escribir esto
-- que no hay filas existentes que los violen.

ALTER TABLE paquetes
  ADD CONSTRAINT chk_paquete_precio CHECK (precio_desde >= 0);

ALTER TABLE salidas
  ADD CONSTRAINT chk_salida_precio CHECK (precio_override IS NULL OR precio_override >= 0),
  ADD CONSTRAINT chk_salida_fechas CHECK (fecha_regreso IS NULL OR fecha_regreso >= fecha_salida);

ALTER TABLE codigos_descuento
  ADD CONSTRAINT chk_descuento_valor CHECK (valor > 0 AND (tipo <> 'porcentaje' OR valor <= 100)),
  ADD CONSTRAINT chk_descuento_fechas CHECK (fecha_fin >= fecha_inicio);

ALTER TABLE reservas
  ADD CONSTRAINT chk_reserva_precio CHECK (precio_total >= 0),
  ADD CONSTRAINT chk_reserva_monto_pagado CHECK (monto_pagado >= 0);
