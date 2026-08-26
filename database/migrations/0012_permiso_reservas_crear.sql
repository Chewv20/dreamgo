-- Auditoria 2026-08-25, hallazgo SEG-06: las rutas de creacion de reserva admin
-- (GET/POST /admin/reservas/crear) solo exigian 'reservas.ver', asi que un rol de
-- solo-lectura (ej. para reportes) podia crear reservas reales que descuentan cupo. El
-- esquema ya separaba reservas.ver/confirmar/cancelar pero le faltaba reservas.crear.

INSERT IGNORE INTO permisos (clave, modulo, descripcion) VALUES
  ('reservas.crear', 'reservas', 'Crear reservas manuales desde el panel');

INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT 1, id FROM permisos WHERE clave = 'reservas.crear';
