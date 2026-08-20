<?php
/** @var array $page */
/** @var string $header */
/** @var string $footer */
use Core\Sanitizer;
?>
<?= $header ?>
<main id="main">
  <section class="page-hero">
    <div class="wrap">
      <div class="eyebrow">Eden Ridge</div>
      <h1><?= e($page['title']) ?></h1>
    </div>
  </section>
  <section>
    <div class="wrap page-body">
      <?= Sanitizer::richtext((string)$page['body']) ?>
    </div>
  </section>
</main>
<?= $footer ?>
