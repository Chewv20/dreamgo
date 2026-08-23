<?php
/** @var array $valores */
$v = static fn (string $valor): string => htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
$etiquetas = [
    'color_primario' => 'Primario (acentos, boton secundario)',
    'color_primario_oscuro' => 'Primario oscuro (boton principal, enlaces)',
    'color_texto_oscuro' => 'Texto / fondos oscuros (footer, hero)',
    'color_fondo' => 'Fondo general del sitio',
    'color_fondo_alterno' => 'Fondo alterno (secciones destacadas)',
    'color_exito' => 'Mensajes de exito',
    'color_error' => 'Mensajes de error',
];
?>
<div class="admin-acciones" style="margin-bottom:1.25rem;">
  <a href="/admin/contenido/home" class="btn btn-secundario">&larr; Volver a secciones</a>
</div>

<form method="post" action="/admin/colores">
  <?= \App\Helpers\Csrf::field() ?>

  <div class="admin-panel" style="max-width:640px;">
    <p style="opacity:0.75;margin-top:0;">Estos colores aplican a todo el sitio publico. Los cambios se ven de inmediato.</p>

    <?php foreach ($etiquetas as $clave => $etiqueta): ?>
      <div class="campo" style="display:flex;align-items:center;gap:1rem;">
        <input type="color" id="<?= $clave ?>" name="<?= $clave ?>" value="<?= $v($valores[$clave]) ?>" style="width:60px;height:42px;padding:0.25rem;">
        <label for="<?= $clave ?>" style="margin:0;"><?= $v($etiqueta) ?></label>
      </div>
    <?php endforeach; ?>
  </div>

  <button type="submit" class="btn btn-primario">Guardar colores</button>
</form>
