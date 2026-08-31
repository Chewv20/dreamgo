-- Auditoria 2026-08-27, informativo (RBAC inconsistente): articulos.gestionar era un permiso
-- unico para ver/crear/editar/archivar, mientras paquetes.* (modulo equivalente: contenido
-- publico con CRUD, slug, imagen, SEO y regeneracion de sitemap) esta dividido en
-- ver/crear/editar/eliminar. Se alinea el blog con ese patron.
--
-- Solo DML + INSERT IGNORE / DELETE idempotente: re-ejecutable aunque migrate.php no llegue a
-- marcarlo como aplicado (ver database/migrations/README.md).

INSERT IGNORE INTO permisos (clave, modulo, descripcion) VALUES
  ('articulos.ver', 'blog', 'Ver el listado de articulos del blog'),
  ('articulos.crear', 'blog', 'Crear nuevos articulos del blog'),
  ('articulos.editar', 'blog', 'Editar articulos del blog existentes'),
  ('articulos.eliminar', 'blog', 'Archivar articulos del blog');

-- Todo rol que tuviera el permiso viejo hereda los 4 nuevos (incluye al rol Administrador).
INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT rp.rol_id, nuevo.id
FROM rol_permiso rp
JOIN permisos viejo ON viejo.id = rp.permiso_id AND viejo.clave = 'articulos.gestionar'
JOIN permisos nuevo ON nuevo.clave IN ('articulos.ver', 'articulos.crear', 'articulos.editar', 'articulos.eliminar');

-- Red de seguridad: los 4 quedan en el rol Administrador aunque no tuviera el viejo.
INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT 1, id FROM permisos WHERE clave IN ('articulos.ver', 'articulos.crear', 'articulos.editar', 'articulos.eliminar');

-- Quita el permiso viejo; ON DELETE CASCADE en rol_permiso limpia el pivote.
DELETE FROM permisos WHERE clave = 'articulos.gestionar';
