<div class="admin-panel">
  <div class="cal-nav">
    <button type="button" class="btn btn-secundario" id="cal-prev">&larr; Anterior</button>
    <h2 id="cal-titulo" class="m-0"></h2>
    <button type="button" class="btn btn-secundario" id="cal-next">Siguiente &rarr;</button>
  </div>
  <div id="cal-grid" class="cal-grid"></div>
</div>

<script src="<?= htmlspecialchars(\App\Helpers\Asset::url('/assets/js/admin-calendario.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
