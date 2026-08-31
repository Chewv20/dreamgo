-- Destinos dinamicos: hasta ahora la tabla `categorias` (destinos) solo se poblaba por SQL /
-- seed_demo.sql. Se agrega el CRUD en /admin/destinos (mismo patron que paquetes/blog) y con
-- el su permiso. La tabla `categorias` ya tiene todas las columnas necesarias (nombre, slug,
-- tipo, descripcion, imagen_portada, orden, activo), asi que no hay cambios de esquema.
--
-- Solo DML idempotente (INSERT IGNORE): re-ejecutable aunque migrate.php no llegue a marcarlo
-- como aplicado (ver database/migrations/README.md).

INSERT IGNORE INTO permisos (clave, modulo, descripcion) VALUES
  ('destinos.gestionar', 'destinos', 'Crear, editar y ordenar los destinos del sitio');

-- Rol Administrador (es_sistema = 1): hereda el permiso nuevo.
INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permisos p
WHERE r.es_sistema = 1 AND p.clave = 'destinos.gestionar';

-- Refresca el sello de permisos de los roles de sistema para que Auth::sesionVigente()
-- cierre las sesiones admin ya abiertas y recarguen el permiso nuevo en el proximo request
-- (mismo mecanismo que Rol::sincronizarPermisos). Sin esto, un admin logueado no veria el
-- modulo Destinos hasta volver a iniciar sesion.
UPDATE roles SET permisos_actualizado_en = NOW() WHERE es_sistema = 1;
