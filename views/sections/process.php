<?php
/** @var array $c */
?>
<section class="section-pad process-section on-dark" id="process">
  <div class="wrap">
    <div class="section-head invert reveal">
      <?php if (($c['eyebrow'] ?? '') !== ''): ?><div class="eyebrow"><?= e($c['eyebrow']) ?></div><?php endif; ?>
      <h2><?= headline($c['headline'] ?? '') ?></h2>
      <?php if (($c['lede'] ?? '') !== ''): ?><p class="lede"><?= para($c['lede']) ?></p><?php endif; ?>
    </div>
    <div class="why-grid stage-grid reveal">
      <?php foreach (rows($c, 'stages') as $stage): ?>
        <div class="why-card stage-card">
          <?php if (($stage['stage_label'] ?? '') !== ''): ?><div class="eyebrow"><?= e($stage['stage_label']) ?></div><?php endif; ?>
          <h3><?= e($stage['title'] ?? '') ?></h3>
          <?php if (($stage['amount'] ?? '') !== ''): ?><div class="stage-amount"><?= e($stage['amount']) ?></div><?php endif; ?>
          <p><?= para($stage['body'] ?? '') ?></p>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if (($c['disclaimer'] ?? '') !== ''): ?>
      <p class="stage-note"><?= para($c['disclaimer']) ?></p>
    <?php endif; ?>
  </div>
</section>
