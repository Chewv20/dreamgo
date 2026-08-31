<?php /** @var array $resenas */ ?>
<div class="admin-panel">
  <div class="admin-tabla-wrap">
    <table class="admin-tabla">
      <thead><tr><th>Fecha</th><th>Cliente</th><th>Paquete</th><th>Calificacion</th><th>Comentario</th><th>Estado</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php foreach ($resenas as $r): ?>
          <tr>
            <td><?= \App\Helpers\Fecha::corta($r['creado_en']) ?></td>
            <td><?= htmlspecialchars($r['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($r['paquete_titulo'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= str_repeat('★', (int) $r['calificacion']) . str_repeat('☆', 5 - (int) $r['calificacion']) ?></td>
            <td style="max-width:320px;"><?= htmlspecialchars(mb_strimwidth($r['comentario'], 0, 140, '...'), ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <?php $badge = ['pendiente' => 'ambar', 'aprobada' => 'verde', 'rechazada' => 'rojo'][$r['estado']]; ?>
              <span class="admin-badge admin-badge--<?= $badge ?>"><?= ucfirst($r['estado']) ?></span>
            </td>
            <td>
              <form method="post" action="/admin/resenas/<?= (int) $r['id'] ?>/estado" style="display:flex;gap:0.4rem;">
                <?= \App\Helpers\Csrf::field() ?>
                <select name="estado" data-autosubmit>
                  <?php foreach (['pendiente', 'aprobada', 'rechazada'] as $estado): ?>
                    <option value="<?= $estado ?>" <?= $r['estado'] === $estado ? 'selected' : '' ?>><?= ucfirst($estado) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($resenas)): ?>
          <tr><td colspan="7">Aun no hay resenas registradas.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php $rutaBase = '/admin/resenas'; require __DIR__ . '/../../partials/paginacion.php'; ?>
</div>
