<?php
include("functions.php");
include("accesscontrol.php");

if (!empty($_POST['update_basket']) && !empty($_POST['sid_list'])) {
  $all_sids = array_filter(array_map('intval', explode(',', $_POST['sid_list'])));
  foreach ($all_sids as $s) {
    if (!empty($_POST[$s])) {
      if (!in_array($s, $_SESSION['basket'], true)) $_SESSION['basket'][] = $s;
    } else {
      $_SESSION['basket'] = array_values(array_diff($_SESSION['basket'], [$s]));
    }
  }
  saveBasket();
}

$eid = intval($_GET['eid'] ?? 0);
$ud  = $_GET['ud'] ?? '';

$row   = mysqli_fetch_object(sqlquery_checked("SELECT Event FROM event WHERE EventID=$eid"));
$event = $row->Event;

$title = sprintf(_('Songs used on %s at %s'), htmlspecialchars($ud, ENT_QUOTES, 'UTF-8'), htmlspecialchars($event, ENT_QUOTES, 'UTF-8'));
header1($title);
?>
<link rel="stylesheet" type="text/css" href="css/jquery-ui.css">
<?php header2(0); ?>

<h3><?= $title ?></h3>
<form action="<?= htmlspecialchars($_SERVER['PHP_SELF'].'?eid='.$eid.'&ud='.urlencode($ud), ENT_QUOTES) ?>" method="post">
  <input type="hidden" name="update_basket" value="1">
  <table>
    <tr>
      <th></th>
      <th><?= _('Title') ?></th>
      <th><input type="checkbox" id="checkall" title="<?= htmlspecialchars(_('Check/uncheck all'), ENT_QUOTES) ?>"></th>
      <th><?= _('Key') ?></th>
      <th><?= _('Tempo') ?></th>
      <th><?= _('Source') ?></th>
    </tr>
<?php
$result = sqlquery_checked(
  "SELECT UseOrder, history.SongID, Title, OrigTitle, Tempo, SongKey, Source, Audio, ".
  "INSTR(Lyrics, '[') AS Chords FROM history LEFT JOIN song ON song.SongID = history.SongID ".
  "WHERE EventID=$eid AND UseDate='".mysqli_real_escape_string($db, $ud)."' ORDER BY UseOrder"
);
$sid_list = '';
while ($row = mysqli_fetch_object($result)) {
  echo "    <tr>\n";
  echo "      <td>".$row->UseOrder."</td>\n";
  echo "      <td style='white-space:nowrap; text-align:left'><a href='song.php?sid=".intval($row->SongID)."' target='_blank'>".d2h($row->Title)."</a>";
  if (strpos(strtolower($row->Title), strtolower($row->OrigTitle)) === false)
    echo " (".d2h($row->OrigTitle).")";
  if ($row->Audio == "1")  echo "&nbsp;<img src='graphics/audio.gif' height='16' width='16'>";
  if ($row->Chords != "0") echo "&nbsp;<img src='graphics/guitar.gif' height='16' width='16'>";
  echo "</td>\n";
  echo "      <td style='text-align:center'><input type='checkbox' class='row-cb' name='".intval($row->SongID)."'".
    (in_array(intval($row->SongID), $_SESSION['basket'] ?? [], true) ? " checked" : "")."></td>\n";
  echo "      <td style='white-space:nowrap'>".d2h($row->SongKey)."</td>\n";
  echo "      <td>".$row->Tempo."</td>\n";
  echo "      <td style='text-align:left'>".($row->Source ? d2h($row->Source) : '')."</td>\n";
  echo "    </tr>\n";
  $sid_list .= ','.$row->SongID;
}
?>
  </table>
  <input type="hidden" name="sid_list" value="<?= htmlspecialchars(ltrim($sid_list, ','), ENT_QUOTES) ?>">
  <div style="margin-top:10px">
    <input type="submit" class="ui-button ui-corner-all" value="<?= htmlspecialchars(_('Update basket according to checkboxes above'), ENT_QUOTES) ?>">
    &nbsp;&nbsp;&nbsp;&nbsp;
    <input type="button" class="ui-button ui-corner-all" value="<?= htmlspecialchars(_('Go to task page with this session'), ENT_QUOTES) ?>"
      onclick="opener.top.location='task.php?sid_list='+this.form.sid_list.value;window.close();">
  </div>
</form>
<script>
  document.getElementById('checkall').addEventListener('change', function() {
    document.querySelectorAll('.row-cb').forEach(function(cb) { cb.checked = this.checked; }, this);
  });
</script>
<?php footer(); ?>