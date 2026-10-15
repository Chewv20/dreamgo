<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Helpers\Fecha;
use App\Models\ConfiguracionSitio;
use Core\Controller;

class LegalController extends Controller
{
    /** Fecha de la última revisión del texto del aviso; súbela al editar la vista. */
    private const AVISO_TEXTO_REVISADO = '2026-10-15';

    public function avisoPrivacidad(): void
    {
        // La fecha visible es la más reciente entre la revisión del texto y el último cambio en
        // Configuración de los datos que aparecen en el aviso (ver ConfiguracionController).
        $fecha = max(self::AVISO_TEXTO_REVISADO, (string) ConfiguracionSitio::get('aviso_privacidad_actualizado', ''));

        $this->view('public/legal/aviso-privacidad', [
            'actualizado' => Fecha::larga($fecha),
            'contacto' => [
                'razon_social' => (string) ConfiguracionSitio::get('razon_social', ''),
                'direccion' => (string) ConfiguracionSitio::get('direccion', ''),
                'email' => (string) ConfiguracionSitio::get('email_contacto', ''),
                'telefono' => (string) ConfiguracionSitio::get('telefono_contacto', ''),
            ],
        ], [
            'title' => 'Aviso de Privacidad | Dream Go Operadora Turística',
            'description' => 'Aviso de Privacidad de Dream Go Operadora Turística conforme a la LFPDPPP.',
        ]);
    }
}
