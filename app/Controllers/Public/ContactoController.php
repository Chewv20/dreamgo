<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Helpers\Atribucion;
use App\Helpers\Flash;
use App\Helpers\RateLimiter;
use App\Helpers\Validator;
use App\Models\BloquePagina;
use App\Models\ConfiguracionSitio;
use App\Models\Cotizacion;
use App\Services\MailerService;
use App\Services\WhatsAppLinkService;
use Core\Controller;

class ContactoController extends Controller
{
    public function index(): void
    {
        $this->render();
    }

    /**
     * Formulario corto de contacto. Reusa la tabla `cotizaciones` y la notificacion al equipo
     * del cotizador (un mensaje de contacto es una cotizacion "General" sin paquete/personas/
     * fecha): asi el lead cae en el mismo CRM (/admin/cotizaciones) que ya usa el equipo, sin
     * tabla nueva. El landing_page (que la atribucion de site.js inyecta) queda en "/contacto",
     * que es lo que distingue estos leads de los del cotizador.
     */
    public function enviar(): void
    {
        $this->verifyCsrf();

        $ip = $this->request->ip();
        if (RateLimiter::demasiados('contacto', null, $ip)) {
            RateLimiter::registrar('contacto', null, $ip);
            $this->abort(429, 'Demasiados envíos. Espera unos minutos e intenta de nuevo.');
        }
        RateLimiter::registrar('contacto', null, $ip);

        $datos = array_merge(
            ['nombre' => '', 'email' => '', 'telefono' => '', 'mensaje' => ''],
            $this->request->only(['nombre', 'email', 'telefono', 'mensaje'])
        );

        $validator = new Validator($datos);
        $validator->requerido('nombre', 'El nombre')->maxLength('nombre', 150, 'El nombre')
            ->requerido('email', 'El correo')->email('email', 'El correo')
            ->requerido('telefono', 'El telefono')->telefono('telefono', 'El telefono')
            ->requerido('mensaje', 'El mensaje')->maxLength('mensaje', 2000, 'El mensaje');

        if (!$validator->pasa()) {
            Flash::set('error', 'Revisa los datos del formulario e intenta de nuevo.');
            $this->render($validator->errores(), $datos);

            return;
        }

        $cotizacionId = Cotizacion::insert([
            'paquete_id' => null,
            'nombre' => $datos['nombre'],
            'email' => $datos['email'],
            'telefono' => $datos['telefono'],
            'num_personas' => null,
            'fecha_tentativa' => null,
            'mensaje' => $datos['mensaje'],
            'ip_origen' => $ip,
        ] + Atribucion::desdeFormulario(
            $this->request->only(Atribucion::campos()),
            $_SERVER['HTTP_REFERER'] ?? null
        ));

        (new MailerService($this->db))->enviarNotificacionCotizacion([
            'id' => $cotizacionId,
            'nombre' => $datos['nombre'],
            'email' => $datos['email'],
            'telefono' => $datos['telefono'],
            'num_personas' => '',
            'fecha_tentativa' => '',
            'mensaje' => $datos['mensaje'],
            'paquete_titulo' => null,
        ]);

        Flash::set('exito', 'Gracias por escribirnos. Un asesor te contactará pronto.');
        $this->redirect('/contacto');
    }

    /**
     * @param array<string, string> $errores
     * @param array<string, string> $valores
     */
    private function render(array $errores = [], array $valores = []): void
    {
        $whatsapp = (new WhatsAppLinkService())->generarLink(
            ConfiguracionSitio::get('whatsapp_numero', ''),
            'Hola, me gustaria mas informacion sobre sus paquetes de viaje.'
        );

        $bloques = BloquePagina::porPagina('contacto');

        $this->view('public/contacto/index', [
            'whatsapp' => $whatsapp,
            'email' => ConfiguracionSitio::get('email_equipo_reportes', ''),
            'intro' => $bloques[0] ?? null,
            'errores' => $errores,
            'valores' => $valores,
        ], [
            'title' => 'Contacto | Dream Go Operadora Turística',
            'description' => 'Ponte en contacto con nuestro equipo de asesores de viaje.',
        ]);
    }
}
