<?php
/** @var array $enquiry */
/** @var array $notes */
use Core\{Auth, Csrf, Enquiry};
$whatsapp = Enquiry::whatsappLink($enquiry);
?>
<div class="page-head">
  <div>
    <h2><?= e((string)$enquiry['name']) ?></h2>
    <p>Received <?= e(human_date((string)$enquiry['created_at'])) ?> UTC</p>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-ghost btn-sm" href="/enquiries">Back to inbox</a>
</div>

<div class="drawer-grid">
  <div>
    <div class="card">
      <div class="card-head"><h3>Message</h3></div>
      <div class="card-body">
        <?php if (!empty($enquiry['message'])): ?>
          <div class="message-box"><?= e((string)$enquiry['message']) ?></div>
        <?php else: ?>
          <p style="color:var(--a-ink-faint);">No message was included.</p>
        <?php endif; ?>
        <div class="actions-row" style="margin-top:18px;">
          <a class="btn btn-primary btn-sm" href="mailto:<?= e((string)$enquiry['email']) ?>?subject=<?= e(rawurlencode('Re: your Eden Ridge enquiry')) ?>">Reply by email</a>
          <?php if ($enquiry['phone']): ?>
            <a class="btn btn-ghost btn-sm" href="tel:<?= e(phone_digits((string)$enquiry['phone'])) ?>">Call</a>
          <?php endif; ?>
          <?php if ($whatsapp !== ''): ?>
            <a class="btn btn-ghost btn-sm" href="<?= e($whatsapp) ?>" target="_blank" rel="noopener">WhatsApp</a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-head"><h3>Internal notes</h3></div>
      <?php if ($notes): ?>
        <div>
          <?php foreach ($notes as $note): ?>
            <div class="note">
              <div class="who"><?= e((string)($note['user_name'] ?? 'System')) ?> · <?= e(human_date((string)$note['created_at'])) ?></div>
              <?= nl2br(e((string)$note['body'])) ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty" style="padding:26px;"><p>No notes yet.</p></div>
      <?php endif; ?>
      <div class="card-body tight">
        <form method="post" action="/enquiries/<?= (int)$enquiry['id'] ?>/note">
          <?= Csrf::field() ?>
          <div class="field">
            <label class="field-label" for="body">Add a note</label>
            <textarea id="body" name="body" rows="3" placeholder="Called on Tuesday, sending the plan pack…"></textarea>
          </div>
          <button type="submit" class="btn btn-ghost btn-sm">Add note</button>
        </form>
      </div>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-head"><h3>Status</h3></div>
      <div class="card-body">
        <form method="post" action="/enquiries/<?= (int)$enquiry['id'] ?>/status">
          <?= Csrf::field() ?>
          <div class="field">
            <label class="field-label" for="status">Pipeline stage</label>
            <select id="status" name="status">
              <?php foreach (Enquiry::STATUSES as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $enquiry['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-accent btn-sm">Update status</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-head"><h3>Details</h3></div>
      <div class="card-body">
        <div class="kv"><span class="k">Email</span><span class="v"><a href="mailto:<?= e((string)$enquiry['email']) ?>"><?= e((string)$enquiry['email']) ?></a></span></div>
        <div class="kv"><span class="k">Phone</span><span class="v"><?= e((string)($enquiry['phone'] ?: '—')) ?></span></div>
        <div class="kv"><span class="k">Interest</span><span class="v"><?= e((string)($enquiry['interest'] ?: '—')) ?></span></div>
        <div class="kv"><span class="k">Video request</span><span class="v"><?= (int)$enquiry['video_request'] === 1 ? 'Yes' : 'No' ?></span></div>
        <div class="kv"><span class="k">Source</span><span class="v"><?= e((string)($enquiry['source_page'] ?: '—')) ?></span></div>
        <div class="kv"><span class="k">Referrer</span><span class="v"><?= e((string)($enquiry['referrer'] ?: '—')) ?></span></div>
        <div class="kv"><span class="k">Campaign</span><span class="v"><?= e(trim(($enquiry['utm_source'] ?? '') . ' / ' . ($enquiry['utm_campaign'] ?? ''), ' /') ?: '—') ?></span></div>
      </div>
    </div>

    <?php if (Auth::can('edit_content')): ?>
      <div class="card">
        <div class="card-body">
          <?php if ($enquiry['deleted_at']): ?>
            <form method="post" action="/enquiries/<?= (int)$enquiry['id'] ?>/restore">
              <?= Csrf::field() ?>
              <button type="submit" class="btn btn-ghost btn-sm">Restore from trash</button>
            </form>
          <?php else: ?>
            <form method="post" action="/enquiries/<?= (int)$enquiry['id'] ?>/trash" data-confirm="Move this enquiry to the trash?">
              <?= Csrf::field() ?>
              <button type="submit" class="btn btn-ghost btn-sm">Move to trash</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
