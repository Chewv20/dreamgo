<?php /** @var array $reservas */ ?>
<div class="admin-acciones mb-md">
  <a href="/admin/reservas/calendario" class="btn btn-secundario">Ver calendario</a>
  <a href="/admin/reservas/exportar" class="btn btn-secundario">Exportar CSV</a>
</div>

<div class="admin-panel">
  <div class="admin-tabla-wrap">
    <table class="admin-tabla">
      <thead><tr><th>Código</th><th>Cliente</th><th>Paquete</th><th>Salida</th><th>Personas</th><th>Estado</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($reservas as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['codigo_reserva'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($r['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($r['paquete_titulo'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= \App\Helpers\Fecha::corta($r['fecha_salida']) ?></td>
            <td><?= (int) $r['num_personas'] ?></td>
            <td>
              <?php $badge = ['pendiente' => 'ambar', 'confirmada' => 'verde', 'cancelada' => 'gris', 'expirada' => 'rojo'][$r['estado']]; ?>
              <span class="admin-badge admin-badge--<?= $badge ?>"><?= ucfirst($r['estado']) ?></span>
            </td>
            <td><a href="/admin/reservas/<?= (int) $r['id'] ?>" class="btn btn-secundario">Ver</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($reservas)): ?>
          <tr><td colspan="7">Aún no hay reservas registradas.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php $rutaBase = '/admin/reservas'; require __DIR__ . '/../../partials/paginacion.php'; ?>
</div>
