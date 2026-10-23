<?php
/** @var array $cotizacion */
/** @var bool $esContacto */
/** @var string $urlAdmin */
?>
<h2 style="margin-top:0;color:#2f3e46;"><?= $esContacto ? 'Nuevo mensaje de contacto' : 'Nueva solicitud de cotización' ?></h2>
<p>Se recibió una nueva solicitud desde <?= $esContacto ? 'el formulario de contacto' : 'el cotizador' ?> del sitio web:</p>
<table role="presentation" width="100%" cellpadding="6" style="border-collapse:collapse;">
  <tr><td style="font-weight:bold;width:140px;">Nombre</td><td><?= htmlspecialchars($cotizacion['nombre'], ENT_QUOTES, 'UTF-8') ?></td></tr>
  <tr><td style="font-weight:bold;">Email</td><td><?= htmlspecialchars($cotizacion['email'], ENT_QUOTES, 'UTF-8') ?></td></tr>
  <tr><td style="font-weight:bold;">Teléfono</td><td><?= htmlspecialchars($cotizacion['telefono'], ENT_QUOTES, 'UTF-8') ?></td></tr>
  <?php if (!$esContacto): ?>
  <tr><td style="font-weight:bold;">Personas</td><td><?= htmlspecialchars((string) ($cotizacion['num_personas'] ?? 'No especificado'), ENT_QUOTES, 'UTF-8') ?></td></tr>
  <tr><td style="font-weight:bold;">Fecha tentativa</td><td><?= !empty($cotizacion['fecha_tentativa']) ? date('d/m/Y', strtotime($cotizacion['fecha_tentativa'])) : 'No especificada' ?></td></tr>
  <tr><td style="font-weight:bold;">Paquete</td><td><?= htmlspecialchars($cotizacion['paquete_titulo'] ?? 'Cotización general', ENT_QUOTES, 'UTF-8') ?></td></tr>
  <?php endif; ?>
</table>
<?php if (!empty($cotizacion['mensaje'])): ?>
  <p style="font-weight:bold;margin-bottom:4px;">Mensaje</p>
  <p><?= nl2br(htmlspecialchars($cotizacion['mensaje'], ENT_QUOTES, 'UTF-8')) ?></p>
<?php endif; ?>
<p style="text-align:center;margin:2rem 0 0;">
  <a href="<?= htmlspecialchars($urlAdmin, ENT_QUOTES, 'UTF-8') ?>" style="background:#a85f4d;color:#ffffff;padding:0.75rem 1.5rem;border-radius:6px;text-decoration:none;font-weight:bold;display:inline-block;">Ver en el panel</a>
</p>
