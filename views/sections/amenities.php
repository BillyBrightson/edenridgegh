<?php
/** @var array $c */
use Core\Icons;
?>
<section class="section-pad" id="amenities">
  <div class="wrap">
    <div class="section-head reveal">
      <?php if (($c['eyebrow'] ?? '') !== ''): ?><div class="eyebrow"><?= e($c['eyebrow']) ?></div><?php endif; ?>
      <h2><?= headline($c['headline'] ?? '') ?></h2>
      <?php if (($c['lede'] ?? '') !== ''): ?><p class="lede"><?= para($c['lede']) ?></p><?php endif; ?>
    </div>
    <div class="amenity-grid reveal">
      <?php foreach (rows($c, 'items') as $item): ?>
        <div class="amenity-item">
          <div class="amenity-num"><?= e($item['number'] ?? '') ?></div>
          <div>
            <?= Icons::svg((string)($item['icon'] ?? 'none'), 'amenity-icon') ?>
            <h3><?= e($item['title'] ?? '') ?></h3>
            <p><?= para($item['body'] ?? '') ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
