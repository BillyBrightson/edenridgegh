<?php
/** @var array $items */
/** @var bool $selectable */
use Core\Media;
?>
<?php if (!$items): ?>
  <div class="empty"><h4>No images</h4><p>Upload one from the Media library.</p></div>
<?php else: ?>
  <div class="media-grid">
    <?php foreach ($items as $item): ?>
      <?php $thumb = Media::url((int)$item['id'], 640); ?>
      <div class="media-tile"
           data-id="<?= (int)$item['id'] ?>"
           data-thumb="<?= e($thumb) ?>"
           data-name="<?= e((string)$item['original_name']) ?>"
           data-alt="<?= e((string)$item['alt']) ?>">
        <div class="thumb"><?= $thumb !== '' ? '<img src="' . e($thumb) . '" alt="' . e((string)$item['alt']) . '" loading="lazy">' : '' ?></div>
        <div class="meta">
          <strong><?= e((string)$item['original_name']) ?></strong>
          <?= (int)$item['width'] ?>×<?= (int)$item['height'] ?> · <?= e(human_bytes((int)$item['bytes'])) ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
