<div class="card">
  <h3>Add User</h3>
  <form method="post" action="/admin/users/create" class="form-row">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>" />
    <div class="stack">
      <label>Email</label>
      <input type="email" name="email" required />
    </div>
    <div class="stack">
      <label>Role</label>
      <select name="role">
        <option value="staff">staff</option>
        <option value="admin">admin</option>
      </select>
    </div>
    <div class="stack">
      <label>Password</label>
      <input type="password" name="password" minlength="8" required />
    </div>
    <div class="stack" style="align-self:end;">
      <button class="btn primary" type="submit">Create</button>
    </div>
  </form>
</div>

<!-- Users (AG Grid) -->
<div class="card">
  <div class="section-title">
    <h3>Users</h3>
  </div>
  <?php if (empty($rows)): ?>
    <p>No users.</p>
  <?php else: ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-grid.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-quartz.css" />
    <style>
      .ag-theme-quartz.users-theme { border-radius: 16px; overflow: hidden; box-shadow: 0 12px 30px rgba(31,41,55,.15); }
      .ag-theme-quartz.users-theme .ag-header { background: #5a67d8; color: #fff; }
      .ag-theme-quartz.users-theme .ag-header-cell-text { color: #fff; font-weight: 600; }
      .ag-theme-quartz.users-theme .ag-row-odd { background: #f3f0ff; }
      .ag-theme-quartz.users-theme .ag-row-hover { background: #eef2ff !important; }
      .ag-theme-quartz.users-theme { --ag-font-size: 14px; --ag-grid-size: 4px; }
      .grid-wrap { height: 520px; }
      .action-buttons { display:flex; align-items:center; gap:8px; justify-content:flex-end; flex-wrap: wrap; }
      .btn { height: 32px; padding: 0 10px; border-radius: 8px; border: 1px solid transparent; background: #fff; color: #374151; cursor: pointer; font-size: 13px; }
      .btn.primary { background:#5a67d8; color:#fff; }
      .btn.danger { border-color:#fecaca; color:#b91c1c; background:#fee2e2; }
      .inline-input { height: 32px; padding: 4px 8px; font-size: 13px; }
      .inline-select { height: 32px; padding: 4px 8px; font-size: 13px; }
    </style>

    <div id="usersGrid" class="ag-theme-quartz users-theme grid-wrap"></div>

    <script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.js"></script>
    <script>
      (function(){
        const rows = <?php
          $mapped = array_map(function($u){
            return [ 'id'=>$u['id'], 'email'=>$u['email'], 'role'=>$u['role'], 'created_at'=>$u['created_at'] ];
          }, $rows);
          echo json_encode($mapped, JSON_UNESCAPED_SLASHES);
        ?>;

        function actionsRenderer(p){
          const d = p.data || {}; const wrap = document.createElement('div'); wrap.className='action-buttons';
          // Role update form with select
          const roleForm = document.createElement('form'); roleForm.method='post'; roleForm.action='/admin/users/role'; roleForm.style.display='inline-flex'; roleForm.style.gap='6px';
          const csrf = document.createElement('input'); csrf.type='hidden'; csrf.name='csrf'; csrf.value = <?= json_encode($csrf) ?>; roleForm.appendChild(csrf);
          const id = document.createElement('input'); id.type='hidden'; id.name='id'; id.value=d.id || ''; roleForm.appendChild(id);
          const sel = document.createElement('select'); sel.name='role'; sel.className='inline-select';
          ['staff','admin'].forEach(v=>{ const o=document.createElement('option'); o.value=v; o.textContent=v; if ((d.role||'')===v) o.selected=true; sel.appendChild(o); });
          roleForm.appendChild(sel);
          const upd = document.createElement('button'); upd.type='submit'; upd.className='btn'; upd.textContent='Update'; roleForm.appendChild(upd);

          // Reset password form (with inline input)
          const resetForm = document.createElement('form'); resetForm.method='post'; resetForm.action='/admin/users/reset'; resetForm.style.display='inline-flex'; resetForm.style.gap='6px';
          const csrf2 = csrf.cloneNode(true); const id2 = id.cloneNode(true); resetForm.appendChild(csrf2); resetForm.appendChild(id2);
          const pass = document.createElement('input'); pass.type='password'; pass.name='password'; pass.placeholder='New password'; pass.required=true; pass.className='inline-input'; resetForm.appendChild(pass);
          const resetBtn = document.createElement('button'); resetBtn.type='submit'; resetBtn.className='btn'; resetBtn.textContent='Reset'; resetForm.appendChild(resetBtn);

          // Delete form
          const delForm = document.createElement('form'); delForm.method='post'; delForm.action='/admin/users/delete'; delForm.style.display='inline';
          const csrf3 = csrf.cloneNode(true); const id3 = id.cloneNode(true); delForm.appendChild(csrf3); delForm.appendChild(id3);
          const delBtn = document.createElement('button'); delBtn.type='submit'; delBtn.className='btn danger'; delBtn.textContent='Delete';
          delBtn.addEventListener('click', function(e){ if (!confirm('Delete this user?')) { e.preventDefault(); } });
          delForm.appendChild(delBtn);

          wrap.append(roleForm, resetForm, delForm);
          return wrap;
        }

        const cols = [
          { field:'email', headerName:'Email', width: 300 },
          { field:'role', headerName:'Role', width: 120 },
          { field:'created_at', headerName:'Created', width: 180 },
          { headerName:'Actions', width: 540, pinned:'right', sortable:false, filter:false, resizable:false, cellRenderer: actionsRenderer },
        ];

        const gridOptions = {
          columnDefs: cols, rowData: rows,
          defaultColDef: { sortable:true, filter:true, resizable:true },
          rowHeight: 64, headerHeight: 56, suppressMovableColumns:true, animateRows:true, pagination:false
        };

        document.addEventListener('DOMContentLoaded', function(){
          const el = document.getElementById('usersGrid'); if (el) agGrid.createGrid(el, gridOptions);
        });
      })();
    </script>
  <?php endif; ?>
</div>
