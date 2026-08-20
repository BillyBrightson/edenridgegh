<?php
/** @var array $c */
use Core\{Gallery, Media};
$categories = Gallery::categories();
$items      = Gallery::items();
?>
<section id="gallery" class="section-pad">
  <div class="wrap">
    <div class="section-head reveal">
      <?php if (($c['eyebrow'] ?? '') !== ''): ?><div class="eyebrow"><?= e($c['eyebrow']) ?></div><?php endif; ?>
      <h2><?= headline($c['headline'] ?? '') ?></h2>
      <?php if (($c['lede'] ?? '') !== ''): ?><p class="lede"><?= para($c['lede']) ?></p><?php endif; ?>
    </div>
    <?php if ($categories): ?>
      <div class="filter-row reveal" role="group" aria-label="Filter the gallery by room">
        <button class="filter-btn active" data-filter="all" aria-pressed="true"><?= e($c['all_label'] ?: 'All') ?></button>
        <?php foreach ($categories as $category): ?>
          <button class="filter-btn" data-filter="<?= e($category['slug']) ?>" aria-pressed="false"><?= e($category['label']) ?></button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="masonry reveal" id="masonryGrid">
      <?php foreach ($items as $item): ?>
        <div class="masonry-item" data-cat="<?= e($item['category_slug'] ?? '') ?>"
             data-full="<?= e(Media::url((int)$item['media_id'], 1920)) ?>"
             role="button" tabindex="0"
             aria-label="<?= e('Open ' . ($item['caption'] ?: $item['media_alt']) . ' in the lightbox') ?>">
          <?= Media::img((int)$item['media_id'], ['sizes' => '(max-width: 780px) 50vw, 33vw']) ?>
          <?php if (($item['caption'] ?? '') !== ''): ?><span class="tag"><?= e($item['caption']) ?></span><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Gallery image" aria-hidden="true">
  <button class="lb-close" id="lbClose" aria-label="Close">&times;</button>
  <button class="lb-prev" id="lbPrev" aria-label="Previous image">&#8249;</button>
  <img id="lbImg" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="">
  <button class="lb-next" id="lbNext" aria-label="Next image">&#8250;</button>
  <div class="cap" id="lbCap"></div>
</div>
