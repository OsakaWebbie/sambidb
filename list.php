<?php
include("functions.php");
include("accesscontrol.php");

$select = "song.SongID,Title,Tagged,OrigTitle,Tempo,SongKey,stripchord(LEFT(Lyrics,INSTR(Lyrics,'\n')-1)) AS FirstLine, ".
"Audio, Lyrics REGEXP '\\\\[[^rR]' AS Chords";
$from = "song";
/*if ($_SESSION['inkeys'] or $kwid) {
  if (ereg(",".$kwid.",", ",".$_SESSION['inkeys'])) {  // The search keyword is already in the filter
    $list = $_SESSION['inkeys'];
  } elseif ($_SESSION['inkeys'] and $kwid) {  // Both are present and unique, so combine them in one list
    $list = $_SESSION['inkeys'].",".$kwid;
  } else {  // We know one but not both is set
    $list = $_SESSION['inkeys'].$kwid;
  }
  $where .= ($where?" AND ":"")."song.SongID IN (SELECT SongID FROM songkey WHERE KeywordID IN ($list))";
}*/
if ($eid) {
  $select .= ",MAX(UseDate) AS Last";
  $from .= " LEFT OUTER JOIN history ON song.SongID=history.SongID AND history.EventID=$eid";
  $groupby = "song.SongID,Title,Tagged,OrigTitle,Tempo,SongKey,FirstLine,Audio,Chords";
}
$href = $_SERVER['PHP_SELF'];

if ($title_search) {
  $text = "Songs whose title contains '".stripslashes($title_search)."' (ignoring punctuation)";
  $where .= ($where?" AND ":"")."Title LIKE '%".preg_replace('/[\W]+/u','%',$title_search)."%' OR OrigTitle LIKE '%".
    preg_replace('/[\W]+/u','%',$title_search)."%'";
  $href .= "?title_search=".stripslashes($title_search).($eid?"&eid=$eid":"")."&sort=";
} elseif ($lyrics_search) {
  $text = "Songs whose lyrics contain '".stripslashes($lyrics_search)."' (ignoring punctuation)";
  $where .= ($where?" AND ":"")."LOWER(stripchord(Lyrics)) LIKE '%".preg_replace('/[\W]+/u','%',$lyrics_search)."%'";
  $href .= "?lyrics_search=".stripslashes($lyrics_search).($eid?"&eid=$eid":"")."&sort=";
} elseif ($source_search) {
  $text = "Songs whose source contains '".stripslashes($source_search)."' (ignoring punctuation)";
  $where .= ($where?" AND ":"")."Source LIKE '%".preg_replace('/[\W]+/u','%',$source_search)."%'";
  $href .= "?source_search=".stripslashes($source_search).($eid?"&eid=$eid":"")."&sort=";
} elseif ($tempo_search) {
  $text = "Songs with ".$tempo_search." tempo";
  $where .= ($where?" AND ":"")."Tempo='".$tempo_search."'";
  $href .= "?tempo_search=".$tempo_search.($eid?"&eid=$eid":"")."&sort=";
} elseif ($kwid) {
  if (!$result = mysqli_query($db,"SELECT Keyword FROM keyword WHERE KeywordID = $kwid")) {
    echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b>");
    exit;
  }
  $kw = mysqli_fetch_object($result);
  $text = "Songs with Keyword '".$kw->Keyword."'";
  $where .= ($where?" AND ":"")."song.SongID IN (SELECT SongID FROM songkey WHERE KeywordID=$kwid)";
  $href .= "?kwid=".$kwid.($eid?"&eid=$eid":"")."&sort=";
} elseif ($where_search) {
  $where_search = stripslashes($where_search);
  $text = "Songs found \"".$where_search."\"";
  $where .= ($where?" AND ":"").$where_search;
  $href .= "?where_search=".$where_search.($eid?"&eid=$eid":"")."&sort=";
} elseif ($tagged) {
  $text = "Tagged Songs";
  $where .= ($where?" AND ":"")."Tagged=1";
  $href .= "?tagged=1".($eid?"&eid=$eid":"")."&sort=";
} else {
  $text = "List All Records";
  $href .= ($eid?"?eid=$eid&":"?")."sort=";
}
/* FILTERS */
if ($_SESSION['inkeys'] AND !($kwid AND strpos(",".$_SESSION['inkeys'].",",",".$kwid.",")!==FALSE)) { //only if keyword search doesn't overlap filter
  $where .= ($where?" AND ":"")."song.SongID IN (SELECT SongID FROM songkey WHERE KeywordID IN (".$_SESSION['inkeys']."))";
}
if ($_SESSION['exkeys']) {
  $where .= ($where?" AND ":"")."NOT song.SongID IN (SELECT SongID FROM songkey WHERE KeywordID IN (".$_SESSION['exkeys']."))";
}

