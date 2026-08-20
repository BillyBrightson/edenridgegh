<?php use Core\Csrf; /** @var string $token */ ?>
<h1>Choose a new password</h1>
<p class="lede">At least 10 characters. A passphrase of three or four words works well.</p>
<form method="post" action="/reset">
  <?= Csrf::field() ?>
  <input type="hidden" name="token" value="<?= e($token) ?>">
  <div class="field">
    <label class="field-label" for="password">New password</label>
    <input type="password" id="password" name="password" minlength="10" required autofocus>
  </div>
  <div class="field">
    <label class="field-label" for="password_confirm">Confirm password</label>
    <input type="password" id="password_confirm" name="password_confirm" minlength="10" required>
  </div>
  <button type="submit" class="btn btn-primary">Save password</button>
</form>
