<?php
/** @var array $user */
/** @var string $link */
?>
<h2 style="font-family:Georgia,serif;font-size:20px;margin:0 0 12px;">Reset your dashboard password</h2>
<p style="margin:0 0 16px;">Hello <?= e((string)$user['name']) ?>,</p>
<p style="margin:0 0 16px;">Use the button below to choose a new password for the Eden Ridge dashboard. The link works once and expires in 60 minutes.</p>
<p style="margin:24px 0;">
  <a href="<?= e($link) ?>" style="display:inline-block;background:#b6904f;color:#161b13;padding:13px 24px;font-size:12px;letter-spacing:.09em;text-transform:uppercase;text-decoration:none;">Choose a new password</a>
</p>
<p style="margin:0;color:#8d8b82;font-size:13px;">If you did not request this, you can ignore this email — your password will not change.</p>
