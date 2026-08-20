<?php
/** @var array $c */
use Core\Media;
$blocks = rows($c, 'blocks');
// The reference groups consecutive blocks of the same colour into one
// <section>, so the pine background runs unbroken behind the dark spreads.
$groups = [];
foreach ($blocks as $block) {
    $theme = ($block['theme'] ?? 'light') === 'dark' ? 'dark' : 'light';
    if (!$groups || end($groups)['theme'] !== $theme) {
        $groups[] = ['theme' => $theme, 'blocks' => []];
    }
    $groups[array_key_last($groups)]['blocks'][] = $block;
}
?>
<?php $first = true; foreach ($groups as $group): ?>
<section<?= $group['theme'] === 'dark' ? ' class="on-dark detail-dark"' : '' ?><?= $first ? ' id="details"' : '' ?>>
  <?php $first = false; ?>
  <?php foreach ($group['blocks'] as $block): ?>
    <?php
    $reverse   = ($block['image_side'] ?? 'left') === 'right';
    $textClass = $group['theme'] === 'dark' ? 'on-dark' : 'on-light';
    ?>
    <div class="spread<?= $reverse ? ' reverse' : '' ?>">
      <?php if ($reverse): ?>
        <div class="spread-text <?= $textClass ?> reveal">
          <?php if (($block['eyebrow'] ?? '') !== ''): ?><div class="eyebrow"><?= e($block['eyebrow']) ?></div><?php endif; ?>
          <h2><?= headline($block['headline'] ?? '') ?></h2>
          <?php foreach (paragraphs($block['body'] ?? '') as $paragraph): ?>
            <p class="body"><?= para($paragraph) ?></p>
          <?php endforeach; ?>
        </div>
        <div class="spread-media">
          <?= Media::img($block['image'] ?? null, ['sizes' => '(max-width: 900px) 100vw, 50vw']) ?>
          <?php if (($block['caption'] ?? '') !== ''): ?><span class="spread-caption"><?= e($block['caption']) ?></span><?php endif; ?>
        </div>
      <?php else: ?>
        <div class="spread-media">
          <?= Media::img($block['image'] ?? null, ['sizes' => '(max-width: 900px) 100vw, 50vw']) ?>
          <?php if (($block['caption'] ?? '') !== ''): ?><span class="spread-caption"><?= e($block['caption']) ?></span><?php endif; ?>
        </div>
        <div class="spread-text <?= $textClass ?> reveal">
          <?php if (($block['eyebrow'] ?? '') !== ''): ?><div class="eyebrow"><?= e($block['eyebrow']) ?></div><?php endif; ?>
          <h2><?= headline($block['headline'] ?? '') ?></h2>
          <?php foreach (paragraphs($block['body'] ?? '') as $paragraph): ?>
            <p class="body"><?= para($paragraph) ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</section>
<?php endforeach; ?>
