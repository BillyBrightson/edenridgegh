<?php use Core\Csrf; ?>
<h1>Sign in</h1>
<p class="lede">Manage the Eden Ridge website and enquiries.</p>
<form method="post" action="/login">
  <?= Csrf::field() ?>
  <div class="field">
    <label class="field-label" for="email">Email</label>
    <input type="email" id="email" name="email" autocomplete="username" required autofocus>
  </div>
  <div class="field">
    <label class="field-label" for="password">Password</label>
    <input type="password" id="password" name="password" autocomplete="current-password" required>
  </div>
  <label class="switch" style="margin-bottom:6px;">
    <input type="checkbox" name="remember" value="1">
    <span class="track"></span>
    <span>Keep me signed in for 30 days</span>
  </label>
  <button type="submit" class="btn btn-primary">Sign in</button>
</form>
<div class="auth-links">
  <a href="/forgot">Forgotten your password?</a>
</div>
