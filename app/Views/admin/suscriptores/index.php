<?php /** @var array $suscriptores */ ?>
<div class="admin-acciones mb-md">
  <a href="/admin/suscriptores/exportar" class="btn btn-secundario">Exportar CSV</a>
</div>

<div class="admin-panel">
  <div class="admin-tabla-wrap">
    <table class="admin-tabla">
      <thead><tr><th>Email</th><th>Estado</th><th>Fecha de alta</th></tr></thead>
      <tbody>
        <?php foreach ($suscriptores as $s): ?>
          <tr>
            <td><?= htmlspecialchars($s['email'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <?php $badge = ['pendiente' => 'ambar', 'confirmado' => 'verde', 'baja' => 'gris'][$s['estado']]; ?>
              <span class="admin-badge admin-badge--<?= $badge ?>"><?= ucfirst($s['estado']) ?></span>
            </td>
            <td><?= \App\Helpers\Fecha::corta($s['creado_en']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($suscriptores)): ?>
          <tr><td colspan="3">Aún no hay suscriptores registrados.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php $rutaBase = '/admin/suscriptores'; require __DIR__ . '/../../partials/paginacion.php'; ?>
</div>
