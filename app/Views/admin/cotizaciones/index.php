<?php
/** @var array $cotizaciones */
/** @var list<string> $fuentes */
/** @var string|null $origenActivo */
$fuentes ??= [];
$origenActivo ??= null;
$queryExtra = $origenActivo !== null ? ['origen' => $origenActivo] : [];
?>
<div class="admin-acciones" style="margin-bottom:1.25rem;display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
  <a href="/admin/cotizaciones/exportar" class="btn btn-secundario">Exportar CSV</a>

  <form method="get" action="/admin/cotizaciones" style="display:flex;gap:0.4rem;align-items:center;">
    <label for="filtro-origen" style="font-size:0.9rem;">Origen:</label>
    <select id="filtro-origen" name="origen" data-autosubmit>
      <option value="">Todos</option>
      <option value="<?= htmlspecialchars(\App\Models\Cotizacion::ORIGEN_DIRECTO, ENT_QUOTES, 'UTF-8') ?>" <?= $origenActivo === \App\Models\Cotizacion::ORIGEN_DIRECTO ? 'selected' : '' ?>>Directo / sin UTM</option>
      <?php foreach ($fuentes as $fuente): ?>
        <option value="<?= htmlspecialchars($fuente, ENT_QUOTES, 'UTF-8') ?>" <?= $origenActivo === $fuente ? 'selected' : '' ?>><?= htmlspecialchars($fuente, ENT_QUOTES, 'UTF-8') ?></option>
      <?php endforeach; ?>
    </select>
    <noscript><button type="submit" class="btn btn-secundario">Filtrar</button></noscript>
  </form>
</div>

<div class="admin-panel">
  <div class="admin-tabla-wrap">
    <table class="admin-tabla">
      <thead><tr><th>Fecha</th><th>Nombre</th><th>Contacto</th><th>Paquete</th><th>Origen</th><th>Estado</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php foreach ($cotizaciones as $c): ?>
          <tr>
            <td><?= date('d M Y H:i', strtotime($c['creado_en'])) ?></td>
            <td><?= htmlspecialchars($c['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($c['email'], ENT_QUOTES, 'UTF-8') ?><br><small><?= htmlspecialchars($c['telefono'], ENT_QUOTES, 'UTF-8') ?></small></td>
            <td><?= htmlspecialchars($c['paquete_titulo'] ?? 'General', ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <?php if (!empty($c['utm_source'])): ?>
                <?= htmlspecialchars($c['utm_source'], ENT_QUOTES, 'UTF-8') ?>
                <?php if (!empty($c['utm_medium']) || !empty($c['utm_campaign'])): ?>
                  <br><small><?= htmlspecialchars(trim(($c['utm_medium'] ?? '') . ' / ' . ($c['utm_campaign'] ?? ''), ' /'), ENT_QUOTES, 'UTF-8') ?></small>
                <?php endif; ?>
              <?php else: ?>
                <small style="color:#888;">Directo</small>
              <?php endif; ?>
            </td>
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
          <tr><td colspan="7">No hay cotizaciones para este filtro.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php $rutaBase = '/admin/cotizaciones'; require __DIR__ . '/../../partials/paginacion.php'; ?>
</div>
