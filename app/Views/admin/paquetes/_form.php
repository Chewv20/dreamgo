<?php
/** @var array $categorias */
/** @var array|null $paquete */
/** @var array $monedas */
/** @var bool $monedaBloqueada */
use App\Helpers\ContenidoPaquete;

$paquete ??= [];
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

// Cada campo de contenido se edita en forma estructurada si su HTML actual se puede
// representar así sin perder nada; si no (enlaces, negritas en medio del texto...), se
// queda con el editor HTML de siempre. Ver ContenidoPaquete.
$descripcionTexto = ContenidoPaquete::parrafosATexto($paquete['descripcion_larga'] ?? '');
$dias = ContenidoPaquete::itinerarioADias($paquete['itinerario'] ?? '');
$incluyeLineas = ContenidoPaquete::listaALineas($paquete['incluye'] ?? '');
$noIncluyeLineas = ContenidoPaquete::listaALineas($paquete['no_incluye'] ?? '');

if ($dias !== null) {
    // Siempre al menos una fila por día de duración (o 3), para no empezar con el repetidor vacío.
    $minimo = max(1, (int) ($paquete['duracion_dias'] ?? 0) ?: 3);
    for ($i = count($dias); $i < $minimo; $i++) {
        $dias[] = ['titulo' => '', 'texto' => ''];
    }
}
$avisoHtml = '<small class="op-70">Este contenido tiene formato avanzado (enlaces, negritas, títulos…), así que se edita como HTML para no perder nada. Etiquetas permitidas: p, br, strong, em, ul, ol, li, h3, h4, a.</small>';
?>
<?= \App\Helpers\Csrf::field() ?>

