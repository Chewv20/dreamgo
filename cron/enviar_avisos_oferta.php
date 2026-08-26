<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

use App\Models\OfertaEnvioCola;
use App\Services\MailerService;
use Core\Database;

// Auditoria 2026-08-25, hallazgo CFG-02: procesa en lotes lo que OfertaAdminController::
// enviarSuscriptores() encola, en vez de mandar todo dentro de una sola request admin.
const LOTE = 50;

$db = Database::connection();
$mailer = new MailerService($db);

$lote = OfertaEnvioCola::loteConDetalle(LOTE);
$enviados = 0;
$fallidos = 0;

foreach ($lote as $fila) {
    $suscriptor = ['id' => $fila['suscriptor_id'], 'email' => $fila['email'], 'token' => $fila['token']];
    $oferta = [
        'id' => $fila['oferta_id'],
        'codigo' => $fila['codigo'],
        'tipo' => $fila['tipo'],
        'valor' => $fila['valor'],
        'fecha_fin' => $fila['fecha_fin'],
    ];

    if ($mailer->enviarAvisoOferta($suscriptor, $oferta)) {
        $enviados++;
    } else {
        $fallidos++;
    }

    // Se saca de la cola aunque el envio haya fallado (MailerService ya lo registra como
    // fallido en log_correos_enviados): un problema de SMTP persistente no debe dejar la
    // cola creciendo para siempre ni reintentando en loop en cada corrida del cron.
    OfertaEnvioCola::delete((int) $fila['cola_id']);
}

cron_log(
    'enviar_avisos_oferta',
    count($lote) . " fila(s) procesada(s) de la cola ({$enviados} enviado(s), {$fallidos} fallido(s))."
);
