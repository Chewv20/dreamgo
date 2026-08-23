<?php /** @var int $totalPaquetes */ ?>
<div class="grid-tarjetas">
  <div class="admin-panel">
    <p style="margin:0;opacity:0.7;">Paquetes totales</p>
    <p style="font-family:var(--fuente-titulos);font-size:2rem;margin:0.25rem 0 0;"><?= $totalPaquetes ?></p>
  </div>
  <div class="admin-panel">
    <p style="margin:0;opacity:0.7;">Paquetes publicados</p>
    <p style="font-family:var(--fuente-titulos);font-size:2rem;margin:0.25rem 0 0;"><?= $totalPaquetesPublicados ?></p>
  </div>
  <?php if (isset($totalCotizacionesNuevas)): ?>
  <div class="admin-panel">
    <p style="margin:0;opacity:0.7;">Cotizaciones nuevas</p>
    <p style="font-family:var(--fuente-titulos);font-size:2rem;margin:0.25rem 0 0;"><?= $totalCotizacionesNuevas ?></p>
  </div>
  <?php endif; ?>
  <?php if (isset($totalReservasPendientes)): ?>
  <div class="admin-panel">
    <p style="margin:0;opacity:0.7;">Reservas pendientes</p>
    <p style="font-family:var(--fuente-titulos);font-size:2rem;margin:0.25rem 0 0;"><?= $totalReservasPendientes ?></p>
  </div>
  <div class="admin-panel">
    <p style="margin:0;opacity:0.7;">Reservas confirmadas</p>
    <p style="font-family:var(--fuente-titulos);font-size:2rem;margin:0.25rem 0 0;"><?= $totalReservasConfirmadas ?></p>
  </div>
  <?php endif; ?>
</div>

<div class="admin-panel">
  <h2 style="margin-top:0;">Accesos rapidos</h2>
  <div class="admin-acciones">
    <a href="/admin/paquetes/crear" class="btn btn-primario">Nuevo paquete</a>
    <a href="/admin/paquetes" class="btn btn-secundario">Ver paquetes</a>
  </div>
</div>
