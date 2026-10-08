<?php /** @var array $paquete */ /** @var array $categorias */ /** @var array $imagenes */ ?>
<div class="admin-filtros">
  <a href="/admin/paquetes" class="admin-volver m-0">&larr; Paquetes</a>
  <?php if (\Core\Auth::hasPermission('salidas.gestionar')): ?>
    <a href="/admin/paquetes/<?= (int) $paquete['id'] ?>/salidas" class="btn btn-secundario">Fechas y cupos</a>
  <?php endif; ?>
  <?php if (($paquete['estado'] ?? '') === 'publicado'): ?>
    <a href="/paquetes/<?= urlencode((string) $paquete['slug']) ?>" class="btn btn-secundario" target="_blank" rel="noopener">Ver en el sitio ↗</a>
  <?php endif; ?>
  <?php if (\Core\Auth::hasPermission('paquetes.eliminar') && ($paquete['estado'] ?? '') !== 'archivado'): ?>
    <form method="post" action="/admin/paquetes/<?= (int) $paquete['id'] ?>/archivar" class="admin-filtros__fin" data-confirm="¿Archivar «<?= htmlspecialchars((string) $paquete['titulo'], ENT_QUOTES, 'UTF-8') ?>»? Dejará de mostrarse en el sitio público. Puedes volver a publicarlo después cambiando su estado.">
      <?= \App\Helpers\Csrf::field() ?>
      <button type="submit" class="btn btn-peligro">Archivar paquete</button>
    </form>
  <?php endif; ?>
</div>

<div class="admin-panel">
  <form method="post" action="/admin/paquetes/<?= (int) $paquete['id'] ?>/editar" enctype="multipart/form-data">
    <?php require __DIR__ . '/_form.php'; ?>
  </form>
</div>

<?php
$galeriaBase = '/admin/paquetes/' . (int) $paquete['id'];
require __DIR__ . '/../partials/_galeria_manager.php';
?>
