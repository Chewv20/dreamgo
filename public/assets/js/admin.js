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

  // Reemplaza los onsubmit="return confirm(...)" inline (bloqueados por la CSP).
  function initConfirmForms() {
    document.addEventListener('submit', function (e) {
      var form = e.target;
      if (form instanceof HTMLFormElement && form.hasAttribute('data-confirm')) {
        if (!window.confirm(form.getAttribute('data-confirm'))) {
          e.preventDefault();
        }
      }
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

  document.addEventListener('DOMContentLoaded', function () {
    initCopiar();
    initFlash();
    initMenuUsuario();
    initSidebarToggle();
    initConfirmForms();
    initAutosubmit();
    initToggleVisibility();
  });
})();
