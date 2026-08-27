<?php

namespace App\Controllers\Public;

use App\Helpers\Atribucion;
use App\Helpers\Flash;
use App\Helpers\RateLimiter;
use App\Helpers\Validator;
use App\Models\ConfiguracionSitio;
use App\Models\Paquete;
use App\Models\Salida;
use App\Services\MercadoPagoService;
use App\Services\ReservaService;
use Core\Controller;
use PDOException;
use RuntimeException;

class ReservaPublicaController extends Controller
{
    public function formulario(string $slug, int $salidaId): void
    {
        $paquete = Paquete::porSlugPublicado($slug);
        if (!$paquete) {
            $this->abort(404, 'Paquete no encontrado.');
        }

        $salida = Salida::find($salidaId);
        if (!$salida || (int) $salida['paquete_id'] !== (int) $paquete['id']) {
            $this->abort(404, 'Fecha de salida no encontrada.');
        }

        if ($salida['estado'] !== 'abierta' || (int) $salida['cupo_disponible'] < 1) {
            $this->abort(404, 'Esta fecha de salida ya no esta disponible.');
        }

        $precioUnitario = $salida['precio_override'] !== null
            ? (float) $salida['precio_override']
            : (float) $paquete['precio_desde'];

        $this->view('public/reservar/formulario', [
            'paquete' => $paquete,
            'salida' => $salida,
            'precioUnitario' => $precioUnitario,
            'porcentajeAnticipo' => $this->porcentajeAnticipo(),
            'errores' => [],
            'valores' => ['nombre' => '', 'email' => '', 'telefono' => '', 'num_personas' => '1', 'codigo_descuento' => ''],
        ], [
            'title' => 'Reservar ' . $paquete['titulo'] . ' | Dream Go Operadora Turistica',
        ]);
    }

