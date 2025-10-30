<div class="card">
  <div class="section-title">
    <h3>Activity</h3>
    <span class="subtle">Recent admin actions (last 200)</span>
  </div>
  <form method="get" action="/admin/activity" class="activity-filter">
    <label class="sr-only" for="af-action">Action</label>
    <input id="af-action" type="text" name="action" value="<?= htmlspecialchars($filters['action'] ?? '') ?>" placeholder="confirm | cancel | reschedule | create | ..." />
    <label class="sr-only" for="af-actor">Actor (email)</label>
    <input id="af-actor" type="email" name="actor" value="<?= htmlspecialchars($filters['actor'] ?? '') ?>" placeholder="staff@example.com" />
    <label class="sr-only" for="af-from">From</label>
    <input id="af-from" type="date" name="from" value="<?= htmlspecialchars($filters['from'] ?? '') ?>" />
    <label class="sr-only" for="af-to">To</label>
    <input id="af-to" type="date" name="to" value="<?= htmlspecialchars($filters['to'] ?? '') ?>" />
    <button class="btn sm primary" type="submit">Filter</button>
    <a class="btn sm ghost" href="/admin/activity">Reset</a>
  </form>
  <?php if (empty($events)): ?>
    <p>No activity recorded yet.</p>
  <?php endif; ?>
</div>

<!-- Activity (AG Grid) -->
<div class="card">
  <div class="section-title">
    <h3>Activity</h3>
    <span class="subtle">Recent admin actions (last 200)</span>
  </div>
  <?php if (empty($events)): ?>
    <p>No activity recorded yet.</p>
  <?php else: ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-grid.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-quartz.css" />
    <style>
      .ag-theme-quartz.activity-theme { border-radius: 16px; overflow: hidden; box-shadow: 0 12px 30px rgba(31,41,55,.15); }
      .ag-theme-quartz.activity-theme .ag-header { background: #5a67d8; color: #fff; }
      .ag-theme-quartz.activity-theme .ag-header-cell-text { color: #fff; font-weight: 600; }
      .ag-theme-quartz.activity-theme .ag-row-odd { background: #f3f0ff; }
      .ag-theme-quartz.activity-theme .ag-row-even { background: #f0f9ff; }
      .ag-theme-quartz.activity-theme .ag-row-hover { background: #dbeafe !important; }
      .ag-theme-quartz.activity-theme { --ag-font-size: 14px; --ag-grid-size: 4px; }
      .grid-wrap { height: 560px; }
      code.meta { font-size: 12px; }
      @media (max-width: 640px){
        .grid-wrap { height: 70vh; }
        .ag-theme-quartz.activity-theme { --ag-font-size: 12px; }
      }
    </style>

    <div id="activityGrid" class="ag-theme-quartz activity-theme grid-wrap"></div>

    <script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.js"></script>
    <script>
      (function(){
        const rows = <?php
          $mapped = array_map(function($e){
            return [
              'ts' => $e['ts'] ?? '',
              'actor' => ($e['actor_email'] ?? '') ?: ($e['actor_id'] ?? ''),
              'action' => $e['action'] ?? '',
              'entity' => ($e['entity'] ?? '') . '#' . ($e['entity_id'] ?? ''),
              'meta' => json_encode($e['meta'] ?? [], JSON_UNESCAPED_SLASHES),
            ];
          }, $events);
          echo json_encode($mapped, JSON_UNESCAPED_SLASHES);
        ?>;

        function metaRenderer(p){
          const code = document.createElement('code'); code.className='meta'; code.textContent = p.value || ''; return code;
        }

        const cols = [
          { field:'ts', headerName:'When', width: 200 },
          { field:'actor', headerName:'Actor', width: 220 },
          { field:'action', headerName:'Action', width: 140 },
          { field:'entity', headerName:'Entity', width: 200 },
          { field:'meta', headerName:'Details', flex: 1, minWidth: 300, cellRenderer: metaRenderer },
        ];

        const gridOptions = { columnDefs: cols, rowData: rows, defaultColDef:{sortable:true, filter:true, resizable:true}, rowHeight:48, headerHeight:56, suppressMovableColumns:true, animateRows:true,
          onFirstDataRendered: (p)=>{ if (window.innerWidth <= 640) p.api.sizeColumnsToFit(); }
        };
        document.addEventListener('DOMContentLoaded', function(){
          const el=document.getElementById('activityGrid'); if (!el) return;
          const api = agGrid.createGrid(el, gridOptions);
          let t=null; window.addEventListener('resize', function(){ if (window.innerWidth > 640) return; clearTimeout(t); t=setTimeout(()=>{ try { api.api.sizeColumnsToFit(); } catch(e){} }, 150); });
        });
      })();
    </script>
  <?php endif; ?>
</div>
