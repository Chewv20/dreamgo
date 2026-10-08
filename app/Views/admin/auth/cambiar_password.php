<?php /** @var bool $forzado */ /** @var string $politica */ $forzado ??= true; ?>
<div class="admin-login">
  <div class="admin-login__card">
    <img src="/assets/img/logo.avif" alt="Dream Go">
    <h1 class="titulo-sm">Cambia tu contraseña</h1>
    <?php if ($forzado): ?>
      <p>Por seguridad debes establecer una contraseña nueva antes de continuar.</p>
    <?php else: ?>
      <p>Al guardarla se cerrará tu sesión y tendrás que entrar con la nueva contraseña.</p>
    <?php endif; ?>

    <?php foreach (\App\Helpers\Flash::pull() as $flash): ?>
      <div class="admin-flash admin-flash--<?= htmlspecialchars($flash['tipo'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($flash['mensaje'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>

    <form method="post" action="/admin/cambiar-password">
      <?= \App\Helpers\Csrf::field() ?>
      <div class="campo">
        <label for="password_actual">Contraseña actual</label>
        <input type="password" id="password_actual" name="password_actual" required autofocus autocomplete="current-password">
      </div>
      <div class="campo">
        <label for="password_nueva">Nueva contraseña</label>
        <input type="password" id="password_nueva" name="password_nueva" required minlength="8" autocomplete="new-password"<?= !empty($politica) ? ' aria-describedby="politica-password"' : '' ?>>
        <?php if (!empty($politica)): ?><small class="op-70" id="politica-password"><?= htmlspecialchars($politica, ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
      </div>
      <div class="campo">
        <label for="password_confirmacion">Confirmar nueva contraseña</label>
        <input type="password" id="password_confirmacion" name="password_confirmacion" required minlength="8" autocomplete="new-password">
      </div>
      <button type="submit" class="btn btn-primario w-100"><?= $forzado ? 'Guardar y continuar' : 'Guardar contraseña' ?></button>
    </form>
    <?php if (!$forzado): ?>
      <p class="centrado admin-mt-sm"><a href="/admin">Cancelar y volver al panel</a></p>
    <?php endif; ?>
  </div>
</div>
