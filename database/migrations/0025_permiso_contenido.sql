-- Contenido y colores del sitio: el modulo /admin/contenido y /admin/colores (editar textos,
-- orden de bloques y paleta de colores de las paginas publicas) ya existe en el codigo, pero
-- su permiso `contenido.gestionar` solo estaba en seed_demo.sql. Las bases que se sembraron
-- antes de que esa linea se agregara al seed nunca lo recibieron y por eso el panel no
-- muestra los enlaces "Contenido del sitio" ni "Colores del sitio". Esta migracion lo agrega
-- a bases ya existentes (mismo patron que 0023 destinos / 0024 modales). Las tablas
-- `bloques_pagina` y `configuracion_sitio` ya estan en schema.sql, asi que no hay DDL.
--
-- Solo DML idempotente (INSERT IGNORE): re-ejecutable aunque migrate.php no llegue a marcarlo
-- como aplicado (ver database/migrations/README.md).

INSERT IGNORE INTO permisos (clave, modulo, descripcion) VALUES
  ('contenido.gestionar', 'contenido', 'Editar textos, orden y colores de las paginas publicas');

-- Rol Administrador (es_sistema = 1): hereda el permiso nuevo.
INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permisos p
WHERE r.es_sistema = 1 AND p.clave = 'contenido.gestionar';

-- Refresca el sello de permisos de los roles de sistema para que Auth::sesionVigente()
-- cierre las sesiones admin ya abiertas y recarguen el permiso nuevo en el proximo request
-- (mismo mecanismo que Rol::sincronizarPermisos). Sin esto, un admin logueado no veria los
-- modulos Contenido / Colores hasta volver a iniciar sesion.
UPDATE roles SET permisos_actualizado_en = NOW() WHERE es_sistema = 1;
