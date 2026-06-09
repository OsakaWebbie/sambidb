<?php
include("functions.php");
include("accesscontrol.php");

if (isset($_POST['filter_submit'])) {
  $result = sqlquery_checked("SELECT * FROM tag ORDER BY Tag");
  $in_list = "";
  $ex_list = "";
  while ($row = mysqli_fetch_object($result)) {
    $tagid = $row->TagID;
    $choice = $_POST[$tagid] ?? '';
    if ($choice == "in") {
      $in_list .= ",".$tagid;
    } elseif ($choice == "ex") {
      $ex_list .= ",".$tagid;
    }
  }
  $_SESSION['intags'] = substr($in_list,1);
  $_SESSION['extags'] = substr($ex_list,1);
  $uid = mysqli_real_escape_string($db, $_SESSION['userid']);
  $sql = "UPDATE user SET IncludeTags='".$_SESSION['intags']."', ExcludeTags='".$_SESSION['extags']."' WHERE UserID='".$uid."'";
  sqlquery_checked($sql);
  header('Location: index.php');
  exit;
}

$result = sqlquery_checked("SELECT * FROM tag ORDER BY Tag");

pageheader(_('Filtering for Search'), 1);
?>
<style>
.filter-wrapper {
  display: flex;
  gap: 2rem;
  align-items: flex-start;
  margin: 1rem 0 1.5rem;
}
.filter-table td:not(:first-child),
.filter-table th:not(:first-child) { text-align: center; }
@media (max-width: 900px) {
  .filter-wrapper { flex-direction: column; }
}
</style>

<h1><?=_('Search Filtering')?></h1>
<p><?= sprintf(_('Modify filter criteria as desired, and click "%s".'), _('Apply Filter'))?></p>
<form name="filterform" action="<?= htmlspecialchars($_SERVER['PHP_SELF'])?>" method="POST">
  <p><button type="submit" name="filter_submit" class="ui-button ui-corner-all"><?=_('Apply Filter')?></button></p>
  <div class="filter-wrapper">
    <table id="filter-table" class="filter-table">
      <thead>
        <tr>
          <th><?=_('Tag')?></th>
          <th><?=_('Filter<br>Off')?></th>
          <th><?=_('Must<br>Include')?></th>
          <th><?=_('Must Not<br>Include')?></th>
        </tr>
      </thead>
      <tbody>
<?php while ($row = mysqli_fetch_object($result)): ?>
        <tr>
          <td><?= htmlspecialchars($row->Tag)?></td>
          <td><input type="radio" name="<?= (int)$row->TagID ?>" value=""<?= (strpos(','.$_SESSION['intags'].','.$_SESSION['extags'].',', ','.$row->TagID.',') === false) ? ' checked' : '' ?>></td>
          <td><input type="radio" name="<?= (int)$row->TagID ?>" value="in"<?= (strpos(','.$_SESSION['intags'].',', ','.$row->TagID.',') !== false) ? ' checked' : '' ?>></td>
          <td><input type="radio" name="<?= (int)$row->TagID ?>" value="ex"<?= (strpos(','.$_SESSION['extags'].',', ','.$row->TagID.',') !== false) ? ' checked' : '' ?>></td>
        </tr>
<?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <p><button type="submit" name="filter_submit" class="ui-button ui-corner-all"><?=_('Apply Filter')?></button></p>
</form>
<?php load_scripts(array('jquery')); ?>
<script>
$(function () {
  function splitTable() {
    if ($('#filter-table-2').length) {
      $('#filter-tbody-2 tr').appendTo('#filter-table tbody');
      $('#filter-table-2').remove();
    }
    if ($(window).width() > 900) {
      var $rows = $('#filter-table tbody tr');
      var half = Math.ceil($rows.length / 2);
      var $t2 = $('<table id="filter-table-2" class="filter-table"></table>')
        .append($('#filter-table thead').clone())
        .append($('<tbody id="filter-tbody-2"></tbody>'));
      $('.filter-wrapper').append($t2);
      $rows.slice(half).appendTo('#filter-tbody-2');
    }
  }
  splitTable();
  var timer;
  $(window).on('resize', function () {
    clearTimeout(timer);
    timer = setTimeout(splitTable, 150);
  });
});
</script>
<?php footer(); ?>
