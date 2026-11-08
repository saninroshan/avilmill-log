<?php
// pages/login.php
// Renders the login card. POST is handled in index.php.
?>
<div class="login-wrap">
  <div class="login-card">
    <div class="brand-center">
      <span class="brand-mark">KT</span>
      <h2>KT Avil Mill</h2>
      <p class="muted">Management &amp; Financial Logbook</p>
    </div>
    <?php if (!empty($loginError)): ?><div class="alert error"><?= esc($loginError) ?></div><?php endif; ?>
    <form method="post" action="index.php?page=login" class="form">
      <?= csrf_field() ?>
      <label>Username
        <input type="text" name="username" required autofocus>
      </label>
      <label>Password
        <input type="password" name="password" required>
      </label>
      <button class="btn primary" type="submit">Sign in</button>
    </form>
    <p class="muted small">Demo login &mdash; admin / admin123 &middot; staff / staff123</p>
  </div>
</div>
