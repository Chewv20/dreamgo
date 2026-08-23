<?php /** @var string|null $email */ ?>
<div class="admin-login">
  <div class="admin-login__card">
    <img src="/assets/img/logo.avif" alt="Dream Go">
    <h1 style="font-size:1.4rem;">Panel administrativo</h1>

    <?php foreach (\App\Helpers\Flash::pull() as $flash): ?>
      <div class="admin-flash admin-flash--<?= htmlspecialchars($flash['tipo'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($flash['mensaje'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>

    <form method="post" action="/admin/login">
      <?= \App\Helpers\Csrf::field() ?>
      <div class="campo">
        <label for="email">Correo</label>
        <input type="email" id="email" name="email" required autofocus value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="campo">
        <label for="password">Contrasena</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primario" style="width:100%;">Entrar</button>
    </form>
  </div>
</div>