<fieldset class="admin-seccion">
  <legend>Datos básicos</legend>
  <div class="admin-form-grid admin-form-grid--2">
    <div class="campo">
      <label for="titulo">Título</label>
      <input type="text" id="titulo" name="titulo" required maxlength="180" value="<?= $e($paquete['titulo'] ?? '') ?>">
    </div>
    <div class="campo">
      <label for="categoria_id">Destino</label>
      <select id="categoria_id" name="categoria_id" required>
        <?php foreach ($categorias as $categoria): ?>
          <option value="<?= (int) $categoria['id'] ?>" <?= (int) ($paquete['categoria_id'] ?? 0) === (int) $categoria['id'] ? 'selected' : '' ?>>
            <?= $e($categoria['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="campo">
    <label for="resumen">Resumen corto</label>
    <input type="text" id="resumen" name="resumen" maxlength="300" aria-describedby="ayuda-resumen" value="<?= $e($paquete['resumen'] ?? '') ?>">
    <small class="op-70" id="ayuda-resumen">Una o dos frases. Aparece en las tarjetas del catálogo y bajo el título de la ficha.</small>
  </div>

  <div class="admin-form-grid admin-form-grid--2">
    <div class="campo">
      <label for="estado">Estado</label>
      <select id="estado" name="estado" required aria-describedby="ayuda-estado">
        <?php foreach (['borrador' => 'Borrador', 'publicado' => 'Publicado', 'archivado' => 'Archivado'] as $valor => $etiqueta): ?>
          <option value="<?= $valor ?>" <?= ($paquete['estado'] ?? 'borrador') === $valor ? 'selected' : '' ?>><?= $etiqueta ?></option>
        <?php endforeach; ?>
      </select>
      <small class="op-70" id="ayuda-estado">Sólo los publicados se ven en el sitio.</small>
    </div>
    <div class="campo campo--check admin-campo-check">
      <input type="checkbox" id="destacado" name="destacado" value="1" <?= (int) ($paquete['destacado'] ?? 0) === 1 ? 'checked' : '' ?>>
      <label for="destacado" class="m-0">Mostrar como destacado en Inicio</label>
    </div>
  </div>
</fieldset>

<fieldset class="admin-seccion">
  <legend>Precio y duración</legend>
  <div class="admin-form-grid admin-form-grid--4">
    <div class="campo">
      <label for="precio_desde">Precio desde</label>
      <input type="number" step="0.01" min="0" id="precio_desde" name="precio_desde" required value="<?= $e($paquete['precio_desde'] ?? '') ?>">
    </div>
    <div class="campo">
      <label for="moneda">Moneda</label>
      <?php if (!empty($monedaBloqueada)): ?>
        <input type="text" id="moneda" value="<?= $e($paquete['moneda'] ?? 'MXN') ?>" disabled aria-describedby="ayuda-moneda">
        <input type="hidden" name="moneda" value="<?= $e($paquete['moneda'] ?? 'MXN') ?>">
        <small class="op-70" id="ayuda-moneda">No se puede cambiar: este paquete ya tiene reservas.</small>
      <?php else: ?>
        <select id="moneda" name="moneda" required>
          <?php foreach ($monedas as $codigo => $etiqueta): ?>
            <option value="<?= $codigo ?>" <?= ($paquete['moneda'] ?? 'MXN') === $codigo ? 'selected' : '' ?>><?= $e($etiqueta) ?></option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>
    </div>
    <div class="campo">
      <label for="duracion_dias">Días</label>
      <input type="number" min="1" id="duracion_dias" name="duracion_dias" value="<?= $e($paquete['duracion_dias'] ?? '') ?>">
    </div>
    <div class="campo">
      <label for="duracion_noches">Noches</label>
      <input type="number" min="0" id="duracion_noches" name="duracion_noches" value="<?= $e($paquete['duracion_noches'] ?? '') ?>">
    </div>
  </div>
</fieldset>

<fieldset class="admin-seccion">
  <legend>Contenido de la ficha</legend>

  <div class="campo">
    <?php if ($descripcionTexto !== null): ?>
      <input type="hidden" name="modo[descripcion_larga]" value="estructurado">
      <label for="descripcion_texto">Descripción</label>
      <textarea id="descripcion_texto" name="descripcion_texto" class="textarea-md" aria-describedby="ayuda-descripcion"><?= $e($descripcionTexto) ?></textarea>
      <small class="op-70" id="ayuda-descripcion">Escribe normal. Deja una línea en blanco para empezar otro párrafo.</small>
    <?php else: ?>
      <input type="hidden" name="modo[descripcion_larga]" value="html">
      <label for="descripcion_larga">Descripción (HTML)</label>
      <textarea id="descripcion_larga" name="descripcion_larga" class="textarea-md"><?= $e($paquete['descripcion_larga'] ?? '') ?></textarea>
      <?= $avisoHtml ?>
    <?php endif; ?>
  </div>

  <div class="campo">
    <?php if ($dias !== null): ?>
      <input type="hidden" name="modo[itinerario]" value="estructurado">
      <span class="admin-etiqueta" id="etiqueta-itinerario">Itinerario</span>
      <div class="itinerario-editor" data-itinerario role="group" aria-labelledby="etiqueta-itinerario">
        <?php foreach ($dias as $i => $dia): ?>
          <div class="itinerario-dia" data-itinerario-dia>
            <input type="text" name="itinerario_titulo[]" value="<?= $e($dia['titulo']) ?>" placeholder="Día <?= $i + 1 ?>" aria-label="Título del día <?= $i + 1 ?>" maxlength="80">
            <textarea name="itinerario_texto[]" rows="2" placeholder="Qué se hace ese día" aria-label="Actividades del día <?= $i + 1 ?>"><?= $e($dia['texto']) ?></textarea>
            <button type="button" class="btn btn-secundario btn--icono" data-itinerario-quitar aria-label="Quitar día <?= $i + 1 ?>">&times;</button>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn btn-secundario btn--xs admin-mt-sm" data-itinerario-agregar>+ Agregar día</button>
      <small class="op-70 d-block">Si dejas el título vacío se numera solo («Día 1», «Día 2»…). Las filas vacías se ignoran.</small>
    <?php else: ?>
      <input type="hidden" name="modo[itinerario]" value="html">
      <label for="itinerario">Itinerario (HTML)</label>
      <textarea id="itinerario" name="itinerario" class="textarea-md"><?= $e($paquete['itinerario'] ?? '') ?></textarea>
      <?= $avisoHtml ?>
    <?php endif; ?>
  </div>

  <div class="admin-form-grid admin-form-grid--2">
    <?php foreach (['incluye' => ['Incluye', $incluyeLineas, "Traslados aeropuerto-hotel\n3 noches de hospedaje"], 'no_incluye' => ['No incluye', $noIncluyeLineas, "Vuelos\nPropinas"]] as $campo => [$etiqueta, $lineas, $ejemplo]): ?>
      <div class="campo">
        <?php if ($lineas !== null): ?>
          <input type="hidden" name="modo[<?= $campo ?>]" value="estructurado">
          <label for="<?= $campo ?>_lineas"><?= $etiqueta ?></label>
          <textarea id="<?= $campo ?>_lineas" name="<?= $campo ?>_lineas" rows="5" placeholder="<?= $e($ejemplo) ?>" aria-describedby="ayuda-<?= $campo ?>"><?= $e($lineas) ?></textarea>
          <small class="op-70" id="ayuda-<?= $campo ?>">Un elemento por línea.</small>
        <?php else: ?>
          <input type="hidden" name="modo[<?= $campo ?>]" value="html">
          <label for="<?= $campo ?>"><?= $etiqueta ?> (HTML)</label>
          <textarea id="<?= $campo ?>" name="<?= $campo ?>" rows="5"><?= $e($paquete[$campo] ?? '') ?></textarea>
          <?= $avisoHtml ?>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</fieldset>

<fieldset class="admin-seccion">
  <legend>Imágenes</legend>
  <div class="campo">
    <label for="imagenes">Agregar imágenes (JPG, PNG o WEBP)</label>
    <input type="file" id="imagenes" name="imagenes[]" multiple accept="image/jpeg,image/png,image/webp" aria-describedby="ayuda-imagenes">
    <small class="op-70" id="ayuda-imagenes">Puedes seleccionar varias. La primera de la galería es la portada; las tarjetas usan formato vertical 4:5, así que una portada vertical se ve completa. Se reordenan después de guardar.</small>
  </div>
</fieldset>

<details class="admin-seccion admin-seccion--plegable"<?= !empty($paquete['meta_title']) || !empty($paquete['meta_description']) ? ' open' : '' ?>>
  <summary>SEO (opcional)</summary>
  <div class="admin-form-grid admin-form-grid--2 admin-mt-sm">
    <div class="campo">
      <label for="meta_title">Título para Google</label>
      <input type="text" id="meta_title" name="meta_title" maxlength="180" value="<?= $e($paquete['meta_title'] ?? '') ?>">
    </div>
    <div class="campo">
      <label for="meta_description">Descripción para Google</label>
      <input type="text" id="meta_description" name="meta_description" maxlength="300" value="<?= $e($paquete['meta_description'] ?? '') ?>">
    </div>
  </div>
  <small class="op-70">Si los dejas vacíos se usan el título y el resumen del paquete.</small>
</details>

<div class="admin-barra-guardar">
  <button type="submit" class="btn btn-primario">Guardar paquete</button>
  <?php if (!empty($paquete['id'])): ?>
    <a href="/admin/paquetes" class="btn btn-secundario">Volver al listado</a>
  <?php endif; ?>
</div>
