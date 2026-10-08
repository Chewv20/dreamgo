<?php
/** @var array $paquete */
/** @var array $salida */
/** @var float $precioUnitario */
/** @var int $porcentajeAnticipo */
/** @var array $errores */
/** @var array $valores */
use App\Helpers\Fecha;

$errores ??= [];
$valores ??= [];
$numPersonasVista = (int) ($valores['num_personas'] ?? 1);
$numPersonasVista = $numPersonasVista > 0 ? $numPersonasVista : 1;
$totalEstimado = $precioUnitario * $numPersonasVista;
$anticipoEstimado = round($totalEstimado * $porcentajeAnticipo / 100, 2);
$moneda = htmlspecialchars($paquete['moneda'], ENT_QUOTES, 'UTF-8');
$dinero = static fn (float $monto): string => '$' . number_format($monto, 2, '.', ',');

// Atributos de accesibilidad de un campo con error: lo marca como inválido y lo enlaza a su mensaje.
$attrsError = static function (string $campo) use ($errores): string {
    return !empty($errores[$campo]) ? ' aria-invalid="true" aria-describedby="error-' . $campo . '"' : '';
};
$mensajeError = static function (string $campo) use ($errores): string {
    if (empty($errores[$campo])) {
        return '';
    }
    return '<small class="campo__error" id="error-' . $campo . '">' . htmlspecialchars($errores[$campo], ENT_QUOTES, 'UTF-8') . '</small>';
};
?>
<section class="seccion contenedor reserva-layout">
  <div>
    <a href="/paquetes/<?= urlencode($paquete['slug']) ?>" class="enlace-volver">&larr; Volver al paquete</a>
    <p class="reserva-paso">Paso 1 de 2 &middot; Tus datos</p>
    <h1>Reservar</h1>

    <?php if (!empty($errores['general'])): ?>
      <p class="campo__error" role="alert"><?= htmlspecialchars($errores['general'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" action="/reservar" data-atribucion>
      <?= \App\Helpers\Csrf::field() ?>
      <input type="hidden" name="salida_id" value="<?= (int) $salida['id'] ?>">

      <div class="campo">
        <label for="nombre">Nombre completo</label>
        <input type="text" id="nombre" name="nombre" autocomplete="name" required value="<?= htmlspecialchars($valores['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>"<?= $attrsError('nombre') ?>>
        <?= $mensajeError('nombre') ?>
      </div>

      <div class="campo">
        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" autocomplete="email" required value="<?= htmlspecialchars($valores['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"<?= $attrsError('email') ?>>
        <?= $mensajeError('email') ?>
      </div>

      <div class="campo">
        <label for="telefono">Teléfono / WhatsApp</label>
        <input type="tel" id="telefono" name="telefono" autocomplete="tel" required value="<?= htmlspecialchars($valores['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>"<?= $attrsError('telefono') ?>>
        <?= $mensajeError('telefono') ?>
      </div>

      <div class="campo">
        <label for="num_personas">Número de personas</label>
        <input type="number" id="num_personas" name="num_personas" min="1" max="<?= (int) $salida['cupo_disponible'] ?>" required value="<?= htmlspecialchars((string) $numPersonasVista, ENT_QUOTES, 'UTF-8') ?>" data-reserva-personas<?= $attrsError('num_personas') ?>>
        <?= $mensajeError('num_personas') ?>
      </div>

      <div class="campo">
        <label for="codigo_descuento">Código de descuento (opcional)</label>
        <input type="text" id="codigo_descuento" name="codigo_descuento" value="<?= htmlspecialchars($valores['codigo_descuento'] ?? '', ENT_QUOTES, 'UTF-8') ?>"<?= $attrsError('codigo_descuento') ?>>
        <?= $mensajeError('codigo_descuento') ?>
      </div>

      <button type="submit" class="btn btn-primario btn--bloque">Continuar al pago del anticipo</button>
      <p class="reserva-legal">
        Pagas el anticipo de forma segura con Mercado Pago. Al continuar aceptas el
        <a href="/aviso-de-privacidad" target="_blank" rel="noopener">aviso de privacidad</a>.
      </p>
    </form>
  </div>

  <aside class="tarjeta reserva-resumen"
         data-reserva-resumen
         data-precio="<?= htmlspecialchars((string) $precioUnitario, ENT_QUOTES, 'UTF-8') ?>"
         data-anticipo="<?= (int) $porcentajeAnticipo ?>">
    <h2>Resumen</h2>
    <p class="reserva-resumen__paquete"><?= htmlspecialchars($paquete['titulo'], ENT_QUOTES, 'UTF-8') ?></p>
    <p class="texto-suave">
      Salida: <?= htmlspecialchars(Fecha::corta($salida['fecha_salida']), ENT_QUOTES, 'UTF-8') ?>
      <?php if (!empty($salida['fecha_regreso'])): ?>
        &middot; Regreso: <?= htmlspecialchars(Fecha::corta($salida['fecha_regreso']), ENT_QUOTES, 'UTF-8') ?>
      <?php endif; ?>
      <br><?= (int) $salida['cupo_disponible'] ?> lugares disponibles
    </p>

    <dl class="reserva-resumen__lineas" aria-live="polite">
      <div><dt>Precio por persona</dt><dd><?= $dinero($precioUnitario) ?> <?= $moneda ?></dd></div>
      <div><dt>Personas</dt><dd data-reserva-num><?= $numPersonasVista ?></dd></div>
      <div class="reserva-resumen__total"><dt>Total</dt><dd><span data-reserva-total><?= $dinero($totalEstimado) ?></span> <?= $moneda ?></dd></div>
      <div class="reserva-resumen__destacado"><dt>Anticipo hoy (<?= (int) $porcentajeAnticipo ?>%)</dt><dd><span data-reserva-anticipo><?= $dinero($anticipoEstimado) ?></span> <?= $moneda ?></dd></div>
      <div><dt>Saldo antes del viaje</dt><dd><span data-reserva-saldo><?= $dinero($totalEstimado - $anticipoEstimado) ?></span> <?= $moneda ?></dd></div>
    </dl>
    <p class="reserva-resumen__nota">Si usas un código de descuento, se aplica al total al continuar. El saldo se liquida directamente con nuestro equipo antes del viaje.</p>
  </aside>
</section>
