<?php
/** @var array $categories */
/** @var array $items */
use Core\{Csrf, Media};
?>
<div class="page-head">
  <div>
    <h2>Gallery</h2>
    <p>The filtered render grid on the home page. Order here is the order on the site.</p>
  </div>
</div>

<form method="post" action="/gallery/save" data-guard>
  <?= Csrf::field() ?>

  <div class="card">
    <div class="card-head"><h3>Filter chips</h3></div>
    <div class="card-body">
      <div class="grid cols-2">
        <?php foreach ($categories as $category): ?>
          <div style="display:flex;gap:8px;align-items:flex-end;">
            <div class="field" style="flex:1;margin:0;">
              <label class="field-label" for="cat-<?= (int)$category['id'] ?>">Label</label>
              <input type="text" id="cat-<?= (int)$category['id'] ?>" name="categories[<?= (int)$category['id'] ?>][label]" value="<?= e((string)$category['label']) ?>">
            </div>
            <div class="field" style="width:88px;margin:0;">
              <label class="field-label" for="cato-<?= (int)$category['id'] ?>">Order</label>
              <input type="number" id="cato-<?= (int)$category['id'] ?>" name="categories[<?= (int)$category['id'] ?>][sort_order]" value="<?= (int)$category['sort_order'] ?>">
            </div>
          </div>
        <?php endforeach; ?>
        <div class="field" style="margin:0;">
          <label class="field-label" for="new_category">Add a filter</label>
          <input type="text" id="new_category" name="new_category" placeholder="e.g. Community">
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-head">
      <h3>Images <span style="color:var(--a-ink-faint);font-weight:400;">(<?= count($items) ?>)</span></h3>
    </div>
    <div class="card-body">
      <?php if (!$items): ?>
        <div class="empty"><h4>The gallery is empty</h4><p>Add images from the media library below.</p></div>
      <?php else: ?>
        <div class="grid cols-3">
          <?php foreach ($items as $item): ?>
            <div class="card" style="box-shadow:none;">
              <div style="aspect-ratio:4/3;background:var(--a-bg);">
                <?php $thumb = Media::url((int)$item['media_id'], 640); ?>
                <?php if ($thumb !== ''): ?>
                  <img src="<?= e($thumb) ?>" alt="<?= e((string)$item['media_alt']) ?>" loading="lazy" style="width:100%;height:100%;object-fit:cover;">
                <?php endif; ?>
              </div>
              <div class="card-body tight">
                <div class="field" style="margin-bottom:9px;">
                  <label class="field-label" for="gcap-<?= (int)$item['id'] ?>">Hover caption</label>
                  <input type="text" id="gcap-<?= (int)$item['id'] ?>" name="items[<?= (int)$item['id'] ?>][caption]" value="<?= e((string)$item['caption']) ?>">
                </div>
                <div class="field" style="margin-bottom:9px;">
                  <label class="field-label" for="gcat-<?= (int)$item['id'] ?>">Filter</label>
                  <select id="gcat-<?= (int)$item['id'] ?>" name="items[<?= (int)$item['id'] ?>][category_id]">
                    <option value="">Unfiltered</option>
                    <?php foreach ($categories as $category): ?>
                      <option value="<?= (int)$category['id'] ?>" <?= (int)$item['category_id'] === (int)$category['id'] ? 'selected' : '' ?>><?= e((string)$category['label']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div style="display:flex;gap:8px;align-items:flex-end;">
                  <div class="field" style="width:82px;margin:0;">
                    <label class="field-label" for="gord-<?= (int)$item['id'] ?>">Order</label>
                    <input type="number" id="gord-<?= (int)$item['id'] ?>" name="items[<?= (int)$item['id'] ?>][sort_order]" value="<?= (int)$item['sort_order'] ?>">
                  </div>
                  <label class="switch" style="margin-bottom:6px;">
                    <input type="hidden" name="items[<?= (int)$item['id'] ?>][is_published]" value="0">
                    <input type="checkbox" name="items[<?= (int)$item['id'] ?>][is_published]" value="1" <?= (int)$item['is_published'] === 1 ? 'checked' : '' ?>>
                    <span class="track"></span><span>Shown</span>
                  </label>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="savebar">
    <span class="state" data-dirty-state>All changes published</span>
    <div class="spacer"></div>
    <button type="submit" class="btn btn-accent">Save gallery</button>
  </div>
</form>

<div class="card" style="margin-top:18px;">
  <div class="card-head"><h3>Add images to the gallery</h3></div>
  <div class="card-body">
    <form method="post" action="/gallery/add">
      <?= Csrf::field() ?>
      <p class="hint">Tick any image from the media library, then add it to the grid.</p>
      <div class="media-grid" style="max-height:420px;overflow:auto;padding:2px;">
        <?php foreach (Media::all('', 60) as $media): ?>
          <label class="media-tile" style="display:block;cursor:pointer;">
            <div class="thumb">
              <?php $thumb = Media::url((int)$media['id'], 640); ?>
              <?php if ($thumb !== ''): ?><img src="<?= e($thumb) ?>" alt="" loading="lazy"><?php endif; ?>
            </div>
            <div class="meta" style="display:flex;gap:7px;align-items:center;">
              <input type="checkbox" name="media_ids[]" value="<?= (int)$media['id'] ?>" style="width:auto;">
              <strong><?= e((string)$media['original_name']) ?></strong>
            </div>
          </label>
        <?php endforeach; ?>
      </div>
      <button type="submit" class="btn btn-ghost btn-sm" style="margin-top:14px;">Add selected</button>
    </form>
  </div>
</div>

<?php if ($items): ?>
  <div class="card" style="margin-top:18px;">
    <div class="card-head"><h3>Remove from the gallery</h3></div>
    <div class="card-body">
      <div class="actions-row">
        <?php foreach ($items as $item): ?>
          <form method="post" action="/gallery/<?= (int)$item['id'] ?>/delete" data-confirm="Remove this image from the gallery? It stays in the media library.">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn-ghost btn-sm"><?= e((string)($item['caption'] ?: $item['media_alt'])) ?> ×</button>
          </form>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
<?php endif; ?>
