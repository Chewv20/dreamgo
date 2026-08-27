<?php

namespace App\Controllers\Public;

use App\Models\ConfiguracionSitio;
use Core\Controller;

class LegalController extends Controller
{
    public function avisoPrivacidad(): void
    {
        $this->view('public/legal/aviso-privacidad', [
            'contacto' => [
                'direccion' => (string) ConfiguracionSitio::get('direccion', ''),
                'email' => (string) ConfiguracionSitio::get('email_contacto', ''),
                'telefono' => (string) ConfiguracionSitio::get('telefono_contacto', ''),
            ],
        ], [
            'title' => 'Aviso de Privacidad | Dream Go Operadora Turistica',
            'description' => 'Aviso de Privacidad de Dream Go Operadora Turistica conforme a la LFPDPPP.',
        ]);
    }
}
