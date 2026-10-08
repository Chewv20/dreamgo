<?php
/** @var string $token */
/** @var array|null $usuario */
/** @var string $politica */
?>
<div class="admin-login">
  <div class="admin-login__card">
    <img src="/assets/img/logo.avif" alt="Dream Go">
    <h1 class="titulo-sm">Elige una contraseña nueva</h1>

    <?php foreach (\App\Helpers\Flash::pull() as $flash): ?>
      <div class="admin-flash admin-flash--<?= htmlspecialchars($flash['tipo'], ENT_QUOTES, 'UTF-8') ?>" role="<?= $flash['tipo'] === 'error' ? 'alert' : 'status' ?>"><?= htmlspecialchars($flash['mensaje'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>

    <?php if ($usuario === null): ?>
      <p><?= htmlspecialchars(\App\Services\RecuperacionPasswordService::MENSAJE_INVALIDO, ENT_QUOTES, 'UTF-8') ?></p>
      <a href="/admin/recuperar" class="btn btn-primario w-100">Solicitar un enlace nuevo</a>
    <?php else: ?>
      <p>Cuenta: <strong><?= htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8') ?></strong></p>
      <form method="post" action="/admin/restablecer/<?= htmlspecialchars(rawurlencode($token), ENT_QUOTES, 'UTF-8') ?>">
        <?= \App\Helpers\Csrf::field() ?>
        <input type="text" name="username" value="<?= htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="username" hidden>
        <div class="campo">
          <label for="password_nueva">Nueva contraseña</label>
          <input type="password" id="password_nueva" name="password_nueva" required minlength="8" autofocus autocomplete="new-password" aria-describedby="politica-password">
          <small class="op-70" id="politica-password"><?= htmlspecialchars($politica, ENT_QUOTES, 'UTF-8') ?></small>
        </div>
        <div class="campo">
          <label for="password_confirmacion">Confirmar nueva contraseña</label>
          <input type="password" id="password_confirmacion" name="password_confirmacion" required minlength="8" autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primario w-100">Guardar contraseña</button>
      </form>
    <?php endif; ?>
    <p class="admin-login__enlaces">
      <a href="/admin/login">&larr; Volver a iniciar sesión</a>
    </p>
  </div>
</div>
