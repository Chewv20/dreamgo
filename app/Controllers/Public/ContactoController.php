<?php

namespace App\Controllers\Public;

use App\Models\ConfiguracionSitio;
use App\Services\WhatsAppLinkService;
use Core\Controller;

class ContactoController extends Controller
{
    public function index(): void
    {
        $whatsapp = (new WhatsAppLinkService())->generarLink(
            ConfiguracionSitio::get('whatsapp_numero', ''),
            'Hola, me gustaria mas informacion sobre sus paquetes de viaje.'
        );

        $this->view('public/contacto/index', [
            'whatsapp' => $whatsapp,
            'email' => ConfiguracionSitio::get('email_equipo_reportes', ''),
        ], [
            'title' => 'Contacto | Dream Go Operadora Turistica',
            'description' => 'Ponte en contacto con nuestro equipo de asesores de viaje.',
        ]);
    }
}
