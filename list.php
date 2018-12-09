<?php
include("functions.php");
include("accesscontrol.php");

$criterialist = "<ul id=\"criteria\">";
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
$eid = $_GET['eid'];
if (!empty($eid)) {
  $select .= ",MAX(UseDate) AS Last";
  $from .= " LEFT OUTER JOIN history ON song.SongID=history.SongID AND history.EventID=$eid";
  $groupby = "song.SongID,Title,Tagged,OrigTitle,Tempo,SongKey,FirstLine,Audio,Chords";
}
$href = $_SERVER['PHP_SELF'];
$where = '';

if (!empty($title)) {
  $text = "Songs whose title contains '".stripslashes($title)."' (ignoring punctuation)";
  $where .= ($where?" AND ":"")."Title LIKE '%".preg_replace('/[\W]+/u','%',$title)."%' OR OrigTitle LIKE '%".
    preg_replace('/[\W]+/u','%',$title)."%'";
  $href .= "?title=".stripslashes($title).($eid?"&eid=$eid":"")."&sort=";
} elseif ($_GET['lyrics']) {
  $text = "Songs whose lyrics contain '".stripslashes($lyrics)."' (ignoring punctuation)";
  $where .= ($where?" AND ":"")."LOWER(stripchord(Lyrics)) LIKE '%".preg_replace('/[\W]+/u','%',$lyrics)."%'";
  $href .= "?lyrics=".stripslashes($_GET['lyrics']).($eid?"&eid=$eid":"")."&sort=";
} elseif ($_GET['source']) {
  $text = "Songs whose source contains '".stripslashes($_GET['source'])."' (ignoring punctuation)";
  $where .= ($where?" AND ":"")."Source LIKE '%".preg_replace('/[\W]+/u','%',$_GET['source'])."%'";
  $href .= "?source=".stripslashes($_GET['source']).($eid?"&eid=$eid":"")."&sort=";
} elseif ($_GET['tempo']) {
  $text = "Songs with ".$_GET['tempo']." tempo";
  $where .= ($where?" AND ":"")."Tempo='".$_GET['tempo']."'";
  $href .= "?tempo_search=".$_GET['tempo'].($eid?"&eid=$eid":"")."&sort=";
} elseif ($_GET['kwid']) {
  if (!$result = mysqli_query($db,"SELECT Keyword FROM keyword WHERE KeywordID = $kwid")) {
    echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b>");
    exit;
  }
  $kw = mysqli_fetch_object($result);
  $text = "Songs with Keyword '".$kw->Keyword."'";
  $where .= ($where?" AND ":"")."song.SongID IN (SELECT SongID FROM songkey WHERE KeywordID=$kwid)";
  $href .= "?kwid=".$kwid.($eid?"&eid=$eid":"")."&sort=";
} elseif ($where) {
  $where_search = stripslashes($where);
  $text = "Songs found \"".$where."\"";
  $where .= ($where?" AND ":"").$where;
  $href .= "?freeform=".$where.($eid?"&eid=$eid":"")."&sort=";
} elseif ($_GET['tagged']) {
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
(!empty($groupby) ? " GROUP BY $groupby":"").(!empty($having) ? " HAVING $having":"");
if (!empty($_GET['sort']) and ($_GET['sort'] != "OrigTitle")) {
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

//build array with: class, whether sortable, type of data for sort
$cols[] = array("songid",1,"digit");
$cols[] = array("title",1,"text");
$cols[] = array("tagged",1,"text");
$cols[] = array("origtitle",1,"text");
$cols[] = array("songkey",1,"text");
$cols[] = array("tempo",1,"text");
$cols[] = array("lastusage",1,"isoDate");
$cols[] = array("numusage",1,"digit");
$cols[] = array("firstline",1,"text");
$cols[] = array("audio",1,"text");
$cols[] = array("categories",1,"text");
$cols[] = array("selectcol",0,"");
$colsHidden = $hideInList = "";
foreach($cols as $i=>$col) {
  if ($col[1]==0) $hideInList .= ",".($i+1);
  elseif (stripos(",".$_SESSION['list_showcols'].",",",".$col[0].",") === FALSE)  $colsHidden .= ",".($i+1);
}
$hideInList = substr($hideInList,1);  //to remove the leading comma
$colsHidden = substr($colsHidden,1);  //to remove the leading comma

header1(_("Search Results"));
header2(1);
echo "<h3>".sprintf(_("%d results of these criteria:"),mysqli_num_rows($result))."</h3>\n";
echo $criterialist;
?>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/cupertino/jquery-ui.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.16/css/dataTables.jqueryui.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.1/css/responsive.dataTables.min.css">

<label>Show history data for: </label>
<select id="event" name="event">
<?php
$events = sqlquery_checked('SELECT * FROM event WHERE Active=1 ORDER BY '.
(isset($_SESSION['default_event'])?'IF (EventID='.$_SESSION['default_event'].',0,1), ':'').'Event');
while ($ev = mysqli_fetch_object($events)) {
  echo "  <option value='".$ev->EventID."'>".$ev->Event."</option>\n";
}
?>
</select>

<input type=button value="Go to action page with\nthis list in this order"
       onclick="location='multiselect.php?sid_list='+document.tagform.sid_list.value;">

<form name="tagform" action="do_tag.php" method="post">
  <input type="submit" value="Update tags according to checkboxes below">
  <input type="button" value="   Check All   " onclick="all_check('true');">
  <input type="button" value="  Uncheck All  " onclick="all_check('false');">

  <table id="songlist">
  <thead>
  <tr>
    <th><?=_('Title')?></th>
    <th><?=_('Tagged')?></th>
    <th><?=_('Original Title')?></th>
    <th><?=_('Key')?></th>
    <th><?=_('Tempo')?></th>
    <th><?=_('Last Use')?></th>
    <th><?=_('# Uses')?></th>
    <th><?=_('First Line')?></th>
    <th><?=_('Audio')?></th>
    <th><?=_('Composer')?></th>
    <th><?=_('Copyright')?></th>
    <th><?=_('Source')?></th>
  </tr>
  </thead>
  <tbody>
<?php
//echo "<center><h2><font color=#A04040>".$text.": ".mysqli_num_rows($result)." Records</font></h2>";


/*if (!$events = mysqli_query($db,"SELECT * FROM event WHERE Active=1 ORDER BY Event")) {
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
}*/



echo "<div align=left><img src=\"graphics/audio.gif\" height=16 width=16>=Has Audio&nbsp;&nbsp;";
echo "<img src=\"graphics/guitar.gif\" height=16 width=16>=Has Chords&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
echo "<font color=#A04040 size=-1>(click on a table heading to sort by that field)</font><br>\n";
/*echo "<table id='songlist'>\n";
echo "<tr><td bgcolor=#FFF0D0 align=center><b>";
echo "<font color=#A04040>Title</font>";
echo "</b></td>\n<td bgcolor=#FFF0D0 align=center><b>";
echo "<font color=#A04040>Tag</font></b></td>";
echo "</b></td>\n<td bgcolor=#FFF0D0 align=center><b>";
echo "<font color=#A04040>Original Title</font>";
echo "</b></td>\n<td bgcolor=#FFF0D0 align=center><b>";
echo "<font color=#A04040>Key</font>";
echo "</b></td>\n<td bgcolor=#FFF0D0 align=center><b>";
echo "<font color=#A04040>Tempo</font>";
echo "</b></td>\n<td bgcolor=#FFF0D0 align=center><b>";
if ($eid) {
  echo "<font color=#A04040>Last Used</font>";
  echo "</b></td>\n<td bgcolor=#FFF0D0 align=center><b>";
}
echo "<font color=#A04040>First Line</font>";
echo "</b></td>\n";*/

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
echo "</tr></tbody></table></form>";
if ($_SESSION['userid']=="dev") echo "<p style=\"font-size:0.8em\">$sql</p>\n";

?>
<script src="https://code.jquery.com/jquery-3.2.1.min.js" type="text/javascript"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" type="text/javascript"></script>
<script src="js/jquery.ui.touch-punch.min.js" type="text/javascript"></script>
<script src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.16/js/dataTables.jqueryui.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/plug-ins/1.10.16/i18n/Japanese.json"></script>

<script type="text/javascript">
  $(document).ready(function() {
    $('#songlist').dataTable( {
      "language": {
        "url": "dataTables.japanese.lang"
      }
    } );
  } );

function all_check(status) {
  for (var i = 0; i < document.tagform.elements.length; i++) {
    var e = document.tagform.elements[i];
    if (e.type == 'checkbox') {
      e.checked = (status==='true');
    }
  }
}

</script>
<?php print_footer(); ?>
