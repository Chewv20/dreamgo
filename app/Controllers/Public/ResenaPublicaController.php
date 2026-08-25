<?php

namespace App\Controllers\Public;

use App\Helpers\Validator;
use App\Models\Resena;
use App\Models\Reserva;
use Core\Controller;

class ResenaPublicaController extends Controller
{
    public function formulario(string $codigo): void
    {
        $this->view('public/resena/formulario', [
            'codigo' => strtoupper(trim($codigo)),
            'enviado' => false,
            'yaExistia' => false,
            'errores' => [],
            'valores' => ['email' => '', 'calificacion' => '', 'comentario' => ''],
        ], ['title' => 'Deja tu resena | Dream Go Operadora Turistica']);
    }

    public function guardar(string $codigo): void
    {
        $this->verifyCsrf();

        $codigo = strtoupper(trim($codigo));
        $valores = $this->request->only(['email', 'calificacion', 'comentario']);
        $valores['email'] = trim((string) ($valores['email'] ?? ''));

        $validator = new Validator($valores);
        $validator->requerido('email', 'El correo')->email('email', 'El correo')
            ->requerido('calificacion', 'La calificacion')->entero('calificacion', 'La calificacion')->enRango('calificacion', 1, 5, 'La calificacion')
            ->requerido('comentario', 'El comentario')->maxLength('comentario', 1000, 'El comentario');

        if (!$validator->pasa()) {
            $this->mostrarError($codigo, $valores, $validator->errores());

            return;
        }

        $reserva = Reserva::porCodigoYEmail($codigo, $valores['email']);

        if (!$reserva) {
            $this->mostrarError($codigo, $valores, ['general' => 'No encontramos una reserva con ese codigo y ese correo.']);

            return;
        }

        if ($reserva['estado'] !== 'confirmada') {
            $this->mostrarError($codigo, $valores, ['general' => 'Solo se pueden dejar resenas de reservas confirmadas.']);

            return;
        }

        $finViaje = $reserva['fecha_regreso'] ?? $reserva['fecha_salida'];
        if (strtotime($finViaje) > strtotime('today')) {
            $this->mostrarError($codigo, $valores, ['general' => 'Podras dejar tu resena despues de completar el viaje.']);

            return;
        }

        if (Resena::existePara((int) $reserva['id'])) {
            $this->view('public/resena/formulario', [
                'codigo' => $codigo,
                'enviado' => true,
                'yaExistia' => true,
                'errores' => [],
                'valores' => $valores,
            ], ['title' => 'Deja tu resena | Dream Go Operadora Turistica']);

            return;
        }

        Resena::insert([
            'reserva_id' => $reserva['id'],
            'cliente_id' => $reserva['cliente_id'],
            'paquete_id' => $reserva['paquete_id'],
            'calificacion' => (int) $valores['calificacion'],
            'comentario' => trim($valores['comentario']),
            'estado' => 'pendiente',
        ]);

        $this->view('public/resena/formulario', [
            'codigo' => $codigo,
            'enviado' => true,
            'yaExistia' => false,
            'errores' => [],
            'valores' => $valores,
        ], ['title' => 'Deja tu resena | Dream Go Operadora Turistica']);
    }

    private function mostrarError(string $codigo, array $valores, array $errores): void
    {
        $this->view('public/resena/formulario', [
            'codigo' => $codigo,
            'enviado' => false,
            'yaExistia' => false,
            'errores' => $errores,
            'valores' => $valores,
        ], ['title' => 'Deja tu resena | Dream Go Operadora Turistica']);
    }
}
