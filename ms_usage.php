<?php
include("functions.php");
include("accesscontrol.php");
print_header("","#F0E0FF",0);
showfile("dates.js");

if ($save_usage) {
  if ($event_select == "new") {  //need to insert the new event record first
    $sql = "INSERT INTO pw_event (Event,Active,Remarks) ".
    "VALUES ('$event',".(($active)?"1":"0").",'$remarks')";
    if (!$result = mysql_query($sql)) {
      echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
      exit;
    }
    if (mysql_affected_rows() > 0) {
      $event_id = mysql_insert_id();
      echo "<h3><font color=\"#663399\">New event successfully added.</font></h3>";
    } else {
      echo "No event record was inserted for some reason.<br>";
      exit;
    }
  } elseif (!$confirmed) {
    // check for songs already on this event and date
    $sql = "SELECT pw_song.Title, pw_usage.UseOrder FROM pw_usage LEFT JOIN pw_song".
    " ON pw_usage.SongID=pw_song.SongID WHERE EventID=$event_id AND UseDate='$use_date' ORDER BY UseOrder";
    if (!$result = mysql_query($sql)) {
      echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
      exit;
    }
    if (mysql_num_rows($result) > 0) {
      // ask for confirmation before replacing song session
      echo "<table border=0 cellspacing=0 cellpadding=5><tr><td width=350>\n";
      echo "<font color=red><b>There are already songs recorded for this event and date.";
      echo " The list is to the right. If you do not want to replace these songs with your selection,";
      echo " just select your browser's Back button.</b></font>";
      echo "<form action=\"$PHP_SELF\" method=post>";
      echo "<input type=hidden name=sid_list value=\"$sid_list\">\n";
      echo "<input type=hidden name=event_id value=\"$event_id\">";
      echo "<input type=hidden name=use_date value=\"$use_date\">\n";
      echo "<input type=hidden name=confirmed value=\"1\">\n";
      echo "<input type=submit name=save_usage value=\"Yes, replace with new selected songs\" border=0>\n";
      echo "</form></td><td><b>Previously recorded song session:</b><br>\n";
      while ($row = mysql_fetch_object($result)) {
        echo "&nbsp; &nbsp; &nbsp;".$row->UseOrder.". ".$row->Title."<br>\n";
      }
      echo "</td></tr></table>\n";
      exit;
    }
  }
  
  echo "<h3><font color=\"#663399\">";
  
  if ($confirmed) {   // there are old records that need to be deleted
    $sql = "DELETE FROM pw_usage WHERE EventID=$event_id AND UseDate='$use_date'";
    if (!$result = mysql_query($sql)) {
      echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
      exit;
    }
  echo "Old usage records deleted.<br>";
  }

  if ($sid_list == "") {
    echo "No new records added, as list was empty.<br>You must have just wanted to get rid of some old data (wink!).";
  } else {
    $sid_array = split(",",$sid_list);
    $num_sids = count($sid_array);
    for ($i=0; $i<$num_sids; $i++) {
      $sql = "INSERT INTO pw_usage (SongID,EventID,UseDate,UseOrder) VALUES (".
      $sid_array[$i].",$event_id,'$use_date',".($i+1).")";
      if (!$result = mysql_query($sql)) {
        echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
        exit;
      }
    }
    echo $num_sids." new usage records added.";
  }
  echo "</font></h3>";
  exit;
}
?>

<script type=text/javascript>

//indexes for arrays (just to keep sane)
var eid = 0;
var event = 1;
var rem = 2;

<?
//get data from pw_event table and build master array

$sql = "SELECT * FROM pw_event ORDER BY Event";
if (!$result = mysql_query($sql)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)<br>");
  exit;
}
echo "var ar = new Array();\n";
$ar_index = 0;
while ($row = mysql_fetch_object($result)) {
  echo "ar[$ar_index] = new Array();\n";
  echo "ar[$ar_index][eid] = \"$row->EventID\";\n";
  echo "ar[$ar_index][event] = \"".escape_quotes($row->Event)."\";\n";
  echo "ar[$ar_index][rem] = \"".escape_quotes($row->Remarks)."\";\n";
  $ar_index++;
}
?>

function load_events() {
  for (var list_index = document.useform.event_list.length-1; list_index > 0; list_index--) {
    document.useform.event_list.options[list_index] = null;
  }
  list_index = 1;
  for (var array_index = 0; array_index < ar.length; array_index++) {
    document.useform.event_list.options[list_index] = new Option(ar[array_index][event], array_index);
    list_index++;
  }
  document.useform.event_list.options[list_index] = new Option("New Event -->", "new");
  document.useform.use_date.value = Today();
}
window.onload=load_events;

function fill_fields() {
  var uf = document.useform;
  var el = uf.event_list;
  if (el.options[el.selectedIndex].value == "") {
    uf.event.disabled = true;
    uf.remarks.disabled = true;
    uf.event_id.value = "";
    uf.event.value = "";
    uf.remarks.value = "";
    uf.event_list.disabled = false;
  } else if (el.options[el.selectedIndex].value == "new") {
    uf.event_list.disabled = true;
    uf.event_id.value = "";
    uf.event.value = "";
    uf.remarks.value = "";
    uf.event.disabled = false;
    uf.remarks.disabled = false;
    uf.event.focus();
  } else {
    uf.event_id.value = ar[el.options[el.selectedIndex].value][eid];
    uf.event.value = ar[el.options[el.selectedIndex].value][event];
    uf.remarks.value = ar[el.options[el.selectedIndex].value][rem];
  }
}

function validate() {
//If new event, check for invalid entries
  if (document.useform.event_select.value == "new") {
    if (document.useform.event.value == "") {
      alert("You need to specify a name for the new event.");
      document.useform.event.focus();
      return false;
    }
  }
  return true;
}
</SCRIPT>

  <div align="center">
    <font color="#663399" size=4><b>Choose existing event and enter date,
        or fill in information for a new event:</b></font>
    <form action="<? echo $PHP_SELF; ?>" method="post" name="useform" target="_self">
      <input type="hidden" name="sid_list" value="<? echo $sid_list; ?>" border="0">
      <input type="hidden" name="event_id" value="" border="0">
      <table border="1" cellspacing="0" cellpadding="4">
        <tr>
          <td nowrap>Event: <select name="event_list" size="1" onchange="fill_fields();">
              <option value="" selected>Select an event...</option>
            </select><br>
          <td nowrap>
            <p>Event: <input type="text" name="event" disabled size="45" maxlength="50" border="0"><br>
              <textarea name="remarks" disabled rows="3" cols="45"></textarea></p>
          </td>
          <td align="center" nowrap>Date Used:<br><input type="text" name="use_date" value="" size="12"
          maxlength="10" border="0"><a href="#" onClick="call_calendar(document.useform.use_date);">
          <img id=usecal src=calendarbutton.gif border=0 width=20 height=20></a><br>&nbsp;<br>
          <input type="submit" name="save_usage" value="Save Data" border="0"></td>
        </tr>
      </table>
    </form>
  </div>
    <? print_footer();
?>
