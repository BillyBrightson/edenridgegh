<?php
use Core\{Auth, Csrf};
$user = Auth::user();
?>
<div class="page-head">
  <div>
    <h2>Your account</h2>
    <p>Signed in as <?= e((string)$user['email']) ?> · <?= e(ucfirst((string)$user['role'])) ?></p>
  </div>
</div>

<div class="grid cols-2">
  <div class="card">
    <div class="card-head"><h3>Details</h3></div>
    <div class="card-body">
      <form method="post" action="/account">
        <?= Csrf::field() ?>
        <div class="field">
          <label class="field-label" for="name">Name</label>
          <input type="text" id="name" name="name" value="<?= e((string)$user['name']) ?>">
        </div>
        <button type="submit" class="btn btn-accent btn-sm">Save</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h3>Security</h3></div>
    <div class="card-body">
      <p style="font-size:13px;color:var(--a-ink-soft);">Sessions expire after two hours of inactivity, and after twelve hours regardless.</p>
      <a class="btn btn-ghost btn-sm" href="/account/password" style="margin-top:14px;">Change password</a>
    </div>
  </div>
</div>
