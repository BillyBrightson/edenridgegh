<?php use Core\Csrf; ?>
<h1>Reset your password</h1>
<p class="lede">We will email you a link that works once and expires in an hour.</p>
<form method="post" action="/forgot">
  <?= Csrf::field() ?>
  <div class="field">
    <label class="field-label" for="email">Email</label>
    <input type="email" id="email" name="email" required autofocus>
  </div>
  <button type="submit" class="btn btn-primary">Email me a link</button>
</form>
<div class="auth-links"><a href="/login">Back to sign in</a></div>
