(function () {
  'use strict';

  function initMenu() {
    var toggle = document.querySelector('[data-nav-toggle]');
    var nav = document.querySelector('[data-nav]');
    if (!toggle || !nav) return;

    toggle.addEventListener('click', function () {
      var isOpen = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      toggle.setAttribute('aria-label', isOpen ? 'Cerrar menú' : 'Abrir menú');
    });
  }

  function initGaleria() {
    var galeria = document.querySelector('[data-galeria]');
    if (!galeria) return;
    var principal = galeria.querySelector('[data-galeria-principal]');
    if (!principal) return;

    var tiras = Array.prototype.slice.call(galeria.querySelectorAll('[data-galeria-tira]'));
    var imagenes = tiras.length
      ? tiras.map(function (t) { return { full: t.getAttribute('data-full'), alt: t.getAttribute('data-alt') || '' }; })
      : [{ full: principal.getAttribute('src'), alt: principal.getAttribute('alt') || '' }];
    var actual = 0;
    var fondo = galeria.querySelector('[data-galeria-fondo]');

    function mostrar(i) {
      actual = (i + imagenes.length) % imagenes.length;
      principal.src = imagenes[actual].full;
      if (fondo) fondo.src = imagenes[actual].full;
      principal.alt = imagenes[actual].alt;
      tiras.forEach(function (t, j) { t.classList.toggle('is-activa', j === actual); });
    }

    tiras.forEach(function (tira, i) {
      tira.addEventListener('click', function () { mostrar(i); });
    });

    // --- Lightbox ---
    var lb = document.querySelector('[data-lightbox]');
    var abrir = galeria.querySelector('[data-galeria-abrir]');
    if (!lb || !abrir) return;

    var lbImg = lb.querySelector('[data-lightbox-img]');
    var btnCerrar = lb.querySelector('[data-lightbox-cerrar]');
    var btnPrev = lb.querySelector('[data-lightbox-prev]');
    var btnNext = lb.querySelector('[data-lightbox-next]');
    var opener = null;

    function pintarLb() {
      lbImg.src = imagenes[actual].full;
      lbImg.alt = imagenes[actual].alt;
    }
    function irLb(delta) {
      mostrar(actual + delta);
      pintarLb();
    }
    function onKey(e) {
      if (e.key === 'Escape') { cerrarLb(); }
      else if (e.key === 'ArrowLeft' && imagenes.length > 1) { irLb(-1); }
      else if (e.key === 'ArrowRight' && imagenes.length > 1) { irLb(1); }
      else if (e.key === 'Tab') { e.preventDefault(); btnCerrar.focus(); }
    }
    function abrirLb() {
      opener = document.activeElement;
      pintarLb();
      lb.hidden = false;
      document.body.style.overflow = 'hidden';
      btnCerrar.focus();
      document.addEventListener('keydown', onKey);
    }
    function cerrarLb() {
      lb.hidden = true;
      document.body.style.overflow = '';
      document.removeEventListener('keydown', onKey);
      if (opener && opener.focus) opener.focus();
    }

    abrir.addEventListener('click', abrirLb);
    btnCerrar.addEventListener('click', cerrarLb);
    lb.addEventListener('click', function (e) { if (e.target === lb) cerrarLb(); });
    if (btnPrev) btnPrev.addEventListener('click', function () { irLb(-1); });
    if (btnNext) btnNext.addEventListener('click', function () { irLb(1); });
  }

  function initHeroCarrusel() {
    var hero = document.querySelector('[data-hero-carrusel]');
    if (!hero) return;
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var slides = hero.querySelectorAll('.hero__slide');
    if (slides.length < 2) return;

    var actual = 0;
    window.setInterval(function () {
      slides[actual].classList.remove('is-activa');
      actual = (actual + 1) % slides.length;
      slides[actual].classList.add('is-activa');
    }, 6000);
  }

  // Marca las imagenes horizontales de las tarjetas de paquete y destino para que llenen el
  // marco 4:5 (ver .is-apaisada en site.css). Se mide la imagen real: el ancho/alto declarado
  // en el HTML es generico.
  function initOrientacionMedia() {
    document.querySelectorAll('.tarjeta__media--paquete .tarjeta__media-img, .tarjeta__media--destino .tarjeta__media-img').forEach(function (img) {
      function medir() {
        if (img.naturalWidth && img.naturalWidth / img.naturalHeight >= 1.15) {
          img.classList.add('is-apaisada');
        }
      }
      if (img.complete) medir(); else img.addEventListener('load', medir, { once: true });
    });
  }

  function initCarruselesHover() {
    var reducido = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.querySelectorAll('[data-carrusel-hover]').forEach(function (media) {
      var frames = media.querySelectorAll('.tarjeta__media-img');
      if (frames.length < 2 || reducido) return;

      var fondo = media.querySelector('.media-fondo');
      var actual = 0;
      var timer = null;

      function mostrar(i) {
        frames[actual].classList.remove('is-activa');
        actual = i % frames.length;
        frames[actual].classList.add('is-activa');
        if (fondo) fondo.src = frames[actual].currentSrc || frames[actual].src;
      }

      function iniciar() {
        if (timer) return;
        timer = window.setInterval(function () { mostrar(actual + 1); }, 1500);
      }

      function detener() {
        window.clearInterval(timer);
        timer = null;
        mostrar(0);
      }

      media.addEventListener('mouseenter', iniciar);
      media.addEventListener('mouseleave', detener);
      media.addEventListener('focusin', iniciar);
      media.addEventListener('focusout', detener);
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
        quitar.setAttribute('aria-label', 'Quitar ' + p.titulo + ' de la comparación');
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

  // Formulario de reserva: recalcula total, anticipo y saldo al cambiar el numero de
  // personas. Es solo una vista previa: el servidor vuelve a calcular todo al reservar.
  function initReservaResumen() {
    var resumen = document.querySelector('[data-reserva-resumen]');
    var input = document.querySelector('[data-reserva-personas]');
    if (!resumen || !input) return;

    var precio = parseFloat(resumen.getAttribute('data-precio')) || 0;
    var pct = parseInt(resumen.getAttribute('data-anticipo'), 10) || 0;
    var fmt = new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    var campos = {
      num: resumen.querySelector('[data-reserva-num]'),
      total: resumen.querySelector('[data-reserva-total]'),
      anticipo: resumen.querySelector('[data-reserva-anticipo]'),
      saldo: resumen.querySelector('[data-reserva-saldo]')
    };

    function actualizar() {
      var n = parseInt(input.value, 10);
      var max = parseInt(input.getAttribute('max'), 10) || n;
      if (!n || n < 1 || n > max) return;
      var total = precio * n;
      var anticipo = Math.round(total * pct) / 100;
      campos.num.textContent = n;
      campos.total.textContent = '$' + fmt.format(total);
      campos.anticipo.textContent = '$' + fmt.format(anticipo);
      campos.saldo.textContent = '$' + fmt.format(total - anticipo);
    }

    input.addEventListener('input', actualizar);
  }

  // Filtros del catalogo en movil: deja visible la busqueda y pliega el resto tras
  // "Mas filtros" (abierto de entrada si ya hay alguno aplicado). Sin JS se ve todo.
  function initFiltrosCatalogo() {
    var form = document.querySelector('[data-filtros]');
    var toggle = form && form.querySelector('[data-filtros-toggle]');
    if (!form || !toggle) return;
    var movil = window.matchMedia('(max-width: 639px)');

    function aplicar() {
      form.classList.toggle('is-colapsable', movil.matches);
      toggle.hidden = !movil.matches;
    }

    if (form.hasAttribute('data-filtros-abiertos')) {
      form.classList.add('is-abierto');
      toggle.setAttribute('aria-expanded', 'true');
      toggle.textContent = 'Menos filtros';
    }

    toggle.addEventListener('click', function () {
      var abierto = form.classList.toggle('is-abierto');
      toggle.setAttribute('aria-expanded', abierto ? 'true' : 'false');
      toggle.textContent = abierto ? 'Menos filtros' : 'Más filtros';
    });

    aplicar();
    if (movil.addEventListener) movil.addEventListener('change', aplicar);
  }

  // Pago de saldo: muestra el campo de monto solo al elegir "Abonar otra cantidad" y
  // mantiene el texto del boton con el monto que se va a cobrar. El servidor vuelve a
  // validar el monto (ReservaService::resolverMontoSaldo).
  function initPagoSaldo() {
    var form = document.querySelector('[data-pago-saldo]');
    if (!form) return;
    var radios = form.querySelectorAll('[data-pago-modo]');
    var campo = form.querySelector('[data-pago-monto-campo]');
    var input = form.querySelector('[data-pago-monto]');
    var boton = form.querySelector('[data-pago-boton]');
    if (!radios.length || !campo || !input || !boton) return;

    var saldo = parseFloat(boton.getAttribute('data-saldo')) || 0;
    var moneda = boton.getAttribute('data-moneda') || '';
    var fmt = new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function modo() {
      var elegido = form.querySelector('[data-pago-modo]:checked');
      return elegido ? elegido.value : 'total';
    }

    function actualizar() {
      var abono = modo() === 'abono';
      campo.hidden = !abono;
      input.required = abono;
      var monto = abono ? Math.min(parseFloat(input.value) || 0, saldo) : saldo;
      boton.textContent = monto > 0
        ? (abono && monto < saldo ? 'Abonar $' : 'Pagar $') + fmt.format(monto) + ' ' + moneda + ' en línea'
        : 'Escribe el monto del abono';
      boton.disabled = monto <= 0;
    }

    Array.prototype.forEach.call(radios, function (r) { r.addEventListener('change', function () {
      actualizar();
      if (modo() === 'abono') input.focus();
    }); });
    input.addEventListener('input', actualizar);
    actualizar();
  }

  function initModalPromocion() {
    var modal = document.querySelector('[data-modal-promo]');
    if (!modal) return;

    var KEY = 'dreamgo_modal_' + (modal.getAttribute('data-modal-id') || '0');
    var DIAS_SILENCIO = 7;

    // Tras mostrarse, el mismo modal no vuelve a aparecer en 7 dias (antes: por sesion, lo
    // que lo repetia en cada visita nueva).
    try {
      var visto = parseInt(localStorage.getItem(KEY), 10);
      if (visto && Date.now() - visto < DIAS_SILENCIO * 864e5) return;
    } catch (e) {
      // localStorage no disponible (modo privado): se mostrara una vez por carga.
    }

    var cerrarBtns = modal.querySelectorAll('[data-modal-promo-cerrar], [data-modal-promo-cta]');
    var opener = null;
    var abierto = false;

    function marcarVisto() {
      try { localStorage.setItem(KEY, String(Date.now())); } catch (e) {}
    }

    function onKey(e) {
      if (e.key === 'Escape') cerrar();
    }

    function abrir() {
      opener = document.activeElement;
      modal.hidden = false;
      document.body.style.overflow = 'hidden';
      abierto = true;
      marcarVisto();
      var cerrar0 = modal.querySelector('[data-modal-promo-cerrar]');
      if (cerrar0) cerrar0.focus();
      document.addEventListener('keydown', onKey);
    }

    function cerrar() {
      if (!abierto) return;
      abierto = false;
      modal.hidden = true;
      document.body.style.overflow = '';
      document.removeEventListener('keydown', onKey);
      if (opener && opener.focus) opener.focus();
    }

    Array.prototype.forEach.call(cerrarBtns, function (btn) {
      // El CTA es un <a>: se deja navegar, solo restauramos el scroll del body.
      btn.addEventListener('click', function () {
        if (btn.hasAttribute('data-modal-promo-cta')) {
          document.body.style.overflow = '';
          marcarVisto();
        } else {
          cerrar();
        }
      });
    });

    modal.addEventListener('click', function (e) {
      if (e.target === modal) cerrar();
    });

    // Se abre por intencion, no al cargar: cuando el visitante ya recorrio la mitad de la
    // pagina, tras 20 s, o (escritorio) cuando el cursor sale por arriba de la ventana.
    var disparado = false;
    function disparar() {
      if (disparado) return;
      disparado = true;
      window.removeEventListener('scroll', onScroll);
      document.removeEventListener('mouseout', onSalida);
      window.clearTimeout(temporizador);
      abrir();
    }
    function onScroll() {
      var alto = document.documentElement.scrollHeight - window.innerHeight;
      if (alto > 0 && window.scrollY / alto >= 0.5) disparar();
    }
    function onSalida(e) {
      if (!e.relatedTarget && e.clientY <= 0) disparar();
    }
    var temporizador = window.setTimeout(disparar, 20000);
    window.addEventListener('scroll', onScroll, { passive: true });
    document.addEventListener('mouseout', onSalida);
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
    initHeroCarrusel();
    initOrientacionMedia();
    initCarruselesHover();
    initAutoSubmit();
    initScrollReveal();
    initComparador();
    initAtribucion();
    initConsentimiento();
    initModalPromocion();
    initReservaResumen();
    initFiltrosCatalogo();
    initPagoSaldo();
  });

  initServiceWorker();
})();
