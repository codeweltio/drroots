<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>AG Grid Sample (CDN, no Node)</title>

    <!-- AG Grid styles via CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-grid.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-quartz.css" />

    <style>
      html, body { height: 100%; margin: 0; font-family: system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans"; background: #f5f6fb; }
      .page { padding: 24px; }
      .grid-container { height: 600px; width: 100%; border-radius: 16px; overflow: hidden; box-shadow: 0 12px 30px rgba(31, 41, 55, 0.15); }
      .header { margin-bottom: 16px; }

      /* Colorlib-like styling for AG Grid (quartz theme) */
      .ag-theme-quartz.custom-theme .ag-root-wrapper { border-radius: 16px; }
      .ag-theme-quartz.custom-theme .ag-header { background: #5a67d8; color: #fff; }
      .ag-theme-quartz.custom-theme .ag-header-cell-text { color: #fff; font-weight: 600; }
      .ag-theme-quartz.custom-theme .ag-header-row { border-bottom: none; }
      .ag-theme-quartz.custom-theme .ag-header-cell, 
      .ag-theme-quartz.custom-theme .ag-cell { border-right: none !important; }
      .ag-theme-quartz.custom-theme .ag-row { border-bottom: none; }
      .ag-theme-quartz.custom-theme .ag-row-odd { background-color: #f3f0ff; }
      .ag-theme-quartz.custom-theme .ag-row-hover { background-color: #eef2ff !important; }
      .ag-theme-quartz.custom-theme .ag-floating-filter-body { display: none; }

      /* Tweaks to spacing and size for a clean look */
      .ag-theme-quartz.custom-theme {
        --ag-font-size: 14px;
        --ag-grid-size: 4px;
      }

      /* Action buttons inside cells */
      .action-buttons { display: flex; align-items: center; gap: 8px; }
      .btn { height: 32px; padding: 0 10px; border-radius: 8px; border: 1px solid transparent; background: #fff; color: #374151; cursor: pointer; font-size: 13px; }
      .btn:focus { outline: none; box-shadow: 0 0 0 2px rgba(90,103,216,0.25); }
      .btn.primary { background: #5a67d8; color: #fff; }
      .btn.primary:hover { background: #4c51bf; }
      .btn.ghost { border-color: #c7d2fe; color: #4f46e5; background: #eef2ff; }
      .btn.ghost:hover { background: #e0e7ff; }
      .btn.danger { border-color: #fecaca; color: #b91c1c; background: #fee2e2; }
      .btn.danger:hover { background: #fecaca; }
    </style>
  </head>
  <body>
    <div class="page">
      <div class="header">
        <h1>AG Grid Sample (CDN, no Node)</h1>
        <p>Sortable, filterable grid using inline sample data.</p>
      </div>

      <!-- Grid container needs an explicit height and a theme class -->
      <div id="myGrid" class="ag-theme-quartz custom-theme grid-container"></div>
    </div>

    <!-- AG Grid script via CDN (Community) -->
    <script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.js"></script>
    <script>
      // Sample data
      const rowData = [
        { make: "Tesla",    model: "Model Y",  price: 64950, electric: true,  date: "2024-01-05" },
        { make: "Ford",     model: "F-Series", price: 33850, electric: false, date: "2023-07-18" },
        { make: "Toyota",   model: "Corolla",  price: 29600, electric: false, date: "2022-11-09" },
        { make: "Mercedes", model: "EQA",      price: 48890, electric: true,  date: "2024-05-23" },
        { make: "Fiat",     model: "500",      price: 15774, electric: false, date: "2021-08-13" },
        { make: "Nissan",   model: "Juke",     price: 20675, electric: false, date: "2022-03-30" }
      ];

      // Column definitions with common defaults
      // Simple cell renderer for action buttons
      function actionsRenderer(params) {
        const wrap = document.createElement('div');
        wrap.className = 'action-buttons';
        const mk = params.data?.make ?? '';
        const model = params.data?.model ?? '';
        const makeBtn = (label, cls, handler) => {
          const b = document.createElement('button');
          b.className = `btn ${cls}`;
          b.textContent = label;
          b.addEventListener('click', (e) => { e.stopPropagation(); handler?.(); });
          return b;
        };
        wrap.append(
          makeBtn('View', 'primary', () => alert(`View: ${mk} ${model}`)),
          makeBtn('Edit', 'ghost', () => alert(`Edit: ${mk} ${model}`)),
          makeBtn('Delete', 'danger', () => {
            const ok = confirm(`Delete ${mk} ${model}?`);
            if (ok) {
              // Example: remove row locally
              params.api.applyTransaction({ remove: [params.data] });
            }
          })
        );
        return wrap;
      }

      const columnDefs = [
        { field: 'make',     headerName: 'Make',     width: 260 },
        { field: 'model',    headerName: 'Model',    width: 180 },
        { field: 'price',    headerName: 'Price',    width: 140, filter: 'agNumberColumnFilter', valueFormatter: p => p.value?.toLocaleString() },
        { field: 'electric', headerName: 'Electric', width: 120, cellRenderer: p => p.value ? 'Yes' : 'No' },
        { field: 'date',     headerName: 'Added',    width: 160, filter: 'agDateColumnFilter' },
        { headerName: 'Actions', colId: 'actions', width: 260, pinned: 'right', sortable: false, filter: false, resizable: false, cellRenderer: actionsRenderer }
      ];

      const gridOptions = {
        columnDefs,
        rowData,
        // Enable drag-to-resize; adjust initial widths above
        defaultColDef: { sortable: true, filter: true, resizable: true },
        animateRows: true,
        rowSelection: 'single',
        suppressMovableColumns: true,
        rowHeight: 56,
        headerHeight: 56,
        pagination: false
      };

      // Create the grid once DOM is ready
      document.addEventListener('DOMContentLoaded', function () {
        const eGridDiv = document.getElementById('myGrid');
        agGrid.createGrid(eGridDiv, gridOptions);
      });
    </script>
  </body>
  </html>
