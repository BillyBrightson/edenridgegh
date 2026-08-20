<?php
/** @var array $c */
use Core\Media;
?>
<section id="homes" class="model-wrap section-pad">
  <div class="wrap">
    <div class="model-top reveal">
      <div>
        <?php if (($c['eyebrow'] ?? '') !== ''): ?><div class="eyebrow"><?= e($c['eyebrow']) ?></div><?php endif; ?>
        <h2><?= headline($c['headline'] ?? '') ?></h2>
      </div>
      <?php if (($c['tab_label'] ?? '') !== ''): ?>
        <div class="model-tabs"><div class="model-tab active"><?= e($c['tab_label']) ?></div></div>
      <?php endif; ?>
    </div>

    <div class="model-card reveal">
      <div class="model-image">
        <?= Media::img($c['image'] ?? null, ['sizes' => '(max-width: 860px) 100vw, 55vw']) ?>
      </div>
      <div class="model-info">
        <h3><?= e($c['card_title'] ?? '') ?></h3>
        <?php if (($c['price_note'] ?? '') !== ''): ?>
          <div class="price-note"><?= e($c['price_note']) ?></div>
        <?php endif; ?>
        <div class="model-specs">
          <?php foreach (rows($c, 'specs') as $spec): ?>
            <div class="spec-row"><span><?= e($spec['label'] ?? '') ?></span><span><?= e($spec['value'] ?? '') ?></span></div>
          <?php endforeach; ?>
        </div>
        <ul class="model-features">
          <?php foreach (rows($c, 'features') as $feature): ?>
            <li><?= e($feature['text'] ?? '') ?></li>
          <?php endforeach; ?>
        </ul>
        <div class="model-actions">
          <?php if (($c['cta_1_label'] ?? '') !== ''): ?>
            <a href="<?= e($c['cta_1_target'] ?? '#') ?>" class="btn btn-ghost"><?= e($c['cta_1_label']) ?></a>
          <?php endif; ?>
          <?php if (($c['cta_2_label'] ?? '') !== ''): ?>
            <a href="<?= e($c['cta_2_target'] ?? '#') ?>" class="btn btn-primary"><?= e($c['cta_2_label']) ?></a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
