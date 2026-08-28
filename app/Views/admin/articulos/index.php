<?php
/** @var array $articulos */
/** @var \Core\Paginator $paginador */
?>
<div class="admin-acciones" style="margin-bottom:1.25rem;">
  <a href="/admin/articulos/crear" class="btn btn-primario">Nuevo articulo</a>
</div>

<div class="admin-panel">
  <div class="admin-tabla-wrap">
    <table class="admin-tabla">
      <thead><tr><th>Titulo</th><th>Destino</th><th>Estado</th><th>Publicado</th><th>Autor</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($articulos as $a): ?>
          <tr>
            <td><?= htmlspecialchars($a['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($a['categoria_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?: '<small style="color:#888;">&mdash;</small>' ?></td>
            <td>
              <?php $badge = ['publicado' => 'verde', 'borrador' => 'ambar', 'archivado' => 'gris'][$a['estado']]; ?>
              <span class="admin-badge admin-badge--<?= $badge ?>"><?= ucfirst($a['estado']) ?></span>
            </td>
            <td><?= $a['publicado_en'] ? date('d M Y', strtotime($a['publicado_en'])) : '&mdash;' ?></td>
            <td><?= htmlspecialchars($a['autor_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            <td class="admin-acciones"><a href="/admin/articulos/<?= (int) $a['id'] ?>/editar" class="btn btn-secundario">Editar</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($articulos)): ?>
          <tr><td colspan="6">Todavia no hay articulos.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php $rutaBase = '/admin/articulos'; require __DIR__ . '/../../partials/paginacion.php'; ?>
</div>
