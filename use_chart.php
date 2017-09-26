<?php
 include("functions.php");
 include("accesscontrol.php");

print_header("","#FFFFFF",0);
?>
<script language = "Javascript">
function song_session(eventid,usedate) {
  var left = Math.floor( (screen.width - 800) / 2);
  var top = Math.floor( (screen.height - 600) / 2);
  window.open('song_session.php?eid='+eventid+'&ud='+usedate,'','top='+top+',left='+left+',WIDTH=800,HEIGHT=600,scrollbars=yes,menubar=yes');
}
</script>

<?php
if (!$eid) {
  exit("No event selected.");
}
//get the list of dates (column headings for table)
$sql = "SELECT DISTINCT UseDate from history WHERE EventID = $eid";
if ($_GET['dateperiod']) {  //this will not be the latest set of dates
  $period_array = explode(",",$_GET['dateperiod']);
  $sql .= " AND UseDate < '".$period_array[0]."'";
}
$sql .= " ORDER BY UseDate DESC LIMIT 20";
if (!$result = mysqli_query($db,$sql)) {
  exit("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)<br>");
}
while ($darray[] = mysqli_fetch_row ($result));
$num_dates = count($darray) - 1;
$start_index = $num_dates - 1;
$startdate = $darray[$start_index][0];

//get the description of the event
$sql = "SELECT Remarks from event WHERE EventID = $eid";
if (!$result = mysqli_query($db,$sql)) {
  exit("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)<br>");
}
$row = mysqli_fetch_row ($result);

//write the description and any buttons needed for navigating to other date periods
echo "<table border=0 cellspacing=0 cellpadding=0><tr>\n";
if ($num_dates == 20) {  //there are 20 dates, so there might be more before this
  $url = $_SERVER['PHP_SELF']."?eid={$eid}&dateperiod={$startdate}";
  if ($_GET['dateperiod'])   $url .= ",".$_GET['dateperiod'];
  echo "<td valign=middle><button onclick=\"window.location='{$url}';\"><< Earlier Dates</button></td>\n";
}
if ($row[0] != "") echo "<td align=center valign=middle width=100%><b>Event Description:</b> ".$row[0]."</td>\n";
if ($_GET['dateperiod']) {  //there are more after this
 /* for ($i = 0; $i < (count($period_array)-1); $i++) {
    $j = $i + 1;
    $passvar = $period_array[$j];
    while ($j < count($period_array)) {
      j++;
      $passvar .= ",".$period_array[$j];
    }
    $url = $_SERVER['PHP_SELF']."?eid={$eid}&dateperiod={$passvar}";
    echo "<td valign=middle><button onclick=\"window.location='{$url}';\">Period Starting<br>{$period_array[$i]}</button></td>\n";
  } */
  $url = $_SERVER['PHP_SELF']."?eid={$eid}";
  echo "<td valign=middle><button onclick=\"window.location='{$url}';\">Most Recent Dates</button></td>\n";
}
echo "</tr></table><br>\n";

//get the list of songs used (row headings for table)
$sql = "SELECT DISTINCT history.SongID,Title from history LEFT JOIN song ".
    "ON history.SongID=song.SongID WHERE EventID = $eid";
if ($startdate)  $sql .= " AND UseDate >= '{$startdate}'";
if ($_GET['dateperiod'])  $sql .= " AND UseDate < '".$period_array[0]."'";
$sql .= " ORDER BY Title";
if (!$result = mysqli_query($db,$sql)) {
  exit("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)<br>");
}
while ($parray[] = mysqli_fetch_row ($result));
$num_songs = count($parray)-1;

echo "<table border=1 cellspacing=0 cellpadding=2>";
for ($r=0; $r<$num_songs; $r++) {
  echo "<tr>";
  if ($r % 15 == 0) {  // repeat date header every 15 rows
    for ($c=$num_dates-1; $c>=0; $c--) {
      if (($num_dates-$c-1) % 10 == 0) {  // repeat song name title every 10 columns
        echo "<td>Song</td>\n";
      }
      echo ("<td align=center nowrap><font size=-1><a href=\"javascript:song_session('".$eid."','".$darray[$c][0]."');\">".
      substr($darray[$c][0],0,4)."<br>".substr($darray[$c][0],5)."</a></font></td>\n");
    }
    echo "</tr><tr>";
  }
  //query for this song's use data and correlate to dates
  $sql = "SELECT UseDate from history WHERE EventID=$eid AND SongID={$parray[$r][0]}";
  if ($startdate)  $sql .= " AND UseDate >= '{$startdate}'";
  if ($_GET['dateperiod'])  $sql .= " AND UseDate < '".$period_array[0]."'";
  $sql .= " ORDER BY UseDate";
  if (!$result = mysqli_query($db,$sql)) {
    exit("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)<br>");
  }
  $row = mysqli_fetch_row ($result);
  $done = 0;
  for ($c=$num_dates-1; $c>=0; $c--) {
    if (($num_dates-$c-1) % 10 == 0) {  // repeat song names every 10 columns
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
