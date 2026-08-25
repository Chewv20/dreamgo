-- Mejora #5 del backlog (MEJORAS.md): dashboard con metricas de negocio. El calculo de
-- ingresos por periodo filtra reservas confirmadas por rango de confirmada_en (no creado_en:
-- el ingreso se realiza cuando se confirma, no cuando se solicita). No existia indice que
-- cubriera (estado, confirmada_en) para ese filtro + agregacion.

CREATE INDEX idx_reservas_estado_confirmada ON reservas(estado, confirmada_en);
