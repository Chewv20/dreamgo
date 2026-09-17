<?php
/** @var array $modales */
/** @var array<string,string> $alcances */
?>
<div class="admin-acciones mb-md">
  <a href="/admin/modales/crear" class="btn btn-primario">Nuevo modal</a>
</div>

<div class="admin-panel">
  <div class="admin-tabla-wrap">
    <table class="admin-tabla">
      <thead><tr><th>Prioridad</th><th>Título</th><th>Paquete</th><th>Dónde</th><th>Vigencia</th><th>Estado</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($modales as $m): ?>
          <?php
          $activo = (int) $m['activo'] === 1;
          $desde = $m['fecha_inicio'] ?? null;
          $hasta = $m['fecha_fin'] ?? null;
          $vigencia = ($desde === null && $hasta === null)
              ? 'Siempre'
              : ($desde ?? '…') . ' → ' . ($hasta ?? '…');
          ?>
          <tr>
            <td><?= (int) $m['prioridad'] ?></td>
            <td><?= htmlspecialchars($m['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <?= htmlspecialchars($m['paquete_titulo'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
              <?php if (($m['paquete_estado'] ?? '') !== 'publicado'): ?>
                <span class="admin-badge admin-badge--gris"><?= htmlspecialchars($m['paquete_estado'] ?? 'sin paquete', ENT_QUOTES, 'UTF-8') ?></span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($alcances[$m['mostrar_en']] ?? $m['mostrar_en'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($vigencia, ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <span class="admin-badge admin-badge--<?= $activo ? 'verde' : 'gris' ?>"><?= $activo ? 'Visible' : 'Oculto' ?></span>
            </td>
            <td class="admin-acciones">
              <a href="/admin/modales/<?= (int) $m['id'] ?>/editar" class="btn btn-secundario">Editar</a>
              <form method="post" action="/admin/modales/<?= (int) $m['id'] ?>/visible">
                <?= \App\Helpers\Csrf::field() ?>
                <button type="submit" class="btn btn-secundario"><?= $activo ? 'Ocultar' : 'Mostrar' ?></button>
              </form>
              <form method="post" action="/admin/modales/<?= (int) $m['id'] ?>/eliminar" data-confirm="¿Eliminar este modal? No se puede deshacer.">
                <?= \App\Helpers\Csrf::field() ?>
                <button type="submit" class="btn btn-secundario btn--xs">Eliminar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($modales)): ?>
          <tr><td colspan="7">Todavía no hay modales. Crea el primero.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
