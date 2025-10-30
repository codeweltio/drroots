<!-- Appointments (AG Grid) -->
<div class="card">
  <div class="section-title">
    <h3>Appointments (<?= htmlspecialchars($status) ?>)</h3>
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
      /* Make even rows a soft blue instead of white */
      .ag-theme-quartz.appt-theme .ag-row-even { background: #edf2ff; }
      .ag-theme-quartz.appt-theme .ag-row-hover { background: #eef2ff !important; }
      .ag-theme-quartz.appt-theme { --ag-font-size: 14px; --ag-grid-size: 4px; }
      .grid-wrap { height: 560px; }
      .action-buttons { display: flex; align-items: center; gap: 8px; justify-content: flex-end; }
      .btn { height: 32px; padding: 0 10px; border-radius: 8px; border: 1px solid transparent; background: #fff; color: #374151; cursor: pointer; font-size: 13px; }
      .btn.primary { background: #5a67d8; color: #fff; }
      .btn.danger { border-color: #fecaca; color: #b91c1c; background: #fee2e2; }
      .inline-input { height: 32px; padding: 4px 8px; font-size: 13px; background:#eef2ff; border:1px solid #c7d2fe; border-radius:8px; color:#111827; }
      .inline-input:focus { outline:none; box-shadow: 0 0 0 2px rgba(90,103,216,0.25); border-color:#818cf8; }
      .inline-input[type="date"]{ min-width: 9rem; }
      .inline-input[type="time"]{ min-width: 6rem; }
      @media (max-width: 640px){
        .grid-wrap { height: 70vh; }
        .ag-theme-quartz.appt-theme { --ag-font-size: 12px; }
      }
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
          { field:'phone', headerName:'Phone', width: 160, cellRenderer: phoneRenderer },
          { field:'email', headerName:'Email', width: 240 },
          { field:'datetime', headerName:'Date/Time', width: 200 },
        ];
        if (showStatus) cols.push({ field:'status', headerName:'Status', width: 130 });
        cols.push({ headerName:'Actions', width: 420, pinned:'right', sortable:false, filter:false, resizable:false, cellRenderer: actionsRenderer });

        const gridOptions = {
          columnDefs: cols,
          rowData: rows,
          defaultColDef: { sortable:true, filter:true, resizable:true },
          rowHeight: 56, headerHeight: 56,
          suppressMovableColumns: true,
          animateRows: true,
          pagination: false,
          onFirstDataRendered: (p)=>{
            if (window.innerWidth <= 640) {
              p.columnApi.applyColumnState({ defaultState: { pinned: null } });
              p.api.sizeColumnsToFit();
            }
          }
        };

        document.addEventListener('DOMContentLoaded', function(){
          const el = document.getElementById('apptGrid'); if (!el) return;
          const api = agGrid.createGrid(el, gridOptions);
          let t=null; window.addEventListener('resize', function(){
            if (window.innerWidth > 640) return; clearTimeout(t);
            t=setTimeout(()=>{ try { api.api.sizeColumnsToFit(); } catch(e){} }, 150);
          });
        });
      })();
    </script>
  <?php endif; ?>
</div>
