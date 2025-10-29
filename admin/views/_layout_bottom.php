  </main>
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/2.1.7/js/dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/2.1.7/js/dataTables.bootstrap5.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function(){
      var tables = ['#pendingTable', '#todayTable', '#apptTable'];
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
    });
  </script>
</body>
</html>