    public function crear(): void
    {
        $this->verifyCsrf();

        // Auditoria 2026-08-25, hallazgos SEG-01/SEG-05/SEG-07: sin esto, una sola IP podia
        // mandar POSTs ilimitados a este endpoint para vaciar cupo real o probar codigos de
        // descuento por fuerza bruta. Se limita por IP (no por email: el propio formulario lo
        // manda el atacante, rotarlo no cuesta nada).
        $ip = $this->request->ip();
        if (RateLimiter::demasiados('reservar', null, $ip)) {
            RateLimiter::registrar('reservar', null, $ip);
            $this->abort(429, 'Demasiados intentos. Espera unos minutos e intenta de nuevo.');
        }
        RateLimiter::registrar('reservar', null, $ip);

        $datos = $this->request->only(['salida_id', 'nombre', 'email', 'telefono', 'num_personas', 'codigo_descuento']);
        $salidaId = (int) ($datos['salida_id'] ?? 0);

        $salida = Salida::find($salidaId);
        if (!$salida) {
            $this->abort(404, 'Fecha de salida no encontrada.');
        }

        $paquete = Paquete::find((int) $salida['paquete_id']);

        $validator = new Validator($datos);
        $validator->requerido('nombre', 'El nombre')->maxLength('nombre', 150, 'El nombre')
            ->requerido('email', 'El correo')->email('email', 'El correo')
            ->requerido('telefono', 'El telefono')->telefono('telefono', 'El telefono')
            ->requerido('num_personas', 'El numero de personas')->entero('num_personas', 'El numero de personas')
            ->enRango('num_personas', 1, 30, 'El numero de personas');

        $precioUnitario = $salida['precio_override'] !== null
            ? (float) $salida['precio_override']
            : (float) $paquete['precio_desde'];

        if (!$validator->pasa()) {
            $this->view('public/reservar/formulario', [
                'paquete' => $paquete,
                'salida' => $salida,
                'precioUnitario' => $precioUnitario,
                'porcentajeAnticipo' => $this->porcentajeAnticipo(),
                'errores' => $validator->errores(),
                'valores' => $datos,
            ], ['title' => 'Reservar ' . $paquete['titulo'] . ' | Dream Go Operadora Turistica']);

            return;
        }

        $service = new ReservaService($this->db);

        try {
            $reserva = $service->crearYNotificar([
                'salida_id' => $salidaId,
                'nombre' => $datos['nombre'],
                'email' => $datos['email'],
                'telefono' => $datos['telefono'],
                'num_personas' => (int) $datos['num_personas'],
                'codigo_descuento' => $datos['codigo_descuento'] ?: null,
                'atribucion' => Atribucion::desdeFormulario(
                    $this->request->only(Atribucion::campos()),
                    $_SERVER['HTTP_REFERER'] ?? null
                ),
            ]);
        } catch (PDOException $e) {
            // PDOException extiende RuntimeException en PHP 8: se captura aparte para nunca
            // mostrarle al visitante un SQLSTATE crudo (ej. un CHECK de la BD) si algo llega
            // a fallar a ese nivel pese a la validacion previa.
            error_log('[ReservaPublicaController] Error de base de datos al crear reserva: ' . $e->getMessage());
            $this->view('public/reservar/formulario', [
                'paquete' => $paquete,
                'salida' => $salida,
                'precioUnitario' => $precioUnitario,
                'porcentajeAnticipo' => $this->porcentajeAnticipo(),
                'errores' => ['general' => 'Ocurrio un error al procesar tu reserva. Intenta de nuevo.'],
                'valores' => $datos,
            ], ['title' => 'Reservar ' . $paquete['titulo'] . ' | Dream Go Operadora Turistica']);

            return;
        } catch (RuntimeException $e) {
            $this->view('public/reservar/formulario', [
                'paquete' => $paquete,
                'salida' => $salida,
                'precioUnitario' => $precioUnitario,
                'porcentajeAnticipo' => $this->porcentajeAnticipo(),
                'errores' => ['general' => $e->getMessage()],
                'valores' => $datos,
            ], ['title' => 'Reservar ' . $paquete['titulo'] . ' | Dream Go Operadora Turistica']);

            return;
        }

        $accessToken = $_ENV['MP_ACCESS_TOKEN'] ?? '';
        if ($accessToken === '') {
            Flash::set('exito', 'Tu reserva ' . $reserva['codigo_reserva'] . ' quedo registrada y tu cupo apartado. No pudimos iniciar el pago en linea (aun no esta configurado): te contactaremos para coordinarlo.');
            $this->redirect('/reservar/' . $reserva['codigo_reserva'] . '/gracias');
        }

        $montoAnticipo = round(((float) $reserva['precio_total']) * $this->porcentajeAnticipo() / 100, 2);

        try {
            $preferencia = (new MercadoPagoService($accessToken))->crearPreferencia(
                $reserva,
                $montoAnticipo,
                $paquete['moneda'] ?? 'MXN',
                (string) ($_ENV['APP_URL'] ?? ''),
                'anticipo'
            );
        } catch (RuntimeException $e) {
            error_log('[ReservaPublicaController] No se pudo crear la preferencia de Mercado Pago: ' . $e->getMessage());
            Flash::set('exito', 'Tu reserva ' . $reserva['codigo_reserva'] . ' quedo registrada y tu cupo apartado. No pudimos iniciar el pago en linea en este momento: te contactaremos para coordinarlo.');
            $this->redirect('/reservar/' . $reserva['codigo_reserva'] . '/gracias');
        }

        $this->redirect($preferencia['init_point']);
    }

    public function gracias(string $codigo): void
    {
        $status = (string) $this->request->query('status', '');
        $esSaldo = $this->request->query('concepto') === 'saldo';

        $mensajes = $esSaldo ? [
            'approved' => 'Tu pago del saldo fue aprobado. En unos minutos veras el saldo actualizado en Mi reserva.',
            'pending' => 'Tu pago del saldo esta en revision. Te avisaremos por correo apenas se acredite.',
            'failure' => 'El pago del saldo no se pudo completar. Puedes intentarlo de nuevo desde Mi reserva o contactarnos.',
        ] : [
            'approved' => 'Tu pago fue aprobado. En unos minutos tu reserva quedara confirmada.',
            'pending' => 'Tu pago esta en revision. Te avisaremos por correo apenas se confirme.',
            'failure' => 'Tu pago no se pudo completar. Puedes intentarlo de nuevo o contactarnos para coordinar el pago de otra forma.',
        ];

        $this->view('public/reservar/gracias', [
            'codigo' => $codigo,
            'mensaje' => $mensajes[$status] ?? null,
            'esAprobado' => $status === 'approved',
        ], ['title' => 'Reserva ' . $codigo . ' | Dream Go Operadora Turistica']);
    }

    private function porcentajeAnticipo(): int
    {
        $valor = (int) ConfiguracionSitio::get('porcentaje_anticipo_reserva', 100);

        return max(1, min(100, $valor));
    }
}
