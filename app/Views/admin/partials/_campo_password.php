<?php
/**
 * Campo de contraseña del panel (alta y edición de usuarios): muestra la política antes de
 * enviar, permite ver lo escrito y generar una contraseña segura que cumple la política.
 *
 * @var string $etiquetaPassword
 * @var bool   $passwordRequerida
 */
$politica = \App\Helpers\PasswordPolicy::mensaje();
?>
<div class="campo">
  <label for="password"><?= htmlspecialchars($etiquetaPassword, ENT_QUOTES, 'UTF-8') ?></label>
  <div class="campo-password" data-campo-password>
    <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" aria-describedby="ayuda-password"<?= $passwordRequerida ? ' required' : '' ?>>
    <button type="button" class="btn btn-secundario btn--xs" data-password-ver aria-pressed="false">Mostrar</button>
    <button type="button" class="btn btn-secundario btn--xs" data-password-generar>Generar</button>
  </div>
  <small class="op-70" id="ayuda-password"><?= htmlspecialchars($politica, ENT_QUOTES, 'UTF-8') ?> Si la generas, cópiala y compártela con la persona por un medio seguro.</small>
</div>
