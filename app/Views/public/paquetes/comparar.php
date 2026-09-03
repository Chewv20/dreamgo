<?php
/** @var array $paquetes */

$aplanarTexto = static function (?string $html, int $largo = 240): string {
    $texto = trim(strip_tags((string) $html));

    return $texto === '' ? '-' : mb_strimwidth($texto, 0, $largo, '...');
};
?>
<section class="seccion contenedor">
  <h1>Comparar paquetes</h1>

  <?php if (count($paquetes) < 2): ?>
    <?php
    $titulo = 'Nada que comparar todavía';
    $texto = 'Selecciona al menos 2 paquetes desde el catálogo (marca la casilla "Comparar" en cada tarjeta) para verlos lado a lado.';
    $cta = ['url' => '/paquetes', 'texto' => 'Ir al catálogo'];
    require __DIR__ . '/../../partials/_estado_vacio.php';
    ?>
  <?php else: ?>
    <div class="tabla-comparativa-wrap">
      <table class="tabla-comparativa">
        <thead>
          <tr>
            <th></th>
            <?php foreach ($paquetes as $p): ?>
              <th>
                <img
                  src="<?= htmlspecialchars($p['imagen_portada'] ?? '/assets/img/logo.avif', ENT_QUOTES, 'UTF-8') ?>"
                  alt="<?= htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8') ?>"
                  loading="lazy" class="img-comparar"
                >
                <a href="/paquetes/<?= htmlspecialchars($p['slug'], ENT_QUOTES, 'UTF-8') ?>" class="d-block mt-05"><?= htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8') ?></a>
              </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Categoría</td>
            <?php foreach ($paquetes as $p): ?><td><?= htmlspecialchars($p['categoria_nombre'], ENT_QUOTES, 'UTF-8') ?></td><?php endforeach; ?>
          </tr>
          <tr>
            <td>Precio desde</td>
            <?php foreach ($paquetes as $p): ?><td>$<?= number_format((float) $p['precio_desde'], 0, '.', ',') ?> <?= htmlspecialchars($p['moneda'], ENT_QUOTES, 'UTF-8') ?></td><?php endforeach; ?>
          </tr>
          <tr>
            <td>Duración</td>
            <?php foreach ($paquetes as $p): ?><td><?= (int) $p['duracion_dias'] ?> días / <?= (int) $p['duracion_noches'] ?> noches</td><?php endforeach; ?>
          </tr>
          <tr>
            <td>Incluye</td>
            <?php foreach ($paquetes as $p): ?><td><?= htmlspecialchars($aplanarTexto($p['incluye'] ?? null), ENT_QUOTES, 'UTF-8') ?></td><?php endforeach; ?>
          </tr>
          <tr>
            <td>No incluye</td>
            <?php foreach ($paquetes as $p): ?><td><?= htmlspecialchars($aplanarTexto($p['no_incluye'] ?? null), ENT_QUOTES, 'UTF-8') ?></td><?php endforeach; ?>
          </tr>
          <tr>
            <td></td>
            <?php foreach ($paquetes as $p): ?><td><a href="/paquetes/<?= htmlspecialchars($p['slug'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primario">Ver paquete</a></td><?php endforeach; ?>
          </tr>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
