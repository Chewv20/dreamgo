<?php
/** @var array $paquete */
/** @var array $imagenes */
/** @var array $salidas */
use App\Models\ConfiguracionSitio;
use App\Services\WhatsAppLinkService;

$whatsapp = (new WhatsAppLinkService())->generarLinkCotizacionPaquete(
    ConfiguracionSitio::get('whatsapp_numero', ''),
    $paquete['titulo']
);
?>
<section class="hero" style="min-height:36vh;">
  <div class="contenedor">
    <p style="text-transform:uppercase;letter-spacing:0.04em;font-weight:600;opacity:0.9;">
      <?= htmlspecialchars($paquete['categoria_nombre'], ENT_QUOTES, 'UTF-8') ?>
    </p>
    <h1><?= htmlspecialchars($paquete['titulo'], ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars($paquete['resumen'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
  </div>
</section>

<section class="seccion contenedor paquete-detalle">
  <div>
    <?php if (!empty($imagenes) && count($imagenes) > 1): ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:0.75rem;margin-bottom:2rem;">
        <?php foreach ($imagenes as $img): ?>
          <img src="<?= htmlspecialchars($img['ruta_thumb'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($img['alt_text'] ?? $paquete['titulo'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" style="border-radius:var(--radio);aspect-ratio:3/2;object-fit:cover;">
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <h2>Itinerario</h2>
    <div><?= $paquete['itinerario'] ?? '<p>Itinerario disponible pronto.</p>' ?></div>

    <div style="display:grid;grid-template-columns:1fr;gap:1.5rem;margin-top:2rem;">
      <div>
        <h3>Incluye</h3>
        <?= $paquete['incluye'] ?? '<p>-</p>' ?>
      </div>
      <div>
        <h3>No incluye</h3>
        <?= $paquete['no_incluye'] ?? '<p>-</p>' ?>
      </div>
    </div>
  </div>

  <aside class="tarjeta" style="padding:1.5rem;align-self:start;">
    <p style="font-family:var(--fuente-titulos);font-size:1.75rem;margin-bottom:0.25rem;">
      Desde $<?= number_format((float) $paquete['precio_desde'], 0, '.', ',') ?> <?= htmlspecialchars($paquete['moneda'], ENT_QUOTES, 'UTF-8') ?>
    </p>
    <p style="opacity:0.75;margin-bottom:1.5rem;"><?= (int) $paquete['duracion_dias'] ?> dias / <?= (int) $paquete['duracion_noches'] ?> noches</p>

    <label class="tarjeta__comparar" style="display:block;margin-bottom:1rem;">
      <input type="checkbox" data-comparar-slug="<?= htmlspecialchars($paquete['slug'], ENT_QUOTES, 'UTF-8') ?>" data-comparar-titulo="<?= htmlspecialchars($paquete['titulo'], ENT_QUOTES, 'UTF-8') ?>">
      Agregar a comparar
    </label>

    <h3 style="font-size:1.1rem;">Proximas salidas</h3>
    <?php if (empty($salidas)): ?>
      <p>Consulta disponibilidad con nuestro equipo.</p>
    <?php else: ?>
      <ul style="list-style:none;padding:0;margin:0 0 1.5rem;">
        <?php foreach ($salidas as $salida): ?>
          <li style="display:flex;justify-content:space-between;align-items:center;gap:0.75rem;padding:0.5rem 0;border-bottom:1px solid var(--color-borde);">
            <span><?= date('d M Y', strtotime($salida['fecha_salida'])) ?></span>
            <span style="font-size:0.85rem;opacity:0.75;"><?= (int) $salida['cupo_disponible'] ?> cupos</span>
            <?php if ($salida['estado'] === 'abierta' && (int) $salida['cupo_disponible'] > 0): ?>
              <a href="/paquetes/<?= urlencode($paquete['slug']) ?>/reservar/<?= (int) $salida['id'] ?>" class="btn btn-primario" style="padding:0.35rem 0.85rem;font-size:0.85rem;">Reservar</a>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <a href="/cotizador?paquete=<?= urlencode($paquete['slug']) ?>" class="btn btn-primario" style="width:100%;margin-bottom:0.75rem;">Solicitar cotizacion</a>
    <a href="<?= htmlspecialchars($whatsapp, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-whatsapp" style="width:100%;" target="_blank" rel="noopener">Cotizar por WhatsApp</a>
  </aside>
</section>

<?php if (!empty($resenas)): ?>
<section class="seccion contenedor">
  <div class="seccion__encabezado">
    <h2>Lo que dicen nuestros viajeros</h2>
  </div>
  <div class="grid-tarjetas grid-tarjetas--3">
    <?php foreach ($resenas as $r): ?>
      <?php
        $partesNombre = explode(' ', trim($r['cliente_nombre']));
        $nombrePublico = $partesNombre[0] . (isset($partesNombre[1]) ? ' ' . mb_strtoupper(mb_substr($partesNombre[1], 0, 1)) . '.' : '');
      ?>
      <figure class="tarjeta-testimonio animar-entrada">
        <span class="tarjeta-testimonio__avatar" aria-hidden="true"><?= htmlspecialchars(mb_strtoupper(mb_substr($partesNombre[0], 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
        <p style="color:#a85f4d;letter-spacing:0.1em;margin:0 0 0.5rem;"><?= str_repeat('★', (int) $r['calificacion']) . str_repeat('☆', 5 - (int) $r['calificacion']) ?></p>
        <blockquote>&quot;<?= htmlspecialchars($r['comentario'], ENT_QUOTES, 'UTF-8') ?>&quot;</blockquote>
        <figcaption><?= htmlspecialchars($nombrePublico, ENT_QUOTES, 'UTF-8') ?></figcaption>
      </figure>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
