<?php
include("functions.php");
include("accesscontrol.php");
print_header("","#E8FFE0",0);

if ($save_kw) {
  if ($kwid == "new") {  //need to insert the new keyword record first
    $sql = "INSERT INTO pw_keyword (Keyword) VALUES ('$keyword')";
    if (!$result = mysql_query($sql)) {
      echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
      exit;
    }
    if (mysql_affected_rows() > 0) {
      $kwid = mysql_insert_id();
      echo "<h3><font color=\"#449933\">New keyword successfully added.</font></h3>";
    } else {
      echo "No keyword record was inserted for some reason.<br>";
      exit;
    }
  }

  $sid_array = split(",",$sid_list);
  $num_sids = count($sid_array);
  $num_previous = 0;
  for ($i=0; $i<$num_sids; $i++) {
    $sql = "SELECT * FROM pw_songkey WHERE SongID=".$sid_array[$i]." AND KeywordID=$kwid";
    if (!$result = mysql_query($sql)) {
      echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
      exit;
    }
    if (mysql_num_rows($result) == 1) {
      $num_previous++;
    } else {
      $sql = "INSERT INTO pw_songkey (SongID,KeywordID) VALUES (".
           $sid_array[$i].",$kwid)";
      if (!$result = mysql_query($sql)) {
        echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
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
    <form action="<? echo $PHP_SELF; ?>" method="post" name="kwform" target="_self" onsubmit="return validate();">
      <input type="hidden" name="sid_list" value="<? echo $sid_list; ?>" border="0">
      <table border="1" cellspacing="0" cellpadding="4">
        <tr>
          <td nowrap>Keyword:
            <select name="kwid" size="1">
              <option value="" selected>Select a keyword...</option>
              <option value="new">New Keyword (input name)</option>
<?
$sql = "SELECT * FROM pw_keyword ORDER BY Keyword";
if (!$result = mysql_query($sql)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)<br>");
  exit;
}
while ($row = mysql_fetch_object($result)) {
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
<? print_footer();
?>