/* PUT IT ALL TOGETHER */
$sql = "SELECT $select FROM $from".($where ? " WHERE $where":"").
($groupby ? " GROUP BY $groupby":"").($having ? " HAVING $having":"");
if ($sort and ($sort != "OrigTitle")) {
  $sql .= " ORDER BY $sort,OrigTitle";
} else {
  $sql .= " ORDER BY OrigTitle";
}

if (!$result = mysqli_query($db,$sql)) {
  echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>$sql");
  exit;
}
if (mysqli_num_rows($result) == 0) {
  header("Location: index.php?text=".urlencode("Search resulted in no records.".(($_SESSION['admin'] == 2)?"<br>".$sql:"")));
  exit;
} elseif (mysqli_num_rows($result) == 1) {
  $kw_song = mysqli_fetch_object($result);
  header("Location: song.php?sid=".$kw_song->SongID);
  exit;
}
print_header("Praise & Worship DB: ".$text,"#FFFFF0",1);
echo "<script language=\"Javascript\">\n";
echo "function all_check(status) {\n";
echo "  for (var i = 0; i < document.tagform.elements.length; i++) {\n";
echo "    var e = document.tagform.elements[i];\n";
echo "    if (e.type == 'checkbox') {\n";
echo "      e.checked = ((status=='true')?true:false);\n";
echo "    }\n";
echo "  }\n";
echo "}\n";
echo "</script>\n";
echo "<center><h2><font color=#A04040>".$text.": ".mysqli_num_rows($result)." Records</font></h2>";

echo "<form name=tagform action=\"do_tag.php\" method=post>\n";

echo "<table border=0 cellspacing=0 cellpadding=0 width=700><tr><td align=center valign=middle>";
echo "<input type=submit value=\"Update tags according to checkboxes below\"><br>&nbsp;<br>\n";
echo "<input type=button value=\"   Check All   \" onclick=\"all_check('true');\">&nbsp;&nbsp;&nbsp;\n";
echo "<input type=button value=\"  Uncheck All  \" onclick=\"all_check('false');\">\n";

echo "</td><td align=center valign=middle>Include last date used for:<br>\n";
if (!$events = mysqli_query($db,"SELECT * FROM event WHERE Active=1 ORDER BY Event")) {
  echo ("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b>");
  exit;
}
while ($event = mysqli_fetch_object($events)) {
  if ($event->EventID == $eid) {
    echo "<font color=#000000><b>".$event->Event." (currently displayed)</b></font><br>\n";
  } else {
    echo "<a href=\"".$href.$sort."&eid=".$event->EventID."\"><font color=#A04040><b>".
    $event->Event."</b></font></a><br>\n";
  }
}
echo "</td><td align=center valign=middle>";
echo "<input type=button value=\"Go to action page with\nthis list in this order\" ";
echo "onclick=\"location='multiselect.php?sid_list='+document.tagform.sid_list.value;\">\n";
echo "</td></tr></table><br>&nbsp;<br>\n";

