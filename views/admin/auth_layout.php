<?php
/** @var string $content */
use Core\{Icons, Session};
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title ?? 'Sign in') ?> — Eden Ridge</title>
<link rel="icon" href="data:,">
<link rel="stylesheet" href="<?= e(admin_asset('/assets/admin.css')) ?>">
</head>
<body>
<main class="auth">
  <div class="auth-card">
    <div class="auth-brand">
      <?= Icons::mark() ?>
      <span class="name">EDEN RIDGE</span>
    </div>
    <?php foreach (Session::flashes() as $flash): ?>
      <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
  </div>
</main>
</body>
</html>
