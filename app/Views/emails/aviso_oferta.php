<?php
/** @var array $oferta */
/** @var string $urlBaja */
$valorTexto = $oferta['tipo'] === 'porcentaje'
    ? ((float) $oferta['valor'] . '%')
    : ('$' . number_format((float) $oferta['valor'], 2));
?>
<h2 style="margin-top:0;color:#2f3e46;">Nueva oferta disponible</h2>
<p>Tenemos una oferta nueva para ti:</p>
<table role="presentation" width="100%" cellpadding="6" style="border-collapse:collapse;">
  <tr><td style="font-weight:bold;width:160px;">Código</td><td><?= htmlspecialchars($oferta['codigo'], ENT_QUOTES, 'UTF-8') ?></td></tr>
  <tr><td style="font-weight:bold;">Descuento</td><td><?= htmlspecialchars($valorTexto, ENT_QUOTES, 'UTF-8') ?></td></tr>
  <tr><td style="font-weight:bold;">Válido hasta</td><td><?= date('d/m/Y', strtotime($oferta['fecha_fin'])) ?></td></tr>
</table>
<p style="text-align:center;margin:2rem 0;">
  <a href="<?= htmlspecialchars(rtrim($_ENV['APP_URL'] ?? '', '/') . '/paquetes', ENT_QUOTES, 'UTF-8') ?>" style="background:#a85f4d;color:#ffffff;padding:0.75rem 1.5rem;border-radius:6px;text-decoration:none;font-weight:bold;display:inline-block;">Ver paquetes</a>
</p>
<p style="font-size:12px;opacity:0.75;">Si ya no quieres recibir estos avisos, <a href="<?= htmlspecialchars($urlBaja, ENT_QUOTES, 'UTF-8') ?>">date de baja aquí</a>.</p>
