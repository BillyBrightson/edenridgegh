<?php
/** @var array $c */
use Core\Media;
$spreads = rows($c, 'spreads');
?>
<section id="about" class="on-dark">
  <?php foreach ($spreads as $spread): ?>
    <?php $reverse = ($spread['image_side'] ?? 'left') === 'right'; ?>
    <div class="spread<?= $reverse ? ' reverse' : '' ?>">
      <?php if ($reverse): ?>
        <div class="spread-text on-dark reveal">
          <?php if (($spread['eyebrow'] ?? '') !== ''): ?><div class="eyebrow"><?= e($spread['eyebrow']) ?></div><?php endif; ?>
          <h2><?= headline($spread['headline'] ?? '') ?></h2>
          <?php foreach (paragraphs($spread['body'] ?? '') as $paragraph): ?>
            <p class="body"><?= para($paragraph) ?></p>
          <?php endforeach; ?>
          <?php if (($spread['pull_quote'] ?? '') !== ''): ?>
            <div class="pull-quote"><?= e($spread['pull_quote']) ?></div>
          <?php endif; ?>
        </div>
        <div class="spread-media">
          <?= Media::img($spread['image'] ?? null, ['sizes' => '(max-width: 900px) 100vw, 50vw']) ?>
          <?php if (($spread['caption'] ?? '') !== ''): ?>
            <div class="media-scrim-b"></div>
            <span class="spread-caption"><?= e($spread['caption']) ?></span>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="spread-media">
          <?= Media::img($spread['image'] ?? null, ['sizes' => '(max-width: 900px) 100vw, 50vw']) ?>
          <?php if (($spread['caption'] ?? '') !== ''): ?>
            <div class="media-scrim-b"></div>
            <span class="spread-caption"><?= e($spread['caption']) ?></span>
          <?php endif; ?>
        </div>
        <div class="spread-text on-dark reveal">
          <?php if (($spread['eyebrow'] ?? '') !== ''): ?><div class="eyebrow"><?= e($spread['eyebrow']) ?></div><?php endif; ?>
          <h2><?= headline($spread['headline'] ?? '') ?></h2>
          <?php foreach (paragraphs($spread['body'] ?? '') as $paragraph): ?>
            <p class="body"><?= para($paragraph) ?></p>
          <?php endforeach; ?>
          <?php if (($spread['pull_quote'] ?? '') !== ''): ?>
            <div class="pull-quote"><?= e($spread['pull_quote']) ?></div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <?php if (!empty($c['show_band'])): ?>
    <div class="band">
      <?= Media::img($c['band_image'] ?? null, ['class' => 'band-img', 'sizes' => '100vw']) ?>
      <div class="band-scrim"></div>
      <div class="wrap band-inner reveal">
        <?php if (($c['band_eyebrow'] ?? '') !== ''): ?><div class="eyebrow"><?= e($c['band_eyebrow']) ?></div><?php endif; ?>
        <h2><?= headline($c['band_headline'] ?? '') ?></h2>
        <?php if (($c['band_body'] ?? '') !== ''): ?><p><?= para($c['band_body']) ?></p><?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</section>
