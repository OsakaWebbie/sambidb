<?php
/*
How to use:
Include this file after jQuery and jQueryUI
Example declaration:
$('#mytable').smarttable( {

main database table
database ID column (for adding data later with AJAX)
list of table columns with: class, database table.column, join fields, sortable true/false

AJAX variables:
index: main table name and column name for IDs passed in
values: ID values of rows desired, in order
field: table name and column name of requested data
join: optional SQL to connect index and field if tables are different


 */

if (!isset($_SESSION['userid'])) exit;  //not logged in

/* AJAX request for column data  */
if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

  die($content);
}

?>
<script src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.16/js/dataTables.jqueryui.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/plug-ins/1.10.16/i18n/Japanese.json"></script>

<script type="text/javascript">
  $(document).ready(function() {
    $('#songlist').dataTable( {
      "language": {
        "url": "dataTables.japanese.lang"
      }
    } );
  } );


</script>
<?php print_footer(); ?>
