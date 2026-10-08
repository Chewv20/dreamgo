<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Auditoria;
use App\Helpers\Flash;
use App\Helpers\Validator;
use App\Models\PagoReserva;
use App\Models\Reserva;
use App\Models\Salida;
use App\Services\ComprobanteReservaService;
use App\Services\MailerService;
use App\Services\ReservaService;
use Core\Response;
use PDOException;
use RuntimeException;

class ReservaAdminController extends AdminController
{
    public function index(): void
    {
        $filtros = [
            'q' => trim((string) $this->request->query('q', '')),
            'estado' => (string) $this->request->query('estado', ''),
            'salida' => (string) $this->request->query('salida', ''),
        ];
        $paginador = $this->paginar(Reserva::contarTotal($filtros));

        $this->view('admin/reservas/index', [
            'reservas' => Reserva::adminListado($paginador->porPagina, $paginador->offset(), $filtros),
            'paginador' => $paginador,
            'filtros' => $filtros,
        ], ['title' => 'Reservas | Dream Go', 'heading' => 'Reservas']);
    }

    public function exportarCsv(): void
    {
        $encabezados = ['Codigo', 'Cliente', 'Email', 'Telefono', 'Paquete', 'Fecha salida', 'Personas', 'Precio total', 'Moneda', 'Monto pagado', 'Estado', 'Creado en', 'Confirmada en'];

        $filas = array_map(static fn (array $r): array => [
            $r['codigo_reserva'],
            $r['cliente_nombre'],
            $r['cliente_email'],
            $r['cliente_telefono'],
            $r['paquete_titulo'],
            $r['fecha_salida'],
            $r['num_personas'],
            $r['precio_total'],
            $r['paquete_moneda'],
            $r['monto_pagado'],
            $r['estado'],
            $r['creado_en'],
            $r['confirmada_en'] ?? '',
        ], Reserva::todasAdmin());

        Response::csv('reservas_' . date('Y-m-d') . '.csv', $encabezados, $filas);
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
            'pagos' => PagoReserva::historialDeReserva($id),
        ], ['title' => 'Reserva ' . $reserva['codigo_reserva'] . ' | Dream Go', 'heading' => 'Reserva ' . $reserva['codigo_reserva']]);
    }

    public function comprobante(int $id): void
    {
        $reserva = Reserva::conDetalle($id);
        if (!$reserva) {
            $this->abort(404);
        }

        $servicio = new ComprobanteReservaService();

        Response::archivo(
            $servicio->nombreArchivo($reserva),
            $servicio->generarPdf($reserva),
            'application/pdf'
        );
    }

    public function crearForm(): void
    {
        $salidaId = (int) $this->request->query('salida_id', 0);
        $salida = $salidaId ? Salida::find($salidaId) : null;

        // Sin salida elegida (p. ej. desde el botón del listado): primero se elige la fecha.
        if (!$salida) {
            $this->view('admin/reservas/elegir_salida', [
                'salidas' => Salida::abiertasParaReservar(),
            ], ['title' => 'Nueva reserva | Dream Go', 'heading' => 'Nueva reserva']);

            return;
        }

        $this->view('admin/reservas/create', [
            'salida' => $salida,
            'paquete' => \App\Models\Paquete::find((int) $salida['paquete_id']),
        ], ['title' => 'Nueva reserva | Dream Go', 'heading' => 'Nueva reserva']);
    }

    public function crear(): void
    {
        $this->verifyCsrf();

        $datos = $this->request->only(['salida_id', 'nombre', 'email', 'telefono', 'num_personas', 'codigo_descuento']);

        $validator = new Validator($datos);
        $validator->requerido('salida_id', 'La fecha de salida')
            ->requerido('nombre', 'El nombre')->maxLength('nombre', 150, 'El nombre')
            ->requerido('email', 'El correo')->email('email', 'El correo')
            ->requerido('telefono', 'El teléfono')->telefono('telefono', 'El teléfono')
            ->requerido('num_personas', 'El número de personas')->entero('num_personas', 'El número de personas')
            ->enRango('num_personas', 1, 30, 'El número de personas');

        $this->redirigirSiInvalido($validator, '/admin/reservas/crear?salida_id=' . (int) $datos['salida_id']);

        $service = new ReservaService($this->db);

        try {
            $reserva = $service->crearYNotificar([
                'salida_id' => (int) $datos['salida_id'],
                'nombre' => $datos['nombre'],
                'email' => $datos['email'],
                'telefono' => $datos['telefono'],
                'num_personas' => (int) $datos['num_personas'],
                'codigo_descuento' => $datos['codigo_descuento'] ?: null,
            ]);
        } catch (PDOException $e) {
            error_log('[ReservaAdminController] Error de base de datos al crear reserva: ' . $e->getMessage());
            Flash::set('error', 'Ocurrió un error al procesar la reserva. Intenta de nuevo.');
            $this->redirect('/admin/reservas/crear?salida_id=' . (int) $datos['salida_id']);
        } catch (RuntimeException $e) {
            Flash::set('error', $e->getMessage());
            $this->redirect('/admin/reservas/crear?salida_id=' . (int) $datos['salida_id']);
        }

        Auditoria::registrar('reserva.crear', 'reserva', (int) $reserva['id'], 'Codigo ' . $reserva['codigo_reserva'] . ', ' . (int) $datos['num_personas'] . ' persona(s)');

        Flash::set('exito', 'Reserva ' . $reserva['codigo_reserva'] . ' creada. El cupo ya quedó apartado.');
        $this->redirect('/admin/reservas/' . $reserva['id']);
    }

    public function confirmar(int $id): void
    {
        $this->verifyCsrf();

        $service = new ReservaService($this->db);
        if (!$service->confirmar($id)) {
            Flash::set('error', 'La reserva ya no está pendiente.');
            $this->redirect('/admin/reservas/' . $id);
        }

        $reserva = Reserva::conDetalle($id);
        (new MailerService($this->db))->enviarConfirmacionReserva($reserva);

        Auditoria::registrar('reserva.confirmar', 'reserva', $id, 'Codigo ' . ($reserva['codigo_reserva'] ?? ''));

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

        Auditoria::registrar('reserva.cancelar', 'reserva', $id);

        Flash::set('exito', 'Reserva cancelada y cupo liberado.');
        $this->redirect('/admin/reservas/' . $id);
    }
}
