<?php

namespace App\Controllers\Admin;

use App\Helpers\Flash;
use App\Helpers\Validator;
use App\Models\Reserva;
use App\Models\Salida;
use App\Services\MailerService;
use App\Services\ReservaService;
use Core\Paginator;
use RuntimeException;

class ReservaAdminController extends AdminController
{
    private const POR_PAGINA = 20;

    public function index(): void
    {
        $paginador = new Paginator(Paginator::paginaDesde($this->request), self::POR_PAGINA, Reserva::contarTotal());

        $this->view('admin/reservas/index', [
            'reservas' => Reserva::adminListado($paginador->porPagina, $paginador->offset()),
            'paginador' => $paginador,
        ], ['title' => 'Reservas | Dream Go', 'heading' => 'Reservas']);
    }

    public function calendario(): void
    {
        $this->view('admin/reservas/calendario', [], ['title' => 'Calendario de reservas | Dream Go', 'heading' => 'Calendario de reservas']);
    }

    public function calendarioDatos(): void
    {
        $anio = (int) $this->request->query('anio', date('Y'));
        $mes = (int) $this->request->query('mes', date('n'));

        $this->json(Reserva::calendarioMes($anio, $mes));
    }

    public function detalle(int $id): void
    {
        $reserva = Reserva::conDetalle($id);
        if (!$reserva) {
            $this->abort(404);
        }

        $this->view('admin/reservas/detalle', [
            'reserva' => $reserva,
        ], ['title' => 'Reserva ' . $reserva['codigo_reserva'] . ' | Dream Go', 'heading' => 'Reserva ' . $reserva['codigo_reserva']]);
    }

    public function crearForm(): void
    {
        $salidaId = (int) $this->request->query('salida_id', 0);
        $salida = $salidaId ? Salida::find($salidaId) : null;

        if (!$salida) {
            Flash::set('error', 'Selecciona una fecha de salida desde el paquete correspondiente para crear una reserva.');
            $this->redirect('/admin/paquetes');
        }

        $this->view('admin/reservas/create', [
            'salida' => $salida,
        ], ['title' => 'Nueva reserva | Dream Go', 'heading' => 'Nueva reserva']);
    }

    public function crear(): void
    {
        $this->verifyCsrf();

        $datos = $this->request->only(['salida_id', 'nombre', 'email', 'telefono', 'num_personas', 'codigo_descuento']);

        $validator = new Validator($datos);
        $validator->requerido('salida_id', 'La fecha de salida')
            ->requerido('nombre', 'El nombre')
            ->requerido('email', 'El correo')->email('email', 'El correo')
            ->requerido('telefono', 'El telefono')
            ->requerido('num_personas', 'El numero de personas')->entero('num_personas', 'El numero de personas');

        $this->redirigirSiInvalido($validator, '/admin/reservas/crear?salida_id=' . (int) $datos['salida_id']);

        $service = new ReservaService($this->db);

        try {
            $reservaId = $service->crear([
                'salida_id' => (int) $datos['salida_id'],
                'nombre' => $datos['nombre'],
                'email' => $datos['email'],
                'telefono' => $datos['telefono'],
                'num_personas' => (int) $datos['num_personas'],
                'codigo_descuento' => $datos['codigo_descuento'] ?: null,
            ]);
        } catch (RuntimeException $e) {
            Flash::set('error', $e->getMessage());
            $this->redirect('/admin/reservas/crear?salida_id=' . (int) $datos['salida_id']);
        }

        $reserva = Reserva::conDetalle($reservaId);
        (new MailerService($this->db))->enviarReservaPendiente($reserva);

        Flash::set('exito', 'Reserva ' . $reserva['codigo_reserva'] . ' creada. El cupo ya quedo apartado.');
        $this->redirect('/admin/reservas/' . $reservaId);
    }

    public function confirmar(int $id): void
    {
        $this->verifyCsrf();

        $service = new ReservaService($this->db);
        if (!$service->confirmar($id)) {
            Flash::set('error', 'La reserva ya no esta pendiente.');
            $this->redirect('/admin/reservas/' . $id);
        }

        $reserva = Reserva::conDetalle($id);
        (new MailerService($this->db))->enviarConfirmacionReserva($reserva);

        Flash::set('exito', 'Reserva confirmada y correo enviado al cliente.');
        $this->redirect('/admin/reservas/' . $id);
    }

    public function cancelar(int $id): void
    {
        $this->verifyCsrf();

        $service = new ReservaService($this->db);
        if (!$service->cancelar($id)) {
            Flash::set('error', 'La reserva no se pudo cancelar.');
            $this->redirect('/admin/reservas/' . $id);
        }

        Flash::set('exito', 'Reserva cancelada y cupo liberado.');
        $this->redirect('/admin/reservas/' . $id);
    }
}
