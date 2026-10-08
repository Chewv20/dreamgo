<?php
/** @var string $nombre */
/** @var string $url */
/** @var int $vigenciaMinutos */
?>
<h2 style="margin-top:0;color:#2f3e46;">Restablece tu contraseña</h2>
<p>Hola <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>, recibimos una solicitud para restablecer la contraseña de tu cuenta en el panel de Dream Go.</p>
<p><a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" style="display:inline-block;background:#a85f4d;color:#ffffff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:bold;">Elegir una contraseña nueva</a></p>
<p>El enlace sirve una sola vez y vence en <?= (int) $vigenciaMinutos ?> minutos.</p>
<p style="color:#6b6b6b;font-size:13px;">Si no fuiste tú, ignora este correo: tu contraseña actual sigue funcionando. Si el botón no abre, copia esta dirección en tu navegador:<br><?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?></p>
