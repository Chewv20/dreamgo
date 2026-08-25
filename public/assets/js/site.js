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

  function initComparador() {
    var KEY = 'dreamgo_comparar';
    var MAX = 3;
    var basePath = null;

    function leer() {
      try {
        var datos = JSON.parse(localStorage.getItem(KEY) || '[]');
        return Array.isArray(datos) ? datos : [];
      } catch (e) {
        return [];
      }
    }

    function guardar(lista) {
      try {
        localStorage.setItem(KEY, JSON.stringify(lista));
      } catch (e) {
        // localStorage no disponible (modo privado, etc.): la seleccion simplemente no persiste.
      }
    }

    function sincronizarCheckboxes() {
      var lista = leer();
      document.querySelectorAll('[data-comparar-slug]').forEach(function (checkbox) {
        var slug = checkbox.getAttribute('data-comparar-slug');
        checkbox.checked = lista.some(function (p) { return p.slug === slug; });
      });
    }

    function refrescarBarra() {
      var barra = document.querySelector('[data-comparador-barra]');
      if (!barra) return;

      var lista = leer();

      if (lista.length === 0) {
        barra.hidden = true;
        return;
      }
      barra.hidden = false;

      var chips = barra.querySelector('[data-comparador-chips]');
      chips.textContent = '';
      lista.forEach(function (p) {
        var chip = document.createElement('span');
        chip.className = 'chip-comparador';

        var texto = document.createElement('span');
        texto.textContent = p.titulo;
        chip.appendChild(texto);

        var quitar = document.createElement('button');
        quitar.type = 'button';
        quitar.className = 'chip-comparador__quitar';
        quitar.setAttribute('aria-label', 'Quitar ' + p.titulo + ' de la comparacion');
        quitar.textContent = '×';
        quitar.addEventListener('click', function () {
          alternar(p.slug, p.titulo, false);
        });
        chip.appendChild(quitar);

        chips.appendChild(chip);
      });

      var link = barra.querySelector('[data-comparador-link]');
      if (lista.length >= 2) {
        var slugs = lista.map(function (p) { return p.slug; }).join(',');
        link.href = basePath + '?paquetes=' + encodeURIComponent(slugs);
        link.removeAttribute('aria-disabled');
        link.textContent = 'Comparar (' + lista.length + ')';
      } else {
        link.href = '#';
        link.setAttribute('aria-disabled', 'true');
        link.textContent = 'Selecciona al menos 2';
      }

      sincronizarCheckboxes();
    }

    function alternar(slug, titulo, marcado) {
      var lista = leer().filter(function (p) { return p.slug !== slug; });

      if (marcado) {
        if (lista.length >= MAX) {
          refrescarBarra();
          return false;
        }
        lista.push({ slug: slug, titulo: titulo });
      }

      guardar(lista);
      refrescarBarra();

      return true;
    }

    var barra = document.querySelector('[data-comparador-barra]');
    if (!barra) return;

    var link = barra.querySelector('[data-comparador-link]');
    basePath = link.getAttribute('href').split('?')[0];
    link.addEventListener('click', function (e) {
      if (link.getAttribute('aria-disabled') === 'true') {
        e.preventDefault();
      }
    });

    document.querySelectorAll('[data-comparar-slug]').forEach(function (checkbox) {
      checkbox.addEventListener('change', function () {
        var ok = alternar(
          checkbox.getAttribute('data-comparar-slug'),
          checkbox.getAttribute('data-comparar-titulo'),
          checkbox.checked
        );
        if (!ok) {
          checkbox.checked = false;
        }
      });
    });

    sincronizarCheckboxes();
    refrescarBarra();
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
    initComparador();
  });

  initServiceWorker();
})();
