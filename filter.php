<?php
include("functions.php");
include("accesscontrol.php");

if ($_POST['filter_submit']) {
  if (!$result = mysql_query("SELECT * FROM pw_keyword ORDER BY Keyword")) {
    echo("<b>SQL Error ".mysql_errno()." while getting keywords: ".mysql_error()."</b>");
    exit;
  }
  $in_list = "";
  $ex_list = "";
  while ($row = mysql_fetch_object($result)) {
    $kwid = $row->KeywordID;
    if ($_POST[$kwid] == "in") {
      $in_list .= ",".$kwid;
    } elseif ($_POST[$kwid] == "ex") {
      $ex_list .= ",".$kwid;
    }
  }
  $_SESSION['pw_inkeys'] = substr($in_list,1);
  $_SESSION['pw_exkeys'] = substr($ex_list,1);
  $sql = "UPDATE pw_login SET IncludeKeywords='".$_SESSION['pw_inkeys'].
  "', ExcludeKeywords='".$_SESSION['pw_exkeys']."' WHERE UserID='".$_SESSION['pw_userid']."'";
  if (!$result = mysql_query($sql)) {
    echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
    exit;
  }
  echo "<html><head></head><body onload=\"window.location = 'index.php';\"></body></html>\n";
  exit;
}
 
print_header("Praise & Worship DB: Search Filtering","#F0FFF0",1);
?>
<center>
  <h1><font color=#40A040>Search Filtering</font></h1>
  <h3>Modify filter criteria as desired, and click "Modify Search Filtering".</h3>
  <form name="filterform" action="<? echo $PHP_SELF; ?>" method="POST">
    <input type="submit" name="filter_submit" value="Modify Search Filtering"><br>&nbsp;<br>
    <table border=0 cellpadding=2 cellspacing=0 bgcolor="#FFFFFF"><tr>
    <td align=center valign=middle bgcolor="#D0DDD0">Keyword</td>
    <td align=center bgcolor="#D0DDD0">&nbsp;Filter&nbsp;<br>Off</td>
    <td align=center bgcolor="#D0DDD0">Must<br>&nbsp;Include&nbsp;</td>
    <td align=center bgcolor="#D0DDD0">&nbsp;Must Not&nbsp;<br>&nbsp;Include&nbsp;</td>
    <td width=30 bgcolor="#F0FFF0">&nbsp;</td>
    <td align=center valign=middle bgcolor="#D0DDD0">Keyword</td>
    <td align=center bgcolor="#D0DDD0">&nbsp;Filter&nbsp;<br>Off</td>
    <td align=center bgcolor="#D0DDD0">Must<br>&nbsp;Include&nbsp;</td>
    <td align=center bgcolor="#D0DDD0">&nbsp;Must Not&nbsp;<br>&nbsp;Include&nbsp;</td>
    </tr><tr>
<?
if (!$result = mysql_query("SELECT * FROM pw_keyword ORDER BY Keyword")) {
  echo("<b>SQL Error ".mysql_errno()." while getting keywords: ".mysql_error()."</b>");
  exit;
}
$column = 1;
while ($row = mysql_fetch_object($result)) {
  echo "      <td>".$row->Keyword.":&nbsp;</td>\n      <td align=center>";
  echo "<input type=\"radio\" name=\"".$row->KeywordID."\" value=\"\"";
  if (!ereg(",".$row->KeywordID.",",",".$_SESSION['pw_inkeys'].",".$_SESSION['pw_exkeys'].","))  echo " checked";
  echo "></td>\n      <td align=center>";
  echo "<input type=\"radio\" name=\"".$row->KeywordID."\" value=\"in\"";
  if (ereg(",".$row->KeywordID.",",",".$_SESSION['pw_inkeys'].","))  echo " checked";
  echo "></td>\n      <td align=center>";
  echo "<input type=\"radio\" name=\"".$row->KeywordID."\" value=\"ex\"";
  if (ereg(",".$row->KeywordID.",",",".$_SESSION['pw_exkeys'].","))  echo " checked";
  echo "></td>\n";
  if ($column == 1) {
    echo "      <td width=30 bgcolor=\"#F0FFF0\">&nbsp;</td>\n";
    $column = 2;
  } else {
    echo "      </tr><tr>\n";
    $column = 1;
  }    
}
?>
    </tr></table>
    <input type="submit" name="filter_submit" value="Modify Search Filtering">
  </form>
</center>
<? print_footer(); ?>