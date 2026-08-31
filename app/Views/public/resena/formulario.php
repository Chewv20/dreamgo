<?php
/** @var string $codigo */
/** @var bool $enviado */
/** @var bool $yaExistia */
/** @var array $errores */
/** @var array $valores */
$errores ??= [];
$valores ??= [];
?>
<section class="seccion contenedor bloque-medio">
  <h1>Deja tu resena</h1>

  <?php if ($enviado): ?>
    <?php if ($yaExistia): ?>
      <p class="mt-1">Ya registramos tu resena anteriormente. ¡Gracias por compartir tu experiencia!</p>
    <?php else: ?>
      <p class="mt-1">Gracias por tu resena. Sera publicada en la ficha del paquete despues de una breve revision.</p>
    <?php endif; ?>
  <?php else: ?>
    <p>Cuentanos que tal estuvo tu viaje. Confirma tu correo para verificar tu reserva.</p>

    <?php if (!empty($errores['general'])): ?>
      <p class="txt-error"><?= htmlspecialchars($errores['general'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" action="/resena/<?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') ?>">
      <?= \App\Helpers\Csrf::field() ?>

      <div class="campo">
        <label for="email">Correo con el que reservaste</label>
        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($valores['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <?php if (!empty($errores['email'])): ?><small class="txt-error"><?= htmlspecialchars($errores['email'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
      </div>

      <div class="campo">
        <label for="calificacion">Calificacion</label>
        <select id="calificacion" name="calificacion" required>
          <option value="">Selecciona una calificacion</option>
          <?php for ($i = 5; $i >= 1; $i--): ?>
            <option value="<?= $i ?>" <?= (string) $i === (string) ($valores['calificacion'] ?? '') ? 'selected' : '' ?>><?= str_repeat('★', $i) ?> (<?= $i ?>)</option>
          <?php endfor; ?>
        </select>
        <?php if (!empty($errores['calificacion'])): ?><small class="txt-error"><?= htmlspecialchars($errores['calificacion'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
      </div>

      <div class="campo">
        <label for="comentario">Tu comentario</label>
        <textarea id="comentario" name="comentario" required maxlength="1000"><?= htmlspecialchars($valores['comentario'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        <?php if (!empty($errores['comentario'])): ?><small class="txt-error"><?= htmlspecialchars($errores['comentario'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
      </div>

      <button type="submit" class="btn btn-primario w-100">Enviar resena</button>
    </form>
  <?php endif; ?>
</section>
