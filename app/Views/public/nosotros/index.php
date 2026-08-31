<?php
/** @var array $bloques */

use App\Models\BloquePagina;

$bloquesPorClave = array_column($bloques, null, 'clave');
$introVisible = isset($bloquesPorClave['intro']) && (int) $bloquesPorClave['intro']['visible'] === 1;

// Ver nota en home/index.php: color de fondo por bloque -> <style nonce>, no style="" inline.
$bloquesColor = array_filter(
    $bloques,
    static fn ($b) => (int) $b['visible'] === 1 && !empty($b['color_fondo'])
        && preg_match('/^#[0-9a-f]{6}$/i', (string) $b['color_fondo']) === 1
);
?>
<?php if ($bloquesColor !== []): ?>
<style nonce="<?= CSP_NONCE ?>">
<?php foreach ($bloquesColor as $b): ?>
[data-bloque="<?= htmlspecialchars($b['clave'], ENT_QUOTES, 'UTF-8') ?>"]{background:<?= $b['color_fondo'] ?>}
<?php endforeach; ?>
</style>
<?php endif; ?>
<?php if (!$introVisible): ?>
  <section class="seccion contenedor">
    <h1>Nosotros</h1>
  </section>
<?php endif; ?>
<?php foreach ($bloques as $bloque): ?>
  <?php if ((int) $bloque['visible'] !== 1) continue; ?>
  <?php $contenido = BloquePagina::contenido($bloque); ?>

  <?php if ($bloque['clave'] === 'intro'): ?>
    <section class="seccion contenedor nosotros-intro">
      <div class="animar-entrada">
        <h1><?= htmlspecialchars($bloque['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars($bloque['subtitulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
        <?php if (!empty($contenido['parrafo_2'])): ?>
          <p><?= htmlspecialchars($contenido['parrafo_2'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
      </div>
      <img src="/assets/img/placeholders/nosotros-equipo.png" alt="Equipo Dream Go" class="nosotros-intro__img animar-entrada" loading="lazy">
    </section>

  <?php elseif ($bloque['clave'] === 'estadisticas'): ?>
    <section class="seccion contenedor seccion--panel" data-bloque="estadisticas">
      <div class="grid-tarjetas grid-tarjetas--3">
        <?php foreach (($contenido['items'] ?? []) as $item): ?>
          <div class="animar-entrada estadistica">
            <p class="estadistica__num"><?= htmlspecialchars($item['numero'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <p class="m-0"><?= htmlspecialchars($item['etiqueta'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

  <?php elseif ($bloque['clave'] === 'cta_final'): ?>
    <section class="seccion contenedor cta-final animar-entrada<?= $bloque['color_fondo'] ? ' seccion--panel' : '' ?>" data-bloque="cta_final">
      <h2><?= htmlspecialchars($bloque['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
      <?php if (!empty($bloque['subtitulo'])): ?><p><?= htmlspecialchars($bloque['subtitulo'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
      <?php if (!empty($contenido['boton_texto'])): ?>
        <a href="<?= htmlspecialchars($contenido['boton_link'] ?? '/cotizador', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primario"><?= htmlspecialchars($contenido['boton_texto'], ENT_QUOTES, 'UTF-8') ?></a>
      <?php endif; ?>
    </section>
  <?php endif; ?>
<?php endforeach; ?>
