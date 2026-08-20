<?php
/** @var array $c */
use Core\Media;
$stats = rows($c, 'stats');
?>
<section class="hero" id="home">
  <?= Media::img($c['background_image'] ?? null, [
      'class' => 'hero-img',
      'sizes' => '100vw',
      'priority' => true,
  ]) ?>
  <div class="hero-scrim"></div>
  <div class="hero-content wrap">
    <?php if (($c['eyebrow'] ?? '') !== ''): ?>
      <div class="hero-eyebrow reveal in"><?= e($c['eyebrow']) ?></div>
    <?php endif; ?>
    <h1 class="reveal in"><?= headline($c['headline'] ?? '') ?></h1>
    <?php if (($c['body'] ?? '') !== ''): ?>
      <p class="hero-sub reveal in"><?= para($c['body']) ?></p>
    <?php endif; ?>
    <div class="hero-ctas reveal in">
      <?php if (($c['primary_cta_label'] ?? '') !== ''): ?>
        <a href="<?= e($c['primary_cta_target'] ?? '#contact') ?>" class="btn btn-primary"><?= e($c['primary_cta_label']) ?></a>
      <?php endif; ?>
      <?php if (($c['secondary_cta_label'] ?? '') !== ''): ?>
        <a href="<?= e($c['secondary_cta_target'] ?? '#homes') ?>" class="btn btn-ghost"><?= e($c['secondary_cta_label']) ?></a>
      <?php endif; ?>
    </div>
  </div>
  <?php if (($c['scroll_label'] ?? '') !== ''): ?>
    <div class="scroll-cue" aria-hidden="true"><div class="line"></div><span><?= e($c['scroll_label']) ?></span></div>
  <?php endif; ?>
</section>

<?php if (!empty($c['show_stats']) && $stats): ?>
<div class="stat-strip">
  <div class="wrap">
    <?php foreach ($stats as $stat): ?>
      <div class="stat-item">
        <div class="stat-num"><?= e($stat['value'] ?? '') ?></div>
        <div class="stat-label"><?= e($stat['label'] ?? '') ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
