<?php
/**
 * Bootstrap de GA4 y/o Meta Pixel, con consentimiento previo (banner en layouts/public.php).
 * Se incluye desde layouts/public.php dentro de <head>. No emite nada si el admin no
 * configuro ningun ID en /admin/configuracion.
 *
 * Ninguna peticion a Google/Meta sale hasta que el visitante acepta en el banner:
 *   - define window.dreamgoAnalitica (ids) y window.dreamgoCargarAnalitica() (inyector);
 *   - si ya hay 'granted' guardado de una visita anterior, carga de una vez;
 *   - el banner (site.js) llama a dreamgoCargarAnalitica() al hacer clic en "Aceptar".
 *
 * $ga4 / $pixel ya vienen validados por formato (App\Helpers\Analytics). El <script> lleva
 * el nonce por peticion (ver config/config.php, CSP).
 */
$ga4 = \App\Helpers\Analytics::ga4Id();
$pixel = \App\Helpers\Analytics::metaPixelId();
if ($ga4 === null && $pixel === null) {
    return;
}
$nonce = htmlspecialchars(CSP_NONCE, ENT_QUOTES, 'UTF-8');
?>
<script nonce="<?= $nonce ?>">
(function () {
  window.dreamgoAnalitica = {
    ga4: <?= $ga4 !== null ? json_encode($ga4) : 'null' ?>,
    pixel: <?= $pixel !== null ? json_encode($pixel) : 'null' ?>,
    cargada: false
  };

  function cargarGa4(id) {
    var s = document.createElement('script');
    s.async = true;
    s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id);
    document.head.appendChild(s);
    window.dataLayer = window.dataLayer || [];
    window.gtag = function () { window.dataLayer.push(arguments); };
    window.gtag('js', new Date());
    window.gtag('config', id);
  }

  function cargarPixel(id) {
    !function (f, b, e, v, n, t, s) {
      if (f.fbq) return; n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); };
      if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = [];
      t = b.createElement(e); t.async = !0; t.src = v;
      s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s);
    }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
    window.fbq('init', id);
    window.fbq('track', 'PageView');
  }

  window.dreamgoCargarAnalitica = function () {
    if (window.dreamgoAnalitica.cargada) return;
    window.dreamgoAnalitica.cargada = true;
    if (window.dreamgoAnalitica.ga4) cargarGa4(window.dreamgoAnalitica.ga4);
    if (window.dreamgoAnalitica.pixel) cargarPixel(window.dreamgoAnalitica.pixel);
  };

  try {
    if (localStorage.getItem('dreamgo_consentimiento') === 'granted') {
      window.dreamgoCargarAnalitica();
    }
  } catch (e) {}
})();
</script>
