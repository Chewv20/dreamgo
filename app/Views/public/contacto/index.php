<?php /** @var string $whatsapp */ /** @var string $email */ /** @var array|null $intro */ ?>
<section class="seccion contenedor" style="max-width:640px;text-align:center;">
  <?php $introVisible = $intro && (int) $intro['visible'] === 1; ?>
  <h1><?= htmlspecialchars($introVisible && !empty($intro['titulo']) ? $intro['titulo'] : 'Contacto', ENT_QUOTES, 'UTF-8') ?></h1>
  <?php if ($introVisible && !empty($intro['subtitulo'])): ?>
    <p><?= htmlspecialchars($intro['subtitulo'], ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <div class="grid-tarjetas" style="margin-top:2rem;text-align:left;">
    <a href="<?= htmlspecialchars($whatsapp, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="tarjeta animar-entrada" style="display:block;padding:1.5rem;text-decoration:none;">
      <h3>WhatsApp</h3>
      <p style="margin:0;">Respuesta rapida para dudas y cotizaciones.</p>
    </a>
    <a href="mailto:<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" class="tarjeta animar-entrada" style="display:block;padding:1.5rem;text-decoration:none;">
      <h3>Correo</h3>
      <p style="margin:0;word-break:break-word;"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></p>
    </a>
    <a href="/cotizador" class="tarjeta animar-entrada" style="display:block;padding:1.5rem;text-decoration:none;">
      <h3>Formulario</h3>
      <p style="margin:0;">Cuentanos los detalles de tu viaje ideal.</p>
    </a>
  </div>
</section>
