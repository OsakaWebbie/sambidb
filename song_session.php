<?php
include("functions.php");
include("accesscontrol.php");

// Handle basket-update form submission (replaces legacy do_tag.php)
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

$sql = "SELECT Event FROM event WHERE EventID=$eid";
if (!$result = mysqli_query($db,$sql)) {
  echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>$sql");
  exit;
}
$row = mysqli_fetch_object($result);
$event = $row->Event;

print_header("$ud, $event songs (PWDB)","#FFFFFF",0);

$sql = "SELECT UseOrder,history.SongID,Title,OrigTitle,Tempo,SongKey,Source,Audio,".
"INSTR(Lyrics,'[') AS Chords FROM history LEFT JOIN song ON song.SongID=history.SongID ".
"WHERE EventID=$eid AND UseDate='$ud' ORDER BY UseOrder";
if (!$result = mysqli_query($db,$sql)) {
  echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>$sql");
  exit;
}
echo "<center><b><font color=#A04040>Songs used on $ud at $event</font></b>";
echo "<form name=tagform action=\"".htmlspecialchars($_SERVER['PHP_SELF'])."\" method=post>\n";
echo "<input type=hidden name=\"eid\" value=\"$eid\">\n";
echo "<input type=hidden name=\"ud\" value=\"$ud\">\n";
echo "<input type=hidden name=\"update_basket\" value=\"1\">\n";
echo "<table border=1 cellspacing=0 cellpadding=2 bordercolor=#A04040 bgcolor=#FFFFFF>";
echo "<tr><td bgcolor=#A04040>&nbsp;</td>";
echo "<td bgcolor=#FFF0D0 align=center><font color=#A04040><b>Title</b></font></td>";
echo "<td bgcolor=#FFF0D0 align=center><font color=#A04040><b>Tag</b></font></td>";
//echo "<td bgcolor=#FFF0D0 align=center><font color=#A04040><b>Original Title</b></font></td>";
echo "<td bgcolor=#FFF0D0 align=center><font color=#A04040><b>Key</b></font></td>";
echo "<td bgcolor=#FFF0D0 align=center><font color=#A04040><b>Tempo</b></font></td>";
echo "<td bgcolor=#FFF0D0 align=center><font color=#A04040><b>Source</b></font></td></tr>";
$sid_list = "";
while ($row = mysqli_fetch_object($result)) {
  echo "<tr><td align=center>$row->UseOrder</td>\n";
  echo "<td nowrap><a href=\"song.php?sid=".$row->SongID."\" target=\"_BLANK\">".$row->Title."</a>";
  if (strpos(strtolower($row->Title),strtolower($row->OrigTitle)) === false)  echo " (".$row->OrigTitle.")";
  if ($row->Audio == "1")  echo "&nbsp;<img src=\"graphics/audio.gif\" height=16 width=16>";
  if ($row->Chords != "0")  echo "&nbsp;<img src=\"graphics/guitar.gif\" height=16 width=16>";
  echo "</td>\n";
  echo "<td align=center><input type=checkbox name=\"$row->SongID\"".
   (in_array($row->SongID, $_SESSION['basket'] ?? [], true) ? " checked" : "")."></td>\n";
  //echo "<td nowrap align=center>".($row->OrigTitle ? $row->OrigTitle : "&nbsp;")."</td>\n";
  echo "<td nowrap align=center>".($row->SongKey ? $row->SongKey : "&nbsp;")."</td>\n";
  echo "<td align=center>".($row->Tempo ? $row->Tempo : "&nbsp;")."</td>\n";
  echo "<td>".($row->Source ? db2table($row->Source) : "&nbsp;")."</td>\n";
  $sid_list .= ",".$row->SongID;
}
echo "</tr></table><br>\n";
echo "<input type=submit value=\"".htmlspecialchars(_('Update basket according to checkboxes above'), ENT_QUOTES)."\">&nbsp;&nbsp;&nbsp;&nbsp;";
echo "<input type=hidden name=\"sid_list\" value=\"".substr($sid_list,1)."\">";
echo "<input type=button value=\"".htmlspecialchars(_('Go to task page with this session'), ENT_QUOTES)."\" ";
echo "onclick=\"opener.top.location='task.php?sid_list='+document.tagform.sid_list.value;window.close();\">\n";
echo "</form></center>\n";

print_footer();
?>
