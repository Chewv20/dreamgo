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

  document.addEventListener('DOMContentLoaded', function () {
    initSidebarToggle();
    initConfirmForms();
    initAutosubmit();
    initToggleVisibility();
  });
})();
