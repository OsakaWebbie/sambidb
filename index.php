<?php
include("functions.php");
include("accesscontrol.php");

if ($cleartags) {
  if (!$result = mysql_query("UPDATE pw_song SET Tagged=0 WHERE Tagged=1")) {
    echo("<b>SQL Error ".mysql_errno()." while untagging all songs: ".mysql_error()."</b>");
    exit;
  }
  $num_tagged = 0;
} else {
  if (!$result = mysql_query("SELECT count(*) AS Num FROM pw_song WHERE Tagged=1")) {
    echo("<b>SQL Error ".mysql_errno()." while counting tagged songs: ".mysql_error()."</b>");
    exit;
  }
  $row = mysql_fetch_object($result);
  $num_tagged = $row->Num;
}

print_header("Praise & Worship DB Menu Page","#F0FFF0",1); ?>
<center>
  <h1><font color=#40A040>Praise & Worship Database</font></h1>
  <font color=#308030><b><i>Welcome, <? echo $_SESSION['pw_username']; ?>!</i></b>
<? if ($_SESSION['pw_admin'] == 0)  echo "<br><font size=-2>Your user status is \"read-only\", so you cannot edit the data.  If you need editing privileges, contact the system administrator.</font>";
?>
</font><br />&nbsp;<br />
<font color=A03030 size=-1><b>Note: This database is only a tool for convenience; existence of copyrighted material (lyrics, recordings, etc.) in it does not give you permission to use that material in ways that violate the copyright.  Users of the material bear their own responsibility for how they use the information stored here.</font><br />&nbsp;<br />
  <? if ($text) echo "<h3><font color=red>$text</font></h3>"; ?>
  <table border=0 cellspacing=0 cellpadding=0><tr><td>
  <table border=1 cellspacing=0 cellpadding=5 bordercolor=#80A080>
    <tr>
      <td>
        <form action="list.php" method=GET>
          Song Name Search: <input type=text name="title_search" length=20>&nbsp;&nbsp;&nbsp; <input type=submit name=search value="Search!">
        </form>
      </td>
    </tr>
    <tr>
      <td>
        <form action="list.php" method=GET>
          Lyrics Search: <input type=text name="lyrics_search" length=20>&nbsp;&nbsp;&nbsp; <input type=submit name=search value="Search!">
        </form>
      </td>
    </tr>
    <tr>
      <td>
        <form action="list.php" method=GET>
          Source Search: <input type=text name="source_search" length=20>&nbsp;&nbsp;&nbsp; <input type=submit name=search value="Search!">
        </form>
      </td>
    </tr>
  </table>
  </td><td>&nbsp;&nbsp;&nbsp;</td><td>
  <table border=1 cellspacing=0 cellpadding=10 bordercolor=#80A080>
    <tr>
      <td>
        <form name="lform" action="list.php" method=GET>
          <p>Keyword: <select size="1" name="kwid" onchange="document.forms.lform.submit();">
              <? // Build option list from pw_keyword table contents
if (!$result = mysql_query("SELECT * FROM pw_keyword ORDER BY Keyword")) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b>");
} else {
  echo "      <option value=\"\">Select a keyword...</option>";
  while ($row = mysql_fetch_object($result)) {
    if (!ereg(",".$row->KeywordID.",", ",".$_SESSION['pw_inkeys'].",") && !ereg(",".$row->KeywordID.",", ",".$_SESSION['pw_exkeys'].",")) {
      echo "      <option value=$row->KeywordID>$row->Keyword</option>";
    }
    $keyword[$row->KeywordID] = $row->Keyword;
  }
}
?>
            </select></p>
        </form>
      </td>
    </tr>
    <tr>
      <td>
        <form name="tform" action="list.php" method=GET>
          <p>Tempo: <select size="1" name="tempo_search" onchange="document.forms.tform.submit();">
            <option value=\"\">Select tempo...</option>";
            <option value="Fast">Fast</option>
            <option value="Medium">Medium</option>
            <option value="Slow">Slow</option>
            </select></p>
        </form>
      </td>
    </tr>
  </table>
  </table>
  <br>
<?
if ($_SESSION['pw_admin'] == 2) {
  echo "  <table border=1 cellspacing=0 cellpadding=5 bordercolor=#80A080>\n";
  echo "    <tr>\n      <td align=center>        <form name=\"wform\" action=\"list.php\" method=GET>\n";
  echo "          <p>Freeform SQL: SELECT this stuff FROM wherever WHERE...<br>\n";
  echo "          <textarea name=\"where_search\" rows=3 cols=60></textarea><br>\n";
  echo "          <input type=submit name=search value=\"Search!\"></p>\n";
  echo "        </form>\n      </td>\n    </tr>\n  </table>\n  <br>\n";
}

echo "    <b><i>Currently there ";
if ($num_tagged == 0) echo "are no tagged songs.</i></b>\n";
elseif ($num_tagged == 1) echo "is 1 tagged song.</i></b>\n";
else echo "are $num_tagged tagged songs.</i></b>\n";

if ($num_tagged > 0) {
  echo "    <h3><a href=\"list.php?tagged=1\">List Tagged Songs</a> &nbsp; &nbsp; &nbsp; ";
  echo "<a href=\"index.php?cleartags=1\">Clear All Tags</a></h3>\n";
}

echo "<hr><table border=0 cellspacing=0 cellpadding=0><tr><td valign=middle>\n";
if (!$_SESSION['pw_inkeys'] & !$_SESSION['pw_exkeys']) {
  echo "<b><i>You are not currently filtering data<br>(you are seeing all records).<i><b>";
} else {
  echo "<b><i>You are currently filtering to see only songs whose keywords...<br>";
  if ($_SESSION['pw_inkeys']) {
    $txt = "";
    $key_array=split(",",$_SESSION['pw_inkeys']);
    while (list($dummy,$kwid) = each($key_array)) {
      $txt .= ", ".$keyword[$kwid];
    }
    echo "<font color=green>Include: ".substr($txt,2)."</font><br>\n";
  }
  if ($_SESSION['pw_exkeys']) {
    $txt = "";
    $key_array=split(",",$_SESSION['pw_exkeys']);
    while (list($dummy,$kwid) = each($key_array)) {
      $txt .= ", ".$keyword[$kwid];
    }
    echo "<font color=red>Do not include: ".substr($txt,2)."</font><br>\n";
  }
}
echo "</i></b></td>\n<td width=30>&nbsp;</td>\n";
echo "<td valign=middle><h3><a href=\"filter.php\">Modify filter criteria</a></h3></td></tr></table>\n";
echo "</center>\n";
print_footer();
?>
