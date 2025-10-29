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
                const name = jQuery(tds[0]).text().trim();
                const email = jQuery(tds[1]).text().trim();
                const $phoneCell = jQuery(tds[2]);
                const phoneHtml = $phoneCell.html();
                const dt = jQuery(tds[3]).text().trim();
                const statusHtml = jQuery(tds[4]).html();
                const $actions = jQuery(tds[5]).clone(true,true);
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
</body>
</html>
