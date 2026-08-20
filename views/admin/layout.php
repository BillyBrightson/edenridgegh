<?php
/** @var string $content */
/** @var string $title */
use Core\{AdminUi, Auth, Config, Csrf, Enquiry, Icons, Mailer, Router, Session, Settings};

$user    = Auth::user() ?? ['name' => '', 'email' => '', 'role' => ''];
$current = Router::currentPath();
$unread  = Enquiry::unreadCount();
$failed  = Mailer::pendingCount();
$siteUrl = rtrim((string)Config::get('site_url', ''), '/') ?: '/';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf" content="<?= e(Csrf::token()) ?>">
<title><?= e($title ?? 'Dashboard') ?> — Eden Ridge</title>
<link rel="icon" href="data:,">
<link rel="stylesheet" href="<?= e(admin_asset('/assets/admin.css')) ?>">
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <div class="sidebar-brand">
      <?= Icons::mark() ?>
      <span>
        <span class="name">EDEN RIDGE</span>
        <span class="tag">Dashboard</span>
      </span>
    </div>
    <nav aria-label="Dashboard">
      <?php foreach (AdminUi::nav() as [$path, $label, $icon, $ability]): ?>
        <?php if ($path === '__group'): ?>
          <div class="group"><?= e($label) ?></div>
          <?php continue; ?>
        <?php endif; ?>
        <?php if ($ability !== '' && !Auth::can($ability)) { continue; } ?>
        <?php
        $active = $path === '/' ? $current === '/' : str_starts_with($current, $path);
        ?>
        <a href="<?= e($path) ?>" class="<?= $active ? 'active' : '' ?>">
          <?= AdminUi::icon($icon) ?>
          <span><?= e($label) ?></span>
          <?php if ($path === '/enquiries' && $unread > 0): ?>
            <span class="badge"><?= (int)$unread ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>
  </aside>

  <div class="main">
    <header class="topbar">
      <button class="menu-toggle" type="button" aria-label="Toggle menu">☰</button>
      <h1><?= e($title ?? 'Dashboard') ?></h1>
      <div class="spacer"></div>
      <a class="ghost" href="<?= e($siteUrl) ?>" target="_blank" rel="noopener">View site</a>
      <details class="account">
        <summary>
          <span class="avatar"><?= e(AdminUi::initials((string)$user['name'])) ?></span>
          <span class="sr-only">Account menu</span>
        </summary>
        <div class="account-menu">
          <div style="padding:8px 11px;border-bottom:1px solid var(--a-line);">
            <strong style="display:block;font-size:13px;"><?= e((string)$user['name']) ?></strong>
            <span style="font-size:11.5px;color:var(--a-ink-faint);"><?= e(ucfirst((string)$user['role'])) ?></span>
          </div>
          <a href="/account">Your account</a>
          <form method="post" action="/logout">
            <?= Csrf::field() ?>
            <button type="submit">Sign out</button>
          </form>
        </div>
      </details>
    </header>

    <div class="content<?= !empty($wide) ? ' wide' : '' ?>">
      <?php foreach (Session::flashes() as $flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
      <?php endforeach; ?>
      <?php if ($failed > 0 && Auth::can('edit_content')): ?>
        <div class="flash flash-warn">
          <?= (int)$failed ?> notification email<?= $failed === 1 ? '' : 's' ?> could not be delivered yet.
          They will be retried automatically — check the SMTP details under
          <a href="/settings#email" style="text-decoration:underline;">Site settings</a>.
        </div>
      <?php endif; ?>
      <?= $content ?>
    </div>
  </div>
</div>

<div class="modal" id="mediaPicker" aria-hidden="true">
  <div class="modal-panel" role="dialog" aria-modal="true" aria-label="Choose an image">
    <div class="modal-head">
      <h3>Media library</h3>
      <div class="spacer"></div>
      <input type="search" id="mediaPickerSearch" placeholder="Search by name or alt text" style="max-width:260px;">
      <button type="button" class="icon-btn" data-picker-close aria-label="Close">&times;</button>
    </div>
    <div class="modal-body" id="mediaPickerBody"></div>
    <div class="modal-foot">
      <button type="button" class="btn btn-ghost" data-picker-close>Cancel</button>
      <button type="button" class="btn btn-accent" id="mediaPickerApply">Use this image</button>
    </div>
  </div>
</div>

<script src="<?= e(admin_asset('/assets/admin.js')) ?>" defer></script>
</body>
</html>
