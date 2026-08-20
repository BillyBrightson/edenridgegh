<?php
/**
 * Renders one schema field as a dashboard control.
 *
 * @var array  $field  schema definition
 * @var mixed  $value  current value
 * @var string $name   input name, e.g. content[hero][headline]
 * @var string $id     unique DOM id
 * @var int    $depth  repeater nesting depth
 */
use Core\{AdminUi, Icons, Media, View};

$field = $field ?? [];
$type  = (string)($field['type'] ?? 'text');
$label = (string)($field['label'] ?? $field['key']);
$hint  = (string)($field['hint'] ?? '');
$depth = (int)($depth ?? 0);
$id    = $id ?? ('f' . substr(md5($name), 0, 8));
$max   = isset($field['max']) ? (int)$field['max'] : 0;
$rowTitle = !empty($field['is_row_title']);
?>

<?php if ($type === 'repeater'): ?>
  <?php
  $token   = '__IDX' . $depth . '__';
  $rowsIn  = is_array($value) ? array_values(array_filter($value, 'is_array')) : [];
  $rowKey  = (string)($field['row_label'] ?? '');
  $emptyLabel = 'New ' . rtrim(mb_strtolower($label), 's');

  $renderRow = static function (array $rowValue, string $index) use ($field, $name, $depth, $rowKey, $emptyLabel): string {
      ob_start(); ?>
      <div class="rep-row collapsed" data-index="<?= e($index) ?>" data-empty-label="<?= e($emptyLabel) ?>">
        <div class="rep-head">
          <button type="button" class="drag" tabindex="-1" aria-hidden="true"><?= AdminUi::glyph('drag') ?></button>
          <span class="num"></span>
          <span class="title"></span>
          <span class="rep-actions">
            <button type="button" class="icon-btn" data-action="up" title="Move up" aria-label="Move up"><?= AdminUi::glyph('up') ?></button>
            <button type="button" class="icon-btn" data-action="down" title="Move down" aria-label="Move down"><?= AdminUi::glyph('down') ?></button>
            <button type="button" class="icon-btn" data-action="duplicate" title="Duplicate" aria-label="Duplicate"><?= AdminUi::glyph('duplicate') ?></button>
            <button type="button" class="icon-btn danger" data-action="delete" title="Remove" aria-label="Remove"><?= AdminUi::glyph('trash') ?></button>
          </span>
        </div>
        <div class="rep-body">
          <?php foreach ($field['fields'] as $sub): ?>
            <?php
            $subName  = $name . '[' . $index . '][' . $sub['key'] . ']';
            $subValue = $rowValue[$sub['key']] ?? null;
            if ($subValue === null && $sub['type'] === 'repeater') {
                $subValue = [];
            }
            echo View::render('admin/partials/field', [
                'field' => $sub + ['is_row_title' => $rowKey === $sub['key']],
                'value' => $subValue,
                'name'  => $subName,
                'id'    => 'f' . substr(md5($subName), 0, 10),
                'depth' => $depth + 1,
            ]);
            ?>
          <?php endforeach; ?>
        </div>
      </div>
      <?php return (string)ob_get_clean();
  };
  ?>
  <div class="field">
    <span class="field-label"><?= e($label) ?></span>
    <?php if ($hint !== ''): ?><p class="hint"><?= e($hint) ?></p><?php endif; ?>
    <div class="rep" data-name="<?= e($name) ?>" data-depth="<?= $depth ?>" data-token="<?= e($token) ?>">
      <div class="rep-rows">
        <?php foreach ($rowsIn as $i => $rowValue): ?>
          <?= $renderRow($rowValue, (string)$i) ?>
        <?php endforeach; ?>
      </div>
      <template><?= $renderRow([], $token) ?></template>
      <button type="button" class="btn btn-ghost btn-sm rep-add"><?= AdminUi::glyph('plus') ?> Add <?= e(rtrim(mb_strtolower($label), 's')) ?></button>
    </div>
  </div>

<?php elseif ($type === 'toggle'): ?>
  <div class="field">
    <input type="hidden" name="<?= e($name) ?>" value="0">
    <label class="switch">
      <input type="checkbox" name="<?= e($name) ?>" value="1" <?= !empty($value) ? 'checked' : '' ?>>
      <span class="track"></span>
      <span><?= e($label) ?></span>
    </label>
    <?php if ($hint !== ''): ?><p class="hint" style="margin-top:6px;"><?= e($hint) ?></p><?php endif; ?>
  </div>

<?php elseif ($type === 'select'): ?>
  <div class="field">
    <label class="field-label" for="<?= e($id) ?>"><?= e($label) ?></label>
    <?php if ($hint !== ''): ?><p class="hint"><?= e($hint) ?></p><?php endif; ?>
    <select id="<?= e($id) ?>" name="<?= e($name) ?>"<?= $rowTitle ? ' data-row-title' : '' ?>>
      <?php foreach (($field['options'] ?? []) as $optionValue => $optionLabel): ?>
        <option value="<?= e((string)$optionValue) ?>" <?= (string)$value === (string)$optionValue ? 'selected' : '' ?>><?= e((string)$optionLabel) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

