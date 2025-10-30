<div class="card">
  <div class="section-title">
    <h3>Pending Approvals</h3>
    <span class="subtle">Approve or cancel new requests</span>
  </div>
  <?php if (empty($pending)): ?>
    <p>No pending appointments.</p>
  <?php else: ?>
    <div class="table-wrap">
    <table id="pendingTable" class="table table-striped table-hover table-sm align-middle">
      <thead>
        <tr><th>Name</th><th>Email</th><th>Date/Time</th><th>Reason</th><th class="right">Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($pending as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars(format_appt_display($row['date'], $row['slot'])) ?></td>
            <td><?= htmlspecialchars($row['reason'] ?? '') ?></td>
            <td class="right">
              <form method="post" action="/admin/appointments/<?= htmlspecialchars($row['id']) ?>/confirm" style="display:inline;">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>" />
                <button class="btn primary btn-sm" type="submit" data-bs-toggle="tooltip" data-bs-title="Confirm appointment" aria-label="Confirm appointment">Confirm</button>
              </form>
              <form method="post" action="/admin/appointments/<?= htmlspecialchars($row['id']) ?>/cancel" style="display:inline;margin-left:6px;">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>" />
                <button class="btn danger btn-sm" type="submit" data-action="cancel" data-name="<?= htmlspecialchars($row['name']) ?>" data-email="<?= htmlspecialchars($row['email']) ?>" data-datetime="<?= htmlspecialchars(format_appt_display($row['date'], $row['slot'])) ?>" data-bs-toggle="tooltip" data-bs-title="Cancel appointment" aria-label="Cancel appointment">Cancel</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
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
    <table id="todayTable" class="table table-striped table-hover table-sm align-middle">
      <thead>
        <tr><th>Name</th><th>Email</th><th>Date/Time</th><th>Status</th><th>Reschedule</th></tr>
      </thead>
      <tbody>
        <?php foreach ($today as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars(format_appt_display($row['date'], $row['slot'])) ?></td>
            <td>
              <?php $s = $row['status']; $badge = $s==='pending'?'badge--pending':($s==='confirmed'?'badge--confirmed':'badge--cancelled'); ?>
              <span class="badge <?= $badge ?>"><?= htmlspecialchars($row['status']) ?></span>
            </td>
            <td>
            <form method="post" action="/admin/appointments/reschedule" class="d-inline-flex align-items-center gap-2 flex-wrap inline-resched">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>" />
              <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>" />
              <input class="form-control form-control-sm" type="date" name="date" value="<?= htmlspecialchars($row['date']) ?>" required />
              <input class="form-control form-control-sm" type="time" name="slot" value="<?= htmlspecialchars($row['slot']) ?>" required />
              <button class="btn btn-sm btn-primary" type="submit" data-action="resched" data-name="<?= htmlspecialchars($row['name']) ?>" data-email="<?= htmlspecialchars($row['email']) ?>" data-old="<?= htmlspecialchars(format_appt_display($row['date'], $row['slot'])) ?>" data-bs-toggle="tooltip" data-bs-title="Reschedule appointment" aria-label="Reschedule appointment">Update</button>
            </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  <?php endif; ?>
</div>

<!-- AG Grid version of Pending Approvals -->
<div class="card">
  <div class="section-title">
    <h3>Pending Approvals (Grid)</h3>
    <span class="subtle">AG Grid with fixed header and actions</span>
  </div>
  <?php if (empty($pending)): ?>
    <p>No pending appointments.</p>
  <?php else: ?>
    <!-- Local styles + CDN CSS for AG Grid without touching global layout -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-grid.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-quartz.css" />
    <style>
      .ag-theme-quartz.dashboard-theme { border-radius: 16px; overflow: hidden; box-shadow: 0 12px 30px rgba(31,41,55,.15); }
      .ag-theme-quartz.dashboard-theme .ag-header { background: #5a67d8; color: #fff; }
      .ag-theme-quartz.dashboard-theme .ag-header-cell-text { color: #fff; font-weight: 600; }
      .ag-theme-quartz.dashboard-theme .ag-row-odd { background: #f3f0ff; }
      .ag-theme-quartz.dashboard-theme .ag-row-hover { background: #eef2ff !important; }
      .ag-theme-quartz.dashboard-theme { --ag-font-size: 14px; --ag-grid-size: 4px; }
      .grid-wrap { height: 520px; }
      .action-buttons { display: flex; align-items: center; gap: 8px; justify-content: flex-end; }
      .btn { height: 32px; padding: 0 10px; border-radius: 8px; border: 1px solid transparent; background: #fff; color: #374151; cursor: pointer; font-size: 13px; }
      .btn.primary { background: #5a67d8; color: #fff; }
      .btn.danger { border-color: #fecaca; color: #b91c1c; background: #fee2e2; }
    </style>

    <div id="pendingGrid" class="ag-theme-quartz dashboard-theme grid-wrap"></div>

    <script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.js"></script>
    <script>
      (function(){
        const rowData = <?php
          $rows = array_map(function($r){
            return [
              'id' => $r['id'],
              'name' => $r['name'],
              'email' => $r['email'],
              'datetime' => format_appt_display($r['date'], $r['slot']),
              'reason' => $r['reason'] ?? ''
            ];
          }, $pending);
          echo json_encode($rows, JSON_UNESCAPED_SLASHES);
        ?>;

        function actionsRenderer(params){
          const data = params.data || {}; const wrap = document.createElement('div');
          wrap.className = 'action-buttons';
          const mkForm = (action, cls, label, extraAttrs) => {
            const form = document.createElement('form');
            form.method = 'post';
            form.action = `/admin/appointments/${encodeURIComponent(data.id)}/${action}`;
            form.style.display = 'inline';
            const csrf = document.createElement('input'); csrf.type='hidden'; csrf.name='csrf'; csrf.value = <?= json_encode($csrf) ?>; form.appendChild(csrf);
            const btn = document.createElement('button'); btn.type='submit'; btn.className = `btn ${cls}`; btn.textContent = label;
            if (extraAttrs) Object.entries(extraAttrs).forEach(([k,v])=> btn.setAttribute(k, v));
            form.appendChild(btn);
            return form;
          };
          wrap.append(
            mkForm('confirm', 'primary', 'Confirm', { 'aria-label':'Confirm appointment', 'data-bs-toggle':'tooltip', 'data-bs-title':'Confirm' }),
            mkForm('cancel', 'danger', 'Cancel', { 'data-action':'cancel', 'data-name': data.name||'', 'data-email': data.email||'', 'data-datetime': data.datetime||'', 'aria-label':'Cancel appointment', 'data-bs-toggle':'tooltip', 'data-bs-title':'Cancel' })
          );
          return wrap;
        }

        const columnDefs = [
          { field: 'name', headerName: 'Name', width: 240 },
          { field: 'email', headerName: 'Email', width: 260 },
          { field: 'datetime', headerName: 'Date/Time', width: 200 },
          { field: 'reason', headerName: 'Reason', flex: 1, minWidth: 220 },
          { headerName: 'Actions', width: 220, pinned: 'right', sortable: false, filter: false, resizable: false, cellRenderer: actionsRenderer }
        ];

        const gridOptions = {
          columnDefs,
          rowData,
          defaultColDef: { sortable: true, filter: true, resizable: true },
          rowHeight: 52,
          headerHeight: 56,
          suppressMovableColumns: true,
          animateRows: true,
          pagination: false
        };

        document.addEventListener('DOMContentLoaded', function(){
          const el = document.getElementById('pendingGrid');
          if (el) agGrid.createGrid(el, gridOptions);
        });
      })();
    </script>
  <?php endif; ?>
</div>
