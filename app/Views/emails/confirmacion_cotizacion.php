<?php
/** @var array $cotizacion */
/** @var bool $esContacto */
/** @var string $urlWhatsapp */
?>
<h2 style="margin-top:0;color:#2f3e46;">¡Hola, <?= htmlspecialchars($cotizacion['nombre'], ENT_QUOTES, 'UTF-8') ?>!</h2>
<?php if ($esContacto): ?>
  <p>Recibimos tu mensaje. Un asesor de Dream Go te contactará muy pronto.</p>
<?php else: ?>
  <p>Recibimos tu solicitud de cotización. Un asesor de Dream Go la está revisando y te contactará muy pronto con una propuesta personalizada.</p>
  <p style="font-weight:bold;margin-bottom:4px;">Resumen de tu solicitud</p>
  <table role="presentation" width="100%" cellpadding="6" style="border-collapse:collapse;">
    <tr><td style="font-weight:bold;width:140px;">Paquete</td><td><?= htmlspecialchars($cotizacion['paquete_titulo'] ?? 'Cotización general', ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><td style="font-weight:bold;">Personas</td><td><?= htmlspecialchars((string) ($cotizacion['num_personas'] ?? 'Por definir'), ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><td style="font-weight:bold;">Fecha tentativa</td><td><?= !empty($cotizacion['fecha_tentativa']) ? date('d/m/Y', strtotime($cotizacion['fecha_tentativa'])) : 'Por definir' ?></td></tr>
  </table>
<?php endif; ?>
<?php if (!empty($cotizacion['mensaje'])): ?>
  <p style="font-weight:bold;margin-bottom:4px;">Tu mensaje</p>
  <p><?= nl2br(htmlspecialchars($cotizacion['mensaje'], ENT_QUOTES, 'UTF-8')) ?></p>
<?php endif; ?>
<?php if ($urlWhatsapp !== ''): ?>
  <p>Si quieres avanzar más rápido, escríbenos por WhatsApp:</p>
  <p style="text-align:center;margin:2rem 0;">
    <a href="<?= htmlspecialchars($urlWhatsapp, ENT_QUOTES, 'UTF-8') ?>" style="background:#25d366;color:#ffffff;padding:0.75rem 1.5rem;border-radius:6px;text-decoration:none;font-weight:bold;display:inline-block;">Escribir por WhatsApp</a>
  </p>
<?php endif; ?>
<p>Si tú no enviaste esta solicitud, puedes ignorar este correo.</p>