<?php elseif ($type === 'icon'): ?>
  <div class="field" data-icon-field>
    <span class="field-label"><?= e($label) ?></span>
    <?php if ($hint !== ''): ?><p class="hint"><?= e($hint) ?></p><?php endif; ?>
    <input type="hidden" name="<?= e($name) ?>" value="<?= e((string)($value ?: 'none')) ?>">
    <div class="icon-picker">
      <?php foreach (Icons::options() as $iconKey => $iconLabel): ?>
        <button type="button" class="icon-choice <?= (string)$value === $iconKey ? 'selected' : '' ?>" data-icon="<?= e($iconKey) ?>">
          <?= $iconKey === 'none' ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><path d="M5 19L19 5"/><circle cx="12" cy="12" r="9"/></svg>' : Icons::svg($iconKey, '') ?>
          <span><?= e($iconLabel) ?></span>
        </button>
      <?php endforeach; ?>
    </div>
  </div>

<?php elseif ($type === 'image'): ?>
  <?php
  $media = Media::find($value ? (int)$value : null);
  $thumb = $media ? Media::url((int)$media['id'], 640) : '';
  ?>
  <div class="field" data-media-field>
    <span class="field-label"><?= e($label) ?></span>
    <?php if ($hint !== ''): ?><p class="hint"><?= e($hint) ?></p><?php endif; ?>
    <input type="hidden" name="<?= e($name) ?>" value="<?= e((string)($value ?? '')) ?>">
    <div class="media-field">
      <div class="media-preview"><?= $thumb !== '' ? '<img src="' . e($thumb) . '" alt="">' : 'No image' ?></div>
      <div class="media-meta">
        <div class="name"><?= $media ? e((string)$media['original_name']) : 'No image selected' ?></div>
        <div class="alt"><?= $media ? e((string)$media['alt']) : '' ?></div>
        <div class="media-actions">
          <button type="button" class="btn btn-ghost btn-sm" data-media-choose>Choose image</button>
          <label class="btn btn-ghost btn-sm">
            Upload
            <input type="file" accept="image/jpeg,image/png,image/webp,image/svg+xml" hidden>
          </label>
          <button type="button" class="btn btn-ghost btn-sm" data-media-clear <?= $media ? '' : 'hidden' ?>>Remove</button>
        </div>
      </div>
    </div>
  </div>

<?php elseif ($type === 'richtext'): ?>
  <div class="field" data-richtext>
    <span class="field-label"><?= e($label) ?></span>
    <?php if ($hint !== ''): ?><p class="hint"><?= e($hint) ?></p><?php endif; ?>
    <div class="rt-toolbar">
      <button type="button" data-cmd="bold" title="Bold"><strong>B</strong></button>
      <button type="button" data-cmd="italic" title="Italic"><em>I</em></button>
      <button type="button" data-cmd="insertUnorderedList" title="Bulleted list">&bull; List</button>
      <button type="button" data-cmd="createLink" title="Add link">Link</button>
      <button type="button" data-cmd="removeFormat" title="Clear formatting">Clear</button>
    </div>
    <div class="rt-area" contenteditable="true" role="textbox" aria-multiline="true" aria-label="<?= e($label) ?>"></div>
    <textarea name="<?= e($name) ?>" hidden><?= e((string)$value) ?></textarea>
  </div>

<?php elseif ($type === 'textarea'): ?>
  <div class="field">
    <label class="field-label" for="<?= e($id) ?>"><?= e($label) ?></label>
    <?php if ($hint !== ''): ?><p class="hint"><?= e($hint) ?></p><?php endif; ?>
    <textarea id="<?= e($id) ?>" name="<?= e($name) ?>" rows="<?= (int)($field['rows'] ?? 4) ?>"
      <?= $max ? 'data-max="' . $max . '"' : '' ?><?= $rowTitle ? ' data-row-title' : '' ?>><?= e((string)$value) ?></textarea>
  </div>

<?php elseif ($type === 'number'): ?>
  <div class="field">
    <label class="field-label" for="<?= e($id) ?>"><?= e($label) ?></label>
    <?php if ($hint !== ''): ?><p class="hint"><?= e($hint) ?></p><?php endif; ?>
    <input type="number" step="any" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e((string)($value ?? '')) ?>">
  </div>

<?php else: ?>
  <div class="field">
    <label class="field-label" for="<?= e($id) ?>"><?= e($label) ?></label>
    <?php if ($hint !== ''): ?><p class="hint"><?= e($hint) ?></p><?php endif; ?>
    <input type="text" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e((string)($value ?? '')) ?>"
      <?= $max ? 'data-max="' . $max . '"' : '' ?><?= $rowTitle ? ' data-row-title' : '' ?>
      <?= $type === 'link' ? 'placeholder="#anchor, /page or https://…"' : '' ?>>
  </div>
<?php endif; ?>
