<?php /** @var array $destino */ /** @var array $tipos */ ?>
<div class="admin-panel">
  <form method="post" action="/admin/destinos/<?= (int) $destino['id'] ?>/editar" enctype="multipart/form-data">
    <?php require __DIR__ . '/_form.php'; ?>
  </form>

  <?php if ((int) $destino['activo'] === 1): ?>
    <p class="mt-1"><a href="/destinos/<?= htmlspecialchars($destino['slug'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Ver en el sitio &rarr;</a></p>
  <?php endif; ?>
</div>
