<?php
/** @var array $c */
use Core\Media;
?>
<section class="section-pad" id="investment">
  <div class="wrap">
    <div class="invest-grid">
      <div class="invest-head reveal">
        <?php if (($c['eyebrow'] ?? '') !== ''): ?><div class="eyebrow"><?= e($c['eyebrow']) ?></div><?php endif; ?>
        <h2><?= headline($c['headline'] ?? '') ?></h2>
        <?= Media::img($c['image'] ?? null, ['class' => 'invest-media', 'sizes' => '(max-width: 900px) 100vw, 45vw']) ?>
      </div>
      <ul class="invest-list reveal">
        <?php foreach (rows($c, 'items') as $item): ?>
          <li>
            <div>
              <h3><?= e($item['title'] ?? '') ?></h3>
              <p><?= para($item['body'] ?? '') ?></p>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php if (($c['summary'] ?? '') !== ''): ?>
      <p class="invest-summary"><?= e($c['summary']) ?></p>
    <?php endif; ?>
  </div>
</section>
