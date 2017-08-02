<?php
error_reporting(E_ALL);
include("functions.php");
include("accesscontrol.php");
header1('');
header2(0,"#F0E0FF",1,0);

if ($save_usage) {
  if ($event_select == "new") {  //need to insert the new event record first
    $sql = "INSERT INTO pw_event (Event,Active,Remarks) ".
    "VALUES ('$event',".(($active)?"1":"0").",'$remarks')";
    if (!$result = mysql_query($sql)) {
      echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
      exit;
    }
    if (mysql_affected_rows() > 0) {
      $event_id = mysql_insert_id();
      echo "<h3><font color=\"#663399\">New event successfully added.</font></h3>";
    } else {
      echo "No event record was inserted for some reason.<br>";
      exit;
    }
  } elseif (!$confirmed) {
    // check for songs already on this event and date
    $sql = "SELECT pw_song.Title, pw_usage.UseOrder FROM pw_usage LEFT JOIN pw_song".
    " ON pw_usage.SongID=pw_song.SongID WHERE EventID=$event_id AND UseDate='$use_date' ORDER BY UseOrder";
    if (!$result = mysql_query($sql)) {
      echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
      exit;
    }
    if (mysql_num_rows($result) > 0) {
      // ask for confirmation before replacing song session
      echo "<table border=0 cellspacing=0 cellpadding=5><tr><td width=350>\n";
      echo "<font color=red><b>There are already songs recorded for this event and date.";
      echo " The list is to the right. If you do not want to replace these songs with your selection,";
      echo " just select your browser's Back button.</b></font>";
      echo "<form action=\"$PHP_SELF\" method=post>";
      echo "<input type=hidden name=sid_list value=\"$sid_list\">\n";
      echo "<input type=hidden name=event_id value=\"$event_id\">";
      echo "<input type=hidden name=use_date value=\"$use_date\">\n";
      echo "<input type=hidden name=confirmed value=\"1\">\n";
      echo "<input type=submit name=save_usage value=\"Yes, replace with new selected songs\" border=0>\n";
      echo "</form></td><td><b>Previously recorded song session:</b><br>\n";
      while ($row = mysql_fetch_object($result)) {
        echo "&nbsp; &nbsp; &nbsp;".$row->UseOrder.". ".$row->Title."<br>\n";
      }
      echo "</td></tr></table>\n";
      exit;
    }
  }
  
  echo "<h3><font color=\"#663399\">";
  
  if ($confirmed) {   // there are old records that need to be deleted
    $sql = "DELETE FROM pw_usage WHERE EventID=$event_id AND UseDate='$use_date'";
    if (!$result = mysql_query($sql)) {
      echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
      exit;
    }
  echo "Old usage records deleted.<br>";
  }

  if ($sid_list == "") {
    echo "No new records added, as list was empty.<br>You must have just wanted to get rid of some old data (wink!).";
  } else {
    $sid_array = split(",",$sid_list);
    $num_sids = count($sid_array);
    for ($i=0; $i<$num_sids; $i++) {
      $sql = "INSERT INTO pw_usage (SongID,EventID,UseDate,UseOrder) VALUES (".
      $sid_array[$i].",$event_id,'$use_date',".($i+1).")";
      if (!$result = mysql_query($sql)) {
        echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
        exit;
      }
    }
    echo $num_sids." new usage records added.";
  }
  echo "</font></h3>";
  exit;
}
?>

<div align="center">
  <h2 style="color:#663399">Choose the event and pick the date:</h2>
  <form action="<? echo $PHP_SELF; ?>" method="post" name="useform" target="_self">
    <input type="hidden" name="sid_list" value="<? echo $sid_list; ?>">
    <div class="flex-container">
      <div class="flexbox align-left">
        <h3>Event:</h3>
<?php
$sql = 'SELECT * FROM pw_event ORDER BY '.(isset($_SESSION['pw_default_event'])?'IF (EventID='.$_SESSION['pw_default_event'].',0,1), ':'').'Event';
$result = sqlquery_checked($sql);
while ($row = mysqli_fetch_object($result)) {
  echo '        <label'.($row->Remarks!==''?' title="'.escape_quotes($row->Remarks).'"':'').'><input type="radio" name="event_id" value="'.$row->EventID.'"'.
      ((isset($_SESSION['pw_default_event']) && $row->EventID==$_SESSION['pw_default_event'])?' checked':'').'> '.
      escape_quotes($row->Event)."</label><br>\n";
}
?>
      </div>
      <div class="flexbox">
        <h3>Date Used: <input type="text" name="use_date" id="use_date" value="" size="12" maxlength="10"></h3>
        <input type="submit" name="save_usage" value="Save Data" border="0">
      </div>
    </div>
  </form>
</div>
<script>
$( function() {
  $( "#use_date" ).datepicker({ dateFormat: "yy-mm-dd"});
} );
</script>
<? print_footer(); ?>
