(function () {
  'use strict';

  function initMenu() {
    var toggle = document.querySelector('[data-nav-toggle]');
    var nav = document.querySelector('[data-nav]');
    if (!toggle || !nav) return;

    toggle.addEventListener('click', function () {
      var isOpen = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
  }

  function initScrollReveal() {
    var elementos = document.querySelectorAll('.animar-entrada');
    if (!elementos.length) return;

    if (!('IntersectionObserver' in window)) {
      elementos.forEach(function (el) { el.classList.add('visible'); });
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15 });

    elementos.forEach(function (el) { observer.observe(el); });
  }

  function baseHref() {
    var link = document.querySelector('link[rel="manifest"]');
    if (!link) return '/';
    var path = new URL(link.getAttribute('href'), window.location.origin).pathname;
    return path.replace(/manifest\.json$/, '');
  }

  function initServiceWorker() {
    if (!('serviceWorker' in navigator)) return;

    // Si el service worker activo cambia (nueva version tomo el control),
    // recargamos una sola vez para mostrar el contenido actualizado sin
    // que el usuario tenga que borrar la cache manualmente.
    var recargando = false;
    navigator.serviceWorker.addEventListener('controllerchange', function () {
      if (recargando) return;
      recargando = true;
      window.location.reload();
    });

    window.addEventListener('load', function () {
      navigator.serviceWorker.register(baseHref() + 'sw.js').then(function (registration) {
        // Revisa si hay una version nueva del service worker al cargar la
        // pagina y cada vez que la pestaña vuelve a estar visible.
        registration.update();
        document.addEventListener('visibilitychange', function () {
          if (document.visibilityState === 'visible') {
            registration.update();
          }
        });
      }).catch(function (err) {
        console.warn('No se pudo registrar el service worker:', err);
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initMenu();
    initScrollReveal();
  });

  initServiceWorker();
})();
