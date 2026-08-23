<?php
/** @var array $bloques */

use App\Models\BloquePagina;

$bloquesPorClave = array_column($bloques, null, 'clave');
$introVisible = isset($bloquesPorClave['intro']) && (int) $bloquesPorClave['intro']['visible'] === 1;
?>
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
    <section class="seccion contenedor" style="background:<?= htmlspecialchars($bloque['color_fondo'] ?: 'var(--color-fondo-alterno)', ENT_QUOTES, 'UTF-8') ?>;border-radius:var(--radio);">
      <div class="grid-tarjetas grid-tarjetas--3">
        <?php foreach (($contenido['items'] ?? []) as $item): ?>
          <div class="animar-entrada" style="text-align:center;padding:1.5rem;">
            <p style="font-family:var(--fuente-titulos);font-size:2.5rem;color:var(--color-primario-oscuro);margin-bottom:0.25rem;"><?= htmlspecialchars($item['numero'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <p style="margin:0;"><?= htmlspecialchars($item['etiqueta'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

  <?php elseif ($bloque['clave'] === 'cta_final'): ?>
    <section class="seccion contenedor cta-final animar-entrada" <?= $bloque['color_fondo'] ? 'style="background:' . htmlspecialchars($bloque['color_fondo'], ENT_QUOTES, 'UTF-8') . ';border-radius:var(--radio);"' : '' ?>>
      <h2><?= htmlspecialchars($bloque['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
      <?php if (!empty($bloque['subtitulo'])): ?><p><?= htmlspecialchars($bloque['subtitulo'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
      <?php if (!empty($contenido['boton_texto'])): ?>
        <a href="<?= htmlspecialchars($contenido['boton_link'] ?? '/cotizador', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primario"><?= htmlspecialchars($contenido['boton_texto'], ENT_QUOTES, 'UTF-8') ?></a>
      <?php endif; ?>
    </section>
  <?php endif; ?>
<?php endforeach; ?>
