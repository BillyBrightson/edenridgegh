<?php
/** @var int $code */
/** @var string $title */
$message = $message ?? 'The page you were looking for is not here. It may have moved, or the link may be out of date.';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($code . ' — ' . $title) ?> | Eden Ridge</title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="<?= e(asset('/assets/css/tokens.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('/assets/css/site.css')) ?>">
</head>
<body>
<main id="main">
  <section class="page-narrow">
    <div class="wrap">
      <div class="code"><?= e((string)$code) ?></div>
      <h1><?= e($title) ?></h1>
      <p><?= e($message) ?></p>
      <a href="/" class="btn btn-primary">Back to Eden Ridge</a>
    </div>
  </section>
</main>
</body>
</html>
