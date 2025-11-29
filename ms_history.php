<?php
include("functions.php");
include("accesscontrol.php");
header1('');
header2(0);
?>
<link rel="stylesheet" type="text/css" href="//code.jquery.com/ui/1.12.1/themes/cupertino/jquery-ui.css">
<?php

if (isset($_POST['save_history'])) {
  if (!isset($_POST['confirmed'])) {
    // check for songs already on this event and date
    $sql = "SELECT song.Title, history.UseOrder FROM history LEFT JOIN song".
    " ON history.SongID=song.SongID WHERE EventID=".$_POST['event_id']." AND UseDate='".$_POST['use_date']."' ORDER BY UseOrder";
    if (!$result = mysqli_query($db,$sql)) {
      echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
      exit;
    }
    if (mysqli_num_rows($result) > 0) {
      // ask for confirmation before replacing song session
      ?>
      <div class="ui-icon-alert"><?=_('There are already songs recorded for this event and date.')?></div>
      <?=_("The list is to the right. If you do not want to replace these songs with your selection, just select your browser's Back button.")?></b></font>
      <form action="<?=$_SERVER['PHP_SELF']?>" method="post">
      <input type=hidden name=sid_list value="$sid_list">
      <input type=hidden name=event_id value="<?=$_POST['event_id']?>">
      <input type=hidden name=use_date value="<?=$_POST['use_date']?>">
      <input type=hidden name=confirmed value="1">
      <input type=submit name=save_history value="<?=_('Yes, replace with new selected songs')?>">
      </form></td><td><b><?=_('Previously recorded song session:')?></b><br>
      <?php
      while ($row = mysqli_fetch_object($result)) {
        echo "&nbsp; &nbsp; &nbsp;".$row->UseOrder.". ".$row->Title."<br>\n";
      }
      echo "</td></tr></table>\n";
      exit;
    }
  }
  
  echo "<h3>";
  
  if (isset($_POST['confirmed'])) {   // there are old records that need to be deleted
    $sql = "DELETE FROM history WHERE EventID=".$_POST['event_id']." AND UseDate='".$_POST['use_date']."'";
    if (!$result = mysqli_query($db,$sql)) {
      echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
      exit;
    }
  echo _("Old history records deleted.");
  }

  if ($sid_list == "") {
    echo _("No new records added, as list was empty.<br>You must have just wanted to get rid of some old data (wink!).");
  } else {
    $sid_array = explode(",",$sid_list);
    $num_sids = count($sid_array);
    for ($i=0; $i<$num_sids; $i++) {
      $sql = "INSERT INTO history (SongID,EventID,UseDate,UseOrder) VALUES (".
      $sid_array[$i].",".$_POST['event_id'].",'".$_POST['use_date']."',".($i+1).")";
      if (!$result = mysqli_query($db,$sql)) {
        echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
        exit;
      }
    }
    echo sprintf(_('%s new history records added.'), $num_sids);
  }
  echo "</h3>";
  exit;
}
?>

<div align="center">
  <h2 style="color:#663399"><?=_('Choose the event and pick the date:')?></h2>
  <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name="hform" target="_self" onsubmit="return validate();">
    <input type="hidden" name="sid_list" value="<?php echo $sid_list; ?>">
    <div class="flex-container">
      <div class="flexbox align-left">
        <h3><?=_('Event:')?></h3>
<?php
$result = sqlquery_checked('SELECT * FROM event WHERE Active=1 ORDER BY '.
    (isset($_SESSION['default_event'])?'IF (EventID='.$_SESSION['default_event'].',0,1), ':''). 'Event');
while ($row = mysqli_fetch_object($result)) {
  echo '        <label'.($row->Remarks!==''?' title="'.escape_quotes($row->Remarks).'"':'').
      '><input type="radio" name="event_id" value="'.$row->EventID.'"'.
      ((isset($_SESSION['default_event']) && $row->EventID==$_SESSION['default_event'])?' checked':'').'> '.
      escape_quotes($row->Event)."</label><br>\n";
}
?>
      </div>
      <div class="flexbox">
        <h3><label><?php echo _('Date Used:'); ?> <input type="text" name="use_date" id="use_date" value="" size="12" maxlength="10"></label></h3>
        <input type="submit" name="save_history" value="<?php echo _('Save Data'); ?>">
      </div>
    </div>
  </form>
</div>
<script src="https://code.jquery.com/jquery-3.2.1.min.js" type="text/javascript"></script>
<script src="//code.jquery.com/ui/1.12.1/jquery-ui.min.js" type="text/javascript"></script>
<script>
$( function() {
  $( "#use_date" ).datepicker({ dateFormat: "yy-mm-dd"});
} );

function validate() {
  date = $('#use_date');
  if (date.val() === '') {
    alert('<?=_("You must enter a date.")?>');
    date.click();
    return false;
  }
  try { $.datepicker.parseDate('yy-mm-dd', date.val()); }
  catch(error) {
    alert('<?=_("Date is invalid.")?>');
    return false;
  }
}
</script>
<?php print_footer(); ?>
