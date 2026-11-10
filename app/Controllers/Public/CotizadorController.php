<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Helpers\AntiSpam;
use App\Helpers\Atribucion;
use App\Helpers\Csrf;
use App\Helpers\Flash;
use App\Helpers\RateLimiter;
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
            'opcionesPaquete' => $paquete ? [] : Paquete::opcionesPublicadas(),
        ], [
            'title' => 'Cotiza tu viaje | Dream Go Operadora Turística',
            'description' => 'Solicita una cotización personalizada para tu próximo viaje con Dream Go.',
        ]);
    }

    public function enviar(): void
    {
        $this->verifyCsrf();

        // Sin esto, una sola IP podia mandar POSTs ilimitados: cada uno inserta una cotizacion
        // y dispara un correo al equipo (mismo criterio que /reservar y /suscribir; el email
        // no sirve de identificador porque lo pone el propio formulario).
        $ip = $this->request->ip();
        if (RateLimiter::demasiados('cotizador', null, $ip)) {
            RateLimiter::registrar('cotizador', null, $ip);
            $this->abort(429, 'Demasiados envíos. Espera unos minutos e intenta de nuevo.');
        }
        RateLimiter::registrar('cotizador', null, $ip);

        // Honeypot lleno: se finge exito (sin guardar ni enviar correos) para que el bot no
        // aprenda a esquivarlo.
        if (AntiSpam::honeypotLleno($this->request->input(AntiSpam::CAMPO_HONEYPOT))) {
            $this->view('public/cotizador/gracias', [
                'whatsapp' => (new WhatsAppLinkService())->generarLink(
                    (string) ConfiguracionSitio::get('whatsapp_numero', ''),
                    'Hola, quiero cotizar un viaje con Dream Go.'
                ),
            ], ['title' => 'Gracias por tu solicitud | Dream Go Operadora Turística']);

            return;
        }
        // Un envio demasiado rapido puede ser una persona con autocompletado, asi que no se
        // descarta en silencio: se vuelve a mostrar el formulario con sus datos.
        $demasiadoRapido = AntiSpam::demasiadoRapido('cotizador');

        $datos = array_merge(
            ['nombre' => '', 'email' => '', 'telefono' => '', 'num_personas' => '', 'fecha_tentativa' => '', 'mensaje' => '', 'paquete_slug' => ''],
            $this->request->only(['nombre', 'email', 'telefono', 'num_personas', 'fecha_tentativa', 'mensaje', 'paquete_slug'])
        );

        $validator = new Validator($datos);
        $validator->requerido('nombre', 'El nombre')->maxLength('nombre', 150, 'El nombre')
            ->nombrePersona('nombre', 'El nombre')
            ->requerido('email', 'El correo')->email('email', 'El correo')
            ->requerido('telefono', 'El teléfono')->telefono('telefono', 'El teléfono')
            ->entero('num_personas', 'El número de personas')->enRango('num_personas', 1, 60, 'El número de personas')
            ->fecha('fecha_tentativa', 'La fecha tentativa')
            ->maxLength('mensaje', 2000, 'El mensaje');

        if (!$validator->pasa() || $demasiadoRapido) {
            Flash::set('error', $validator->pasa()
                ? 'No pudimos procesar el envío. Revisa tus datos y vuelve a enviar el formulario.'
                : 'Revisa los datos del formulario e intenta de nuevo.');
            // Si venía de una ficha se conserva el aviso "Estás cotizando"; si no, el selector.
            $paqueteError = $datos['paquete_slug'] !== '' && $this->request->input('paquete_desde_ficha') === '1'
                ? Paquete::porSlugPublicado($datos['paquete_slug'])
                : null;
            $this->view('public/cotizador/formulario', [
                'paquete' => $paqueteError ?: null,
                'opcionesPaquete' => $paqueteError ? [] : Paquete::opcionesPublicadas(),
                'errores' => $validator->errores(),
                'valores' => $datos,
            ], ['title' => 'Cotiza tu viaje | Dream Go Operadora Turística']);

            return;
        }

        $paquete = !empty($datos['paquete_slug']) ? Paquete::porSlugPublicado($datos['paquete_slug']) : null;

        // Los opcionales vacios van como null (no ''): asi los correos y el mensaje de WhatsApp
        // muestran "No especificado"/"Por definir" en vez de una celda en blanco.
        $cotizacion = [
            'nombre' => $datos['nombre'],
            'email' => $datos['email'],
            'telefono' => $datos['telefono'],
            'num_personas' => $datos['num_personas'] !== '' ? (int) $datos['num_personas'] : null,
            'fecha_tentativa' => $datos['fecha_tentativa'] !== '' ? $datos['fecha_tentativa'] : null,
            'mensaje' => $datos['mensaje'] !== '' ? $datos['mensaje'] : null,
        ];

        $cotizacionId = Cotizacion::insert($cotizacion + [
            'paquete_id' => $paquete['id'] ?? null,
            'ip_origen' => $this->request->ip(),
        ] + Atribucion::desdeFormulario(
            $this->request->only(Atribucion::campos()),
            $_SERVER['HTTP_REFERER'] ?? null
        ));

        $cotizacion += [
            'id' => $cotizacionId,
            'origen' => 'cotizador',
            'paquete_titulo' => $paquete['titulo'] ?? null,
        ];

        $numeroWhatsapp = (string) ConfiguracionSitio::get('whatsapp_numero', '');
        $whatsapp = (new WhatsAppLinkService())->generarLinkCotizacionGeneral($numeroWhatsapp, $cotizacion);

        $mailer = new MailerService($this->db);
        $mailer->enviarNotificacionCotizacion($cotizacion);
        $mailer->enviarConfirmacionCotizacion($cotizacion, $numeroWhatsapp !== '' ? $whatsapp : '');

        $this->view('public/cotizador/gracias', [
            'whatsapp' => $whatsapp,
        ], ['title' => 'Gracias por tu solicitud | Dream Go Operadora Turística']);
    }
}
