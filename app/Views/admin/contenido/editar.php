<?php
/** @var array $bloque */
/** @var array $contenido */
/** @var string $etiqueta */
$v = static fn (string $valor): string => htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
$iconos = [
    'chat' => 'Conversación',
    'mapa' => 'Mapa / ruta',
    'reloj' => 'Reloj',
    'etiqueta' => 'Etiqueta de precio',
    'escudo' => 'Escudo / confianza',
    'estrella' => 'Estrella / calidad',
];
?>
<div class="admin-acciones mb-md">
  <a href="/admin/contenido/home" class="btn btn-secundario">&larr; Volver a secciones</a>
</div>

<form method="post" action="/admin/contenido/<?= (int) $bloque['id'] ?>/editar">
  <?= \App\Helpers\Csrf::field() ?>

  <div class="admin-panel ancho-760">
    <h2 class="mt-0"><?= $v($etiqueta) ?></h2>

    <div class="campo">
      <label for="titulo">Título de la sección</label>
      <input type="text" id="titulo" name="titulo" value="<?= $v($bloque['titulo'] ?? '') ?>">
    </div>

    <div class="campo">
      <label for="subtitulo">Texto / subtítulo</label>
      <textarea id="subtitulo" name="subtitulo"><?= $v($bloque['subtitulo'] ?? '') ?></textarea>
    </div>

    <?php if ($bloque['clave'] !== 'hero'): ?>
      <div class="campo mw-220">
        <label for="color_fondo">Color de fondo de la sección</label>
        <input type="color" id="color_fondo" name="color_fondo" value="<?= $v($bloque['color_fondo'] ?: '#ffffff') ?>">
      </div>
    <?php endif; ?>
  </div>

  <?php if ($bloque['clave'] === 'hero'): ?>
    <div class="admin-panel ancho-760">
      <h2 class="mt-0">Botones</h2>
      <div class="admin-form-grid admin-form-grid--2">
        <div class="campo">
          <label for="cta_primario_texto">Texto del botón principal</label>
          <input type="text" id="cta_primario_texto" name="cta_primario_texto" value="<?= $v($contenido['cta_primario_texto'] ?? '') ?>">
        </div>
        <div class="campo">
          <label for="cta_primario_link">Enlace del botón principal</label>
          <input type="text" id="cta_primario_link" name="cta_primario_link" value="<?= $v($contenido['cta_primario_link'] ?? '') ?>">
        </div>
        <div class="campo">
          <label for="cta_secundario_texto">Texto del botón secundario</label>
          <input type="text" id="cta_secundario_texto" name="cta_secundario_texto" value="<?= $v($contenido['cta_secundario_texto'] ?? '') ?>">
        </div>
        <div class="campo">
          <label for="cta_secundario_link">Enlace del botón secundario</label>
          <input type="text" id="cta_secundario_link" name="cta_secundario_link" value="<?= $v($contenido['cta_secundario_link'] ?? '') ?>">
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($bloque['clave'] === 'cta_final'): ?>
    <div class="admin-panel ancho-760">
      <h2 class="mt-0">Botón</h2>
      <div class="admin-form-grid admin-form-grid--2">
        <div class="campo">
          <label for="boton_texto">Texto del botón</label>
          <input type="text" id="boton_texto" name="boton_texto" value="<?= $v($contenido['boton_texto'] ?? '') ?>">
        </div>
        <div class="campo">
          <label for="boton_link">Enlace del botón</label>
          <input type="text" id="boton_link" name="boton_link" value="<?= $v($contenido['boton_link'] ?? '') ?>">
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($bloque['clave'] === 'ventajas'): ?>
    <?php foreach (($contenido['items'] ?? []) as $i => $item): ?>
      <div class="admin-panel ancho-760">
        <h2 class="mt-0">Tarjeta <?= $i + 1 ?></h2>
        <div class="campo mw-280">
          <label for="item_<?= $i ?>_icono">Icono</label>
          <select id="item_<?= $i ?>_icono" name="item_<?= $i ?>_icono">
            <?php foreach ($iconos as $clave => $etiquetaIcono): ?>
              <option value="<?= $v($clave) ?>" <?= ($item['icono'] ?? '') === $clave ? 'selected' : '' ?>><?= $v($etiquetaIcono) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="campo">
          <label for="item_<?= $i ?>_titulo">Título</label>
          <input type="text" id="item_<?= $i ?>_titulo" name="item_<?= $i ?>_titulo" value="<?= $v($item['titulo'] ?? '') ?>">
        </div>
        <div class="campo">
          <label for="item_<?= $i ?>_texto">Texto</label>
          <textarea id="item_<?= $i ?>_texto" name="item_<?= $i ?>_texto"><?= $v($item['texto'] ?? '') ?></textarea>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($bloque['clave'] === 'intro' && $bloque['pagina'] === 'nosotros'): ?>
    <div class="admin-panel ancho-760">
      <h2 class="mt-0">Segundo párrafo</h2>
      <div class="campo">
        <label for="parrafo_2">Texto</label>
        <textarea id="parrafo_2" name="parrafo_2"><?= $v($contenido['parrafo_2'] ?? '') ?></textarea>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($bloque['clave'] === 'estadisticas'): ?>
    <?php foreach (($contenido['items'] ?? []) as $i => $item): ?>
      <div class="admin-panel ancho-760">
        <h2 class="mt-0">Cifra <?= $i + 1 ?></h2>
        <div class="admin-form-grid admin-form-grid--2">
          <div class="campo">
            <label for="item_<?= $i ?>_numero">Número</label>
            <input type="text" id="item_<?= $i ?>_numero" name="item_<?= $i ?>_numero" value="<?= $v($item['numero'] ?? '') ?>" placeholder="Ej. +50">
          </div>
          <div class="campo">
            <label for="item_<?= $i ?>_etiqueta">Etiqueta</label>
            <input type="text" id="item_<?= $i ?>_etiqueta" name="item_<?= $i ?>_etiqueta" value="<?= $v($item['etiqueta'] ?? '') ?>" placeholder="Ej. Destinos disponibles">
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($bloque['clave'] === 'testimonios'): ?>
    <?php foreach (($contenido['items'] ?? []) as $i => $item): ?>
      <div class="admin-panel ancho-760">
        <h2 class="mt-0">Testimonio <?= $i + 1 ?></h2>
        <div class="campo mw-120">
          <label for="item_<?= $i ?>_inicial">Inicial (avatar)</label>
          <input type="text" id="item_<?= $i ?>_inicial" name="item_<?= $i ?>_inicial" maxlength="2" value="<?= $v($item['inicial'] ?? '') ?>">
        </div>
        <div class="campo">
          <label for="item_<?= $i ?>_texto">Cita</label>
          <textarea id="item_<?= $i ?>_texto" name="item_<?= $i ?>_texto"><?= $v($item['texto'] ?? '') ?></textarea>
        </div>
        <div class="campo">
          <label for="item_<?= $i ?>_autor">Autor / referencia</label>
          <input type="text" id="item_<?= $i ?>_autor" name="item_<?= $i ?>_autor" value="<?= $v($item['autor'] ?? '') ?>">
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <button type="submit" class="btn btn-primario">Guardar cambios</button>
</form>
