<div class="card">
  <div class="section-title">
    <h3>Appointments (<?= htmlspecialchars($status) ?>)</h3>
  </div>
  <div class="table-wrap">
  <table id="apptTable" class="table table-striped table-hover table-sm align-middle nowrap" style="width:100%">
    <thead>
      <tr>
        <th>Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Date/Time</th>
        <?php if ($status !== 'confirmed'): ?>
        <th>Status</th>
        <?php endif; ?>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $row): ?>
        <?php $tel = preg_replace('/[^+0-9]/', '', (string)($row['phone'] ?? '')); ?>
        <tr>
          <td><?= htmlspecialchars($row['name']) ?></td>
          <td><?= htmlspecialchars($row['email']) ?></td>
          <td data-tel="<?= htmlspecialchars($tel) ?>">
            <?php if ($tel): ?><a class="phone-link" href="tel:<?= htmlspecialchars($tel) ?>"><?= htmlspecialchars($row['phone']) ?></a><?php else: ?><?= htmlspecialchars($row['phone'] ?? '') ?><?php endif; ?>
          </td>
          <td><?= htmlspecialchars(format_appt_display($row['date'], $row['slot'])) ?></td>
          <?php if ($status !== 'confirmed'): ?>
            <td>
              <?php $s = $row['status']; $badge = $s==='pending'?'badge--pending':($s==='confirmed'?'badge--confirmed':'badge--cancelled'); ?>
              <span class="badge <?= $badge ?>"><?= htmlspecialchars($row['status']) ?></span>
            </td>
          <?php endif; ?>
          <td class="text-end">
            <div class="actions-inline">
              <?php if ($status === 'pending'): ?>
                <form method="post" action="/admin/appointments/<?= htmlspecialchars($row['id']) ?>/confirm" class="d-inline">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>" />
                  <button class="btn primary btn-sm" type="submit" data-bs-toggle="tooltip" data-bs-title="Confirm appointment" aria-label="Confirm appointment"><i class="bi bi-check2-circle me-1"></i><span class="d-none d-sm-inline">Confirm</span></button>
                </form>
                <form method="post" action="/admin/appointments/<?= htmlspecialchars($row['id']) ?>/cancel" class="d-inline">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>" />
                  <button class="btn danger btn-sm" type="submit" data-bs-toggle="tooltip" data-bs-title="Cancel appointment" aria-label="Cancel appointment"><i class="bi bi-x-circle me-1"></i><span class="d-none d-sm-inline">Cancel</span></button>
                </form>
              <?php elseif ($status === 'confirmed'): ?>
                <form method="post" action="/admin/appointments/reschedule" class="d-inline">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>" />
                  <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>" />
                  <input class="form-control form-control-sm" style="width:auto" type="date" name="date" value="<?= htmlspecialchars($row['date']) ?>" aria-label="New date" required />
                  <input class="form-control form-control-sm" style="width:auto" type="time" name="slot" value="<?= htmlspecialchars($row['slot']) ?>" aria-label="New time" required />
                  <button class="btn btn-sm btn-primary" type="submit" data-bs-toggle="tooltip" data-bs-title="Reschedule appointment" aria-label="Reschedule appointment"><i class="bi bi-calendar-check me-1"></i><span class="d-none d-sm-inline">Reschedule</span></button>
                </form>
                <form method="post" action="/admin/appointments/<?= htmlspecialchars($row['id']) ?>/cancel" class="d-inline">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>" />
                  <button class="btn btn-sm btn-outline-danger" type="submit" data-bs-toggle="tooltip" data-bs-title="Cancel appointment" aria-label="Cancel appointment"><i class="bi bi-x-circle me-1"></i><span class="d-none d-sm-inline">Cancel</span></button>
                </form>
              <?php else: ?>
                <em class="text-secondary">N/A</em>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
