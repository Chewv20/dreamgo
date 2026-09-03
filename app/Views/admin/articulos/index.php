<?php
/** @var array $articulos */
/** @var \Core\Paginator $paginador */
?>
<div class="admin-acciones mb-md">
  <a href="/admin/articulos/crear" class="btn btn-primario">Nuevo artículo</a>
</div>

<div class="admin-panel">
  <div class="admin-tabla-wrap">
    <table class="admin-tabla">
      <thead><tr><th>Título</th><th>Destino</th><th>Estado</th><th>Publicado</th><th>Autor</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($articulos as $a): ?>
          <tr>
            <td><?= htmlspecialchars($a['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($a['categoria_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?: '<small class="txt-mute">&mdash;</small>' ?></td>
            <td>
              <?php $badge = ['publicado' => 'verde', 'borrador' => 'ambar', 'archivado' => 'gris'][$a['estado']]; ?>
              <span class="admin-badge admin-badge--<?= $badge ?>"><?= ucfirst($a['estado']) ?></span>
            </td>
            <td><?= $a['publicado_en'] ? \App\Helpers\Fecha::corta($a['publicado_en']) : '&mdash;' ?></td>
            <td><?= htmlspecialchars($a['autor_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            <td class="admin-acciones"><a href="/admin/articulos/<?= (int) $a['id'] ?>/editar" class="btn btn-secundario">Editar</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($articulos)): ?>
          <tr><td colspan="6">Todavía no hay artículos.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php $rutaBase = '/admin/articulos'; require __DIR__ . '/../../partials/paginacion.php'; ?>
</div>
