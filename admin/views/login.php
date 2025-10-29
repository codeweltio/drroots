<div class="card" style="max-width:420px;margin:48px auto;">
  <h2>Admin Login</h2>
  <?php if (!empty($error)): ?>
    <p style="color:#dc3545;"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>
  <form method="post" action="/admin/login">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>" />
    <div class="row" style="margin-bottom:8px;flex-direction:column;align-items:stretch;">
      <label>Email</label>
      <input name="email" type="email" value="admin@local" required />
    </div>
    <div class="row" style="margin-bottom:8px;flex-direction:column;align-items:stretch;">
      <label>Password</label>
      <input name="password" type="password" value="admin123" required />
    </div>
    <button class="btn primary" type="submit">Login</button>
  </form>
</div>

