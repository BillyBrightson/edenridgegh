<?php
/** @var array $c */
use Core\Sanitizer;
$items = rows($c, 'items');
?>
<section class="section-pad faq-section" id="faq">
  <div class="wrap narrow">
    <div class="section-head reveal">
      <?php if (($c['eyebrow'] ?? '') !== ''): ?><div class="eyebrow"><?= e($c['eyebrow']) ?></div><?php endif; ?>
      <h2><?= headline($c['headline'] ?? '') ?></h2>
    </div>
    <div class="reveal">
      <?php foreach ($items as $i => $item): ?>
        <?php $open = $i === 0 && !empty($c['first_open']); ?>
        <div class="faq-item<?= $open ? ' open' : '' ?>"<?= $open ? ' data-open="1"' : '' ?>>
          <button class="faq-q" id="faq-q-<?= $i ?>" aria-expanded="<?= $open ? 'true' : 'false' ?>" aria-controls="faq-a-<?= $i ?>">
            <?= e($item['question'] ?? '') ?><span class="plus" aria-hidden="true">+</span>
          </button>
          <div class="faq-a" id="faq-a-<?= $i ?>" role="region" aria-labelledby="faq-q-<?= $i ?>">
            <?= Sanitizer::richtext((string)($item['answer'] ?? '')) ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
