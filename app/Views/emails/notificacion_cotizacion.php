<?php /** @var array $cotizacion */ ?>
<h2 style="margin-top:0;color:#2f3e46;">Nueva solicitud de cotización</h2>
<p>Se recibió una nueva solicitud desde el sitio web:</p>
<table role="presentation" width="100%" cellpadding="6" style="border-collapse:collapse;">
  <tr><td style="font-weight:bold;width:140px;">Nombre</td><td><?= htmlspecialchars($cotizacion['nombre'], ENT_QUOTES, 'UTF-8') ?></td></tr>
  <tr><td style="font-weight:bold;">Email</td><td><?= htmlspecialchars($cotizacion['email'], ENT_QUOTES, 'UTF-8') ?></td></tr>
  <tr><td style="font-weight:bold;">Teléfono</td><td><?= htmlspecialchars($cotizacion['telefono'], ENT_QUOTES, 'UTF-8') ?></td></tr>
  <tr><td style="font-weight:bold;">Personas</td><td><?= htmlspecialchars((string) ($cotizacion['num_personas'] ?? 'No especificado'), ENT_QUOTES, 'UTF-8') ?></td></tr>
  <tr><td style="font-weight:bold;">Fecha tentativa</td><td><?= htmlspecialchars($cotizacion['fecha_tentativa'] ?? 'No especificada', ENT_QUOTES, 'UTF-8') ?></td></tr>
  <tr><td style="font-weight:bold;">Paquete</td><td><?= htmlspecialchars($cotizacion['paquete_titulo'] ?? 'Cotización general', ENT_QUOTES, 'UTF-8') ?></td></tr>
</table>
<?php if (!empty($cotizacion['mensaje'])): ?>
  <p style="font-weight:bold;margin-bottom:4px;">Mensaje</p>
  <p><?= nl2br(htmlspecialchars($cotizacion['mensaje'], ENT_QUOTES, 'UTF-8')) ?></p>
<?php endif; ?>
