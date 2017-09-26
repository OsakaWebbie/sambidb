<?php
include("functions.php");
include("accesscontrol.php");

print_header("Action on Tagged Songs","#E0FFE0",1);

if (!$sid_list) {
  if (!$result = mysqli_query($db,"SELECT count(*) AS Num FROM song WHERE Tagged=1")) {
    echo("<b>SQL Error ".mysqli_errno($db)." while counting tagged songs: ".mysqli_error($db)."</b>");
    exit;
  }
  $row = mysqli_fetch_object($result);
  if ($row->Num == 0) {
    echo "&nbsp;<br><b>There are no tagged songs.  Please use the tools on the Top(Search) page to
    select songs for tagging, then use this page to put them in order and/or take actions.</b><br>";
    exit;
  }
}
?>
<script language="JavaScript" src="sort.js"></script>

<script language="JavaScript">
function make_list() {
  var f = document.sform;
  f.sid_list.value = "";
  for (var index = 0; index < f.tagged.length; index++) {
    if (f.sid_list.value == "") {
      f.sid_list.value = f.tagged[index].value;
    } else {
      f.sid_list.value = f.sid_list.value + "," + f.tagged[index].value;
    }
  }
}
</script>

&nbsp;<br><b>Reorder the songs as desired, by selecting one or more (hold down Ctrl to select
multiples) and then choosing a move button (or if using Internet Explorer, you can
also use your mouse wheel to move the selected songs).  Then choose an action from
the buttons on the right.</b><br>
<form action="ms_copysongs.php" method="get" name="sform" target="ActionFrame"
onsubmit="make_list();">
  <input type="hidden" name="sid_list" value="" border="0">
  <table border="0" cellspacing="0" cellpadding="8"><tr>
    <td valign="top" align="center">
      <table border="0" cellspacing="0" cellpadding="5" bgcolor="white"><tr><td>
        <select name="tagged[]" size="16" multiple="multiple" id="tagged"
        onmousewheel="mousewheel(this);">
<?php
//get list of songs from database and fill select box
if ($sid_list) {
  $sql = "SELECT * FROM song WHERE SongID In (".$sid_list.") ORDER BY FIND_IN_SET(SongID,'".$sid_list."')";
} else {
  $sql = "SELECT * FROM song WHERE tagged=1 ORDER BY OrigTitle";
}
if (!$result = mysqli_query($db,$sql)) {
  echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b> ($sql)");
  exit;
}
while ($song = mysqli_fetch_object($result)) {
  $txt = "[".$song->SongKey."][".$song->Tempo."] ".$song->Title;
  if (ereg_replace("^[[:digit:]]{3}: ","",$song->Title) != $song->OrigTitle) $txt .= " (".$song->OrigTitle.")";
  echo "          <option value=\"$song->SongID\">$txt</option>\n";
}
?>
        </select>
      </td></tr></table>
      Operations on selected songs:<br>
      <input type="button" value="Move Up" onclick="up('tagged');" title="Move up" />
      <input type="button" value="Move Down" onclick="down('tagged');" title="Move down" />
      <input type="button" value="Clone" onclick="clone('tagged');" title="Clone" />
      <input type="button" value="Remove" onclick="remove('tagged');" title="Remove" />
    </td>
    <td valign="top" align="center">
      <table width="180" border="1" cellspacing="0" cellpadding="2" bgcolor="#006400">
        <tr><td align="center"><font color="white"><b>Choose an Action</b></font></td></tr>
        <tr><td align="center"><input type="submit" name="ms_text" value="Output Songs (text)"
        border="0" onclick="document.sform.action='ms_text.php';"></td></tr>
        <tr><td align="center"><input type="submit" name="ms_format" value="Output Songs (PDF)"
        border="0" onclick="document.sform.action='ms_pdf.php';"></td></tr>
        <tr><td align="center"><input type="submit" name="ms_history" value="Record As Event Song Session"
        border="0" onclick="document.sform.action='ms_history.php';"<?php if ($_SESSION['admin']==0) echo " disabled"; ?>></td></tr>
        <tr><td align="center"><input type="submit" name="ms_keyword" value="Add a Keyword"
        border="0" onclick="document.sform.action='ms_keyword.php';"<?php if ($_SESSION['admin']==0) echo " disabled"; ?>></td></tr>
        <tr><td align="center"><input type="submit" name="ms_format" value="[Output Songs (old)]"
        border="0" onclick="document.sform.action='ms_format.php';"></td></tr>
      </table>
      &nbsp;<br>
    </td></tr>
  </table>
</form>
  <?php print_footer();
?>
</body>