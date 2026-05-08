<?php
include("functions.php");
include("accesscontrol.php");

if (!empty($_POST['filter_submit'])) {
  $result = sqlquery_checked("SELECT * FROM tag ORDER BY Tag");
  $in_list = "";
  $ex_list = "";
  while ($row = mysqli_fetch_object($result)) {
    $tagid = $row->TagID;
    $choice = $_POST[$tagid] ?? '';
    if ($choice == "in") {
      $in_list .= ",".$tagid;
    } elseif ($choice == "ex") {
      $ex_list .= ",".$tagid;
    }
  }
  $_SESSION['intags'] = substr($in_list,1);
  $_SESSION['extags'] = substr($ex_list,1);
  $sql = "UPDATE user SET IncludeTags='".$_SESSION['intags'].
  "', ExcludeTags='".$_SESSION['extags']."' WHERE UserID='".$_SESSION['userid']."'";
  $result = sqlquery_checked($sql);
  echo "<html><head></head><body onload=\"window.location = 'index.php';\"></body></html>\n";
  exit;
}
 
print_header("Praise & Worship DB: Search Filtering","#F0FFF0",1);
?>
  <h1><?php echo _('Search Filtering'); ?></h1>
  <h3><?php echo sprintf(_('Modify filter criteria as desired, and click "%s".'), _('Modify Search Filtering')); ?></h3>
  <form name="filterform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
    <input type="submit" name="filter_submit" value="<?php echo _('Modify Search Filtering'); ?>"><br>&nbsp;<br>
    <table border=0 cellpadding=2 cellspacing=0 bgcolor="#FFFFFF"><tr>
    <td align=center valign=middle bgcolor="#D0DDD0"><?php echo _('Tag'); ?></td>
    <td align=center bgcolor="#D0DDD0"><?php echo _('&nbsp;Filter&nbsp;<br>Off'); ?></td>
    <td align=center bgcolor="#D0DDD0"><?php echo _('Must<br>&nbsp;Include&nbsp;'); ?></td>
    <td align=center bgcolor="#D0DDD0"><?php echo _('&nbsp;Must Not&nbsp;<br>&nbsp;Include&nbsp;'); ?></td>
    <td width=30 bgcolor="#F0FFF0">&nbsp;</td>
    <td align=center valign=middle bgcolor="#D0DDD0"><?php echo _('Tag'); ?></td>
    <td align=center bgcolor="#D0DDD0"><?php echo _('&nbsp;Filter&nbsp;<br>Off'); ?></td>
    <td align=center bgcolor="#D0DDD0"><?php echo _('Must<br>&nbsp;Include&nbsp;'); ?></td>
    <td align=center bgcolor="#D0DDD0"><?php echo _('&nbsp;Must Not&nbsp;<br>&nbsp;Include&nbsp;'); ?></td>
    </tr><tr>
<?php
$result = sqlquery_checked("SELECT * FROM tag ORDER BY Tag");
$column = 1;
while ($row = mysqli_fetch_object($result)) {
  echo "      <td>".$row->Tag.":&nbsp;</td>\n      <td align=center>";
  echo "<input type=\"radio\" name=\"".$row->TagID."\" value=\"\"";
  if (strpos(",".$_SESSION['intags'].",".$_SESSION['extags'].",",",".$row->TagID.",")===FALSE)  echo " checked";
  echo "></td>\n      <td align=center>";
  echo "<input type=\"radio\" name=\"".$row->TagID."\" value=\"in\"";
  if (strpos(",".$_SESSION['intags'].",",",".$row->TagID.",")!==FALSE)  echo " checked";
  echo "></td>\n      <td align=center>";
  echo "<input type=\"radio\" name=\"".$row->TagID."\" value=\"ex\"";
  if (strpos(",".$_SESSION['extags'].",",",".$row->TagID.",")!==FALSE)  echo " checked";
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
    <input type="submit" name="filter_submit" value="<?php echo _('Modify Search Filtering'); ?>">
  </form>
<?php print_footer(); ?>