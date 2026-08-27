<?php
/** @var array $registros */
/** @var list<string> $acciones */
/** @var string|null $accionActiva */
$acciones ??= [];
$accionActiva ??= null;
$queryExtra = $accionActiva !== null ? ['accion' => $accionActiva] : [];
?>
<div class="admin-acciones" style="margin-bottom:1.25rem;display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
  <form method="get" action="/admin/bitacora" style="display:flex;gap:0.4rem;align-items:center;">
    <label for="filtro-accion" style="font-size:0.9rem;">Accion:</label>
    <select id="filtro-accion" name="accion" data-autosubmit>
      <option value="">Todas</option>
      <?php foreach ($acciones as $a): ?>
        <option value="<?= htmlspecialchars($a, ENT_QUOTES, 'UTF-8') ?>" <?= $accionActiva === $a ? 'selected' : '' ?>><?= htmlspecialchars($a, ENT_QUOTES, 'UTF-8') ?></option>
      <?php endforeach; ?>
    </select>
    <noscript><button type="submit" class="btn btn-secundario">Filtrar</button></noscript>
  </form>
</div>

<div class="admin-panel">
  <div class="admin-tabla-wrap">
    <table class="admin-tabla">
      <thead><tr><th>Fecha</th><th>Usuario</th><th>Accion</th><th>Entidad</th><th>Detalle</th><th>IP</th></tr></thead>
      <tbody>
        <?php foreach ($registros as $r): ?>
          <tr>
            <td><?= date('d M Y H:i', strtotime($r['creado_en'])) ?></td>
            <td><?= htmlspecialchars($r['usuario_nombre'] ?? ('#' . ($r['usuario_id'] ?? '?')), ENT_QUOTES, 'UTF-8') ?></td>
            <td><code><?= htmlspecialchars($r['accion'], ENT_QUOTES, 'UTF-8') ?></code></td>
            <td>
              <?php if (!empty($r['entidad_tipo'])): ?>
                <?= htmlspecialchars($r['entidad_tipo'], ENT_QUOTES, 'UTF-8') ?><?= $r['entidad_id'] !== null ? ' #' . (int) $r['entidad_id'] : '' ?>
              <?php else: ?>
                &mdash;
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($r['detalle'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            <td><small><?= htmlspecialchars($r['ip'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($registros)): ?>
          <tr><td colspan="6">No hay registros para este filtro.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php $rutaBase = '/admin/bitacora'; require __DIR__ . '/../../partials/paginacion.php'; ?>
</div>
