<?php
include("functions.php");
include("accesscontrol.php");
print_header("","#FFF0E0",0);

$sql = "SELECT DISTINCT SetName FROM pw_outputset ORDER BY SetName";
if (!$result = mysql_query($sql)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)<br>");
  exit;
}
?>
    <h3><font color="#8b4513">Select preset format from list and click the button...</font></h3>
    <form action="format.php" method="get" name="optionsform" target="_blank">
      <input type="hidden" name="sid_list" value="<? echo $sid_list; ?>" border="0">
      <table width="639" border="0" cellspacing="0" cellpadding="5">
        <tr>
          <td>Layout:<select name="outputset_name" size="1">
          <option value="">Select Format...</option>
          <? while ($row = mysql_fetch_object($result)) {
  echo  "                <option value=\"".$row->SetName."\">".$row->SetName."</option>\n";
}
?>
          </select><br>&nbsp;<br>
          <input type="checkbox" name="showchords" value="yes"> Include Chords&nbsp;&nbsp;&nbsp;&nbsp;
          <input type="checkbox" name="multilingual" value="yes" checked> Combine Multilingual Songs</td>
          <td><input type="submit" name="submit" value="Make Page to Copy or Print" border="0"></td>
        </tr>
      </table>
    </form>
<? print_footer();
?>