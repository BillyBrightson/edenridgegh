<?php
/** @var array $checks */
/** @var bool $blocked */
/** @var array $errors */
/** @var bool $done */
use Core\Csrf;
?>
<?php if ($done): ?>
  <h1>Eden Ridge is installed</h1>
  <p class="lede">The reference content, images and gallery have been seeded. Sign in with the account you just created.</p>
  <ul style="font-size:13px;color:var(--a-ink-soft);line-height:1.9;padding-left:18px;">
    <li><?= (int)($counts['sections'] ?? 0) ?> sections</li>
    <li><?= (int)($counts['media'] ?? 0) ?> images</li>
    <li><?= (int)($counts['gallery'] ?? 0) ?> gallery items</li>
    <li><?= (int)($counts['settings'] ?? 0) ?> settings</li>
  </ul>
  <a class="btn btn-primary" href="/login" style="width:100%;justify-content:center;margin-top:14px;">Go to the dashboard</a>
  <p class="hint" style="margin-top:16px;">Delete nothing — this installer locks itself automatically now that <code>config.php</code> exists.</p>
<?php else: ?>
  <h1>Install Eden Ridge</h1>
  <p class="lede">One pass sets up the database, seeds the site content and creates your account.</p>

  <?php foreach ($errors as $error): ?>
    <div class="flash flash-error"><?= e($error) ?></div>
  <?php endforeach; ?>

  <h3 style="font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:var(--a-ink-faint);margin:20px 0 8px;">Environment</h3>
  <table class="data" style="margin-bottom:18px;">
    <tbody>
    <?php foreach ($checks as $label => [$ok, $detail]): ?>
      <tr>
        <td style="padding:7px 0;border:none;"><?= e($label) ?></td>
        <td style="padding:7px 0;border:none;text-align:right;">
          <span class="pill <?= $ok ? 'pill-won' : 'pill-spam' ?>"><?= $ok ? 'OK' : 'Missing' ?></span>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <?php if ($blocked): ?>
    <div class="flash flash-error">This server is missing something required. Fix the items marked Missing, then reload this page.</div>
  <?php else: ?>
    <form method="post" action="/install">
      <?= Csrf::field() ?>
      <div class="field">
        <label class="field-label" for="site_url">Public site URL</label>
        <input type="url" id="site_url" name="site_url" value="<?= e((string)($_POST['site_url'] ?? 'https://edenridgegh.com')) ?>" required>
      </div>
      <div class="field">
        <label class="field-label" for="admin_url">Dashboard URL</label>
        <input type="url" id="admin_url" name="admin_url" value="<?= e((string)($_POST['admin_url'] ?? 'https://app.edenridgegh.com')) ?>" required>
      </div>
      <div class="field">
        <label class="field-label" for="admin_name">Your name</label>
        <input type="text" id="admin_name" name="admin_name" value="<?= e((string)($_POST['admin_name'] ?? '')) ?>" required>
      </div>
      <div class="field">
        <label class="field-label" for="admin_email">Your email</label>
        <input type="email" id="admin_email" name="admin_email" value="<?= e((string)($_POST['admin_email'] ?? '')) ?>" required>
      </div>
      <div class="field">
        <label class="field-label" for="admin_password">Password (10+ characters)</label>
        <input type="password" id="admin_password" name="admin_password" required minlength="10">
      </div>
      <div class="field">
        <label class="field-label" for="admin_password_confirm">Confirm password</label>
        <input type="password" id="admin_password_confirm" name="admin_password_confirm" required minlength="10">
      </div>

      <details style="margin-bottom:16px;">
        <summary style="cursor:pointer;font-size:13px;color:var(--a-ink-soft);">SMTP details (optional — can be set later)</summary>
        <div style="padding-top:12px;">
          <div class="field"><label class="field-label" for="smtp_host">SMTP host</label><input type="text" id="smtp_host" name="smtp_host"></div>
          <div class="field"><label class="field-label" for="smtp_port">Port</label><input type="number" id="smtp_port" name="smtp_port" value="587"></div>
          <div class="field">
            <label class="field-label" for="smtp_encryption">Encryption</label>
            <select id="smtp_encryption" name="smtp_encryption">
              <option value="tls">STARTTLS (587)</option>
              <option value="ssl">SSL (465)</option>
              <option value="none">None</option>
            </select>
          </div>
          <div class="field"><label class="field-label" for="smtp_username">Username</label><input type="text" id="smtp_username" name="smtp_username"></div>
          <div class="field"><label class="field-label" for="smtp_password">Password</label><input type="password" id="smtp_password" name="smtp_password"></div>
          <div class="field"><label class="field-label" for="smtp_from_email">From address</label><input type="email" id="smtp_from_email" name="smtp_from_email"></div>
        </div>
      </details>

      <button type="submit" class="btn btn-accent">Install Eden Ridge</button>
    </form>
  <?php endif; ?>
<?php endif; ?>
