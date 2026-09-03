<?php
/** @var array $valores */
$v = static fn (string $valor): string => htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
$etiquetas = [
    'color_primario' => 'Primario (acentos, botón secundario)',
    'color_primario_oscuro' => 'Primario oscuro (botón principal, enlaces)',
    'color_texto_oscuro' => 'Texto / fondos oscuros (footer, hero)',
    'color_fondo' => 'Fondo general del sitio',
    'color_fondo_alterno' => 'Fondo alterno (secciones destacadas)',
    'color_exito' => 'Mensajes de éxito',
    'color_error' => 'Mensajes de error',
];
?>
<div class="admin-acciones mb-md">
  <a href="/admin/contenido/home" class="btn btn-secundario">&larr; Volver a secciones</a>
</div>

<form method="post" action="/admin/colores">
  <?= \App\Helpers\Csrf::field() ?>

  <div class="admin-panel bloque-medio">
    <p class="op-75 mt-0">Estos colores aplican a todo el sitio público. Los cambios se ven de inmediato.</p>

    <?php foreach ($etiquetas as $clave => $etiqueta): ?>
      <div class="campo fila-color">
        <input type="color" id="<?= $clave ?>" name="<?= $clave ?>" value="<?= $v($valores[$clave]) ?>" class="input-color">
        <label for="<?= $clave ?>" class="m-0"><?= $v($etiqueta) ?></label>
      </div>
    <?php endforeach; ?>
  </div>

  <button type="submit" class="btn btn-primario">Guardar colores</button>
</form>
