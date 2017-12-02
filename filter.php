<?php
include("functions.php");
include("accesscontrol.php");

if ($_POST['filter_submit']) {
  $result = sqlquery_checked("SELECT * FROM keyword ORDER BY Keyword");
  $in_list = "";
  $ex_list = "";
  while ($row = mysqli_fetch_object($result)) {
    $kwid = $row->KeywordID;
    if ($_POST[$kwid] == "in") {
      $in_list .= ",".$kwid;
    } elseif ($_POST[$kwid] == "ex") {
      $ex_list .= ",".$kwid;
    }
  }
  $_SESSION['inkeys'] = substr($in_list,1);
  $_SESSION['exkeys'] = substr($ex_list,1);
  $sql = "UPDATE user SET IncludeKeywords='".$_SESSION['inkeys'].
  "', ExcludeKeywords='".$_SESSION['exkeys']."' WHERE UserID='".$_SESSION['userid']."'";
  $result = sqlquery_checked($sql);
  echo "<html><head></head><body onload=\"window.location = 'index.php';\"></body></html>\n";
  exit;
}
 
print_header("Praise & Worship DB: Search Filtering","#F0FFF0",1);
?>
  <h1>Search Filtering</h1>
  <h3>Modify filter criteria as desired, and click "Modify Search Filtering".</h3>
  <form name="filterform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
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
<?php
$result = sqlquery_checked("SELECT * FROM keyword ORDER BY Keyword");
$column = 1;
while ($row = mysqli_fetch_object($result)) {
  echo "      <td>".$row->Keyword.":&nbsp;</td>\n      <td align=center>";
  echo "<input type=\"radio\" name=\"".$row->KeywordID."\" value=\"\"";
  if (strpos(",".$_SESSION['inkeys'].",".$_SESSION['exkeys'].",",",".$row->KeywordID.",")===FALSE)  echo " checked";
  echo "></td>\n      <td align=center>";
  echo "<input type=\"radio\" name=\"".$row->KeywordID."\" value=\"in\"";
  if (strpos(",".$_SESSION['inkeys'].",",",".$row->KeywordID.",")!==FALSE)  echo " checked";
  echo "></td>\n      <td align=center>";
  echo "<input type=\"radio\" name=\"".$row->KeywordID."\" value=\"ex\"";
  if (strpos(",".$_SESSION['exkeys'].",",",".$row->KeywordID.",")!==FALSE)  echo " checked";
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
<?php print_footer(); ?>