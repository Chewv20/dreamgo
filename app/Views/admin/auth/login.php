<?php /** @var string|null $email */ ?>
<div class="admin-login">
  <div class="admin-login__card">
    <img src="/assets/img/logo.avif" alt="Dream Go">
    <h1 class="titulo-sm">Panel administrativo</h1>

    <?php foreach (\App\Helpers\Flash::pull() as $flash): ?>
      <div class="admin-flash admin-flash--<?= htmlspecialchars($flash['tipo'], ENT_QUOTES, 'UTF-8') ?>" role="<?= $flash['tipo'] === 'error' ? 'alert' : 'status' ?>"><?= htmlspecialchars($flash['mensaje'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>

    <form method="post" action="/admin/login">
      <?= \App\Helpers\Csrf::field() ?>
      <div class="campo">
        <label for="email">Correo</label>
        <input type="email" id="email" name="email" required autofocus autocomplete="username" value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="campo">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn-primario w-100">Entrar</button>
    </form>
    <p class="admin-login__enlaces">
      <a href="/admin/recuperar">¿Olvidaste tu contraseña?</a>
      <a href="/">&larr; Volver al sitio</a>
    </p>
  </div>
</div>
