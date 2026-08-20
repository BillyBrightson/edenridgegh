<?php
/** @var array $c section content */
use Core\{Icons, Media};
$navItems = array_filter(rows($c, 'nav_items'), 'row_visible');
?>
<header class="nav" id="siteNav" data-sticky="<?= !empty($c['sticky_on_scroll']) ? '1' : '0' ?>">
  <div class="nav-inner">
    <a href="#home" class="logo" aria-label="<?= e(($c['logo_text'] ?? 'Eden Ridge') . ' home') ?>">
      <?php if (!empty($c['logo_image'])): ?>
        <?= Media::img((int)$c['logo_image'], ['class' => 'logo-img', 'sizes' => '160px', 'loading' => 'eager', 'alt' => (string)($c['logo_text'] ?? 'Eden Ridge')]) ?>
      <?php else: ?>
        <?= Icons::mark() ?>
        <span class="logo-text">
          <span class="name"><?= e($c['logo_text'] ?? '') ?></span>
          <?php if (($c['logo_tagline'] ?? '') !== ''): ?><span class="tag"><?= e($c['logo_tagline']) ?></span><?php endif; ?>
        </span>
      <?php endif; ?>
    </a>
    <nav class="links" aria-label="Primary">
      <?php foreach ($navItems as $item): ?>
        <a href="<?= e($item['anchor'] ?? '#') ?>"><?= e($item['label'] ?? '') ?></a>
      <?php endforeach; ?>
    </nav>
    <?php if (($c['cta_label'] ?? '') !== ''): ?>
      <a href="<?= e($c['cta_target'] ?? '#contact') ?>" class="nav-cta"><?= e($c['cta_label']) ?></a>
    <?php endif; ?>
    <button class="burger" id="burgerBtn" aria-label="Open menu" aria-expanded="false" aria-controls="mobileMenu">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>

<div class="mobile-menu" id="mobileMenu" aria-hidden="true">
  <div class="top">
    <span class="logo-text mobile-wordmark"><span class="name"><?= e($c['logo_text'] ?? '') ?></span></span>
    <button class="close" id="mobileClose" aria-label="Close menu">&times;</button>
  </div>
  <?php foreach ($navItems as $item): ?>
    <a href="<?= e($item['anchor'] ?? '#') ?>"><?= e($item['label'] ?? '') ?></a>
  <?php endforeach; ?>
</div>
