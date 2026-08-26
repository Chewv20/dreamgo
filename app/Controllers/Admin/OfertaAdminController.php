<?php

namespace App\Controllers\Admin;

use App\Helpers\Flash;
use App\Helpers\Validator;
use App\Models\CodigoDescuento;
use App\Models\OfertaEnvioCola;
use App\Models\Paquete;

class OfertaAdminController extends AdminController
{
    public function index(): void
    {
        $this->view('admin/ofertas/index', [
            'ofertas' => CodigoDescuento::adminListado(),
        ], ['title' => 'Ofertas | Dream Go', 'heading' => 'Ofertas y codigos de descuento']);
    }

    public function crearForm(): void
    {
        $this->view('admin/ofertas/create', [
            'paquetes' => Paquete::all('titulo ASC'),
        ], ['title' => 'Nueva oferta | Dream Go', 'heading' => 'Nuevo codigo de descuento']);
    }

    public function crear(): void
    {
        $this->verifyCsrf();

        $datos = $this->datosFormulario();

        if (!$this->validar($datos)) {
            $this->redirect('/admin/ofertas/crear');
        }

        if (CodigoDescuento::porCodigo($datos['codigo'])) {
            Flash::set('error', 'Ya existe un codigo con ese nombre.');
            $this->redirect('/admin/ofertas/crear');
        }

        CodigoDescuento::insert($datos);

        Flash::set('exito', 'Codigo de descuento creado.');
        $this->redirect('/admin/ofertas');
    }

    public function editarForm(int $id): void
    {
        $oferta = $this->encontrarO404(CodigoDescuento::class, $id);

        $this->view('admin/ofertas/edit', [
            'oferta' => $oferta,
            'paquetes' => Paquete::all('titulo ASC'),
        ], ['title' => 'Editar oferta | Dream Go', 'heading' => 'Editar codigo de descuento']);
    }

    public function editar(int $id): void
    {
        $this->verifyCsrf();

        $this->encontrarO404(CodigoDescuento::class, $id);

        $datos = $this->datosFormulario();

        if (!$this->validar($datos)) {
            $this->redirect("/admin/ofertas/{$id}/editar");
        }

        $existente = CodigoDescuento::porCodigo($datos['codigo']);
        if ($existente && (int) $existente['id'] !== $id) {
            Flash::set('error', 'Ya existe otro codigo con ese nombre.');
            $this->redirect("/admin/ofertas/{$id}/editar");
        }

        CodigoDescuento::update($id, $datos);

        Flash::set('exito', 'Codigo de descuento actualizado.');
        $this->redirect('/admin/ofertas');
    }

    public function desactivar(int $id): void
    {
        $this->verifyCsrf();

        $this->encontrarO404(CodigoDescuento::class, $id);

        CodigoDescuento::update($id, ['activo' => 0]);
        Flash::set('exito', 'Codigo desactivado.');
        $this->redirect('/admin/ofertas');
    }

    /**
     * Auditoria 2026-08-25, hallazgo CFG-02: antes esto mandaba el correo a cada suscriptor
     * uno por uno dentro de esta misma request admin (riesgo real de agotar
     * max_execution_time a mitad del envio con una lista mediana, sin que el admin se
     * enterara de que se corto). Ahora solo encola (INSERT rapido); cron/enviar_avisos_oferta.php
     * procesa la cola en lotes.
     */
    public function enviarSuscriptores(int $id): void
    {
        $this->verifyCsrf();

        $this->encontrarO404(CodigoDescuento::class, $id);

        $encolados = OfertaEnvioCola::encolarParaOferta($id);

        Flash::set(
            'exito',
            $encolados > 0
                ? "Se encolo el envio a {$encolados} suscriptor(es); se enviaran en los proximos minutos."
                : 'No hay suscriptores nuevos para avisar (ya se les habia encolado este aviso, o no hay suscriptores confirmados).'
        );
        $this->redirect('/admin/ofertas');
    }

    private function datosFormulario(): array
    {
        $alcance = $this->request->input('alcance', 'global');

        return [
            'codigo' => strtoupper(trim((string) $this->request->input('codigo', ''))),
            'tipo' => $this->request->input('tipo', 'porcentaje'),
            'valor' => $this->request->input('valor'),
            'alcance' => $alcance,
            'paquete_id' => $alcance === 'paquete' ? (int) $this->request->input('paquete_id') : null,
            'fecha_inicio' => $this->request->input('fecha_inicio'),
            'fecha_fin' => $this->request->input('fecha_fin'),
            'uso_maximo' => $this->usoMaximoDesdeInput(),
            'activo' => $this->request->input('activo') ? 1 : 0,
        ];
    }

    private function usoMaximoDesdeInput(): ?int
    {
        $valor = $this->request->input('uso_maximo');

        return ($valor === null || $valor === '') ? null : (int) $valor;
    }

    private function validar(array $datos): bool
    {
        $validator = new Validator($datos);
        $validator->requerido('codigo', 'El codigo')->maxLength('codigo', 40, 'El codigo')
            ->requerido('valor', 'El valor')
            ->requerido('fecha_inicio', 'La fecha de inicio')
            ->requerido('fecha_fin', 'La fecha de fin');

        if (!$validator->pasa()) {
            Flash::set('error', 'Revisa los datos del formulario.');

            return false;
        }

        if (!is_numeric($datos['valor']) || (float) $datos['valor'] <= 0) {
            Flash::set('error', 'El valor del descuento debe ser un numero mayor a cero.');

            return false;
        }

        if ($datos['tipo'] === 'porcentaje' && (float) $datos['valor'] > 100) {
            Flash::set('error', 'Un descuento porcentual no puede ser mayor a 100.');

            return false;
        }

        if ($datos['fecha_fin'] < $datos['fecha_inicio']) {
            Flash::set('error', 'La fecha de fin no puede ser anterior a la fecha de inicio.');

            return false;
        }

        if ($datos['alcance'] === 'paquete' && empty($datos['paquete_id'])) {
            Flash::set('error', 'Selecciona un paquete para un descuento de alcance especifico.');

            return false;
        }

        return true;
    }
}
