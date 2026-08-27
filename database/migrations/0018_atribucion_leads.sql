-- Atribucion de origen para leads (cotizaciones) y conversiones (reservas): de donde vino el
-- visitante. Los utm_* llegan en la URL de aterrizaje y el front (site.js) los arrastra por
-- sessionStorage hasta el formulario; referrer/landing_page son el respaldo para trafico
-- organico. Todas NULL: el trafico directo o las reservas creadas por un admin no las traen.
-- ADD COLUMN IF NOT EXISTS: MariaDB 10.6, para que la migracion sea re-ejecutable tras un
-- fallo parcial (ver database/migrations/README.md).
ALTER TABLE cotizaciones
  ADD COLUMN IF NOT EXISTS utm_source   VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS utm_medium   VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS utm_campaign VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS utm_term     VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS utm_content  VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS referrer     VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS landing_page VARCHAR(255) NULL;

ALTER TABLE reservas
  ADD COLUMN IF NOT EXISTS utm_source   VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS utm_medium   VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS utm_campaign VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS utm_term     VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS utm_content  VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS referrer     VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS landing_page VARCHAR(255) NULL;

CREATE INDEX IF NOT EXISTS idx_cotizaciones_utm_source ON cotizaciones(utm_source);
