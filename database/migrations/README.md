# Migraciones

`schema.sql` sigue siendo la referencia completa para una instalacion **nueva** (import
directo via phpMyAdmin o `mysql < database/schema.sql`, como describe `DEPLOY.md`). Esta
carpeta es para llevar una base de datos **ya existente** al dia sin reimportar nada.

## Uso

```
php database/migrate.php
```

Crea la tabla `schema_migrations` si no existe, aplica en orden los archivos `.sql` de esta
carpeta que todavia no esten registrados ahi, y va marcando cada uno como aplicado. Correrlo
de nuevo cuando no hay nada pendiente no hace nada (imprime "Nada que aplicar").

## Agregar un cambio nuevo

1. Crea `NNNN_descripcion_corta.sql` (siguiente numero, 4 digitos, orden alfabetico =
   orden de ejecucion) con las sentencias `ALTER`/`CREATE`/etc. necesarias.
2. Aplica el mismo cambio a mano en `../schema.sql`, para que una instalacion nueva quede
   igual que una actualizada via migracion.
3. Corre `php database/migrate.php` localmente para probarlo antes de hacer commit.

Mantén cada archivo chico y autocontenido (un cambio lógico por archivo): MySQL/MariaDB
hace commit implícito en cada sentencia DDL, así que si un archivo con varias sentencias
falla a medio camino, las anteriores quedan aplicadas de todas formas.

Justamente por eso, cada `CREATE TABLE`/`CREATE INDEX` debe llevar `IF NOT EXISTS`, y cada
`INSERT INTO permisos` debe ser `INSERT IGNORE` (o `ON DUPLICATE KEY UPDATE`): si el archivo
falla a medio camino y `migrate.php` no llega a marcarlo como aplicado, el reintento no debe
reventar con "ya existe" sobre lo que sí se alcanzó a crear antes del fallo. (Auditoría
2026-08-25, hallazgo BD-01: `0006` y `0009` no siguen esto — no se corrigieron ahí porque la
regla de abajo lo prohíbe, pero cualquier migración nueva sí debe seguir este patrón.)

Nunca edites un archivo ya commiteado si ya se aplicó en algún ambiente (local, staging,
producción) — agrega uno nuevo, igual que con cualquier sistema de migraciones.
