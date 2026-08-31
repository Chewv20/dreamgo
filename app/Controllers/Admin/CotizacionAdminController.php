<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Auditoria;
use App\Helpers\Flash;
use App\Helpers\Validator;
use App\Models\Cotizacion;
use App\Models\CotizacionNota;
use App\Models\Usuario;
use Core\Response;

class CotizacionAdminController extends AdminController
{
    private const ESTADOS = ['nueva', 'contactada', 'convertida', 'descartada'];

    public function index(): void
    {
        $filtros = [
            'origen' => $this->filtroQuery('origen'),
            'asignado' => $this->filtroQuery('asignado'),
            'seguimiento' => $this->request->query('seguimiento') === 'vencidos' ? 'vencidos' : null,
        ];

        $paginador = $this->paginar(Cotizacion::contarTotal($filtros));

        $this->view('admin/cotizaciones/index', [
            'cotizaciones' => Cotizacion::adminListado($paginador->porPagina, $paginador->offset(), $filtros),
            'paginador' => $paginador,
            'fuentes' => Cotizacion::fuentesDistintas(),
            'asesores' => Usuario::conRol(),
            'filtros' => $filtros,
        ], ['title' => 'Cotizaciones | Dream Go', 'heading' => 'Cotizaciones']);
    }

    public function detalle(int $id): void
    {
        $cotizacion = Cotizacion::conDetalle($id);
        if (!$cotizacion) {
            $this->abort(404);
        }

        $this->view('admin/cotizaciones/detalle', [
            'cotizacion' => $cotizacion,
            'notas' => CotizacionNota::porCotizacion($id),
            'asesores' => Usuario::conRol(),
            'estados' => self::ESTADOS,
        ], ['title' => 'Cotizacion de ' . $cotizacion['nombre'] . ' | Dream Go', 'heading' => 'Cotizacion de ' . $cotizacion['nombre']]);
    }

    public function exportarCsv(): void
    {
        $encabezados = [
            'Fecha', 'Nombre', 'Email', 'Telefono', 'Paquete', 'Personas', 'Fecha tentativa', 'Mensaje', 'Estado',
            'Asignado a', 'Seguimiento',
            'UTM source', 'UTM medium', 'UTM campaign', 'UTM term', 'UTM content', 'Referrer', 'Landing page',
        ];

        $filas = array_map(static fn (array $c): array => [
            $c['creado_en'],
            $c['nombre'],
            $c['email'],
            $c['telefono'],
            $c['paquete_titulo'] ?? 'General',
            $c['num_personas'],
            $c['fecha_tentativa'] ?? '',
            $c['mensaje'],
            $c['estado'],
            $c['asignado_nombre'] ?? '',
            $c['seguimiento_en'] ?? '',
            $c['utm_source'] ?? '',
            $c['utm_medium'] ?? '',
            $c['utm_campaign'] ?? '',
            $c['utm_term'] ?? '',
            $c['utm_content'] ?? '',
            $c['referrer'] ?? '',
            $c['landing_page'] ?? '',
        ], Cotizacion::todasAdmin());

        Response::csv('cotizaciones_' . date('Y-m-d') . '.csv', $encabezados, $filas);
    }

    public function cambiarEstado(int $id): void
    {
        $this->verifyCsrf();

        $cotizacion = Cotizacion::find($id);
        if (!$cotizacion) {
            $this->abort(404);
        }

        $estado = (string) $this->request->input('estado');
        if (!in_array($estado, self::ESTADOS, true)) {
            $this->abort(404);
        }

        Cotizacion::update($id, ['estado' => $estado]);
        Auditoria::registrar('cotizacion.estado', 'cotizacion', $id, ($cotizacion['estado'] ?? '?') . ' -> ' . $estado);
        Flash::set('exito', 'Estado actualizado.');
        $this->volver($id);
    }

    public function asignar(int $id): void
    {
        $this->verifyCsrf();

        $cotizacion = Cotizacion::find($id);
        if (!$cotizacion) {
            $this->abort(404);
        }

        $valor = trim((string) $this->request->input('asignado_a', ''));
        $asesor = null;

        if ($valor !== '') {
            if (!ctype_digit($valor) || !Usuario::find((int) $valor)) {
                Flash::set('error', 'El asesor seleccionado no es valido.');
                $this->redirect('/admin/cotizaciones/' . $id);
            }
            $asesor = (int) $valor;
        }

        Cotizacion::update($id, ['asignado_a' => $asesor]);
        Auditoria::registrar('cotizacion.asignar', 'cotizacion', $id, $asesor !== null ? 'asesor #' . $asesor : 'sin asignar');
        Flash::set('exito', 'Asignacion actualizada.');
        $this->redirect('/admin/cotizaciones/' . $id);
    }

    public function seguimiento(int $id): void
    {
        $this->verifyCsrf();

        if (!Cotizacion::find($id)) {
            $this->abort(404);
        }

        $valor = trim((string) $this->request->input('seguimiento_en', ''));

        // Validator::fecha() rechaza formatos distintos y fechas imposibles que strtotime()
        // aceptaria desbordando (2026-02-30 -> 2 de marzo). Valor vacio = limpiar la fecha.
        $validador = new Validator(['seguimiento_en' => $valor]);
        $validador->fecha('seguimiento_en', 'La fecha de seguimiento');
        if (!$validador->pasa()) {
            Flash::set('error', 'La fecha de seguimiento no es valida.');
            $this->redirect('/admin/cotizaciones/' . $id);
        }

        Cotizacion::update($id, ['seguimiento_en' => $valor !== '' ? $valor : null]);
        Auditoria::registrar('cotizacion.seguimiento', 'cotizacion', $id, $valor !== '' ? 'fecha ' . $valor : 'sin fecha');
        Flash::set('exito', 'Fecha de seguimiento actualizada.');
        $this->redirect('/admin/cotizaciones/' . $id);
    }

    public function agregarNota(int $id): void
    {
        $this->verifyCsrf();

        if (!Cotizacion::find($id)) {
            $this->abort(404);
        }

        $nota = trim((string) $this->request->input('nota', ''));
        if ($nota === '') {
            Flash::set('error', 'La nota no puede estar vacia.');
            $this->redirect('/admin/cotizaciones/' . $id);
        }

        CotizacionNota::agregar($id, mb_substr($nota, 0, 2000));
        Auditoria::registrar('cotizacion.nota', 'cotizacion', $id);
        Flash::set('exito', 'Nota agregada.');
        $this->redirect('/admin/cotizaciones/' . $id);
    }

    private function filtroQuery(string $clave): ?string
    {
        $valor = trim((string) $this->request->query($clave, ''));

        return $valor !== '' ? $valor : null;
    }

    private function volver(int $id): void
    {
        if ($this->request->input('volver') === 'detalle') {
            $this->redirect('/admin/cotizaciones/' . $id);
        }

        $this->redirect('/admin/cotizaciones');
    }
}
