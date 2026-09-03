<?php
/**
 * Migas de pan visuales.
 * @var array<int, array{texto: string, url?: string|null}> $migas
 *   El ultimo elemento es la pagina actual (sin url).
 */
if (empty($migas)) {
    return;
}
?>
<nav class="breadcrumbs" aria-label="Ruta de navegación">
  <ol>
    <?php foreach ($migas as $i => $miga): ?>
      <li>
        <?php if (!empty($miga['url']) && $i < count($migas) - 1): ?>
          <a href="<?= htmlspecialchars($miga['url'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($miga['texto'], ENT_QUOTES, 'UTF-8') ?></a>
        <?php else: ?>
          <span aria-current="page"><?= htmlspecialchars($miga['texto'], ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ol>
</nav>
