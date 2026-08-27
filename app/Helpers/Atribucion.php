<?php

namespace App\Helpers;

/**
 * Normaliza los datos de atribucion de origen (UTM + referrer + landing) que llegan de un
 * formulario publico. El front (site.js) los arrastra desde la URL de aterrizaje via
 * sessionStorage; aca solo se sanea lo que llegue en el POST antes de guardarlo.
 */
final class Atribucion
{
    /** Claves aceptadas y su largo maximo (debe coincidir con la migracion 0018). */
    private const CAMPOS = [
        'utm_source' => 100,
        'utm_medium' => 100,
        'utm_campaign' => 100,
        'utm_term' => 100,
        'utm_content' => 100,
        'referrer' => 255,
        'landing_page' => 255,
    ];

    /**
     * Igual que sanitizar(), pero si el formulario no trajo referrer (JS deshabilitado, o el
     * visitante llego directo al form) usa el header Referer de la peticion como respaldo.
     *
     * @param array<string, mixed> $crudo  normalmente $request->only(Atribucion::campos())
     * @return array<string, string|null>
     */
    public static function desdeFormulario(array $crudo, ?string $refererHeader = null): array
    {
        if (($crudo['referrer'] ?? '') === '' && $refererHeader !== null && $refererHeader !== '') {
            $crudo['referrer'] = $refererHeader;
        }

        return self::sanitizar($crudo);
    }

    /**
     * @param array<string, mixed> $crudo  normalmente $request->only(array_keys(Atribucion::campos()))
     * @return array<string, string|null>  las 7 claves siempre presentes; valor null si venia vacia
     */
    public static function sanitizar(array $crudo): array
    {
        $limpio = [];

        foreach (self::CAMPOS as $campo => $maxLen) {
            $valor = $crudo[$campo] ?? '';
            $valor = is_string($valor) ? $valor : '';
            // Quita saltos de linea y otros caracteres de control (evita headers falseados en
            // reportes/CSV y ruido en el panel), colapsa espacios y recorta al largo de columna.
            $valor = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $valor) ?? '';
            $valor = trim(preg_replace('/\s+/u', ' ', $valor) ?? '');
            $valor = mb_substr($valor, 0, $maxLen);

            $limpio[$campo] = $valor === '' ? null : $valor;
        }

        return $limpio;
    }

    /** @return list<string> nombres de los campos de atribucion */
    public static function campos(): array
    {
        return array_keys(self::CAMPOS);
    }
}
