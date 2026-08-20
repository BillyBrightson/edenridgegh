<?php
/** @var array $backups */
/** @var int $cacheSize */
/** @var string $version */
/** @var array $mailFailed */
use Core\{Backup, Csrf, Media};
?>
<div class="page-head">
  <div>
    <h2>Tools</h2>
    <p>Backups, cache and maintenance. Schema version <code><?= e($version) ?></code>.</p>
  </div>
</div>

<div class="grid cols-2">
  <div class="card">
    <div class="card-head"><h3>Download a backup</h3></div>
    <div class="card-body">
      <?php if (!Backup::available()): ?>
        <div class="flash flash-warn">The ZipArchive extension is not enabled on this server, so backups are unavailable. Ask your host to enable it.</div>
      <?php else: ?>
        <p style="font-size:13px;color:var(--a-ink-soft);">A zip containing the database and, optionally, every uploaded original. Keep a copy off the server.</p>
        <form method="post" action="/tools/backup" style="margin-top:14px;">
          <?= Csrf::field() ?>
          <div class="field">
            <input type="hidden" name="include_uploads" value="0">
            <label class="switch">
              <input type="checkbox" name="include_uploads" value="1" checked>
              <span class="track"></span><span>Include uploaded images</span>
            </label>
          </div>
          <button type="submit" class="btn btn-accent btn-sm">Create &amp; download</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h3>Cache</h3></div>
    <div class="card-body">
      <p style="font-size:13px;color:var(--a-ink-soft);">
        The rendered home page is cached and cleared automatically whenever you publish.
        Currently holding <?= e(human_bytes($cacheSize)) ?>.
      </p>
      <form method="post" action="/tools/cache" style="margin-top:14px;">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn-ghost btn-sm">Clear the cache</button>
      </form>
      <form method="post" action="/tools/derivatives" style="margin-top:10px;" data-confirm="Regenerate every image size? This can take a minute.">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn-ghost btn-sm">Rebuild image sizes</button>
      </form>
    </div>
  </div>
</div>

<div class="card" style="margin-top:18px;">
  <div class="card-head"><h3>Backups on the server</h3></div>
  <?php if ($backups): ?>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>File</th><th>Size</th><th>Created</th></tr></thead>
        <tbody>
        <?php foreach ($backups as $backup): ?>
          <tr>
            <td><code><?= e($backup['name']) ?></code></td>
            <td><?= e(human_bytes($backup['bytes'])) ?></td>
            <td style="color:var(--a-ink-faint);"><?= e(date('j M Y, H:i', $backup['mtime'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-body tight">
      <p class="hint">Stored in <code>storage/backups</code>, outside the web root, and rotated after seven days.</p>
    </div>
  <?php else: ?>
    <div class="empty"><p>No automatic backups yet — the first one runs after midnight UTC.</p></div>
  <?php endif; ?>
</div>

<div class="card" style="margin-top:18px;">
  <div class="card-head"><h3>Restore from a backup</h3></div>
  <div class="card-body">
    <div class="flash flash-warn">Restoring replaces all current content, media records and enquiries with the contents of the archive. A safety backup is taken first.</div>
    <form method="post" action="/tools/restore" enctype="multipart/form-data" data-confirm="Replace everything with this backup?">
      <?= Csrf::field() ?>
      <div class="field">
        <label class="field-label" for="archive">Backup archive (.zip)</label>
        <input type="file" id="archive" name="archive" accept=".zip,application/zip" required>
        <p class="hint">Maximum upload size on this server: <?= e(human_bytes(Media::serverLimit())) ?>.</p>
      </div>
      <div class="field">
        <label class="field-label" for="confirm">Type RESTORE to confirm</label>
        <input type="text" id="confirm" name="confirm" placeholder="RESTORE" required style="max-width:220px;">
      </div>
      <button type="submit" class="btn btn-danger btn-sm">Restore this backup</button>
    </form>
  </div>
</div>

<?php if ($mailFailed): ?>
  <div class="card" style="margin-top:18px;margin-bottom:40px;">
    <div class="card-head">
      <h3>Undelivered email</h3>
      <div class="spacer"></div>
      <form method="post" action="/tools/mail-retry">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn-ghost btn-sm">Retry now</button>
      </form>
    </div>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>To</th><th>Subject</th><th>Attempts</th><th>Last error</th></tr></thead>
        <tbody>
        <?php foreach ($mailFailed as $mail): ?>
          <tr>
            <td><?= e((string)$mail['to_email']) ?></td>
            <td><?= e((string)$mail['subject']) ?></td>
            <td><?= (int)$mail['attempts'] ?></td>
            <td style="color:var(--a-danger);font-size:12px;"><?= e(mb_substr((string)$mail['last_error'], 0, 120)) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
