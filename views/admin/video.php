<?php
/** @var array $requests */
/** @var int $count */
use Core\{Csrf, Media, Settings, Video, View};
?>
<div class="page-head">
  <div>
    <h2>Video tour</h2>
    <p>The walkthrough is hosted externally — the file never touches this server. <?= (int)$count ?> request<?= $count === 1 ? '' : 's' ?> so far.</p>
  </div>
</div>

<form method="post" action="/video" data-guard>
  <?= Csrf::field() ?>
  <div class="card">
    <div class="card-head"><h3>Where the video lives</h3></div>
    <div class="card-body">
      <div class="field">
        <label class="field-label" for="video_provider">Provider</label>
        <select id="video_provider" name="video_provider">
          <?php foreach (Video::PROVIDERS as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= (string)Settings::get('video_provider', 'youtube') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label class="field-label" for="video_url">Video URL</label>
        <p class="hint">Paste the normal watch URL — an unlisted YouTube link, a Vimeo link, or the embed URL from Bunny Stream.</p>
        <input type="text" id="video_url" name="video_url" value="<?= e((string)Settings::get('video_url', '')) ?>" placeholder="https://youtu.be/…">
      </div>
      <div class="field">
        <input type="hidden" name="video_gate_enabled" value="0">
        <label class="switch">
          <input type="checkbox" name="video_gate_enabled" value="1" <?= Settings::bool('video_gate_enabled', true) ? 'checked' : '' ?>>
          <span class="track"></span>
          <span>Ask for a name and email before playing (recommended — it turns the walkthrough into a lead source)</span>
        </label>
      </div>
      <?= View::render('admin/partials/field', [
          'field' => ['key' => 'video_poster_media_id', 'type' => 'image', 'label' => 'Poster image', 'hint' => 'Shown before the player loads. Leave empty to use the section background.'],
          'value' => Settings::get('video_poster_media_id', ''),
          'name'  => 'video_poster_media_id',
          'id'    => 'video_poster',
          'depth' => 0,
      ]) ?>
      <div class="field">
        <label class="field-label" for="video_email_body">Email sent with the link</label>
        <p class="hint">Merge tags: <code>{{name}}</code>, <code>{{video_url}}</code>.</p>
        <textarea id="video_email_body" name="video_email_body" rows="9"><?= e((string)Settings::get('video_email_body', '')) ?></textarea>
      </div>
    </div>
  </div>
  <div class="savebar">
    <span class="state" data-dirty-state>All changes published</span>
    <div class="spacer"></div>
    <button type="submit" class="btn btn-accent">Save video settings</button>
  </div>
</form>

<div class="card" style="margin-top:18px;">
  <div class="card-head"><h3>Request log</h3></div>
  <?php if ($requests): ?>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Date</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($requests as $request): ?>
          <tr>
            <td style="color:var(--a-ink-faint);white-space:nowrap;"><?= e(human_date((string)$request['created_at'], 'j M Y, H:i')) ?></td>
            <td><a class="row-link" href="/enquiries/<?= (int)$request['enquiry_id'] ?>"><?= e((string)($request['name'] ?? '—')) ?></a></td>
            <td><?= e((string)($request['email'] ?? '—')) ?></td>
            <td><?= e((string)($request['phone'] ?: '—')) ?></td>
            <td><span class="pill pill-<?= e((string)($request['status'] ?? 'new')) ?>"><?= e(ucfirst((string)($request['status'] ?? 'new'))) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="empty"><h4>No requests yet</h4><p>Once the video URL is set, every request lands here and in the enquiries inbox.</p></div>
  <?php endif; ?>
</div>
