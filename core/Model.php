<?php

declare(strict_types=1);

namespace Core;

use PDO;

abstract class Model
{
    protected static string $table;
    protected static string $primaryKey = 'id';

    protected static function db(): PDO
    {
        return Database::connection();
    }

    /**
     * @param callable(PDO): mixed $callback
     */
    protected static function transaction(callable $callback): mixed
    {
        return Database::transaction(static::db(), $callback);
    }

    public static function find(int|string $id): array|false
    {
        $stmt = static::db()->prepare(
            'SELECT * FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch();
    }

    public static function all(string $orderBy = ''): array
    {
        $sql = 'SELECT * FROM ' . static::$table;
        if ($orderBy !== '') {
            self::assertOrderBy($orderBy);
            $sql .= ' ORDER BY ' . $orderBy;
        }

        return static::db()->query($sql)->fetchAll();
    }

    /**
     * @param array<string, mixed> $conditions clave => valor, unidas con AND
     */
    public static function where(array $conditions, string $orderBy = '', ?int $limit = null): array
    {
        [$clause, $params] = self::buildWhere($conditions);

        $sql = 'SELECT * FROM ' . static::$table;
        if ($clause !== '') {
            $sql .= ' WHERE ' . $clause;
        }
        if ($orderBy !== '') {
            self::assertOrderBy($orderBy);
            $sql .= ' ORDER BY ' . $orderBy;
        }
        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function first(array $conditions): array|false
    {
        $rows = self::where($conditions, '', 1);

        return $rows[0] ?? false;
    }

    public static function insert(array $data): int
    {
        $columns = array_keys($data);
        self::assertColumnas($columns);
        $placeholders = array_map(fn ($c) => ':' . $c, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            static::$table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = static::db()->prepare($sql);
        $stmt->execute($data);

        return (int) static::db()->lastInsertId();
    }

    public static function update(int|string $id, array $data): bool
    {
        self::assertColumnas(array_keys($data));
        $set = implode(', ', array_map(fn ($c) => "{$c} = :{$c}", array_keys($data)));

        $sql = 'UPDATE ' . static::$table . ' SET ' . $set . ' WHERE ' . static::$primaryKey . ' = :__id';

        $stmt = static::db()->prepare($sql);

        return $stmt->execute([...$data, '__id' => $id]);
    }

    public static function delete(int|string $id): bool
    {
        $stmt = static::db()->prepare(
            'DELETE FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = :id'
        );

        return $stmt->execute(['id' => $id]);
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private static function buildWhere(array $conditions): array
    {
        if ($conditions === []) {
            return ['', []];
        }

        $parts = [];
        $params = [];
        foreach ($conditions as $column => $value) {
            self::assertColumnas([$column]);
            $paramKey = 'w_' . $column;
            $parts[] = "{$column} = :{$paramKey}";
            $params[$paramKey] = $value;
        }

        return [implode(' AND ', $parts), $params];
    }

    /**
     * Auditoria 2026-08-31, hallazgo CAL-02: insert()/update()/where() interpolan nombres de
     * columna sin binding (PDO no permite bindear identificadores). Hoy todos los llamadores
     * pasan claves literales, pero un futuro `insert($request->all())` seria inyeccion SQL +
     * mass-assignment. Estas guardas exigen identificadores sencillos: no bloquean una columna
     * mal escrita (eso lo caza la BD con "Unknown column"), pero si cualquier cosa que no sea
     * un identificador -> InvalidArgumentException antes de tocar la BD.
     *
     * @param list<string> $columnas
     */
    protected static function assertColumnas(array $columnas): void
    {
        foreach ($columnas as $columna) {
            if (!is_string($columna) || preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $columna) !== 1) {
                throw new \InvalidArgumentException('Nombre de columna invalido: ' . var_export($columna, true));
            }
        }
    }

    /**
     * Acepta "col", "col ASC", "col DESC", "alias.col DESC", y listas separadas por comas
     * ("a ASC, b DESC"). Rechaza parentesis, subconsultas, comentarios y cualquier otra cosa.
     */
    protected static function assertOrderBy(string $orderBy): void
    {
        $termino = '[a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z_][a-zA-Z0-9_]*)?(?:\s+(?:ASC|DESC))?';

        if (preg_match('/^' . $termino . '(?:\s*,\s*' . $termino . ')*$/i', trim($orderBy)) !== 1) {
            throw new \InvalidArgumentException('Clausula ORDER BY invalida: ' . $orderBy);
        }
    }
}
