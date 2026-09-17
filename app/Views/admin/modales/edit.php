<?php /** @var array $modal */ /** @var array|null $paqueteActual */ /** @var array $paquetes */ /** @var array $alcances */ ?>
<div class="admin-panel">
  <form method="post" action="/admin/modales/<?= (int) $modal['id'] ?>/editar" enctype="multipart/form-data">
    <?php require __DIR__ . '/_form.php'; ?>
  </form>

  <?php if ($paqueteActual !== null): ?>
    <p class="mt-1"><a href="/paquetes/<?= htmlspecialchars($paqueteActual['slug'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Ver el paquete enlazado &rarr;</a></p>
  <?php endif; ?>
</div>
