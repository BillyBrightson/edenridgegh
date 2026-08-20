<?php
/** @var string $body */
/** @var string $video_url */
foreach (preg_split('/\n\s*\n/', trim($body)) ?: [] as $paragraph): ?>
  <p style="margin:0 0 16px;"><?= nl2br(e(trim($paragraph))) ?></p>
<?php endforeach; ?>
<p style="margin:24px 0 0;">
  <a href="<?= e($video_url) ?>" style="display:inline-block;background:#b6904f;color:#161b13;padding:13px 24px;font-size:12px;letter-spacing:.09em;text-transform:uppercase;text-decoration:none;">Watch the walkthrough</a>
</p>
