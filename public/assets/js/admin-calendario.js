(function () {
  'use strict';

  var meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
  var hoy = new Date();
  var anio = hoy.getFullYear();
  var mes = hoy.getMonth() + 1;

  function cargar() {
    document.getElementById('cal-titulo').textContent = meses[mes - 1] + ' ' + anio;
    fetch('/admin/reservas/calendario/datos?anio=' + anio + '&mes=' + mes)
      .then(function (r) { return r.json(); })
      .then(pintar);
  }

  function pintar(salidas) {
    var porDia = {};
    salidas.forEach(function (s) {
      var dia = parseInt(s.fecha_salida.split('-')[2], 10);
      (porDia[dia] = porDia[dia] || []).push(s);
    });

    var grid = document.getElementById('cal-grid');
    grid.innerHTML = '';

    var primerDia = new Date(anio, mes - 1, 1).getDay();
    var diasEnMes = new Date(anio, mes, 0).getDate();

    for (var i = 0; i < primerDia; i++) {
      grid.appendChild(document.createElement('div'));
    }

    for (var dia = 1; dia <= diasEnMes; dia++) {
      var celda = document.createElement('div');
      celda.style.cssText = 'background:#fff;border-radius:8px;padding:0.5rem;min-height:90px;font-size:0.8rem;';
      var numero = document.createElement('div');
      numero.style.fontWeight = '700';
      numero.textContent = dia;
      celda.appendChild(numero);

      (porDia[dia] || []).forEach(function (s) {
        var badge = document.createElement('a');
        badge.href = '/admin/paquetes/' + s.paquete_id + '/salidas';
        badge.textContent = s.paquete_titulo + ' (' + s.cupo_disponible + '/' + s.cupo_maximo + ')';
        badge.style.cssText = 'display:block;background:#faf3ef;color:#a85f4d;border-radius:6px;padding:0.2rem 0.4rem;margin-top:0.25rem;text-decoration:none;font-size:0.72rem;';
        celda.appendChild(badge);
      });

      grid.appendChild(celda);
    }
  }

  document.getElementById('cal-prev').addEventListener('click', function () {
    mes--; if (mes < 1) { mes = 12; anio--; }
    cargar();
  });
  document.getElementById('cal-next').addEventListener('click', function () {
    mes++; if (mes > 12) { mes = 1; anio++; }
    cargar();
  });

  cargar();
})();
