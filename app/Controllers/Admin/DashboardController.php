<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Fecha;
use App\Models\Cotizacion;
use App\Models\Reserva;
use App\Models\Salida;
use Core\Auth;

class DashboardController extends AdminController
{
    public function index(): void
    {
        $db = $this->db;

        [$desde, $hasta] = $this->rangoPeriodo();
        [$desdeAnt, $hastaAnt] = Fecha::periodoAnterior($desde, $hasta);

        $datos = [
            'totalPaquetes' => (int) $db->query('SELECT COUNT(*) FROM paquetes')->fetchColumn(),
            'totalPaquetesPublicados' => (int) $db->query("SELECT COUNT(*) FROM paquetes WHERE estado = 'publicado'")->fetchColumn(),
            'periodoDesde' => $desde,
            'periodoHasta' => $hasta,
            'atajos' => $this->atajosPeriodo(),
        ];

        if (Auth::hasPermission('cotizaciones.ver')) {
            $datos['totalCotizacionesNuevas'] = (int) $db->query("SELECT COUNT(*) FROM cotizaciones WHERE estado = 'nueva'")->fetchColumn();
            $datos['seguimientosVencidos'] = Cotizacion::seguimientosVencidos();
            $datos['conversion'] = Cotizacion::tasaConversionPeriodo($desde, $hasta);
            $datos['conversionAnterior'] = Cotizacion::tasaConversionPeriodo($desdeAnt, $hastaAnt);
            $datos['cotizacionesPorOrigen'] = Cotizacion::porOrigenPeriodo($desde, $hasta);
        }

        if (Auth::hasPermission('reservas.ver')) {
            $datos['totalReservasPendientes'] = (int) $db->query("SELECT COUNT(*) FROM reservas WHERE estado = 'pendiente'")->fetchColumn();
            $datos['saldosPorCobrar'] = Reserva::saldosPorCobrar();
            $datos['confirmadasPeriodo'] = Reserva::confirmadasPeriodo($desde, $hasta);
            $datos['confirmadasAnterior'] = Reserva::confirmadasPeriodo($desdeAnt, $hastaAnt);
            $datos['ingresosPeriodo'] = Reserva::ingresosPeriodo($desde, $hasta);
            $datos['ingresosAnterior'] = Reserva::ingresosPeriodo($desdeAnt, $hastaAnt);
            $datos['cobradoPeriodo'] = Reserva::cobradoPeriodo($desde, $hasta);
            $datos['cobradoAnterior'] = Reserva::cobradoPeriodo($desdeAnt, $hastaAnt);
            $datos['proximasSalidas'] = Salida::proximasConOcupacion();
        }

        $this->view('admin/dashboard/index', $datos, ['title' => 'Panel | Dream Go', 'heading' => 'Panel']);
    }

    /**
     * Rangos frecuentes para no tener que escribir fechas a mano.
     *
     * @return array<string, array{0: string, 1: string}> etiqueta => [desde, hasta]
     */
    private function atajosPeriodo(): array
    {
        return [
            'Este mes' => [date('Y-m-01'), date('Y-m-d')],
            'Mes pasado' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
            'Últimos 30 días' => [date('Y-m-d', strtotime('-29 days')), date('Y-m-d')],
            'Este año' => [date('Y-01-01'), date('Y-m-d')],
        ];
    }

    /**
     * Lee ?desde=YYYY-MM-DD&hasta=YYYY-MM-DD de la query string y valida el formato con
     * preg_match para no dejar pasar basura a las consultas (aunque van parametrizadas,
     * strtotime() con entrada arbitraria produce fechas absurdas silenciosamente). Si falta,
     * es invalido, o desde > hasta, cae al default: primer dia del mes actual hasta hoy.
     */
    private function rangoPeriodo(): array
    {
        $formato = '/^\d{4}-\d{2}-\d{2}$/';

        $desde = (string) $this->request->query('desde', '');
        $hasta = (string) $this->request->query('hasta', '');

        $desdeValido = preg_match($formato, $desde) === 1 && strtotime($desde) !== false;
        $hastaValido = preg_match($formato, $hasta) === 1 && strtotime($hasta) !== false;

        if (!$desdeValido || !$hastaValido || $desde > $hasta) {
            $desde = date('Y-m-01');
            $hasta = date('Y-m-d');
        }

        return [$desde, $hasta];
    }
}
