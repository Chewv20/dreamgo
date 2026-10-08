<?php /** @var array $salidas */ ?>
<div class="admin-panel admin-panel--720">
  <a href="/admin/reservas" class="admin-volver">&larr; Volver a reservas</a>
  <p>Elige la fecha de salida para la que vas a registrar la reserva.</p>
  <?php if ($salidas === []): ?>
    <p class="op-70">No hay fechas abiertas con cupo. Crea una desde <a href="/admin/paquetes">Paquetes → Fechas y cupos</a>.</p>
  <?php else: ?>
    <div class="admin-tabla-wrap">
      <table class="admin-tabla">
        <thead><tr><th>Paquete</th><th>Salida</th><th>Regreso</th><th>Cupo</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($salidas as $s): ?>
            <tr>
              <td><?= htmlspecialchars($s['paquete_titulo'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= \App\Helpers\Fecha::corta($s['fecha_salida']) ?></td>
              <td><?= $s['fecha_regreso'] ? \App\Helpers\Fecha::corta($s['fecha_regreso']) : '—' ?></td>
              <td><?= (int) $s['cupo_disponible'] ?></td>
              <td><a href="/admin/reservas/crear?salida_id=<?= (int) $s['id'] ?>" class="btn btn-primario btn--xs">Elegir</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
