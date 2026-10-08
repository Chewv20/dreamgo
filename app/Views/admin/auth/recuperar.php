<div class="admin-login">
  <div class="admin-login__card">
    <img src="/assets/img/logo.avif" alt="Dream Go">
    <h1 class="titulo-sm">Recuperar contraseña</h1>
    <p>Escribe el correo con el que entras al panel y te enviaremos un enlace para elegir una contraseña nueva.</p>

    <?php foreach (\App\Helpers\Flash::pull() as $flash): ?>
      <div class="admin-flash admin-flash--<?= htmlspecialchars($flash['tipo'], ENT_QUOTES, 'UTF-8') ?>" role="<?= $flash['tipo'] === 'error' ? 'alert' : 'status' ?>"><?= htmlspecialchars($flash['mensaje'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>

    <form method="post" action="/admin/recuperar">
      <?= \App\Helpers\Csrf::field() ?>
      <div class="campo">
        <label for="email">Correo</label>
        <input type="email" id="email" name="email" required autofocus autocomplete="username">
      </div>
      <button type="submit" class="btn btn-primario w-100">Enviar enlace</button>
    </form>
    <p class="admin-login__enlaces">
      <a href="/admin/login">&larr; Volver a iniciar sesión</a>
    </p>
  </div>
</div>
