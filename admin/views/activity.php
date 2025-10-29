<div class="card">
  <div class="section-title">
    <h3>Activity</h3>
    <span class="subtle">Recent admin actions (last 200)</span>
  </div>
  <form method="get" action="/admin/activity" class="row mb-16">
    <div class="stack">
      <label>Action</label>
      <input type="text" name="action" value="<?= htmlspecialchars($filters['action'] ?? '') ?>" placeholder="confirm | cancel | reschedule | create | ..." />
    </div>
    <div class="stack">
      <label>Actor (email)</label>
      <input type="email" name="actor" value="<?= htmlspecialchars($filters['actor'] ?? '') ?>" placeholder="staff@example.com" />
    </div>
    <div class="stack">
      <label>From</label>
      <input type="date" name="from" value="<?= htmlspecialchars($filters['from'] ?? '') ?>" />
    </div>
    <div class="stack">
      <label>To</label>
      <input type="date" name="to" value="<?= htmlspecialchars($filters['to'] ?? '') ?>" />
    </div>
    <div class="stack" style="align-self:end;">
      <button class="btn" type="submit">Filter</button>
      <a class="btn" href="/admin/activity" style="margin-left:6px;">Reset</a>
    </div>
  </form>
  <?php if (empty($events)): ?>
    <p>No activity recorded yet.</p>
  <?php else: ?>
    <div class="table-wrap">
    <table>
      <tr><th>When</th><th>Actor</th><th>Action</th><th>Entity</th><th>Details</th></tr>
      <?php foreach ($events as $e): ?>
        <tr>
          <td><?= htmlspecialchars($e['ts'] ?? '') ?></td>
          <td><?= htmlspecialchars(($e['actor_email'] ?? '') ?: ($e['actor_id'] ?? '')) ?></td>
          <td><?= htmlspecialchars($e['action'] ?? '') ?></td>
          <td><?= htmlspecialchars(($e['entity'] ?? '').'#'.($e['entity_id'] ?? '')) ?></td>
          <td><code style="font-size:12px;"><?= htmlspecialchars(json_encode($e['meta'] ?? [])) ?></code></td>
        </tr>
      <?php endforeach; ?>
    </table>
    </div>
  <?php endif; ?>
</div>
