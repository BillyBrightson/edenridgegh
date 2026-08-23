<?php
use Core\{Csrf, Icons, Settings};
?>
<div class="page-head">
  <div>
    <h2>Site settings</h2>
    <p>Contact details, email delivery, analytics and spam protection.</p>
  </div>
</div>

<form method="post" action="/settings" data-guard>
  <?= Csrf::field() ?>

  <div class="card">
    <div class="card-head"><h3>Contact details</h3></div>
    <div class="card-body">
      <p class="hint">These feed the WhatsApp button, structured data for Google, and the email footers. The contact block on the home page has its own copy under Home page → Enquiry form.</p>
      <div class="grid cols-2">
        <div class="field"><label class="field-label" for="contact_phone">Phone</label><input type="text" id="contact_phone" name="contact_phone" value="<?= e((string)Settings::get('contact_phone', '')) ?>"></div>
        <div class="field"><label class="field-label" for="contact_whatsapp">WhatsApp number</label><input type="text" id="contact_whatsapp" name="contact_whatsapp" value="<?= e((string)Settings::get('contact_whatsapp', '')) ?>"></div>
        <div class="field"><label class="field-label" for="contact_email">Email</label><input type="email" id="contact_email" name="contact_email" value="<?= e((string)Settings::get('contact_email', '')) ?>"></div>
        <div class="field"><label class="field-label" for="contact_address">Address</label><input type="text" id="contact_address" name="contact_address" value="<?= e((string)Settings::get('contact_address', '')) ?>"></div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h3>Social accounts</h3></div>
    <div class="card-body">
      <p class="hint">A handle alone is enough for search engines. Add the full URL to make the icon appear in the footer (switch the row on under Home page → Footer).</p>
      <div class="grid cols-2">
        <?php foreach (Icons::socialOptions() as $platform => $label): ?>
          <div style="display:flex;gap:8px;">
            <div class="field" style="flex:1;">
              <label class="field-label" for="h-<?= e($platform) ?>"><?= e($label) ?> handle</label>
              <input type="text" id="h-<?= e($platform) ?>" name="social_<?= e($platform) ?>_handle" value="<?= e((string)Settings::get('social_' . $platform . '_handle', '')) ?>">
            </div>
            <div class="field" style="flex:1.3;">
              <label class="field-label" for="u-<?= e($platform) ?>">URL</label>
              <input type="text" id="u-<?= e($platform) ?>" name="social_<?= e($platform) ?>_url" value="<?= e((string)Settings::get('social_' . $platform . '_url', '')) ?>" placeholder="https://…">
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="card" id="email">
    <div class="card-head"><h3>Email</h3></div>
    <div class="card-body">
      <div class="field">
        <label class="field-label" for="notification_emails">Send new enquiries to</label>
        <p class="hint">Comma-separated. Everyone listed gets an email the moment a form is submitted.</p>
        <input type="text" id="notification_emails" name="notification_emails" value="<?= e((string)Settings::get('notification_emails', '')) ?>">
      </div>
      <div class="field">
        <input type="hidden" name="autoreply_enabled" value="0">
        <label class="switch">
          <input type="checkbox" name="autoreply_enabled" value="1" <?= Settings::bool('autoreply_enabled', true) ? 'checked' : '' ?>>
          <span class="track"></span><span>Send an automatic reply to the enquirer</span>
        </label>
      </div>
      <div class="field">
        <label class="field-label" for="autoreply_subject">Auto-reply subject</label>
        <input type="text" id="autoreply_subject" name="autoreply_subject" value="<?= e((string)Settings::get('autoreply_subject', '')) ?>">
      </div>
      <div class="field">
        <label class="field-label" for="autoreply_body">Auto-reply message</label>
        <p class="hint">Merge tags: <code>{{name}}</code>, <code>{{email}}</code>, <code>{{phone}}</code>, <code>{{interest}}</code>. Blank lines become paragraphs.</p>
        <textarea id="autoreply_body" name="autoreply_body" rows="10"><?= e((string)Settings::get('autoreply_body', '')) ?></textarea>
      </div>

      <h4 style="font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:var(--a-ink-faint);margin:22px 0 10px;">SMTP delivery</h4>
      <div class="grid cols-2">
        <div class="field">
          <label class="field-label" for="smtp_transport">Transport</label>
          <select id="smtp_transport" name="smtp_transport">
            <option value="smtp" <?= Settings::get('smtp_transport', 'smtp') === 'smtp' ? 'selected' : '' ?>>SMTP</option>
            <option value="log" <?= Settings::get('smtp_transport', 'smtp') === 'log' ? 'selected' : '' ?>>Log only (no email sent)</option>
          </select>
        </div>
        <div class="field"><label class="field-label" for="smtp_host">Host</label><input type="text" id="smtp_host" name="smtp_host" value="<?= e((string)Settings::get('smtp_host', '')) ?>"></div>
        <div class="field"><label class="field-label" for="smtp_port">Port</label><input type="number" id="smtp_port" name="smtp_port" value="<?= (int)Settings::get('smtp_port', 587) ?>"></div>
        <div class="field">
          <label class="field-label" for="smtp_encryption">Encryption</label>
          <select id="smtp_encryption" name="smtp_encryption">
            <?php foreach (['tls' => 'STARTTLS (587)', 'ssl' => 'SSL (465)', 'none' => 'None'] as $value => $label): ?>
              <option value="<?= e($value) ?>" <?= Settings::get('smtp_encryption', 'tls') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label class="field-label" for="smtp_username">Username</label><input type="text" id="smtp_username" name="smtp_username" value="<?= e((string)Settings::get('smtp_username', '')) ?>"></div>
        <div class="field"><label class="field-label" for="smtp_password">Password</label><input type="password" id="smtp_password" name="smtp_password" placeholder="<?= Settings::get('smtp_password', '') !== '' ? '•••••••• (unchanged)' : '' ?>"></div>
        <div class="field"><label class="field-label" for="smtp_from_email">From address</label><input type="email" id="smtp_from_email" name="smtp_from_email" value="<?= e((string)Settings::get('smtp_from_email', '')) ?>"></div>
        <div class="field"><label class="field-label" for="smtp_from_name">From name</label><input type="text" id="smtp_from_name" name="smtp_from_name" value="<?= e((string)Settings::get('smtp_from_name', '')) ?>"></div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h3>Analytics &amp; spam</h3></div>
    <div class="card-body">
      <div class="grid cols-2">
        <div class="field"><label class="field-label" for="ga4_id">Google Analytics 4 ID</label><input type="text" id="ga4_id" name="ga4_id" value="<?= e((string)Settings::get('ga4_id', '')) ?>" placeholder="G-XXXXXXX"></div>
        <div class="field"><label class="field-label" for="meta_pixel_id">Meta Pixel ID</label><input type="text" id="meta_pixel_id" name="meta_pixel_id" value="<?= e((string)Settings::get('meta_pixel_id', '')) ?>"></div>
        <div class="field"><label class="field-label" for="turnstile_site_key">Cloudflare Turnstile site key</label><input type="text" id="turnstile_site_key" name="turnstile_site_key" value="<?= e((string)Settings::get('turnstile_site_key', '')) ?>"></div>
        <div class="field"><label class="field-label" for="turnstile_secret">Turnstile secret</label><input type="text" id="turnstile_secret" name="turnstile_secret" value="<?= e((string)Settings::get('turnstile_secret', '')) ?>"></div>
      </div>
      <div class="field">
        <input type="hidden" name="cookie_consent_enabled" value="0">
        <label class="switch">
          <input type="checkbox" name="cookie_consent_enabled" value="1" <?= Settings::bool('cookie_consent_enabled', false) ? 'checked' : '' ?>>
          <span class="track"></span><span>Show a cookie consent banner before loading analytics</span>
        </label>
      </div>
      <div class="field">
        <label class="field-label" for="cookie_consent_text">Consent banner text</label>
        <textarea id="cookie_consent_text" name="cookie_consent_text" rows="2"><?= e((string)Settings::get('cookie_consent_text', '')) ?></textarea>
      </div>
      <div class="field">
        <label class="field-label" for="whatsapp_webhook_url">WhatsApp outbound webhook (optional)</label>
        <p class="hint">Reserved for a future WhatsApp Business API or Twilio hookup. Leave blank for now — the inbox already offers one-tap click-to-chat.</p>
        <input type="text" id="whatsapp_webhook_url" name="whatsapp_webhook_url" value="<?= e((string)Settings::get('whatsapp_webhook_url', '')) ?>">
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h3>Site behaviour</h3></div>
    <div class="card-body">
      <div class="field">
        <input type="hidden" name="cache_enabled" value="0">
        <label class="switch">
          <input type="checkbox" name="cache_enabled" value="1" <?= Settings::bool('cache_enabled', true) ? 'checked' : '' ?>>
          <span class="track"></span><span>Cache the rendered home page (recommended)</span>
        </label>
      </div>
      <div class="field">
        <input type="hidden" name="auto_backup_enabled" value="0">
        <label class="switch">
          <input type="checkbox" name="auto_backup_enabled" value="1" <?= Settings::bool('auto_backup_enabled', true) ? 'checked' : '' ?>>
          <span class="track"></span><span>Take an automatic daily database backup</span>
        </label>
      </div>
      <div class="field">
        <input type="hidden" name="maintenance_mode" value="0">
        <label class="switch">
          <input type="checkbox" name="maintenance_mode" value="1" <?= Settings::bool('maintenance_mode', false) ? 'checked' : '' ?>>
          <span class="track"></span><span>Maintenance mode — show a holding page to visitors</span>
        </label>
      </div>
      <?php $previewToken = (string)Settings::get('maintenance_bypass_token', ''); ?>
      <?php if ($previewToken !== ''): ?>
        <div class="field">
          <span class="field-label">Private preview link</span>
          <p class="hint">Opens the real site even while the holding page is up, for this browser, for twelve hours. Share it only with people reviewing the site.</p>
          <input type="text" readonly onclick="this.select()" value="<?= e(rtrim((string)\Core\Config::get('site_url', ''), '/') . '/?preview=' . $previewToken) ?>">
        </div>
      <?php endif; ?>
      <div class="field">
        <label class="field-label" for="maintenance_message">Holding page message</label>
        <textarea id="maintenance_message" name="maintenance_message" rows="2"><?= e((string)Settings::get('maintenance_message', '')) ?></textarea>
      </div>
      <div class="field">
        <label class="field-label" for="robots_txt">robots.txt override</label>
        <p class="hint">Leave blank to use the generated default, which allows everything and points to the sitemap.</p>
        <textarea id="robots_txt" name="robots_txt" rows="4"><?= e((string)Settings::get('robots_txt', '')) ?></textarea>
      </div>
    </div>
  </div>

  <div class="savebar">
    <span class="state" data-dirty-state>All changes published</span>
    <div class="spacer"></div>
    <button type="submit" class="btn btn-accent">Save settings</button>
  </div>
</form>

<div class="card" style="margin-top:18px;margin-bottom:40px;">
  <div class="card-head"><h3>Send a test email</h3></div>
  <div class="card-body">
    <form method="post" action="/settings/test-email" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
      <?= Csrf::field() ?>
      <div class="field" style="flex:1;min-width:240px;margin:0;">
        <label class="field-label" for="test_email">Send to</label>
        <input type="email" id="test_email" name="test_email" value="<?= e((string)Settings::get('contact_email', '')) ?>" required>
      </div>
      <button type="submit" class="btn btn-ghost btn-sm">Send test</button>
    </form>
  </div>
</div>
