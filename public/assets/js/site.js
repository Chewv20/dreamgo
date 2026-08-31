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

  function initGaleria() {
    var galeria = document.querySelector('[data-galeria]');
    if (!galeria) return;
    var principal = galeria.querySelector('[data-galeria-principal]');
    var tiras = galeria.querySelectorAll('[data-galeria-tira]');
    if (!principal || !tiras.length) return;

    tiras.forEach(function (tira) {
      tira.addEventListener('click', function () {
        principal.src = tira.getAttribute('data-full');
        principal.alt = tira.getAttribute('data-alt') || '';
        tiras.forEach(function (t) { t.classList.remove('is-activa'); });
        tira.classList.add('is-activa');
      });
    });
  }

  function initAutoSubmit() {
    document.querySelectorAll('[data-autosubmit]').forEach(function (control) {
      control.addEventListener('change', function () {
        if (control.form) control.form.submit();
      });
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
    var MAX = parseInt(document.body.getAttribute('data-comparar-max'), 10) || 3;
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

  function initAtribucion() {
    var KEY = 'dreamgo_atribucion';
    var CAMPOS_UTM = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];

    function leerGuardado() {
      try {
        var datos = JSON.parse(sessionStorage.getItem(KEY) || 'null');
        return datos && typeof datos === 'object' ? datos : null;
      } catch (e) {
        return null;
      }
    }

    // Primer toque de la sesion: si la URL trae algun utm_* y todavia no guardamos nada,
    // fijamos el origen (utm + referrer externo + pagina de aterrizaje) para todo lo que
    // reste de la sesion, aunque el visitante navegue a otras paginas antes de enviar un form.
    function capturar() {
      if (leerGuardado()) return;

      var params = new URLSearchParams(window.location.search);
      var trajoUtm = CAMPOS_UTM.some(function (c) { return params.get(c); });
      if (!trajoUtm) return;

      var datos = {};
      CAMPOS_UTM.forEach(function (c) {
        if (params.get(c)) datos[c] = params.get(c).slice(0, 100);
      });

      var ref = document.referrer || '';
      if (ref && ref.indexOf(window.location.origin) !== 0) {
        datos.referrer = ref.slice(0, 255);
      }
      datos.landing_page = (window.location.pathname + window.location.search).slice(0, 255);

      try {
        sessionStorage.setItem(KEY, JSON.stringify(datos));
      } catch (e) {
        // sessionStorage no disponible: la atribucion simplemente no se arrastra entre paginas.
      }
    }

    function inyectarEnFormularios() {
      var datos = leerGuardado();
      if (!datos) return;

      document.querySelectorAll('form[data-atribucion]').forEach(function (form) {
        Object.keys(datos).forEach(function (nombre) {
          if (form.querySelector('[name="' + nombre + '"]')) return;
          var input = document.createElement('input');
          input.type = 'hidden';
          input.name = nombre;
          input.value = datos[nombre];
          form.appendChild(input);
        });
      });
    }

    capturar();
    inyectarEnFormularios();
  }

  function initConsentimiento() {
    var CLAVE = 'dreamgo_consentimiento';
    var banner = document.querySelector('[data-consentimiento]');
    if (!banner || !window.dreamgoAnalitica) return;

    var elegido = null;
    try { elegido = localStorage.getItem(CLAVE); } catch (e) {}
    if (elegido === 'granted' || elegido === 'denied') return;

    banner.hidden = false;

    function decidir(valor) {
      try { localStorage.setItem(CLAVE, valor); } catch (e) {}
      banner.hidden = true;
      if (valor === 'granted' && typeof window.dreamgoCargarAnalitica === 'function') {
        window.dreamgoCargarAnalitica();
      }
    }

    var aceptar = banner.querySelector('[data-consentimiento-aceptar]');
    var rechazar = banner.querySelector('[data-consentimiento-rechazar]');
    if (aceptar) aceptar.addEventListener('click', function () { decidir('granted'); });
    if (rechazar) rechazar.addEventListener('click', function () { decidir('denied'); });
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
    initGaleria();
    initAutoSubmit();
    initScrollReveal();
    initComparador();
    initAtribucion();
    initConsentimiento();
  });

  initServiceWorker();
})();
