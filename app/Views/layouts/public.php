<!doctype html>
<html lang="es-MX">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($meta['title'], ENT_QUOTES, 'UTF-8') ?></title>
<meta name="description" content="<?= htmlspecialchars($meta['description'], ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:title" content="<?= htmlspecialchars($meta['title'], ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:description" content="<?= htmlspecialchars($meta['description'], ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:image" content="<?= htmlspecialchars($meta['ogImage'], ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:type" content="website">
<link rel="canonical" href="<?= htmlspecialchars(($_ENV['APP_URL'] ?? '') . $_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') ?>">

<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#d99e8e">
<link rel="apple-touch-icon" href="/assets/icons/icon-180.png">
<link rel="icon" href="/assets/icons/icon-96.png" type="image/png">

<link rel="stylesheet" href="/assets/css/site.css">
<?php if (!empty($meta['jsonLd'])): ?>
<script type="application/ld+json"><?= $meta['jsonLd'] ?></script>
<?php endif; ?>
</head>
<body>
<a href="#contenido-principal" class="sr-only-focusable">Saltar al contenido principal</a>

<header class="header">
  <div class="contenedor header__contenido">
    <a href="/" class="header__logo" aria-label="Dream Go - Inicio">
      <img src="/assets/img/logo.avif" alt="Dream Go Operadora Turistica">
    </a>
    <button type="button" class="nav-toggle" data-nav-toggle aria-expanded="false" aria-controls="nav-principal">&#9776;</button>
    <nav class="nav" id="nav-principal" data-nav>
      <a href="/">Inicio</a>
      <a href="/destinos">Destinos</a>
      <a href="/paquetes">Paquetes</a>
      <a href="/nosotros">Nosotros</a>
      <a href="/cotizador" class="btn btn-primario">Cotizar</a>
      <a href="/contacto">Contacto</a>
    </nav>
  </div>
</header>

<?php foreach (\App\Helpers\Flash::pull() as $flash): ?>
  <div class="contenedor" style="margin-top:1rem;">
    <div style="padding:0.9rem 1.2rem;border-radius:12px;background:<?= $flash['tipo'] === 'error' ? '#fbe7e5' : '#e9f2ea' ?>;color:<?= $flash['tipo'] === 'error' ? 'var(--color-error)' : 'var(--color-exito)' ?>;">
      <?= htmlspecialchars($flash['mensaje'], ENT_QUOTES, 'UTF-8') ?>
    </div>
  </div>
<?php endforeach; ?>

<main id="contenido-principal">
<?= $content ?>
</main>

<?php
$footerDireccion = \App\Models\ConfiguracionSitio::get('direccion', '');
$footerTelefono = \App\Models\ConfiguracionSitio::get('telefono_contacto', '');
$footerEmail = \App\Models\ConfiguracionSitio::get('email_contacto', '');
$footerWhatsappNumero = \App\Models\ConfiguracionSitio::get('whatsapp_numero', '');
$footerWhatsappLink = $footerWhatsappNumero !== ''
    ? (new \App\Services\WhatsAppLinkService())->generarLink($footerWhatsappNumero, 'Hola, me gustaria mas informacion sobre sus paquetes de viaje.')
    : '';
