<div class="card">
  <div class="section-title">
    <h3>Appointments (<?= htmlspecialchars($status) ?>)</h3>
  </div>
  <div class="table-wrap">
  <table>
    <tr><th>Name</th><th>Email</th><th>Date/Time</th><th>Status</th><th class="right">Actions</th></tr>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= htmlspecialchars($row['name']) ?></td>
        <td><?= htmlspecialchars($row['email']) ?></td>
        <td><?= htmlspecialchars(format_appt_display($row['date'], $row['slot'])) ?></td>
        <td>
          <?php $s = $row['status']; $badge = $s==='pending'?'badge--pending':($s==='confirmed'?'badge--confirmed':'badge--cancelled'); ?>
          <span class="badge <?= $badge ?>"><?= htmlspecialchars($row['status']) ?></span>
        </td>
        <td class="right">
          <?php if ($status === 'pending'): ?>
            <form method="post" action="/admin/appointments/<?= htmlspecialchars($row['id']) ?>/confirm" style="display:inline;">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>" />
              <button class="btn primary" type="submit">Confirm</button>
            </form>
            <form method="post" action="/admin/appointments/<?= htmlspecialchars($row['id']) ?>/cancel" style="display:inline;margin-left:6px;">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>" />
              <button class="btn danger" type="submit">Cancel</button>
            </form>
          <?php elseif ($status === 'confirmed'): ?>
            <form method="post" action="/admin/appointments/<?= htmlspecialchars($row['id']) ?>/cancel" style="display:inline;">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>" />
              <button class="btn danger" type="submit">Cancel</button>
            </form>
            <form method="post" action="/admin/appointments/reschedule" class="row" style="display:inline;margin-left:6px;">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>" />
              <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>" />
              <input type="date" name="date" value="<?= htmlspecialchars($row['date']) ?>" required />
              <input type="time" name="slot" value="<?= htmlspecialchars($row['slot']) ?>" required />
              <button class="btn" type="submit">Reschedule</button>
            </form>
          <?php else: ?>
            <em>N/A</em>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  </div>
</div>
