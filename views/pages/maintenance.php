<?php
use Core\{Icons, Settings};
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Eden Ridge</title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="<?= e(asset('/assets/css/tokens.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('/assets/css/site.css')) ?>">
</head>
<body>
<main class="maintenance" id="main">
  <div>
    <div class="maintenance-mark"><?= Icons::mark() ?></div>
    <h1>Eden Ridge</h1>
    <p><?= e((string)Settings::get('maintenance_message', 'We will be back shortly.')) ?></p>
  </div>
</main>
</body>
</html>
