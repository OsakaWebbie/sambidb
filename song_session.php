<?php
include("functions.php");
include("accesscontrol.php");

$sql = "SELECT Event FROM pw_event WHERE EventID=$eid";
if (!$result = mysql_query($sql)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>$sql");
  exit;
}
$row = mysql_fetch_object($result);
$event = $row->Event;

print_header("$ud, $event songs (PWDB)","#FFFFFF",0);

$sql = "SELECT UseOrder,pw_usage.SongID,Title,Tagged,OrigTitle,Tempo,SongKey,Source,Audio,".
"INSTR(Lyrics,'[') AS Chords FROM pw_usage LEFT JOIN pw_song ON pw_song.SongID=pw_usage.SongID ".
"WHERE EventID=$eid AND UseDate='$ud' ORDER BY UseOrder";
if (!$result = mysql_query($sql)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>$sql");
  exit;
}
echo "<center><b><font color=#A04040>Songs used on $ud at $event</font></b>";
echo "<form name=tagform action=\"do_tag.php\" method=post>\n";
echo "<input type=hidden name=\"eid\" value=\"$eid\">\n";
echo "<input type=hidden name=\"ud\" value=\"$ud\">\n";
echo "<table border=1 cellspacing=0 cellpadding=2 bordercolor=#A04040 bgcolor=#FFFFFF>";
echo "<tr><td bgcolor=#A04040>&nbsp;</td>";
echo "<td bgcolor=#FFF0D0 align=center><font color=#A04040><b>Title</b></font></td>";
echo "<td bgcolor=#FFF0D0 align=center><font color=#A04040><b>Tag</b></font></td>";
//echo "<td bgcolor=#FFF0D0 align=center><font color=#A04040><b>Original Title</b></font></td>";
echo "<td bgcolor=#FFF0D0 align=center><font color=#A04040><b>Key</b></font></td>";
echo "<td bgcolor=#FFF0D0 align=center><font color=#A04040><b>Tempo</b></font></td>";
echo "<td bgcolor=#FFF0D0 align=center><font color=#A04040><b>Source</b></font></td></tr>";
$sid_list = "";
while ($row = mysql_fetch_object($result)) {
  echo "<tr><td align=center>$row->UseOrder</td>\n";
  echo "<td nowrap><a href=\"song.php?sid=".$row->SongID."\" target=\"_BLANK\">".$row->Title."</a>";
  if (strpos(strtolower($row->Title),strtolower($row->OrigTitle)) === false)  echo " (".$row->OrigTitle.")";
  if ($row->Audio == "1")  echo "&nbsp;<img src=\"graphics/audio.gif\" height=16 width=16>";
  if ($row->Chords != "0")  echo "&nbsp;<img src=\"graphics/guitar.gif\" height=16 width=16>";
  echo "</td>\n";
  echo "<td align=center><input type=checkbox name=\"$row->SongID\"".
   ($row->Tagged ? " checked" : "")."></td>\n";
  //echo "<td nowrap align=center>".($row->OrigTitle ? $row->OrigTitle : "&nbsp;")."</td>\n";
  echo "<td nowrap align=center>".($row->SongKey ? $row->SongKey : "&nbsp;")."</td>\n";
  echo "<td align=center>".($row->Tempo ? $row->Tempo : "&nbsp;")."</td>\n";
  echo "<td>".($row->Source ? db2table($row->Source) : "&nbsp;")."</td>\n";
  $sid_list .= ",".$row->SongID;
}
echo "</tr></table><br>\n";
echo "<input type=submit value=\"Update tags according\nto checkboxes above\">&nbsp;&nbsp;&nbsp;&nbsp;";
echo "<input type=hidden name=\"sid_list\" value=\"".substr($sid_list,1)."\">";
echo "<input type=button value=\"Go to action page\nwith this session\" ";
echo "onclick=\"opener.top.location='multiselect.php?sid_list='+document.tagform.sid_list.value;window.close();\">\n";
echo "</form></center>\n";

print_footer();
?>
