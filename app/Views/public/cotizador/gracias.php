<?php /** @var string $whatsapp */ ?>
<section class="seccion contenedor ancho-560 centrado">
  <h1>¡Gracias por tu solicitud!</h1>
  <p>Recibimos tu informacion y nuestro equipo te contactara muy pronto con una propuesta personalizada.</p>
  <p>Si quieres avanzar mas rapido, escribenos directo por WhatsApp:</p>
  <a href="<?= htmlspecialchars($whatsapp, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-whatsapp" target="_blank" rel="noopener">Continuar por WhatsApp</a>
  <p class="mt-2"><a href="/paquetes">Ver mas paquetes</a></p>
</section>
<?php if (\App\Helpers\Analytics::habilitado()): ?>
<script nonce="<?= htmlspecialchars(CSP_NONCE, ENT_QUOTES, 'UTF-8') ?>">
  if (typeof gtag === 'function') { gtag('event', 'generate_lead'); }
  if (typeof fbq === 'function') { fbq('track', 'Lead'); }
</script>
<?php endif; ?>
