<?php

namespace App\Controllers\Admin;

use App\Helpers\Flash;
use App\Models\ConfiguracionSitio;

class ConfiguracionController extends AdminController
{
    private const CLAVES_EDITABLES = [
        'direccion',
        'telefono_contacto',
        'email_contacto',
        'whatsapp_numero',
        'facebook_url',
        'instagram_url',
        'tiktok_url',
        'youtube_url',
        'horas_expiracion_reserva',
        'dias_recordatorio_viaje',
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
        ], ['title' => 'Configuracion | Dream Go', 'heading' => 'Configuracion del sitio']);
    }

    public function guardar(): void
    {
        $this->verifyCsrf();

        foreach (self::CLAVES_EDITABLES as $clave) {
            $valor = (string) $this->request->input($clave, '');
            ConfiguracionSitio::set($clave, $valor);
        }

        Flash::set('exito', 'Configuracion actualizada correctamente.');
        $this->redirect('/admin/configuracion');
    }
}
