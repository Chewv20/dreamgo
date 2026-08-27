<?php

namespace App\Services;

use App\Models\ConfiguracionSitio;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Genera el comprobante PDF de una reserva a partir del array de Reserva::conDetalle().
 * Render 100% server-side: la vista usa estilos inline propios, sin relacion con el CSS del
 * sitio ni con la CSP (dompdf no ejecuta JS ni pide recursos remotos -- isRemoteEnabled off).
 */
final class ComprobanteReservaService
{
    /**
     * @param array $reserva fila de Reserva::conDetalle() (r.*, cliente_*, paquete_titulo,
     *                        paquete_moneda, fecha_salida, fecha_regreso)
     * @return string bytes del PDF
     */
    public function generarPdf(array $reserva): string
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans'); // incluye acentos y ñ
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->renderHtml($reserva), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function nombreArchivo(array $reserva): string
    {
        $codigo = preg_replace('/[^A-Za-z0-9\-]/', '', (string) $reserva['codigo_reserva']);

        return 'comprobante-' . $codigo . '.pdf';
    }

    private function renderHtml(array $reserva): string
    {
        $agencia = [
            'direccion' => (string) ConfiguracionSitio::get('direccion', ''),
            'telefono' => (string) ConfiguracionSitio::get('telefono_contacto', ''),
            'email' => (string) ConfiguracionSitio::get('email_contacto', ''),
            'whatsapp' => (string) ConfiguracionSitio::get('whatsapp_numero', ''),
        ];

        extract(['reserva' => $reserva, 'agencia' => $agencia], EXTR_SKIP);
        ob_start();
        require BASE_PATH . '/app/Views/comprobantes/reserva.php';

        return ob_get_clean();
    }
}
