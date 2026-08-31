<?php

namespace Tests\Unit\Core;

use Core\Model;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Core\Model interpola nombres de columna y la clausula ORDER BY sin binding (PDO no lo
 * permite para identificadores). Auditoria 2026-08-31, hallazgo CAL-02: las guardas
 * assertColumnas()/assertOrderBy() son la red contra un futuro `insert($request->all())`.
 */
final class ModelGuardsTest extends TestCase
{
    /**
     * @dataProvider columnasValidas
     */
    public function testColumnasValidasPasan(string $columna): void
    {
        $this->expectNotToPerformAssertions();
        ModelGuardsFixture::probarColumnas([$columna]);
    }

    public static function columnasValidas(): array
    {
        return [['id'], ['creado_en'], ['utm_source'], ['_interno'], ['col123']];
    }

    /**
     * @dataProvider columnasInvalidas
     */
    public function testColumnasInvalidasLanzan(mixed $columna): void
    {
        $this->expectException(InvalidArgumentException::class);
        ModelGuardsFixture::probarColumnas([$columna]);
    }

    public static function columnasInvalidas(): array
    {
        return [
            'con espacio' => ['nombre, (SELECT 1)'],
            'con punto' => ['t.col'],
            'con parentesis' => ['count(*)'],
            'comilla' => ["nombre'"],
            'guion' => ['created-at'],
            'vacia' => [''],
            'empieza por numero' => ['1col'],
            'no string' => [123],
        ];
    }

    /**
     * @dataProvider ordenesValidos
     */
    public function testOrderByValidoPasa(string $orderBy): void
    {
        $this->expectNotToPerformAssertions();
        ModelGuardsFixture::probarOrderBy($orderBy);
    }

    public static function ordenesValidos(): array
    {
        return [
            ['creado_en ASC'],
            ['nombre'],
            ['orden ASC'],
            ['modulo ASC, clave ASC'],
            ['p.destacado DESC, p.creado_en DESC'],
        ];
    }

    /**
     * @dataProvider ordenesInvalidos
     */
    public function testOrderByInvalidoLanza(string $orderBy): void
    {
        $this->expectException(InvalidArgumentException::class);
        ModelGuardsFixture::probarOrderBy($orderBy);
    }

    public static function ordenesInvalidos(): array
    {
        return [
            'subconsulta' => ['(SELECT 1)'],
            'inyeccion union' => ['id; DROP TABLE reservas'],
            'comentario' => ['id -- x'],
            'funcion' => ['RAND()'],
            'direccion rara' => ['id SIDEWAYS'],
            'coma colgando' => ['id ASC,'],
        ];
    }
}

/**
 * Expone las guardas protegidas de Core\Model sin necesidad de conexion a base de datos.
 */
final class ModelGuardsFixture extends Model
{
    protected static string $table = 'fixture';

    /** @param list<mixed> $columnas */
    public static function probarColumnas(array $columnas): void
    {
        self::assertColumnas($columnas);
    }

    public static function probarOrderBy(string $orderBy): void
    {
        self::assertOrderBy($orderBy);
    }
}
