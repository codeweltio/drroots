<div class="card">
  <div class="section-title">
    <h3>Users</h3>
    <span class="subtle">Admins can manage staff access</span>
  </div>
  <div class="table-wrap">
    <table>
      <tr><th>Email</th><th>Role</th><th>Created</th><th class="right">Actions</th></tr>
      <?php foreach ($rows as $u): ?>
        <tr>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><?= htmlspecialchars($u['role']) ?></td>
          <td><?= htmlspecialchars($u['created_at']) ?></td>
          <td class="right">
            <form method="post" action="/admin/users/role" style="display:inline-block;">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>" />
              <input type="hidden" name="id" value="<?= htmlspecialchars($u['id']) ?>" />
              <select name="role">
                <option value="staff" <?= $u['role']==='staff'?'selected':'' ?>>staff</option>
                <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>admin</option>
              </select>
              <button class="btn" type="submit">Update</button>
            </form>
            <form method="post" action="/admin/users/reset" class="row" style="display:inline-block; margin-left:6px;">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>" />
              <input type="hidden" name="id" value="<?= htmlspecialchars($u['id']) ?>" />
              <input type="password" name="password" placeholder="New password" required />
              <button class="btn" type="submit">Reset</button>
            </form>
            <form method="post" action="/admin/users/delete" style="display:inline-block; margin-left:6px;" onsubmit="return confirm('Delete this user?');">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>" />
              <input type="hidden" name="id" value="<?= htmlspecialchars($u['id']) ?>" />
              <button class="btn danger" type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>

<div class="card">
  <h3>Add User</h3>
  <form method="post" action="/admin/users/create" class="form-row">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>" />
    <div class="stack">
      <label>Email</label>
      <input type="email" name="email" required />
    </div>
    <div class="stack">
      <label>Role</label>
      <select name="role">
        <option value="staff">staff</option>
        <option value="admin">admin</option>
      </select>
    </div>
    <div class="stack">
      <label>Password</label>
      <input type="password" name="password" minlength="8" required />
    </div>
    <div class="stack" style="align-self:end;">
      <button class="btn primary" type="submit">Create</button>
    </div>
  </form>
</div>

