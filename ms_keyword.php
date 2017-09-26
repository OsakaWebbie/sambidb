<?php
include("functions.php");
include("accesscontrol.php");
print_header("","#E8FFE0",0);

if ($save_kw) {
  if ($kwid == "new") {  //need to insert the new keyword record first
    $sql = "INSERT INTO keyword (Keyword) VALUES ('$keyword')";
    if (!$result = mysqli_query($db,$sql)) {
      echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
      exit;
    }
    if (mysqli_affected_rows($db) > 0) {
      $kwid = mysqli_insert_id($db);
      echo "<h3><font color=\"#449933\">New keyword successfully added.</font></h3>";
    } else {
      echo "No keyword record was inserted for some reason.<br>";
      exit;
    }
  }

  $sid_array = explode(",",$sid_list);
  $num_sids = count($sid_array);
  $num_previous = 0;
  for ($i=0; $i<$num_sids; $i++) {
    $sql = "SELECT * FROM songkey WHERE SongID=".$sid_array[$i]." AND KeywordID=$kwid";
    if (!$result = mysqli_query($db,$sql)) {
      echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
      exit;
    }
    if (mysqli_num_rows($result) == 1) {
      $num_previous++;
    } else {
      $sql = "INSERT INTO songkey (SongID,KeywordID) VALUES (".
           $sid_array[$i].",$kwid)";
      if (!$result = mysqli_query($db,$sql)) {
        echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
        exit;
      }
    }
  }
  echo "<h3><font color=\"#449933\">".($num_sids - $num_previous)." new records successfully added.";
  if ($num_previous > 0) {
    echo "<br>&nbsp; ($num_previous songs in this list already included this keyword.)";
  }
  echo "</font></h3>";
  exit;
}

?>

<SCRIPT language=Javascript>
function validate() {
//If new keyword, make sure name is not blank
  if (document.kwform.kwid.value == "new" && document.kwform.keyword.value == "") {
    alert("You need to specify a name for the new keyword.");
    document.kwform.keyword.focus();
    return false;
  } else {
    return true;
  }
}
</SCRIPT>

  <div align="center">
    <font color="#449933" size=4><b>Choose existing keyword,
        or choose New and fill in new keyword name:</b></font>
    <form action="<?php echo $PHP_SELF; ?>" method="post" name="kwform" target="_self" onsubmit="return validate();">
      <input type="hidden" name="sid_list" value="<?php echo $sid_list; ?>" border="0">
      <table border="1" cellspacing="0" cellpadding="4">
        <tr>
          <td nowrap>Keyword:
            <select name="kwid" size="1">
              <option value="" selected>Select a keyword...</option>
              <option value="new">New Keyword (input name)</option>
<?php
$sql = "SELECT * FROM keyword ORDER BY Keyword";
if (!$result = mysqli_query($db,$sql)) {
  echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)<br>");
  exit;
}
while ($row = mysqli_fetch_object($result)) {
  echo "              <option value=\"".$row->KeywordID."\">$row->Keyword</option>\n";
}
?>
            </select><br>&nbsp;<br>
            New Keyword Name: <input type="text" name="keyword" size="35"
            maxlength="50" border="0">
          </td>
          <td align="center" valign="middle" nowrap>
          <input type="submit" name="save_kw" value="Add This Keyword to These Songs" border="0"></td>
        </tr>
      </table>
    </form>
  </div>
<?php print_footer();
?>
