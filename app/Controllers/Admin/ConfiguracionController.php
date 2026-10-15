<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Auditoria;
use App\Helpers\Flash;
use App\Helpers\Url;
use App\Models\ConfiguracionSitio;

class ConfiguracionController extends AdminController
{
    /** Claves cuyo valor se renderiza como href en el footer: se validan con Url::segura. */
    private const CLAVES_URL = ['facebook_url', 'instagram_url', 'tiktok_url', 'youtube_url'];

    /** Claves que se muestran en /aviso-de-privacidad: al cambiar, se fecha la actualización del aviso. */
    private const CLAVES_AVISO_PRIVACIDAD = ['razon_social', 'direccion', 'email_contacto', 'telefono_contacto'];

    private const CLAVES_EDITABLES = [
        'razon_social',
        'direccion',
        'telefono_contacto',
        'email_contacto',
        'whatsapp_numero',
        'facebook_url',
        'instagram_url',
        'tiktok_url',
        'youtube_url',
        'horas_expiracion_reserva',
        'porcentaje_anticipo_reserva',
        'abono_minimo_saldo',
        'dias_recordatorio_viaje',
        'dias_recordatorio_saldo',
        'email_equipo_reportes',
        'meta_title_default',
        'meta_description_default',
    ];

    public function index(): void
    {
        $valores = [];
        foreach (self::CLAVES_EDITABLES as $clave) {
            $valores[$clave] = ConfiguracionSitio::get($clave, '');
        }

        $this->view('admin/configuracion/index', [
            'valores' => $valores,
        ], ['title' => 'Configuración | Dream Go', 'heading' => 'Configuración del sitio']);
    }

    public function guardar(): void
    {
        $this->verifyCsrf();

        $cambiadas = [];
        foreach (self::CLAVES_EDITABLES as $clave) {
            $valor = (string) $this->request->input($clave, '');
            if (in_array($clave, self::CLAVES_URL, true)) {
                $valor = Url::segura($valor);
            }
            if ($clave === 'abono_minimo_saldo') {
                // Monto en dinero: no negativo, dos decimales; vacío o inválido vuelve al default.
                $valor = is_numeric($valor) && (float) $valor >= 0 ? (string) round((float) $valor, 2) : '500';
            }
            if ($valor !== (string) ConfiguracionSitio::get($clave, '')) {
                $cambiadas[] = $clave;
            }
            ConfiguracionSitio::set($clave, $valor);
        }

        if (array_intersect($cambiadas, self::CLAVES_AVISO_PRIVACIDAD) !== []) {
            ConfiguracionSitio::set('aviso_privacidad_actualizado', date('Y-m-d'));
        }

        if ($cambiadas !== []) {
            Auditoria::registrar('configuracion.guardar', 'configuracion', null, 'Claves: ' . implode(', ', $cambiadas));
        }

        Flash::set('exito', 'Configuración actualizada correctamente.');
        $this->redirect('/admin/configuracion');
    }
}
