<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testRequeridoFallaConCampoVacioOAusente(): void
    {
        $v = new Validator(['nombre' => '   ']);
        $v->requerido('nombre', 'El nombre');
        $this->assertFalse($v->pasa());
        $this->assertArrayHasKey('nombre', $v->errores());

        $v2 = new Validator([]);
        $v2->requerido('nombre', 'El nombre');
        $this->assertFalse($v2->pasa());
    }

    public function testEmailValidoPasaEInvalidoFalla(): void
    {
        $ok = new Validator(['email' => 'a@b.com']);
        $ok->email('email', 'El correo');
        $this->assertTrue($ok->pasa());

        $mal = new Validator(['email' => 'no-es-un-correo']);
        $mal->email('email', 'El correo');
        $this->assertFalse($mal->pasa());
    }

    /**
     * enRango() es la validacion agregada en la auditoria del 2026-08-25 para num_personas:
     * antes solo se exigia entero(), que acepta negativos (filter_var(-5, FILTER_VALIDATE_INT)
     * no es false), permitiendo vaciar/invertir cupo_disponible en ReservaService::crear().
     */
    public function testEnRangoRechazaNegativosCeroYExcesivos(): void
    {
        foreach ([-5, 0, 31] as $valor) {
            $v = new Validator(['num_personas' => $valor]);
            $v->enRango('num_personas', 1, 30, 'El numero de personas');
            $this->assertFalse($v->pasa(), "num_personas={$valor} deberia fallar enRango(1,30)");
        }
    }

    public function testEnRangoAceptaLimitesInclusive(): void
    {
        foreach ([1, 15, 30] as $valor) {
            $v = new Validator(['num_personas' => $valor]);
            $v->enRango('num_personas', 1, 30, 'El numero de personas');
            $this->assertTrue($v->pasa(), "num_personas={$valor} deberia pasar enRango(1,30)");
        }
    }

    public function testEnRangoIgnoraCampoVacioOAusente(): void
    {
        // Igual que el resto de los metodos de Validator: el rango solo aplica si hay valor;
        // combinarlo con requerido() es responsabilidad del llamador.
        $v = new Validator(['num_personas' => '']);
        $v->enRango('num_personas', 1, 30, 'El numero de personas');
        $this->assertTrue($v->pasa());

        $v2 = new Validator([]);
        $v2->enRango('num_personas', 1, 30, 'El numero de personas');
        $this->assertTrue($v2->pasa());
    }

    public function testEnteroAceptaNegativosPeroNoDecimalesNiTexto(): void
    {
        // Documenta el comportamiento real de entero(): NO exige positivo (por eso
        // num_personas necesita enRango() ademas). Sirve de red si alguien vuelve a asumir
        // que entero() alcanza para validar cantidades.
        $negativo = new Validator(['n' => '-5']);
        $negativo->entero('n', 'N');
        $this->assertTrue($negativo->pasa());

        $decimal = new Validator(['n' => '1.5']);
        $decimal->entero('n', 'N');
        $this->assertFalse($decimal->pasa());

        $texto = new Validator(['n' => 'abc']);
        $texto->entero('n', 'N');
        $this->assertFalse($texto->pasa());
    }

    public function testMaxLengthCuentaCaracteresMultibyte(): void
    {
        $v = new Validator(['nombre' => str_repeat('ñ', 151)]);
        $v->maxLength('nombre', 150, 'El nombre');
        $this->assertFalse($v->pasa());
    }

    public function testEncadenamientoAcumulaTodosLosErrores(): void
    {
        $v = new Validator(['email' => 'malo', 'num_personas' => -1]);
        $v->requerido('nombre', 'El nombre')
            ->email('email', 'El correo')
            ->enRango('num_personas', 1, 30, 'El numero de personas');

        $errores = $v->errores();
        $this->assertCount(3, $errores);
        $this->assertArrayHasKey('nombre', $errores);
        $this->assertArrayHasKey('email', $errores);
        $this->assertArrayHasKey('num_personas', $errores);
    }
}
