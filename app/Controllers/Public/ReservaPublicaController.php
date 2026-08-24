<?php

namespace App\Controllers\Public;

use App\Helpers\Flash;
use App\Helpers\Validator;
use App\Models\ConfiguracionSitio;
use App\Models\Paquete;
use App\Models\Reserva;
use App\Models\Salida;
use App\Services\MailerService;
use App\Services\MercadoPagoService;
use App\Services\ReservaService;
use Core\Controller;
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
            ->requerido('num_personas', 'El numero de personas')->entero('num_personas', 'El numero de personas');

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
            $reservaId = $service->crear([
                'salida_id' => $salidaId,
                'nombre' => $datos['nombre'],
                'email' => $datos['email'],
                'telefono' => $datos['telefono'],
                'num_personas' => (int) $datos['num_personas'],
                'codigo_descuento' => $datos['codigo_descuento'] ?: null,
            ]);
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

        $reserva = Reserva::conDetalle($reservaId);
        (new MailerService($this->db))->enviarReservaPendiente($reserva);

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
                (string) ($_ENV['APP_URL'] ?? '')
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

        $mensajes = [
            'approved' => 'Tu pago fue aprobado. En unos minutos tu reserva quedara confirmada.',
            'pending' => 'Tu pago esta en revision. Te avisaremos por correo apenas se confirme.',
            'failure' => 'Tu pago no se pudo completar. Puedes intentarlo de nuevo o contactarnos para coordinar el pago de otra forma.',
        ];

        $this->view('public/reservar/gracias', [
            'codigo' => $codigo,
            'mensaje' => $mensajes[$status] ?? null,
        ], ['title' => 'Reserva ' . $codigo . ' | Dream Go Operadora Turistica']);
    }

    private function porcentajeAnticipo(): int
    {
        $valor = (int) ConfiguracionSitio::get('porcentaje_anticipo_reserva', 100);

        return max(1, min(100, $valor));
    }
}
