<?php

namespace App\Controllers\Public;

use App\Helpers\Validator;
use App\Models\Reserva;
use Core\Controller;

class ReservaConsultaController extends Controller
{
    public function mostrar(): void
    {
        $this->view('public/reserva-consulta/formulario', [
            'buscado' => false,
            'reserva' => null,
            'errores' => [],
            'valores' => ['codigo' => '', 'email' => ''],
        ], [
            'title' => 'Mi reserva | Dream Go Operadora Turistica',
            'description' => 'Consulta el estado de tu reserva con tu codigo y correo.',
        ]);
    }

    public function buscar(): void
    {
        $this->verifyCsrf();

        $valores = $this->request->only(['codigo', 'email']);
        $valores['codigo'] = strtoupper(trim((string) ($valores['codigo'] ?? '')));
        $valores['email'] = trim((string) ($valores['email'] ?? ''));

        $validator = new Validator($valores);
        $validator->requerido('codigo', 'El codigo de reserva')->maxLength('codigo', 20, 'El codigo de reserva')
            ->requerido('email', 'El correo')->email('email', 'El correo');

        if (!$validator->pasa()) {
            $this->view('public/reserva-consulta/formulario', [
                'buscado' => false,
                'reserva' => null,
                'errores' => $validator->errores(),
                'valores' => $valores,
            ], ['title' => 'Mi reserva | Dream Go Operadora Turistica']);

            return;
        }

        $reserva = Reserva::porCodigoYEmail($valores['codigo'], $valores['email']);

        $this->view('public/reserva-consulta/formulario', [
            'buscado' => true,
            'reserva' => $reserva ?: null,
            'errores' => [],
            'valores' => $valores,
        ], ['title' => 'Mi reserva | Dream Go Operadora Turistica']);
    }
}
