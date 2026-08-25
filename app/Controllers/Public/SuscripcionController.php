<?php

namespace App\Controllers\Public;

use App\Helpers\Flash;
use App\Helpers\Validator;
use App\Models\Suscriptor;
use App\Services\MailerService;
use Core\Controller;

class SuscripcionController extends Controller
{
    public function suscribir(): void
    {
        $this->verifyCsrf();

        $email = strtolower(trim((string) $this->request->input('email', '')));

        $validator = new Validator(['email' => $email]);
        $validator->requerido('email', 'El correo')->email('email', 'El correo');

        if (!$validator->pasa()) {
            Flash::set('error', 'Ingresa un correo valido.');
            $this->redirect('/#newsletter');
        }

        $existente = Suscriptor::porEmail($email);

        if ($existente && $existente['estado'] === 'confirmado') {
            Flash::set('exito', 'Este correo ya esta suscrito. ¡Gracias!');
            $this->redirect('/#newsletter');
        }

        $token = bin2hex(random_bytes(32));

        if ($existente) {
            Suscriptor::update($existente['id'], ['estado' => 'pendiente', 'token' => $token]);
            $suscriptor = Suscriptor::find($existente['id']);
        } else {
            $id = Suscriptor::insert([
                'email' => $email,
                'estado' => 'pendiente',
                'token' => $token,
                'ip_origen' => $this->request->ip(),
            ]);
            $suscriptor = Suscriptor::find($id);
        }

        (new MailerService($this->db))->enviarConfirmacionSuscripcion($suscriptor);

        Flash::set('exito', 'Revisa tu correo y confirma tu suscripcion.');
        $this->redirect('/#newsletter');
    }

    public function confirmar(string $token): void
    {
        $suscriptor = Suscriptor::porToken($token);

        if (!$suscriptor) {
            $this->abort(404);
        }

        if ($suscriptor['estado'] === 'pendiente') {
            Suscriptor::update($suscriptor['id'], ['estado' => 'confirmado', 'confirmado_en' => date('Y-m-d H:i:s')]);
        }

        $this->view('public/suscripcion/confirmado', [], ['title' => 'Suscripcion confirmada | Dream Go Operadora Turistica']);
    }

    public function baja(string $token): void
    {
        $suscriptor = Suscriptor::porToken($token);

        if (!$suscriptor) {
            $this->abort(404);
        }

        if ($suscriptor['estado'] !== 'baja') {
            Suscriptor::update($suscriptor['id'], ['estado' => 'baja', 'baja_en' => date('Y-m-d H:i:s')]);
        }

        $this->view('public/suscripcion/baja', [], ['title' => 'Suscripcion cancelada | Dream Go Operadora Turistica']);
    }
}
