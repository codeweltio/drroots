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

<!-- AG Grid version of Appointments -->
<div class="card">
  <div class="section-title">
    <h3>Appointments (<?= htmlspecialchars($status) ?>, Grid)</h3>
    <span class="subtle">AG Grid with actions</span>
  </div>
  <?php if (empty($rows)): ?>
    <p>No appointments.</p>
  <?php else: ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-grid.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-quartz.css" />
    <style>
      .ag-theme-quartz.appt-theme { border-radius: 16px; overflow: hidden; box-shadow: 0 12px 30px rgba(31,41,55,.15); }
      .ag-theme-quartz.appt-theme .ag-header { background: #5a67d8; color: #fff; }
      .ag-theme-quartz.appt-theme .ag-header-cell-text { color: #fff; font-weight: 600; }
      .ag-theme-quartz.appt-theme .ag-row-odd { background: #f3f0ff; }
      .ag-theme-quartz.appt-theme .ag-row-hover { background: #eef2ff !important; }
      .ag-theme-quartz.appt-theme { --ag-font-size: 14px; --ag-grid-size: 4px; }
      .grid-wrap { height: 560px; }
      .action-buttons { display: flex; align-items: center; gap: 8px; justify-content: flex-end; }
      .btn { height: 32px; padding: 0 10px; border-radius: 8px; border: 1px solid transparent; background: #fff; color: #374151; cursor: pointer; font-size: 13px; }
      .btn.primary { background: #5a67d8; color: #fff; }
      .btn.danger { border-color: #fecaca; color: #b91c1c; background: #fee2e2; }
      .inline-input { height: 32px; padding: 4px 8px; font-size: 13px; }
    </style>

    <div id="apptGrid" class="ag-theme-quartz appt-theme grid-wrap"></div>

    <script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.js"></script>
    <script>
      (function(){
        const status = <?= json_encode($status) ?>;
        const rows = <?php
          $mapped = array_map(function($r) use ($status){
            $tel = preg_replace('/[^+0-9]/', '', (string)($r['phone'] ?? ''));
            return [
              'id' => $r['id'],
              'name' => $r['name'],
              'email' => $r['email'],
              'phone' => $r['phone'] ?? '',
              'tel' => $tel,
              'datetime' => format_appt_display($r['date'], $r['slot']),
              'date' => $r['date'],
              'slot' => $r['slot'],
              'status' => $r['status'],
            ];
          }, $rows);
          echo json_encode($mapped, JSON_UNESCAPED_SLASHES);
        ?>;

        function phoneRenderer(p){
          const tel = p.data?.tel || '';
          const phone = p.data?.phone || '';
          if (tel) {
            const a = document.createElement('a'); a.href = 'tel:' + tel; a.textContent = phone; a.className = 'phone-link'; return a;
          }
          return document.createTextNode(phone);
        }

        function actionsRenderer(params){
          const d = params.data || {}; const wrap = document.createElement('div'); wrap.className='action-buttons';
          const mkForm = (opts) => {
            const form = document.createElement('form'); form.method='post'; form.action = opts.action; form.style.display='inline';
            const csrf = document.createElement('input'); csrf.type='hidden'; csrf.name='csrf'; csrf.value = <?= json_encode($csrf) ?>; form.appendChild(csrf);
            (opts.hidden||[]).forEach(([n,v])=>{ const i=document.createElement('input'); i.type='hidden'; i.name=n; i.value=v; form.appendChild(i); });
            if (opts.inputs) opts.inputs.forEach(el => form.appendChild(el));
            const btn = document.createElement('button'); btn.type='submit'; btn.className=opts.btnClass; btn.textContent=opts.label; if (opts.attrs) Object.entries(opts.attrs).forEach(([k,v])=>btn.setAttribute(k,v));
            form.appendChild(btn); return form;
          };

          if (status === 'pending') {
            wrap.append(
              mkForm({ action:`/admin/appointments/${encodeURIComponent(d.id)}/confirm`, label:'Confirm', btnClass:'btn primary', attrs:{'data-bs-toggle':'tooltip','data-bs-title':'Confirm appointment','aria-label':'Confirm appointment'} }),
              mkForm({ action:`/admin/appointments/${encodeURIComponent(d.id)}/cancel`, label:'Cancel', btnClass:'btn danger', attrs:{'data-action':'cancel','data-name':d.name||'','data-email':d.email||'','data-datetime':d.datetime||'','data-bs-toggle':'tooltip','data-bs-title':'Cancel appointment','aria-label':'Cancel appointment'} })
            );
          } else if (status === 'confirmed') {
            const dateInput = document.createElement('input'); dateInput.type='date'; dateInput.name='date'; dateInput.required=true; dateInput.value=d.date||''; dateInput.className='inline-input';
            const timeInput = document.createElement('input'); timeInput.type='time'; timeInput.name='slot'; timeInput.required=true; timeInput.value=d.slot||''; timeInput.className='inline-input';
            wrap.append(
              mkForm({ action:'/admin/appointments/reschedule', label:'Update', btnClass:'btn primary', hidden:[['id', d.id]], inputs:[dateInput, timeInput], attrs:{'data-action':'resched','data-name':d.name||'','data-email':d.email||'','data-old':d.datetime||'','data-bs-toggle':'tooltip','data-bs-title':'Reschedule appointment','aria-label':'Reschedule appointment'} }),
              mkForm({ action:`/admin/appointments/${encodeURIComponent(d.id)}/cancel`, label:'Cancel', btnClass:'btn danger', attrs:{'data-action':'cancel','data-name':d.name||'','data-email':d.email||'','data-datetime':d.datetime||'','data-bs-toggle':'tooltip','data-bs-title':'Cancel appointment','aria-label':'Cancel appointment'} })
            );
          }
          return wrap;
        }

        const showStatus = status !== 'confirmed';
        const cols = [
          { field:'name', headerName:'Name', width: 220 },
          { field:'email', headerName:'Email', width: 240 },
          { field:'phone', headerName:'Phone', width: 160, cellRenderer: phoneRenderer },
          { field:'datetime', headerName:'Date/Time', width: 200 },
        ];
        if (showStatus) cols.push({ field:'status', headerName:'Status', width: 130 });
        cols.push({ headerName:'Actions', width: 340, pinned:'right', sortable:false, filter:false, resizable:false, cellRenderer: actionsRenderer });

        const gridOptions = {
          columnDefs: cols,
          rowData: rows,
          defaultColDef: { sortable:true, filter:true, resizable:true },
          rowHeight: 56, headerHeight: 56,
          suppressMovableColumns: true,
          animateRows: true,
          pagination: false
        };

        document.addEventListener('DOMContentLoaded', function(){
          const el = document.getElementById('apptGrid'); if (el) agGrid.createGrid(el, gridOptions);
        });
      })();
    </script>
  <?php endif; ?>
</div>
