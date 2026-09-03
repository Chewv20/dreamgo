<?php /** @var array $articulo */ /** @var array $categorias */ /** @var array $estados */ ?>
<div class="admin-panel">
  <form method="post" action="/admin/articulos/<?= (int) $articulo['id'] ?>/editar" enctype="multipart/form-data">
    <?php require __DIR__ . '/_form.php'; ?>
  </form>

  <?php if ($articulo['estado'] === 'publicado'): ?>
    <p class="mt-1"><a href="/blog/<?= htmlspecialchars($articulo['slug'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Ver en el sitio &rarr;</a></p>
  <?php endif; ?>
</div>

<div class="admin-panel ancho-420">
  <form method="post" action="/admin/articulos/<?= (int) $articulo['id'] ?>/archivar" data-confirm="¿Archivar este articulo? Dejara de verse en el blog.">
    <?= \App\Helpers\Csrf::field() ?>
    <button type="submit" class="btn btn-secundario">Archivar artículo</button>
  </form>
</div>
