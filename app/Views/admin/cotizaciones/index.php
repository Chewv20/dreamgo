<?php
/** @var array $cotizaciones */
/** @var list<string> $fuentes */
/** @var array $asesores */
/** @var array $filtros */
$fuentes ??= [];
$asesores ??= [];
$filtros ??= [];
$origenActivo = $filtros['origen'] ?? null;
$asignadoActivo = $filtros['asignado'] ?? null;
$seguimientoActivo = $filtros['seguimiento'] ?? null;
$queryExtra = array_filter([
    'origen' => $origenActivo,
    'asignado' => $asignadoActivo,
    'seguimiento' => $seguimientoActivo,
], static fn ($v) => $v !== null && $v !== '');
$hoy = date('Y-m-d');
?>
<div class="admin-filtros">
  <a href="/admin/cotizaciones/exportar" class="btn btn-secundario">Exportar CSV</a>

  <form method="get" action="/admin/cotizaciones">
    <label class="label-sm">Origen:
      <select name="origen" data-autosubmit>
        <option value="">Todos</option>
        <option value="<?= htmlspecialchars(\App\Models\Cotizacion::ORIGEN_DIRECTO, ENT_QUOTES, 'UTF-8') ?>" <?= $origenActivo === \App\Models\Cotizacion::ORIGEN_DIRECTO ? 'selected' : '' ?>>Directo / sin UTM</option>
        <?php foreach ($fuentes as $fuente): ?>
          <option value="<?= htmlspecialchars($fuente, ENT_QUOTES, 'UTF-8') ?>" <?= $origenActivo === $fuente ? 'selected' : '' ?>><?= htmlspecialchars($fuente, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="label-sm">Asesor:
      <select name="asignado" data-autosubmit>
        <option value="">Todos</option>
        <option value="<?= htmlspecialchars(\App\Models\Cotizacion::SIN_ASIGNAR, ENT_QUOTES, 'UTF-8') ?>" <?= $asignadoActivo === \App\Models\Cotizacion::SIN_ASIGNAR ? 'selected' : '' ?>>Sin asignar</option>
        <?php foreach ($asesores as $asesor): ?>
          <option value="<?= (int) $asesor['id'] ?>" <?= (string) $asignadoActivo === (string) $asesor['id'] ? 'selected' : '' ?>><?= htmlspecialchars($asesor['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="label-sm label-check">
      <input type="checkbox" name="seguimiento" value="vencidos" data-autosubmit <?= $seguimientoActivo === 'vencidos' ? 'checked' : '' ?>>
      Solo seguimientos vencidos
    </label>
    <noscript><button type="submit" class="btn btn-secundario">Filtrar</button></noscript>
  </form>
</div>

<div class="admin-panel">
  <div class="admin-tabla-wrap">
    <table class="admin-tabla">
      <thead><tr><th>Fecha</th><th>Nombre</th><th>Contacto</th><th>Paquete</th><th>Origen</th><th>Asesor</th><th>Estado</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($cotizaciones as $c): ?>
          <?php $vencida = !empty($c['seguimiento_en']) && $c['seguimiento_en'] < $hoy && in_array($c['estado'], ['nueva', 'contactada'], true); ?>
          <tr>
            <td><?= \App\Helpers\Fecha::cortaHora($c['creado_en']) ?></td>
            <td><?= htmlspecialchars($c['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($c['email'], ENT_QUOTES, 'UTF-8') ?><br><small><?= htmlspecialchars($c['telefono'], ENT_QUOTES, 'UTF-8') ?></small></td>
            <td><?= htmlspecialchars($c['paquete_titulo'] ?? 'General', ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <?php if (!empty($c['utm_source'])): ?>
                <?= htmlspecialchars($c['utm_source'], ENT_QUOTES, 'UTF-8') ?>
              <?php else: ?>
                <small class="txt-mute">Directo</small>
              <?php endif; ?>
            </td>
            <td>
              <?= htmlspecialchars($c['asignado_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?: '<small class="txt-mute">&mdash;</small>' ?>
              <?php if ($vencida): ?>
                <br><span class="admin-badge admin-badge--rojo">Seguimiento vencido</span>
              <?php endif; ?>
            </td>
            <td>
              <?php $badge = ['nueva' => 'ambar', 'contactada' => 'verde', 'convertida' => 'verde', 'descartada' => 'gris'][$c['estado']]; ?>
              <span class="admin-badge admin-badge--<?= $badge ?>"><?= ucfirst($c['estado']) ?></span>
            </td>
            <td><a href="/admin/cotizaciones/<?= (int) $c['id'] ?>" class="btn btn-secundario">Ver</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($cotizaciones)): ?>
          <tr><td colspan="8">No hay cotizaciones para este filtro.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php $rutaBase = '/admin/cotizaciones'; require __DIR__ . '/../../partials/paginacion.php'; ?>
</div>
