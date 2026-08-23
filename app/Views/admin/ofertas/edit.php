<?php /** @var array $oferta */ /** @var array $paquetes */ ?>
<div class="admin-panel">
  <form method="post" action="/admin/ofertas/<?= (int) $oferta['id'] ?>/editar">
    <?php require __DIR__ . '/_form.php'; ?>
  </form>
</div>
