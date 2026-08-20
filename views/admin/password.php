<?php
/** @var bool $forced */
use Core\Csrf;
?>
<div class="page-head">
  <div>
    <h2>Change password</h2>
    <?php if ($forced): ?>
      <p>Choose your own password before continuing.</p>
    <?php endif; ?>
  </div>
</div>

<div class="card" style="max-width:520px;">
  <div class="card-body">
    <form method="post" action="/account/password">
      <?= Csrf::field() ?>
      <?php if (!$forced): ?>
        <div class="field">
          <label class="field-label" for="current_password">Current password</label>
          <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
        </div>
      <?php endif; ?>
      <div class="field">
        <label class="field-label" for="password">New password</label>
        <p class="hint">At least 10 characters. A passphrase of three or four words is both stronger and easier to remember.</p>
        <input type="password" id="password" name="password" autocomplete="new-password" minlength="10" required>
      </div>
      <div class="field">
        <label class="field-label" for="password_confirm">Confirm new password</label>
        <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" minlength="10" required>
      </div>
      <button type="submit" class="btn btn-accent">Save password</button>
    </form>
  </div>
</div>
