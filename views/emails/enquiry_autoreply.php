<?php
/** @var array $enquiry */
/** @var string $body */
foreach (preg_split('/\n\s*\n/', trim($body)) ?: [] as $paragraph): ?>
  <p style="margin:0 0 16px;"><?= nl2br(e(trim($paragraph))) ?></p>
<?php endforeach; ?>
