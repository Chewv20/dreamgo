<?php

namespace App\Controllers\Public;

use App\Helpers\Atribucion;
use App\Helpers\Csrf;
use App\Helpers\Flash;
use App\Helpers\Validator;
use App\Models\Cotizacion;
use App\Models\ConfiguracionSitio;
use App\Models\Paquete;
use App\Services\MailerService;
use App\Services\WhatsAppLinkService;
use Core\Controller;

class CotizadorController extends Controller
{
    public function mostrar(): void
    {
        $paqueteSlug = (string) $this->request->query('paquete', '');
        $paquete = $paqueteSlug !== '' ? Paquete::porSlugPublicado($paqueteSlug) : null;

        $this->view('public/cotizador/formulario', [
            'paquete' => $paquete,
        ], [
            'title' => 'Cotiza tu viaje | Dream Go Operadora Turistica',
            'description' => 'Solicita una cotizacion personalizada para tu proximo viaje con Dream Go.',
        ]);
    }

    public function enviar(): void
    {
        $this->verifyCsrf();

        $datos = array_merge(
            ['nombre' => '', 'email' => '', 'telefono' => '', 'num_personas' => '', 'fecha_tentativa' => '', 'mensaje' => '', 'paquete_slug' => ''],
            $this->request->only(['nombre', 'email', 'telefono', 'num_personas', 'fecha_tentativa', 'mensaje', 'paquete_slug'])
        );

        $validator = new Validator($datos);
        $validator->requerido('nombre', 'El nombre')->maxLength('nombre', 150, 'El nombre')
            ->requerido('email', 'El correo')->email('email', 'El correo')
            ->requerido('telefono', 'El telefono')->telefono('telefono', 'El telefono')
            ->entero('num_personas', 'El numero de personas')->enRango('num_personas', 1, 60, 'El numero de personas')
            ->fecha('fecha_tentativa', 'La fecha tentativa')
            ->maxLength('mensaje', 2000, 'El mensaje');

        if (!$validator->pasa()) {
            Flash::set('error', 'Revisa los datos del formulario e intenta de nuevo.');
            $this->view('public/cotizador/formulario', [
                'paquete' => null,
                'errores' => $validator->errores(),
                'valores' => $datos,
            ], ['title' => 'Cotiza tu viaje | Dream Go Operadora Turistica']);

            return;
        }

        $paquete = !empty($datos['paquete_slug']) ? Paquete::porSlugPublicado($datos['paquete_slug']) : null;

        $cotizacionId = Cotizacion::insert([
            'paquete_id' => $paquete['id'] ?? null,
            'nombre' => $datos['nombre'],
            'email' => $datos['email'],
            'telefono' => $datos['telefono'],
            'num_personas' => $datos['num_personas'] !== '' ? (int) $datos['num_personas'] : null,
            'fecha_tentativa' => $datos['fecha_tentativa'] !== '' ? $datos['fecha_tentativa'] : null,
            'mensaje' => $datos['mensaje'] !== '' ? $datos['mensaje'] : null,
            'ip_origen' => $this->request->ip(),
        ] + Atribucion::desdeFormulario(
            $this->request->only(Atribucion::campos()),
            $_SERVER['HTTP_REFERER'] ?? null
        ));

        $mailer = new MailerService($this->db);
        $mailer->enviarNotificacionCotizacion([
            'id' => $cotizacionId,
            'nombre' => $datos['nombre'],
            'email' => $datos['email'],
            'telefono' => $datos['telefono'],
            'num_personas' => $datos['num_personas'],
            'fecha_tentativa' => $datos['fecha_tentativa'],
            'mensaje' => $datos['mensaje'],
            'paquete_titulo' => $paquete['titulo'] ?? null,
        ]);

        $whatsapp = (new WhatsAppLinkService())->generarLinkCotizacionGeneral(
            ConfiguracionSitio::get('whatsapp_numero', ''),
            $datos
        );

        $this->view('public/cotizador/gracias', [
            'whatsapp' => $whatsapp,
        ], ['title' => 'Gracias por tu solicitud | Dream Go Operadora Turistica']);
    }
}
