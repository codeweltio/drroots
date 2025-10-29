  </main>
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/2.1.7/js/dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/2.1.7/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function(){
      // Enable Bootstrap tooltips globally
      const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
      tooltipTriggerList.map(el => new bootstrap.Tooltip(el));

      var tables = ['#pendingTable', '#todayTable'];
      tables.forEach(function(sel){
        var el = document.querySelector(sel);
        if (el && window.jQuery && jQuery.fn && jQuery.fn.DataTable) {
          jQuery(el).DataTable({
            pageLength: 25,
            order: [],
            language: { search: "", searchPlaceholder: "Search..." }
          });
        }
      });

      // Appointments table with Responsive details → mobile cards
      var appt = document.querySelector('#apptTable');
      if (appt && window.jQuery && jQuery.fn && jQuery.fn.DataTable) {
        jQuery('#apptTable').DataTable({
          responsive: {
            details: {
              type: 'inline',
              renderer: function ( api, rowIdx ) {
                const $row = jQuery(api.row(rowIdx).node());
                const tds = $row.find('td');
                const n = tds.length;
                const name = jQuery(tds[0]).text().trim();
                const email = jQuery(tds[1]).text().trim();
                const $phoneCell = jQuery(tds[2]);
                const phoneHtml = $phoneCell.html();
                // Actions is always the last cell
                const $actions = jQuery(tds[n-1]).clone(true,true);
                // Date/Time is the penultimate cell (or n-2)
                const dt = jQuery(tds[n-2]).text().trim();
                // Status exists only when columns include it (n===6)
                const statusHtml = (n === 6) ? jQuery(tds[4]).html() : '<span class="badge badge--confirmed">confirmed</span>';
                // Rebind tooltips inside cloned actions
                $actions.find('[data-bs-toggle="tooltip"]').tooltip();

                var $card = jQuery('<div/>').addClass('dt-rowcard').append(
                  jQuery('<div/>',{ 'class':'row-top' }).append(
                    jQuery('<div/>').text(name),
                    jQuery('<div/>').html(statusHtml)
                  ),
                  jQuery('<div/>',{ 'class':'row-mid' }).append(
                    jQuery('<div/>').html('<i class="bi bi-envelope me-1"></i><a class="link-light text-decoration-none" href="mailto:'+email+'">'+email+'</a>'),
                    jQuery('<div/>').html('<i class="bi bi-telephone me-1"></i>'+phoneHtml),
                    jQuery('<div/>').html('<i class="bi bi-calendar-event me-1"></i>'+dt)
                  ),
                  jQuery('<div/>',{ 'class':'row-bottom-bar' }).append($actions.children())
                );
                return $card[0];
              }
            }
          },
          pageLength: 10,
          lengthChange: false,
          order: [],
          columnDefs: [ { targets: -1, orderable: false } ],
          language: { search: '', searchPlaceholder: 'Search appointments...' }
        });
      }
    });
  </script>
  <!-- Global Confirm Modal reused for cancel/reschedule -->
  <div class="modal fade" id="actionConfirmModal" tabindex="-1" aria-labelledby="actionConfirmLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="background:#111827; color:#e5e7eb; border:1px solid rgba(255,255,255,.08)">
        <div class="modal-header">
          <h5 class="modal-title" id="actionConfirmLabel">Confirm Action</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="small text-secondary mb-2">Please confirm the following:</div>
          <div id="actionSummary" class="fw-medium"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" id="actionConfirmBtn">Confirm</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Intercept cancel and reschedule everywhere (pending/today/appointments tables)
    (function(){
      let pendingSubmit = null;
      function showConfirm(html, confirmText){
        document.getElementById('actionSummary').innerHTML = html;
        document.getElementById('actionConfirmBtn').textContent = confirmText || 'Confirm';
        new bootstrap.Modal('#actionConfirmModal').show();
      }
      document.getElementById('actionConfirmBtn').addEventListener('click', function(){
        const modal = bootstrap.Modal.getInstance(document.getElementById('actionConfirmModal'));
        if (pendingSubmit) pendingSubmit();
        pendingSubmit = null;
        modal.hide();
      });

      document.addEventListener('click', function(e){
        const cancelBtn = e.target.closest('[data-action="cancel"]');
        if (cancelBtn) {
          e.preventDefault();
          const form = cancelBtn.closest('form');
          const name = cancelBtn.getAttribute('data-name') || '';
          const email = cancelBtn.getAttribute('data-email') || '';
          const dt = cancelBtn.getAttribute('data-datetime') || '';
          const summary = `<div><strong>${name}</strong></div>
                           <div class="small text-secondary">${email}</div>
                           <div class="small text-secondary">${dt}</div>
                           <div class="mt-2 text-danger">This appointment will be cancelled.</div>`;
          pendingSubmit = () => form.submit();
          showConfirm(summary, 'Cancel Appointment');
        }

        const resBtn = e.target.closest('[data-action="resched"]');
        if (resBtn) {
          e.preventDefault();
          const form = resBtn.closest('form');
          const dateInput = form.querySelector('input[type="date"]');
          const timeInput = form.querySelector('input[type="time"]');
          const newDate = dateInput && dateInput.value || '';
          const newTime = timeInput && timeInput.value || '';
          const name = resBtn.getAttribute('data-name') || '';
          const email = resBtn.getAttribute('data-email') || '';
          const oldDt = resBtn.getAttribute('data-old') || '';
          const newDt = (newDate && newTime) ? `${newDate} ${newTime}` : '(pick date & time)';
          const summary = `<div><strong>${name}</strong></div>
                           <div class="small text-secondary">${email}</div>
                           <div class="small text-secondary">From: ${oldDt}</div>
                           <div class="small text-secondary">To: ${newDt}</div>`;
          pendingSubmit = () => form.submit();
          showConfirm(summary, 'Confirm Reschedule');
        }
      });
    })();
  </script>
</body>
</html>
