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
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/ju/dt-1.10.18/b-1.5.4/b-colvis-1.5.4/fc-3.2.5/fh-3.1.4/r-2.2.2/sl-1.2.6/datatables.min.css"/>
<script type="text/javascript" src="//cdn.datatables.net/v/ju/dt-1.10.18/b-1.5.4/b-colvis-1.5.4/fc-3.2.5/fh-3.1.4/r-2.2.2/sl-1.2.6/datatables.js"></script>

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
