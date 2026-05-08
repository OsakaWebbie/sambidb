<?php
include("functions.php");
include("accesscontrol.php");
print_header("","#E8FFE0",0);

if (!empty($_POST['save_tag'])) {
  if ($tagid == "new") {  //need to insert the new tag record first
    $sql = "INSERT INTO tag (Tag) VALUES ('".mysqli_real_escape_string($db, $tag)."')";
    if (!$result = mysqli_query($db,$sql)) {
      echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
      exit;
    }
    if (mysqli_affected_rows($db) > 0) {
      $tagid = mysqli_insert_id($db);
      echo "<h3>"._('New tag successfully added.')."</h3>";
    } else {
      echo _('The tag was not added for some reason.')."<br>";
      exit;
    }
  }

  $sid_array = explode(",",$sid_list);
  $num_sids = count($sid_array);
  $num_previous = 0;
  for ($i=0; $i<$num_sids; $i++) {
    $sql = "SELECT * FROM songtag WHERE SongID=".intval($sid_array[$i])." AND TagID=".intval($tagid);
    if (!$result = mysqli_query($db,$sql)) {
      echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
      exit;
    }
    if (mysqli_num_rows($result) == 1) {
      $num_previous++;
    } else {
      $sql = "INSERT INTO songtag (SongID,TagID) VALUES (".
           intval($sid_array[$i]).",".intval($tagid).")";
      if (!$result = mysqli_query($db,$sql)) {
        echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
        exit;
      }
    }
  }
  echo "<h3>".sprintf(_('Tag added to %s songs.'), ($num_sids - $num_previous));
  if ($num_previous > 0) {
    echo "<br>&nbsp; ".sprintf(_('(%s songs in this list already included this tag.)'), $num_previous);
  }
  echo "</h3>";
  exit;
}

?>

<SCRIPT language=Javascript>
function validate() {
//If new tag, make sure name is not blank
  if (document.tagform.tagid.value == "new" && document.tagform.tag.value == "") {
    alert("<?=_('Tag name cannot be blank.')?>");
    document.tagform.tag.focus();
    return false;
  } else {
    return true;
  }
}
</SCRIPT>

  <div align="center">
    <h3><?=_('Choose existing tag, or choose New and fill in new tag name:')?></h3>
    <form action="<?=$_SERVER['PHP_SELF']?>" method="post" name="tagform" target="_self" onsubmit="return validate();">
      <input type="hidden" name="sid_list" value="<?=$sid_list?>" border="0">
      <table border="1" cellspacing="0" cellpadding="4">
        <tr>
          <td nowrap><?=_('Tag')?>:
            <select name="tagid" size="1">
              <option value="" selected><?=_('Select a tag...')?></option>
              <option value="new"><?=_('New Tag (input name)')?></option>
<?php
$sql = "SELECT * FROM tag ORDER BY Tag";
if (!$result = mysqli_query($db,$sql)) {
  echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)<br>");
  exit;
}
while ($row = mysqli_fetch_object($result)) {
  echo "              <option value=\"".$row->TagID."\">$row->Tag</option>\n";
}
?>
            </select><br>&nbsp;<br>
            <?=_('New Tag Name:')?> <input type="text" name="tag" size="35"
            maxlength="50" border="0">
          </td>
          <td align="center" valign="middle" nowrap>
          <input type="submit" name="save_tag" value="<?=_('Add This Tag to These Songs')?>" border="0"></td>
        </tr>
      </table>
    </form>
  </div>
<?php print_footer();
?>
