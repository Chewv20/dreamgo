-- Token publico no adivinable para el link de descarga del comprobante PDF de una reserva.
-- El codigo_reserva es correlativo (DG-2026-000001, 000002...) y por si solo seria
-- enumerable; el link que va en el correo de confirmacion combina codigo + este token para
-- que no se pueda adivinar. Mismo criterio que el token de suscriptores / resenas.
ALTER TABLE reservas ADD COLUMN token_publico CHAR(32) NULL UNIQUE AFTER codigo_reserva;

-- Backfill de las reservas ya existentes: un valor pseudoaleatorio distinto por fila
-- (RAND() y UUID() se evaluan por fila dentro de un UPDATE). Las reservas nuevas reciben el
-- token desde PHP (bin2hex(random_bytes(16)) en ReservaService::crear()).
UPDATE reservas SET token_publico = MD5(CONCAT(RAND(), '-', id, '-', UUID())) WHERE token_publico IS NULL;
