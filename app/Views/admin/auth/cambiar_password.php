<div class="admin-login">
  <div class="admin-login__card">
    <img src="/assets/img/logo.avif" alt="Dream Go">
    <h1 class="titulo-sm">Cambia tu contrasena</h1>
    <p>Por seguridad debes establecer una contrasena nueva antes de continuar.</p>

    <?php foreach (\App\Helpers\Flash::pull() as $flash): ?>
      <div class="admin-flash admin-flash--<?= htmlspecialchars($flash['tipo'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($flash['mensaje'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>

    <form method="post" action="/admin/cambiar-password">
      <?= \App\Helpers\Csrf::field() ?>
      <div class="campo">
        <label for="password_actual">Contrasena actual</label>
        <input type="password" id="password_actual" name="password_actual" required autofocus>
      </div>
      <div class="campo">
        <label for="password_nueva">Nueva contrasena</label>
        <input type="password" id="password_nueva" name="password_nueva" required minlength="8">
      </div>
      <div class="campo">
        <label for="password_confirmacion">Confirmar nueva contrasena</label>
        <input type="password" id="password_confirmacion" name="password_confirmacion" required minlength="8">
      </div>
      <button type="submit" class="btn btn-primario w-100">Guardar y continuar</button>
    </form>
  </div>
</div>
