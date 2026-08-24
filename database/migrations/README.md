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

Nunca edites un archivo ya commiteado si ya se aplicó en algún ambiente (local, staging,
producción) — agrega uno nuevo, igual que con cualquier sistema de migraciones.
