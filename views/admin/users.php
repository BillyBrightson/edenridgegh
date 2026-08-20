<?php
/** @var array $users */
use Core\{Auth, Csrf};
$roles = ['admin' => 'Administrator — everything', 'editor' => 'Editor — content, media and enquiries', 'viewer' => 'Viewer — read-only plus the inbox'];
?>
<div class="page-head">
  <div>
    <h2>Users</h2>
    <p>Who can sign in to this dashboard, and what they can reach.</p>
  </div>
</div>

<div class="grid cols-2">
  <?php foreach ($users as $user): ?>
    <div class="card">
      <div class="card-head">
        <h3><?= e((string)$user['email']) ?></h3>
        <div class="spacer"></div>
        <span class="pill <?= (int)$user['is_active'] === 1 ? 'pill-won' : 'pill-lost' ?>"><?= (int)$user['is_active'] === 1 ? 'Active' : 'Disabled' ?></span>
      </div>
      <div class="card-body">
        <form method="post" action="/users/<?= (int)$user['id'] ?>/update">
          <?= Csrf::field() ?>
          <div class="field">
            <label class="field-label" for="name-<?= (int)$user['id'] ?>">Name</label>
            <input type="text" id="name-<?= (int)$user['id'] ?>" name="name" value="<?= e((string)$user['name']) ?>">
          </div>
          <div class="field">
            <label class="field-label" for="role-<?= (int)$user['id'] ?>">Role</label>
            <select id="role-<?= (int)$user['id'] ?>" name="role">
              <?php foreach ($roles as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $user['role'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <input type="hidden" name="is_active" value="0">
            <label class="switch">
              <input type="checkbox" name="is_active" value="1" <?= (int)$user['is_active'] === 1 ? 'checked' : '' ?>>
              <span class="track"></span><span>Can sign in</span>
            </label>
          </div>
          <p class="hint">Last signed in: <?= e(human_date((string)($user['last_login_at'] ?? ''), 'j M Y, H:i')) ?></p>
          <button type="submit" class="btn btn-ghost btn-sm">Save changes</button>
        </form>
        <?php if ((int)$user['id'] !== Auth::id()): ?>
          <form method="post" action="/users/<?= (int)$user['id'] ?>/delete" style="margin-top:10px;" data-confirm="Delete <?= e((string)$user['email']) ?>? This cannot be undone.">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--a-danger);">Delete account</button>
          </form>
        <?php else: ?>
          <p class="hint" style="margin-top:10px;">This is your own account.</p>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card" style="margin-top:18px;">
  <div class="card-head"><h3>Invite someone</h3></div>
  <div class="card-body">
    <form method="post" action="/users">
      <?= Csrf::field() ?>
      <div class="grid cols-3">
        <div class="field"><label class="field-label" for="new-name">Name</label><input type="text" id="new-name" name="name" required></div>
        <div class="field"><label class="field-label" for="new-email">Email</label><input type="email" id="new-email" name="email" required></div>
        <div class="field">
          <label class="field-label" for="new-role">Role</label>
          <select id="new-role" name="role">
            <?php foreach ($roles as $value => $label): ?>
              <option value="<?= e($value) ?>"><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <p class="hint">They receive an email with a one-time link to choose their own password.</p>
      <button type="submit" class="btn btn-accent btn-sm">Create account</button>
    </form>
  </div>
</div>
