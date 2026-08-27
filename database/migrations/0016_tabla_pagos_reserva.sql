-- Historial de pagos de una reserva (anticipo + saldo). Antes reservas.monto_pagado se
-- sobrescribia con el ultimo pago; con el cobro del saldo puede haber mas de un pago, asi
-- que monto_pagado pasa a ser SUM(pagos_reserva.monto). La UNIQUE sobre referencia_pago hace
-- de guarda anti-duplicado ante los reintentos de notificacion de Mercado Pago.
CREATE TABLE IF NOT EXISTS pagos_reserva (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reserva_id INT UNSIGNED NOT NULL,
  referencia_pago VARCHAR(100) NOT NULL,
  metodo_pago VARCHAR(30) NOT NULL DEFAULT 'mercadopago',
  concepto ENUM('anticipo','saldo','otro') NOT NULL DEFAULT 'otro',
  monto DECIMAL(10,2) NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pago_referencia (referencia_pago),
  INDEX idx_pagos_reserva (reserva_id),
  CONSTRAINT fk_pago_reserva FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE CASCADE,
  CONSTRAINT chk_pago_monto CHECK (monto >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
