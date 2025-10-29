<div class="card">
  <div class="section-title">
    <h3>Pending Approvals</h3>
    <span class="subtle">Approve or cancel new requests</span>
  </div>
  <?php if (empty($pending)): ?>
    <p>No pending appointments.</p>
  <?php else: ?>
    <div class="table-wrap">
    <table>
      <tr><th>Name</th><th>Email</th><th>Date/Time</th><th>Reason</th><th class="right">Actions</th></tr>
      <?php foreach ($pending as $row): ?>
        <tr>
          <td><?= htmlspecialchars($row['name']) ?></td>
          <td><?= htmlspecialchars($row['email']) ?></td>
          <td><?= htmlspecialchars(format_appt_display($row['date'], $row['slot'])) ?></td>
          <td><?= htmlspecialchars($row['reason'] ?? '') ?></td>
          <td class="right">
            <form method="post" action="/admin/appointments/<?= htmlspecialchars($row['id']) ?>/confirm" style="display:inline;">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>" />
              <button class="btn primary" type="submit">Confirm</button>
            </form>
            <form method="post" action="/admin/appointments/<?= htmlspecialchars($row['id']) ?>/cancel" style="display:inline;margin-left:6px;">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>" />
              <button class="btn danger" type="submit">Cancel</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
    </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="section-title">
    <h3>Today</h3>
    <span class="subtle">Quick reschedule</span>
  </div>
  <?php if (empty($today)): ?>
    <p>No appointments today.</p>
  <?php else: ?>
    <div class="table-wrap">
    <table>
      <tr><th>Name</th><th>Email</th><th>Time</th><th>Status</th><th>Reschedule</th></tr>
      <?php foreach ($today as $row): ?>
        <tr>
          <td><?= htmlspecialchars($row['name']) ?></td>
          <td><?= htmlspecialchars($row['email']) ?></td>
          <td><?= htmlspecialchars($row['slot']) ?></td>
          <td>
            <?php $s = $row['status']; $badge = $s==='pending'?'badge--pending':($s==='confirmed'?'badge--confirmed':'badge--cancelled'); ?>
            <span class="badge <?= $badge ?>"><?= htmlspecialchars($row['status']) ?></span>
          </td>
          <td>
            <form method="post" action="/admin/appointments/reschedule" class="row">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>" />
              <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>" />
              <input type="date" name="date" value="<?= htmlspecialchars($row['date']) ?>" required />
              <input type="time" name="slot" value="<?= htmlspecialchars($row['slot']) ?>" required />
              <button class="btn" type="submit">Update</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
    </div>
  <?php endif; ?>
</div>
