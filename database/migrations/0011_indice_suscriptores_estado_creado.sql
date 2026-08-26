-- Auditoria 2026-08-25, hallazgo BD-03: OfertaAdminController::enviarSuscriptores() filtra
-- suscriptores por estado='confirmado' y ordena por creado_en ASC (Suscriptor::confirmados()),
-- pero el indice existente solo cubre estado -- el ORDER BY requiere filesort. El indice
-- compuesto sirve tambien para consultas que solo filtran por estado, asi que el anterior
-- queda redundante.

DROP INDEX idx_suscriptores_estado ON suscriptores;
CREATE INDEX idx_suscriptores_estado_creado ON suscriptores(estado, creado_en);
