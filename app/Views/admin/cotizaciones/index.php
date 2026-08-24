<?php /** @var array $cotizaciones */ ?>
<div class="admin-panel">
  <div class="admin-tabla-wrap">
    <table class="admin-tabla">
      <thead><tr><th>Fecha</th><th>Nombre</th><th>Contacto</th><th>Paquete</th><th>Estado</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php foreach ($cotizaciones as $c): ?>
          <tr>
            <td><?= date('d M Y H:i', strtotime($c['creado_en'])) ?></td>
            <td><?= htmlspecialchars($c['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($c['email'], ENT_QUOTES, 'UTF-8') ?><br><small><?= htmlspecialchars($c['telefono'], ENT_QUOTES, 'UTF-8') ?></small></td>
            <td><?= htmlspecialchars($c['paquete_titulo'] ?? 'General', ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <?php $badge = ['nueva' => 'ambar', 'contactada' => 'verde', 'convertida' => 'verde', 'descartada' => 'gris'][$c['estado']]; ?>
              <span class="admin-badge admin-badge--<?= $badge ?>"><?= ucfirst($c['estado']) ?></span>
            </td>
            <td>
              <form method="post" action="/admin/cotizaciones/<?= (int) $c['id'] ?>/estado" style="display:flex;gap:0.4rem;">
                <?= \App\Helpers\Csrf::field() ?>
                <select name="estado" data-autosubmit>
                  <?php foreach (['nueva', 'contactada', 'convertida', 'descartada'] as $estado): ?>
                    <option value="<?= $estado ?>" <?= $c['estado'] === $estado ? 'selected' : '' ?>><?= ucfirst($estado) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($cotizaciones)): ?>
          <tr><td colspan="6">Aun no hay cotizaciones.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php $rutaBase = '/admin/cotizaciones'; require __DIR__ . '/../../partials/paginacion.php'; ?>
</div>
