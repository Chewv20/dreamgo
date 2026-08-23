<?php
/** @var \Core\Paginator $paginador */
/** @var string $rutaBase */
/** @var array<string, string> $queryExtra */
$queryExtra ??= [];

if ($paginador->totalPaginas > 1):
    $construirUrl = fn (int $pagina): string => $rutaBase . '?' . http_build_query([...$queryExtra, 'pagina' => $pagina]);
?>
<nav class="paginacion" aria-label="Paginacion de resultados">
  <?php if ($paginador->tieneAnterior()): ?>
    <a href="<?= htmlspecialchars($construirUrl($paginador->pagina - 1), ENT_QUOTES, 'UTF-8') ?>" class="paginacion__enlace">&laquo; Anterior</a>
  <?php else: ?>
    <span class="paginacion__enlace paginacion__enlace--deshabilitado" aria-disabled="true">&laquo; Anterior</span>
  <?php endif; ?>

  <span class="paginacion__estado">Pagina <?= $paginador->pagina ?> de <?= $paginador->totalPaginas ?></span>

  <?php if ($paginador->tieneSiguiente()): ?>
    <a href="<?= htmlspecialchars($construirUrl($paginador->pagina + 1), ENT_QUOTES, 'UTF-8') ?>" class="paginacion__enlace">Siguiente &raquo;</a>
  <?php else: ?>
    <span class="paginacion__enlace paginacion__enlace--deshabilitado" aria-disabled="true">Siguiente &raquo;</span>
  <?php endif; ?>
</nav>
<?php endif; ?>
