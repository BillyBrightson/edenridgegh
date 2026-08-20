<?php
/** @var array $items */
/** @var int $total */
/** @var int $page */
/** @var int $limit */
/** @var string $search */
use Core\{Csrf, Media};
$pages = (int)max(1, ceil($total / $limit));
?>
<div class="page-head">
  <div>
    <h2>Media library</h2>
    <p><?= (int)$total ?> image<?= $total === 1 ? '' : 's' ?>. This server accepts files up to <?= e(human_bytes(Media::serverLimit())) ?>.</p>
  </div>
</div>

<div class="card">
  <div class="card-head">
    <h3>Upload</h3>
    <div class="spacer"></div>
    <form method="get" action="/media">
      <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search name or alt text" style="max-width:260px;">
    </form>
  </div>
  <div class="card-body">
    <form method="post" action="/media/upload-many" enctype="multipart/form-data">
      <?= Csrf::field() ?>
      <div class="field">
        <label class="field-label" for="files">Choose images (JPEG, PNG, WebP or SVG)</label>
        <input type="file" id="files" name="files[]" multiple accept="image/jpeg,image/png,image/webp,image/svg+xml">
        <p class="hint">Every upload is converted to WebP at six widths plus a JPEG fallback, so pages stay fast on mobile data.</p>
      </div>
      <button type="submit" class="btn btn-accent btn-sm">Upload</button>
    </form>
  </div>
</div>

<?php if ($items): ?>
  <div class="grid cols-3" style="margin-top:18px;">
    <?php foreach ($items as $item): ?>
      <div class="card">
        <div class="thumb" style="aspect-ratio:4/3;background:var(--a-bg);">
          <?php $thumb = Media::url((int)$item['id'], 960); ?>
          <?php if ($thumb !== ''): ?>
            <img src="<?= e($thumb) ?>" alt="<?= e((string)$item['alt']) ?>" loading="lazy" style="width:100%;height:100%;object-fit:cover;">
          <?php endif; ?>
        </div>
        <div class="card-body tight">
          <strong style="display:block;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e((string)$item['original_name']) ?></strong>
          <p style="font-size:11.5px;color:var(--a-ink-faint);margin:2px 0 10px;">
            <?= (int)$item['width'] ?>×<?= (int)$item['height'] ?> · <?= e(human_bytes((int)$item['bytes'])) ?> · <?= e(human_date((string)$item['created_at'], 'j M Y')) ?>
          </p>
          <form method="post" action="/media/<?= (int)$item['id'] ?>/update">
            <?= Csrf::field() ?>
            <div class="field" style="margin-bottom:10px;">
              <label class="field-label" for="alt-<?= (int)$item['id'] ?>">Alt text</label>
              <input type="text" id="alt-<?= (int)$item['id'] ?>" name="alt" value="<?= e((string)$item['alt']) ?>">
            </div>
            <div class="field" style="margin-bottom:10px;">
              <label class="field-label" for="cap-<?= (int)$item['id'] ?>">Caption</label>
              <input type="text" id="cap-<?= (int)$item['id'] ?>" name="caption" value="<?= e((string)$item['caption']) ?>">
            </div>
            <div class="grid cols-2" style="gap:8px;margin-bottom:10px;">
              <div class="field" style="margin:0;">
                <label class="field-label" for="fx-<?= (int)$item['id'] ?>">Focal X</label>
                <input type="number" step="0.05" min="0" max="1" id="fx-<?= (int)$item['id'] ?>" name="focal_x" value="<?= e((string)$item['focal_x']) ?>">
              </div>
              <div class="field" style="margin:0;">
                <label class="field-label" for="fy-<?= (int)$item['id'] ?>">Focal Y</label>
                <input type="number" step="0.05" min="0" max="1" id="fy-<?= (int)$item['id'] ?>" name="focal_y" value="<?= e((string)$item['focal_y']) ?>">
              </div>
            </div>
            <button type="submit" class="btn btn-ghost btn-sm">Save details</button>
          </form>
          <?php $usage = Media::usage((int)$item['id']); ?>
          <form method="post" action="/media/<?= (int)$item['id'] ?>/delete" style="margin-top:8px;"
                data-confirm="<?= e($usage ? 'This image is used in: ' . implode(', ', $usage) . '. Delete anyway?' : 'Delete this image permanently?') ?>">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--a-danger);">Delete</button>
          </form>
          <?php if ($usage): ?>
            <p style="font-size:11.5px;color:var(--a-ink-faint);margin-top:8px;">Used in: <?= e(implode(', ', $usage)) ?></p>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($pages > 1): ?>
    <div class="card" style="margin-top:18px;">
      <div class="pagination">
        <span>Page <?= $page ?> of <?= $pages ?></span>
        <div class="spacer"></div>
        <?php if ($page > 1): ?><a href="/media?page=<?= $page - 1 ?>&q=<?= e($search) ?>">Previous</a><?php endif; ?>
        <?php if ($page < $pages): ?><a href="/media?page=<?= $page + 1 ?>&q=<?= e($search) ?>">Next</a><?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
<?php else: ?>
  <div class="card" style="margin-top:18px;"><div class="empty"><h4>No images yet</h4><p>Upload the render set to get started.</p></div></div>
<?php endif; ?>
