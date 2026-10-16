-- Actualización de categorias (destinos) en producción (2026-10-16).
--
-- Upsert sobre la llave única `slug`: actualiza la categoría si existe y la crea si no
-- (seed_produccion.sql no trae categorías, así que en una instalación limpia se insertan).
-- `creado_en` no se toca: en las nuevas toma la fecha actual y en las existentes se conserva.
-- Es idempotente: correrlo dos veces deja el mismo resultado.
--
-- Sin prefijo de base de datos: selecciona la BD de producción antes de correrlo.
-- Uso: phpMyAdmin > pestaña SQL, o  mysql --default-character-set=utf8mb4 <bd> < este_archivo
--
-- OJO: las portadas apuntan a /uploads/destinos/original/; esos archivos deben existir en el
-- servidor (los uploads no viven en el repo).

SET NAMES utf8mb4;

START TRANSACTION;

INSERT INTO categorias (nombre, slug, tipo, descripcion, imagen_portada, orden, activo) VALUES
  ('Playas del Caribe', 'playas-del-caribe', 'nacional',
   'Sol, arena blanca y mar turquesa en el Caribe mexicano.',
   '/uploads/destinos/original/playas-del-caribe-783a1d52d2ab.jpg', 1, 1),

  ('Pueblos Mágicos', 'pueblos-magicos', 'nacional',
   'Historia, arquitectura colonial y tradición en cada rincón.',
   '/uploads/destinos/original/pueblos-magicos-d8bebfed3d18.jpg', 2, 1),

  ('Aventura y Naturaleza', 'aventura-y-naturaleza', 'nacional',
   'Cascadas, selva y experiencias al aire libre.',
   '/uploads/destinos/original/aventura-y-naturaleza-8f6c33685345.jpg', 3, 1),

  ('Europa Clásica', 'europa-clasica', 'internacional',
   'Las ciudades más emblemáticas del viejo continente.',
   '/uploads/destinos/original/europa-clasica-54db1bb80a41.jpg', 4, 1),

  ('Sudamérica Ancestral', 'sudamerica-ancestral', 'internacional',
   'Cultura e historia de las civilizaciones andinas.',
   NULL, 5, 0)
ON DUPLICATE KEY UPDATE
  nombre = VALUES(nombre),
  tipo = VALUES(tipo),
  descripcion = VALUES(descripcion),
  imagen_portada = VALUES(imagen_portada),
  orden = VALUES(orden),
  activo = VALUES(activo);

COMMIT;

-- Verificación
SELECT id, nombre, slug, tipo, imagen_portada, orden, activo, creado_en
FROM categorias
ORDER BY orden;