echo "<div align=left><img src=\"graphics/audio.gif\" height=16 width=16>=Has Audio&nbsp;&nbsp;";
echo "<img src=\"graphics/guitar.gif\" height=16 width=16>=Has Chords&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
echo "<font color=#A04040 size=-1>(click on a table heading to sort by that field)</font><br>\n";
echo "<table border=1 cellspacing=0 cellpadding=2 bordercolor=#A04040 bgcolor=#FFFFFF>\n";
echo "<tr><td bgcolor=#FFF0D0 align=center><b>";
if ($sort == "Title") echo "<font color=#A04040>Title</font>";
else echo "<a href=\"".$href."Title\"><font color=#A04040>Title</font></a>";
echo "</b></td>\n<td bgcolor=#FFF0D0 align=center><b>";
if ($sort == "Tagged") echo "<font color=#A04040>Tag</font></b></td>";
else echo "<a href=\"".$href."Tagged\"><font color=#A04040>Tag</font></a>";
echo "</b></td>\n<td bgcolor=#FFF0D0 align=center><b>";
if ($sort == "OrigTitle") echo "<font color=#A04040>Original Title</font>";
else echo "<a href=\"".$href."OrigTitle\"><font color=#A04040>Original Title</font></a>";
echo "</b></td>\n<td bgcolor=#FFF0D0 align=center><b>";
if ($sort == "SongKey") echo "<font color=#A04040>Key</font>";
else echo "<a href=\"".$href."SongKey\"><font color=#A04040>Key</font></a>";
echo "</b></td>\n<td bgcolor=#FFF0D0 align=center><b>";
if ($sort == "Tempo") echo "<font color=#A04040>Tempo</font>";
else echo "<a href=\"".$href."Tempo\"><font color=#A04040>Tempo</font></a>";
echo "</b></td>\n<td bgcolor=#FFF0D0 align=center><b>";
if ($eid) {
  if ($sort == "Last") echo "<font color=#A04040>Last Used</font>";
  else echo "<a href=\"".$href."Last\"><font color=#A04040>Last Used</font></a>";
  echo "</b></td>\n<td bgcolor=#FFF0D0 align=center><b>";
}
if ($sort == "FirstLine") echo "<font color=#A04040>First Line</font>";
else echo "<a href=\"".$href."FirstLine\"><font color=#A04040>First Line</font></a>";
echo "</b></td>\n";

$sid_list = "";
while ($row = mysqli_fetch_object($result)) {
  echo "<tr><td nowrap><a href=\"song.php?sid=".$row->SongID."\">".$row->Title."</a>";
  if ($row->Audio == "1")  echo "&nbsp;<img src=\"graphics/audio.gif\" height=16 width=16>";
  if ($row->Chords)  echo "&nbsp;<img src=\"graphics/guitar.gif\" height=16 width=16>";
  echo "</td>\n";
  echo "<td nowrap align=center><input type=checkbox name=\"$row->SongID\"".
   ($row->Tagged ? " checked" : "")."></td>\n";
  echo "<td nowrap>".($row->OrigTitle ? $row->OrigTitle : "&nbsp;")."</td>\n";
  echo "<td nowrap align=center>".($row->SongKey ? $row->SongKey : "&nbsp;")."</td>\n";
  echo "<td nowrap align=center>".($row->Tempo ? $row->Tempo : "&nbsp;")."</td>\n";
  if ($eid) {
    echo "<td nowrap align=center>".($row->Last ? $row->Last : "(never)")."</td>\n";
  }
  echo "<td nowrap>".($row->FirstLine ? $row->FirstLine : "&nbsp;")."</td>\n";
  $sid_list .= ",".$row->SongID;   //  \[[^\[]*\]
}
echo "<input type=hidden name=\"sid_list\" value=\"".substr($sid_list,1)."\">";
echo "</tr></table></form></center>";
if ($_SESSION['userid']=="karen") echo "<p style=\"textsize:0.8em\">$sql</p>\n";

print_footer();
