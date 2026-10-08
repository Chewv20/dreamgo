(function () {
  'use strict';

  function initSidebarToggle() {
    var toggle = document.querySelector('[data-admin-sidebar-toggle]');
    var sidebar = document.querySelector('[data-admin-sidebar]');
    if (!toggle || !sidebar) return;

    toggle.addEventListener('click', function () {
      var isOpen = sidebar.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
  }

  // Confirmacion de acciones (form[data-confirm]) con un <dialog> propio en vez de
  // window.confirm(): se puede estilizar, el boton dice que va a pasar y las acciones
  // destructivas se ven en rojo. <dialog>.showModal() ya atrapa el foco y cierra con Esc.
  // Sin soporte de <dialog> se cae a window.confirm().
  function initConfirmForms() {
    var dialogo = null;
    var pendiente = null; // { form, submitter } esperando respuesta

    function crearDialogo() {
      dialogo = document.createElement('dialog');
      dialogo.className = 'admin-dialogo';
      dialogo.setAttribute('aria-labelledby', 'admin-dialogo-texto');
      dialogo.innerHTML =
        '<p class="admin-dialogo__texto" id="admin-dialogo-texto"></p>' +
        '<div class="admin-dialogo__acciones">' +
        '<button type="button" class="btn btn-secundario" data-dialogo-volver>Volver</button>' +
        '<button type="button" class="btn" data-dialogo-confirmar></button>' +
        '</div>';
      document.body.appendChild(dialogo);

      // Respuesta por clic (sincrona): no depende del evento "close", que es asincrono.
      dialogo.querySelector('[data-dialogo-volver]').addEventListener('click', function () {
        cerrar(false);
      });
      dialogo.querySelector('[data-dialogo-confirmar]').addEventListener('click', function () {
        cerrar(true);
      });
      // Escape: el navegador dispara "cancel" y cierra el dialogo; el keydown cubre a los
      // navegadores que no lo hacen de forma consistente.
      dialogo.addEventListener('cancel', function () {
        cerrar(false);
      });
      dialogo.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
          e.preventDefault();
          cerrar(false);
        }
      });
    }

    function cerrar(confirmado) {
      var actual = pendiente;
      pendiente = null;
      if (dialogo.open) dialogo.close();
      if (!actual) return;

      if (confirmado) {
        actual.form.dataset.confirmado = '1';
        if (actual.form.requestSubmit) {
          actual.form.requestSubmit(actual.submitter && actual.submitter.form === actual.form ? actual.submitter : undefined);
        } else {
          actual.form.submit();
        }
      } else if (actual.submitter && actual.submitter.focus) {
        actual.submitter.focus();
      }
    }

    document.addEventListener('submit', function (e) {
      var form = e.target;
      if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) return;
      if (form.dataset.confirmado === '1') {
        delete form.dataset.confirmado;
        return;
      }

      var mensaje = form.getAttribute('data-confirm');
      if (typeof HTMLDialogElement !== 'function') {
        if (!window.confirm(mensaje)) e.preventDefault();
        return;
      }

      e.preventDefault();
      if (!dialogo) crearDialogo();

      var submitter = e.submitter || form.querySelector('[type="submit"]');
      var boton = dialogo.querySelector('[data-dialogo-confirmar]');
      var peligro = !!form.querySelector('.btn-peligro') || /eliminar|cancelar|archivar/i.test(mensaje);
      dialogo.querySelector('.admin-dialogo__texto').textContent = mensaje;
      boton.textContent = (submitter && submitter.textContent.trim()) || 'Confirmar';
      boton.className = 'btn ' + (peligro ? 'btn-peligro' : 'btn-primario');

      pendiente = { form: form, submitter: submitter };
      dialogo.showModal();
      dialogo.querySelector('[data-dialogo-volver]').focus();
    });
  }

  // Reemplaza los onchange="this.form.submit()" inline.
  function initAutosubmit() {
    document.addEventListener('change', function (e) {
      var el = e.target;
      if (el.hasAttribute('data-autosubmit') && el.form) {
        el.form.submit();
      }
    });
  }

  // Reemplaza los onchange="document.getElementById(...).style.display = ..." inline.
  // Uso: <select data-toggle-target="id-del-elemento" data-toggle-value="valor-que-lo-muestra">
  function initToggleVisibility() {
    document.querySelectorAll('[data-toggle-target]').forEach(function (el) {
      var target = document.getElementById(el.getAttribute('data-toggle-target'));
      var valorEsperado = el.getAttribute('data-toggle-value');
      if (!target) return;

      el.addEventListener('change', function () {
        target.classList.toggle('oculto', el.value !== valorEsperado);
      });
    });
  }

  // Mensajes flash: boton de cerrar en todos; los de exito/info se desvanecen solos a los
  // 6 s (los de error se quedan hasta que el usuario los cierre).
  function initFlash() {
    document.querySelectorAll('[data-admin-flash]').forEach(function (flash) {
      function cerrar() { flash.remove(); }
      var btn = flash.querySelector('[data-admin-flash-cerrar]');
      if (btn) btn.addEventListener('click', cerrar);
      if (!flash.classList.contains('admin-flash--error')) {
        window.setTimeout(function () {
          flash.classList.add('is-saliendo');
          window.setTimeout(cerrar, 400);
        }, 6000);
      }
    });
  }

  // Menu de usuario (<details>): se cierra al hacer clic fuera o con Escape.
  function initMenuUsuario() {
    var menu = document.querySelector('[data-admin-usuario]');
    if (!menu) return;
    document.addEventListener('click', function (e) {
      if (menu.open && !menu.contains(e.target)) menu.open = false;
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && menu.open) {
        menu.open = false;
        menu.querySelector('summary').focus();
      }
    });
  }

  // Boton "Copiar" junto a un campo de solo lectura (link de pago del cliente).
  function initCopiar() {
    document.querySelectorAll('[data-copiar]').forEach(function (btn) {
      var campo = document.querySelector(btn.getAttribute('data-copiar'));
      if (!campo) return;
      btn.addEventListener('click', function () {
        campo.select();
        var hecho = function () {
          var texto = btn.textContent;
          btn.textContent = 'Copiado';
          window.setTimeout(function () { btn.textContent = texto; }, 1500);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(campo.value).then(hecho, function () { document.execCommand('copy'); hecho(); });
        } else {
          document.execCommand('copy');
          hecho();
        }
      });
    });
  }

  // Itinerario del paquete: agregar / quitar filas de dia. Siempre queda al menos una.
  function initItinerario() {
    var lista = document.querySelector('[data-itinerario]');
    var agregar = document.querySelector('[data-itinerario-agregar]');
    if (!lista || !agregar) return;

    function renumerar() {
      lista.querySelectorAll('[data-itinerario-dia]').forEach(function (fila, i) {
        var n = i + 1;
        fila.querySelector('input').placeholder = 'Día ' + n;
        fila.querySelector('input').setAttribute('aria-label', 'Título del día ' + n);
        fila.querySelector('textarea').setAttribute('aria-label', 'Actividades del día ' + n);
        fila.querySelector('[data-itinerario-quitar]').setAttribute('aria-label', 'Quitar día ' + n);
      });
    }

    lista.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-itinerario-quitar]');
      if (!btn) return;
      var filas = lista.querySelectorAll('[data-itinerario-dia]');
      var fila = btn.closest('[data-itinerario-dia]');
      if (filas.length === 1) {
        fila.querySelector('input').value = '';
        fila.querySelector('textarea').value = '';
      } else {
        fila.remove();
      }
      renumerar();
    });

    agregar.addEventListener('click', function () {
      var filas = lista.querySelectorAll('[data-itinerario-dia]');
      var nueva = filas[filas.length - 1].cloneNode(true);
      nueva.querySelector('input').value = '';
      nueva.querySelector('textarea').value = '';
      lista.appendChild(nueva);
      renumerar();
      nueva.querySelector('textarea').focus();
    });
  }

  // Campo de contrasena: "Mostrar" y "Generar" (12 caracteres con letras y numeros, sin
  // los que se confunden: 0/O, 1/l/I). Cumple PasswordPolicy (8+, letras y numeros).
  function initCampoPassword() {
    document.querySelectorAll('[data-campo-password]').forEach(function (grupo) {
      var input = grupo.querySelector('input');
      var ver = grupo.querySelector('[data-password-ver]');
      var generar = grupo.querySelector('[data-password-generar]');

      function mostrar(visible) {
        input.type = visible ? 'text' : 'password';
        ver.textContent = visible ? 'Ocultar' : 'Mostrar';
        ver.setAttribute('aria-pressed', visible ? 'true' : 'false');
      }

      ver.addEventListener('click', function () { mostrar(input.type === 'password'); });

      if (generar && window.crypto && window.crypto.getRandomValues) {
        generar.addEventListener('click', function () {
          var letras = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
          var numeros = '23456789';
          var todos = letras + numeros;
          var valores = new Uint32Array(12);
          window.crypto.getRandomValues(valores);
          var clave = '';
          for (var i = 0; i < valores.length; i++) {
            var fuente = i === 0 ? letras : (i === 1 ? numeros : todos);
            clave += fuente.charAt(valores[i] % fuente.length);
          }
          input.value = clave;
          mostrar(true);
          input.focus();
          input.select();
        });
      } else if (generar) {
        generar.hidden = true;
      }
    });
  }

  // Matriz de permisos: "Todo" marca los permisos del grupo para ese rol; si ya estaban
  // todos marcados, los desmarca. Respeta las casillas bloqueadas (fuera de alcance).
  function initMatrizTodos() {
    document.querySelectorAll('[data-matriz-todos]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var selector = 'input[type="checkbox"][data-modulo="' + btn.getAttribute('data-matriz-todos') + '"][data-rol="' + btn.getAttribute('data-rol') + '"]:not(:disabled)';
        var casillas = Array.prototype.slice.call(document.querySelectorAll(selector));
        var todas = casillas.every(function (c) { return c.checked; });
        casillas.forEach(function (c) { c.checked = !todas; });
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initMatrizTodos();
    initCampoPassword();
    initItinerario();
    initCopiar();
    initFlash();
    initMenuUsuario();
    initSidebarToggle();
    initConfirmForms();
    initAutosubmit();
    initToggleVisibility();
  });
})();
