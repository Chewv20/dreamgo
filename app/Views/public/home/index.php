<?php
/** @var array $bloques */
/** @var array $destacados */
/** @var array $categorias */

use App\Models\BloquePagina;

$iconosSvg = [
    'chat' => '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5Z"/></svg>',
    'mapa' => '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/><line x1="9" y1="3" x2="9" y2="18"/><line x1="15" y1="6" x2="15" y2="21"/></svg>',
    'reloj' => '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15.5 14"/></svg>',
    'etiqueta' => '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41 11 3.83A2 2 0 0 0 9.59 3.24L4 3l.24 5.59a2 2 0 0 0 .59 1.41l9.58 9.58a2 2 0 0 0 2.83 0l3.35-3.35a2 2 0 0 0 0-2.82Z"/><circle cx="8.5" cy="8.5" r="1.5" fill="currentColor" stroke="none"/></svg>',
    'escudo' => '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 5v6c0 5 3.5 9 8 11 4.5-2 8-6 8-11V5l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg>',
    'estrella' => '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15 9 22 9.5 16.5 14 18.5 21 12 17 5.5 21 7.5 14 2 9.5 9 9"/></svg>',
];

$bloquesPorClave = array_column($bloques, null, 'clave');
$heroVisible = isset($bloquesPorClave['hero']) && (int) $bloquesPorClave['hero']['visible'] === 1;

// Colores de fondo por bloque: el admin los edita (validados como hex en ContenidoController),
// pero un style="" inline lo bloquea la CSP. Se emiten en un <style nonce> (mismo nonce por
// peticion que ya usa script-src). Fallback al token via .seccion--panel si no hay color.
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
<?php if (!$heroVisible): ?>
  <section class="seccion contenedor">
    <h1>Dream Go Operadora Turistica</h1>
  </section>
