<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Helpers\Flash;
use App\Helpers\RateLimiter;
use App\Helpers\Validator;
use App\Models\Suscriptor;
use App\Services\MailerService;
use Core\Controller;
use PDOException;

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

        $ip = $this->request->ip();
        if (RateLimiter::demasiados('suscribir', null, $ip)) {
            $this->abort(429, 'Demasiados intentos. Espera unos minutos e intenta de nuevo.');
        }
        RateLimiter::registrar('suscribir', null, $ip);

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
            try {
                $id = Suscriptor::insert([
                    'email' => $email,
                    'estado' => 'pendiente',
                    'token' => $token,
                    'ip_origen' => $this->request->ip(),
                ]);
                $suscriptor = Suscriptor::find($id);
            } catch (PDOException $e) {
                // Ventana TOCTOU entre porEmail() y este INSERT (doble submit del mismo
                // correo nuevo): suscriptores.email UNIQUE ya protege la integridad, esto
                // solo evita un 500 y recupera la fila que gano la carrera, igual que
                // Cliente::encontrarOCrear() hace para el mismo tipo de condicion.
                if ($e->getCode() !== '23000') {
                    throw $e;
                }

                $ganador = Suscriptor::porEmail($email);
                if (!$ganador) {
                    throw $e;
                }

                Suscriptor::update($ganador['id'], ['estado' => 'pendiente', 'token' => $token]);
                $suscriptor = Suscriptor::find($ganador['id']);
            }
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
