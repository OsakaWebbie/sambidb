<?php
include("functions.php");
include("accesscontrol.php");

print_header("Maintenance Page","#FFFFFF",1);
?>

<script language="JavaScript">

//indexes for event array (just to keep sane)
var eid = 0;
var event = 1;
var active = 2;
var rem = 3;

<?php
//get data from event table and build master array for event management section
$sql = "SELECT * FROM event ORDER BY Event";
if (!$result = mysqli_query($db,$sql)) {
  echo("</script><b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)<br>");
  exit;
}
echo "var ar = new Array();\n";
$ar_index = 0;
  while ($row = mysqli_fetch_object($result)) {
  echo "ar[$ar_index] = new Array();\n";
  echo "ar[$ar_index][eid] = \"$row->EventID\";\n";
  echo "ar[$ar_index][event] = \"".escape_quotes($row->Event)."\";\n";
  echo "ar[$ar_index][active] = \"$row->Active\";\n";
  echo "ar[$ar_index][rem] = \"".escape_quotes($row->Remarks)."\";\n";
  $ar_index++;
}
?>

function fill_keyword() {
  var kw = document.kwform.kw_select;
  document.kwform.keyword.value=kw.options[kw.selectedIndex].text;
  if (kw.options[kw.selectedIndex].value == "new") {
    document.kwform.kw_del.disabled=true;
  } else {
    document.kwform.kw_del.disabled=false;
  }
}

function fill_event() {
  var el = document.eventform.event_list;
  
  if (el.options[el.selectedIndex].value == "new") {
    document.eventform.event_id.value = "";
    document.eventform.event.value = "";
    document.eventform.active.checked = true;
    document.eventform.remarks.value = "";
    document.eventform.event_del.disabled = true;
  } else {
    document.eventform.event_id.value = ar[el.options[el.selectedIndex].value][eid];
    document.eventform.event.value = ar[el.options[el.selectedIndex].value][event];
    if (ar[el.options[el.selectedIndex].value][active] == "1") {
      document.eventform.active.checked = true;
    } else {
      document.eventform.active.checked = false;
    }
    document.eventform.remarks.value = ar[el.options[el.selectedIndex].value][rem];
    document.eventform.event_del.disabled = false;
  }
}

</script>

<center>&nbsp;<br>
    <table width="661" border="0" cellspacing="5" cellpadding="5">
<?php if ($_SESSION['admin'] > 0) { ?>
      <tr>
        <td bgcolor="#cfe2ec" width="224">
          <div align="center">
            <h3><font color="#2b6482">Keyword Management</font></h3>
          </div>
          <p><font size="2" color="black">Select a keyword to rename, and type its new name.&nbsp;
          Or select &quot;New Keyword&quot; and type a new keyword name.  Or select a keyword
          to delete, and press Delete.</font></p>
          <form action="do_maint.php" method="post" name="kwform">
            <input type="hidden" name="process" value="keyword">
            <select name="kw_select" size="1"
            onchange="fill_keyword();">
              <option value="new">New Keyword...</option>
<?php
$sql = "SELECT * FROM keyword ORDER BY Keyword";
if (!$result = mysqli_query($db,$sql)) {
  echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)<br>");
  exit;
}
while ($row = mysqli_fetch_object($result)) {
  echo "              <option value=\"$row->KeywordID\">$row->Keyword</option>\n";
}
?>
            </select> <br>
          Keyword Name:<br><input type="text" name="keyword" size="30" maxlength=30>
            <input type="submit" name="kw_add_upd" value="Add or Rename">
            <input type="submit" name="kw_del" value="Delete" disabled>
          </form>
        </td>
        <td bgcolor="#e3e0ac" width="402">
          <div align="center">
            <h3><font color="#826500">Event Management</font></h3>
          </div>
          <p><font size="2" color="black">Fill in the information to add a new event.  Or select an event, and modify its info (name, remarks, and/or active status).  Or select an event to delete and press Delete.</font></p>
          <font color="black">
            <form action="do_maint.php" method="post" name="eventform">
              <input type="hidden" name="process" value="keyword">
              <select name="event_list" size="1" onchange="fill_event();">
                <option value="new">New Event...</option>
              </select><br>
              <input type="hidden" name="event_id" value="">
              Event: <input type="text" name="event" size="50" maxlength=50>
              &nbsp;&nbsp;&nbsp;<input type="checkbox" name="active"
              value="CheckboxValue" checked>Currently Occurring Event<br>
              Description:<br>
            <textarea name="remarks" rows="3" cols="50"></textarea>
              <input type="submit" name="event_add_upd" value="Add or Update">
              <input type="submit" name="event_del" value="Delete" disabled>
            </form>
          </font>
        </td>
      </tr>
<?php }   // end of if admin>0 ?>
      <tr>
		<td align="center" bgcolor="#ffa6ec">
				<h3><font color="#9e0773">Change My Password</font></h3>
				<form action="do_maint.php" method="post" name="pwform">
					<input type="hidden" name="process" value="category"> Old: <input type="password" name="old_pw" size="16" maxlength=16><br>
					New:<input type="password" name="new_pw1" size="16" maxlength=16><br>
					New again:<input type="password" name="new_pw2" size="16" maxlength=16><br>
					 <input type="submit" name="upd" value="Change Password">
				</form>
			</td>
		<td bgcolor="white">
			
		</td>
	  </tr>
    </table>

<SCRIPT FOR=window EVENT=onload LANGUAGE="Javascript">
list_index = 1;
for (var array_index = 0; array_index < ar.length; array_index++) {
  document.eventform.event_list.options[list_index] = new Option(ar[array_index][event], array_index);
  list_index++;
}
</SCRIPT>

<?php print_footer(); ?>