<?php endif; ?>
<?php foreach ($bloques as $bloque): ?>
  <?php if ((int) $bloque['visible'] !== 1) continue; ?>
  <?php $contenido = BloquePagina::contenido($bloque); ?>

  <?php if ($bloque['clave'] === 'hero'): ?>
    <section class="hero hero--inicio">
      <div class="contenedor">
        <div class="hero__contenido animar-entrada">
          <h1><?= htmlspecialchars($bloque['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
          <p><?= htmlspecialchars($bloque['subtitulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
          <div class="hero__acciones">
            <?php if (!empty($contenido['cta_primario_texto'])): ?>
              <a href="<?= htmlspecialchars($contenido['cta_primario_link'] ?? '/cotizador', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primario"><?= htmlspecialchars($contenido['cta_primario_texto'], ENT_QUOTES, 'UTF-8') ?></a>
            <?php endif; ?>
            <?php if (!empty($contenido['cta_secundario_texto'])): ?>
              <a href="<?= htmlspecialchars($contenido['cta_secundario_link'] ?? '/paquetes', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secundario"><?= htmlspecialchars($contenido['cta_secundario_texto'], ENT_QUOTES, 'UTF-8') ?></a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>

  <?php elseif ($bloque['clave'] === 'ventajas'): ?>
    <section class="seccion contenedor<?= $bloque['color_fondo'] ? ' seccion--panel' : '' ?>" data-bloque="<?= htmlspecialchars($bloque['clave'], ENT_QUOTES, 'UTF-8') ?>">
      <div class="seccion__encabezado">
        <h2><?= htmlspecialchars($bloque['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
        <?php if (!empty($bloque['subtitulo'])): ?><p><?= htmlspecialchars($bloque['subtitulo'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
      </div>
      <div class="grid-tarjetas grid-tarjetas--4">
        <?php foreach (($contenido['items'] ?? []) as $item): ?>
          <div class="tarjeta-ventaja animar-entrada">
            <span class="tarjeta-ventaja__icono" aria-hidden="true"><?= $iconosSvg[$item['icono'] ?? 'chat'] ?? $iconosSvg['chat'] ?></span>
            <h3><?= htmlspecialchars($item['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
            <p><?= htmlspecialchars($item['texto'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

  <?php elseif ($bloque['clave'] === 'destinos' && !empty($categorias)): ?>
    <section class="seccion contenedor<?= $bloque['color_fondo'] ? ' seccion--panel' : '' ?>" data-bloque="<?= htmlspecialchars($bloque['clave'], ENT_QUOTES, 'UTF-8') ?>">
      <div class="seccion__encabezado">
        <h2><?= htmlspecialchars($bloque['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
        <?php if (!empty($bloque['subtitulo'])): ?><p><?= htmlspecialchars($bloque['subtitulo'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
      </div>
      <div class="grid-tarjetas">
        <?php foreach ($categorias as $categoria): ?>
          <a href="/destinos/<?= htmlspecialchars($categoria['slug'], ENT_QUOTES, 'UTF-8') ?>" class="tarjeta tarjeta-destino animar-entrada">
            <p class="tarjeta-destino__etiqueta">
              <?= $categoria['tipo'] === 'internacional' ? 'Internacional' : 'Nacional' ?>
            </p>
            <h3><?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="tarjeta-destino__descripcion"><?= htmlspecialchars($categoria['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
          </a>
        <?php endforeach; ?>
      </div>
    </section>

  <?php elseif ($bloque['clave'] === 'paquetes_destacados' && !empty($destacados)): ?>
    <section class="seccion contenedor seccion--panel" data-bloque="paquetes_destacados">
      <div class="seccion__encabezado">
        <h2><?= htmlspecialchars($bloque['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
        <?php if (!empty($bloque['subtitulo'])): ?><p><?= htmlspecialchars($bloque['subtitulo'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
      </div>
      <div class="grid-tarjetas">
        <?php foreach ($destacados as $paquete): ?>
          <?php require __DIR__ . '/../paquetes/_tarjeta.php'; ?>
        <?php endforeach; ?>
      </div>
      <div class="centrado mt-2">
        <a href="/paquetes" class="btn btn-secundario">Ver todos los paquetes</a>
      </div>
    </section>

  <?php elseif ($bloque['clave'] === 'testimonios'): ?>
    <section class="seccion contenedor<?= $bloque['color_fondo'] ? ' seccion--panel' : '' ?>" data-bloque="<?= htmlspecialchars($bloque['clave'], ENT_QUOTES, 'UTF-8') ?>">
      <div class="seccion__encabezado">
        <h2><?= htmlspecialchars($bloque['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
        <?php if (!empty($bloque['subtitulo'])): ?><p><?= htmlspecialchars($bloque['subtitulo'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
      </div>
      <div class="grid-tarjetas grid-tarjetas--3">
        <?php foreach (($contenido['items'] ?? []) as $item): ?>
          <figure class="tarjeta-testimonio animar-entrada">
            <span class="tarjeta-testimonio__avatar" aria-hidden="true"><?= htmlspecialchars($item['inicial'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            <blockquote>&quot;<?= htmlspecialchars($item['texto'] ?? '', ENT_QUOTES, 'UTF-8') ?>&quot;</blockquote>
            <figcaption><?= htmlspecialchars($item['autor'] ?? '', ENT_QUOTES, 'UTF-8') ?></figcaption>
          </figure>
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

<section class="seccion contenedor" id="newsletter">
  <div class="seccion__encabezado">
    <h2>No te pierdas nuestras ofertas</h2>
    <p>Suscribete y enterate primero de descuentos y paquetes nuevos.</p>
  </div>
  <form method="post" action="/suscribir" class="newsletter-form">
    <?= \App\Helpers\Csrf::field() ?>
    <input type="email" name="email" autocomplete="email" required placeholder="tu@correo.com">
    <button type="submit" class="btn btn-primario">Suscribirme</button>
  </form>
</section>