$footerRedes = [
    'Facebook' => \App\Models\ConfiguracionSitio::get('facebook_url', ''),
    'Instagram' => \App\Models\ConfiguracionSitio::get('instagram_url', ''),
    'TikTok' => \App\Models\ConfiguracionSitio::get('tiktok_url', ''),
    'YouTube' => \App\Models\ConfiguracionSitio::get('youtube_url', ''),
];
$footerIconos = [
    'Facebook' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M13.5 21v-8h2.7l.4-3.1h-3.1V8c0-.9.2-1.5 1.6-1.5H17V3.7C16.6 3.6 15.5 3.5 14.2 3.5c-2.6 0-4.4 1.6-4.4 4.5v2H7v3.1h2.8V21h3.7Z"/></svg>',
    'Instagram' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>',
    'TikTok' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M16.5 3c.3 2.3 1.8 3.8 4 4v3c-1.4 0-2.8-.4-4-1.2v6.4a5.7 5.7 0 1 1-5.7-5.7c.3 0 .6 0 .9.1v3.1a2.6 2.6 0 1 0 1.8 2.5V3h3Z"/></svg>',
    'YouTube' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="4" fill="none" stroke="currentColor" stroke-width="2"/><path d="M10 9.5v5l4.5-2.5Z"/></svg>',
];
$footerTieneContacto = $footerDireccion !== '' || $footerTelefono !== '' || $footerEmail !== '' || $footerWhatsappLink !== '';
$footerTieneRedes = array_filter($footerRedes) !== [];
?>
<footer class="footer">
  <div class="contenedor footer__grid">
    <div class="footer__marca">
      <img src="/assets/img/logo.avif" alt="Dream Go" class="footer__logo">
      <p>Dream Go Operadora Turistica &mdash; Excursiones nacionales e internacionales.</p>
    </div>

    <?php if ($footerTieneContacto): ?>
    <div class="footer__col">
      <h3>Contacto</h3>
      <ul class="footer__lista">
        <?php if ($footerDireccion !== ''): ?>
          <li>
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0Z"/><circle cx="12" cy="10" r="3"/></svg>
            <span><?= htmlspecialchars($footerDireccion, ENT_QUOTES, 'UTF-8') ?></span>
          </li>
        <?php endif; ?>
        <?php if ($footerTelefono !== ''): ?>
          <li>
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92Z"/></svg>
            <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $footerTelefono), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($footerTelefono, ENT_QUOTES, 'UTF-8') ?></a>
          </li>
        <?php endif; ?>
        <?php if ($footerEmail !== ''): ?>
          <li>
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>
            <a href="mailto:<?= htmlspecialchars($footerEmail, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($footerEmail, ENT_QUOTES, 'UTF-8') ?></a>
          </li>
        <?php endif; ?>
        <?php if ($footerWhatsappLink !== ''): ?>
          <li>
            <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.5 15.2L2 22l4.9-1.5A10 10 0 1 0 12 2Zm5.2 14.2c-.2.6-1.2 1.2-1.7 1.3-.4.1-1 .1-1.6-.1-.4-.1-.9-.3-1.5-.6-2.7-1.2-4.5-3.9-4.6-4.1-.1-.2-1.1-1.5-1.1-2.8 0-1.3.7-2 1-2.2.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.4.2.5.7 1.7.8 1.8.1.2.1.4 0 .6-.1.2-.2.4-.4.6-.2.2-.4.4-.2.8.2.4.9 1.5 2 2.4 1.4 1.2 2.4 1.5 2.8 1.7.4.2.6.1.8-.1.2-.2.9-1 1.1-1.4.2-.4.4-.3.7-.2.3.1 1.8.9 2.1 1 .3.2.5.2.6.3.1.2.1.7-.1 1.3Z"/></svg>
            <a href="<?= htmlspecialchars($footerWhatsappLink, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">WhatsApp</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
    <?php endif; ?>

    <div class="footer__col">
      <h3>Enlaces</h3>
      <ul class="footer__lista footer__lista--enlaces">
        <li><a href="/paquetes">Paquetes</a></li>
        <li><a href="/destinos">Destinos</a></li>
        <li><a href="/cotizador">Cotizar</a></li>
        <li><a href="/nosotros">Nosotros</a></li>
      </ul>

      <?php if ($footerTieneRedes): ?>
        <h3 style="margin-top:1.5rem;">Siguenos</h3>
        <div class="footer__social">
          <?php foreach ($footerRedes as $nombre => $url): ?>
            <?php if ($url !== ''): ?>
              <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="footer__social-icon" aria-label="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>">
                <?= $footerIconos[$nombre] ?>
              </a>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="contenedor footer__bottom">
    <p>&copy; <?= date('Y') ?> Dream Go. Todos los derechos reservados.</p>
  </div>
</footer>

<script src="/assets/js/site.js" defer></script>
</body>
</html>
