 

<!-- Today (AG Grid) -->
<div class="card">
  <div class="section-title">
    <h3>Today</h3>
    <span class="subtle">Quick reschedule</span>
  </div>
  <?php if (empty($today)): ?>
    <p>No appointments today.</p>
  <?php else: ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-grid.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-quartz.css" />
    <style>
      .ag-theme-quartz.today-theme { border-radius: 16px; overflow: hidden; box-shadow: 0 12px 30px rgba(31,41,55,.15); }
      .ag-theme-quartz.today-theme .ag-header { background: #5a67d8; color: #fff; }
      .ag-theme-quartz.today-theme .ag-header-cell-text { color: #fff; font-weight: 600; }
      .ag-theme-quartz.today-theme .ag-row-odd { background: #f3f0ff; }
      .ag-theme-quartz.today-theme .ag-row-hover { background: #eef2ff !important; }
      .ag-theme-quartz.today-theme { --ag-font-size: 14px; --ag-grid-size: 4px; }
      .grid-wrap { height: 520px; }
      .action-buttons { display: flex; align-items: center; gap: 8px; justify-content: flex-end; flex-wrap: wrap; }
      .btn { height: 32px; padding: 0 10px; border-radius: 8px; border: 1px solid transparent; background: #5a67d8; color: #fff; cursor: pointer; font-size: 13px; }
      .inline-input { height: 32px; padding: 4px 8px; font-size: 13px; }
      .inline-input[type="date"]{ min-width: 9rem; }
      .inline-input[type="time"]{ min-width: 6rem; }
      .badge { padding: 4px 8px; border-radius: 999px; font-size: 12px; }
      .badge--pending { background:#fff7ed; color:#9a3412; }
      .badge--confirmed { background:#ecfdf5; color:#065f46; }
      .badge--cancelled { background:#fef2f2; color:#b91c1c; }
      @media (max-width: 640px){
        .grid-wrap { height: 70vh; }
        .ag-theme-quartz.today-theme { --ag-font-size: 12px; }
      }
    </style>

    <div id="todayGrid" class="ag-theme-quartz today-theme grid-wrap"></div>

    <script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.js"></script>
    <script>
      (function(){
        const rows = <?php
          $mapped = array_map(function($r){
            return [
              'id' => $r['id'],
              'name' => $r['name'],
              'email' => $r['email'],
              'datetime' => format_appt_display($r['date'], $r['slot']),
              'date' => $r['date'],
              'slot' => $r['slot'],
              'status' => $r['status'],
            ];
          }, $today);
          echo json_encode($mapped, JSON_UNESCAPED_SLASHES);
        ?>;

        function statusRenderer(p){
          const s = p.value || ''; const span = document.createElement('span');
          const cls = s==='pending'?'badge--pending':(s==='confirmed'?'badge--confirmed':'badge--cancelled');
          span.className = 'badge ' + cls; span.textContent = s; return span;
        }

        function actionsRenderer(p){
          const d = p.data || {}; const wrap = document.createElement('div'); wrap.className='action-buttons';
          const form = document.createElement('form'); form.method='post'; form.action='/admin/appointments/reschedule'; form.style.display='inline-flex'; form.style.gap='6px';
          const csrf = document.createElement('input'); csrf.type='hidden'; csrf.name='csrf'; csrf.value = <?= json_encode($csrf) ?>; form.appendChild(csrf);
          const id = document.createElement('input'); id.type='hidden'; id.name='id'; id.value = d.id || ''; form.appendChild(id);
          const dateInput = document.createElement('input'); dateInput.type='date'; dateInput.name='date'; dateInput.required=true; dateInput.value=d.date||''; dateInput.className='inline-input'; form.appendChild(dateInput);
          const timeInput = document.createElement('input'); timeInput.type='time'; timeInput.name='slot'; timeInput.required=true; timeInput.value=d.slot||''; timeInput.className='inline-input'; form.appendChild(timeInput);
          const btn = document.createElement('button'); btn.type='submit'; btn.className='btn'; btn.textContent='Update';
          btn.setAttribute('data-action','resched');
          btn.setAttribute('data-name', d.name||'');
          btn.setAttribute('data-email', d.email||'');
          btn.setAttribute('data-old', d.datetime||'');
          btn.setAttribute('data-bs-toggle','tooltip');
          btn.setAttribute('data-bs-title','Reschedule appointment');
          form.appendChild(btn);
          wrap.appendChild(form);
          return wrap;
        }

        const cols = [
          { field:'name', headerName:'Name', width: 220 },
          { field:'email', headerName:'Email', width: 260 },
          { field:'datetime', headerName:'Date/Time', width: 200 },
          { field:'status', headerName:'Status', width: 130, cellRenderer: statusRenderer },
          { headerName:'Reschedule', width: 360, pinned:'right', sortable:false, filter:false, resizable:false, cellRenderer: actionsRenderer },
        ];

        const gridOptions = { columnDefs: cols, rowData: rows, defaultColDef:{sortable:true, filter:true, resizable:true}, rowHeight:56, headerHeight:56, suppressMovableColumns:true, animateRows:true,
          onFirstDataRendered: (p)=>{
            if (window.innerWidth <= 640) {
              p.columnApi.applyColumnState({ defaultState: { pinned: null } });
              p.api.sizeColumnsToFit();
            }
          }
        };
        document.addEventListener('DOMContentLoaded', function(){
          const el=document.getElementById('todayGrid');
          if (!el) return;
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

<!-- Pending Approvals (AG Grid) -->
<div class="card">
  <div class="section-title">
    <h3>Pending Approvals</h3>
    <span class="subtle">Approve or cancel new requests</span>
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
      @media (max-width: 640px){
        .grid-wrap { height: 70vh; }
        .ag-theme-quartz.dashboard-theme { --ag-font-size: 12px; }
      }
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
          pagination: false,
          onFirstDataRendered: (p)=>{
            if (window.innerWidth <= 640) {
              p.columnApi.applyColumnState({ defaultState: { pinned: null } });
              p.api.sizeColumnsToFit();
            }
          }
        };

        document.addEventListener('DOMContentLoaded', function(){
          const el = document.getElementById('pendingGrid');
          if (!el) return;
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
