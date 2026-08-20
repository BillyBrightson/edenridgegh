<?php
/** @var string $header */
/** @var string $footer */
/** @var string $message */
?>
<?= $header ?>
<main id="main">
  <section class="page-narrow">
    <div class="wrap">
      <div class="eyebrow center-eyebrow">Enquiry received</div>
      <h1>Thank you</h1>
      <p><?= e($message) ?></p>
      <a href="/" class="btn btn-primary">Back to the site</a>
    </div>
  </section>
</main>
<?= $footer ?>
