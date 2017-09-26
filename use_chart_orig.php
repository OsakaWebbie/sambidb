<?php
//just to force an error for testing
junk();
//end of error forcing

 include("functions.php");
 include("accesscontrol.php");

print_header("","#FFFFFF",0);
?>
<script language = "Javascript">
function song_session(eventid,usedate) {
  var left = Math.floor( (screen.width - 700) / 2);
  var top = Math.floor( (screen.height - 600) / 2);
  window.open('song_session.php?eid='+eventid+'&ud='+usedate,'','top='+top+',left='+left+',WIDTH=700,HEIGHT=600,scrollbars=yes,menubar=yes');
}
</script>

<?php
if (!$eid) {
  exit("No event selected.");
}
//get the description of the event
$sql = "SELECT Remarks from event WHERE EventID = $eid";
if (!$result = mysqli_query($db,$sql)) {
  exit("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)<br>");
}
$row = mysqli_fetch_row ($result);
if ($row[0] != "") echo "<b>Event Description:</b> ".$row[0]."<br>&nbsp;<br>\n";

//get the list of songs used (row headings for table)
$sql = "SELECT DISTINCT history.SongID,Title from history LEFT JOIN song ".
    "ON history.SongID=song.SongID WHERE EventID = $eid ORDER BY Title";
if (!$result = mysqli_query($db,$sql)) {
  exit("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)<br>");
}
while ($parray[] = mysqli_fetch_row ($result));
$num_songs = count($parray)-1;

//get the list of dates (column headings for table)
$sql = "SELECT DISTINCT UseDate from history WHERE EventID = $eid ORDER BY UseDate";
if (!$result = mysqli_query($db,$sql)) {
  exit("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)<br>");
}
while ($darray[] = mysqli_fetch_row ($result));
$num_dates = count($darray)-1;

echo "<table border=1 cellspacing=0 cellpadding=2>";
for ($r=0; $r<$num_songs; $r++) {
  echo "<tr>";
  if ($r % 15 == 0) {  // repeat date header every 15 rows
    for ($c=0; $c<$num_dates; $c++) {
      if ($c % 10 == 0) {  // repeat song name title every 10 columns
        echo "<td>Song</td>\n";
      }
      echo ("<td align=center><font size=-1><a href=\"javascript:song_session('".$eid."','".$darray[$c][0]."');\">".
      substr($darray[$c][0],0,4)."<br>".substr($darray[$c][0],5)."</a></font></td>\n");
    }
    echo "</tr><tr>";
  }
  //query for this song's use data and correlate to dates
  $sql = "SELECT UseDate from history WHERE EventID=$eid and SongID={$parray[$r][0]} ORDER BY UseDate";
  if (!$result = mysqli_query($db,$sql)) {
    exit("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)<br>");
  }
  $row = mysqli_fetch_row ($result);
  $done = 0;
  for ($c=0; $c<$num_dates; $c++) {
    if ($c % 10 == 0) {  // repeat song names every 10 columns
      echo "<td nowrap><a href=\"song.php?sid=".$parray[$r][0]."\" target=\"_blank\">";
      echo $parray[$r][1]."</a></td>\n";
    }
    if (($done == 0) && ($row[0] == $darray[$c][0])) {
      echo "<td align=center bgcolor=#8080FF>*</td>";
      if (!$row = mysqli_fetch_row ($result)) $done = 1;
    } else {
      echo "<td>&nbsp;</td>";
    }
  }
  echo "</tr>\n";
}
echo "</table>\n";
echo "<SCRIPT FOR=window EVENT=onload LANGUAGE=\"Javascript\">\n";
echo "window.scrollTo(4000, 0);\n";
echo "</SCRIPT>\n";

print_footer();
?>
