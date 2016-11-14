<?php
 include("functions.php");
 include("accesscontrol.php");

print_header("Praise & Worship DB - Song Use Chart","#D0FFF0",1); ?>

<SCRIPT language="Javascript">

function show_event() {
  window.frames.ResultFrame.location.href = "use_chart.php?eid="+
  document.eform.event.options[document.eform.event.selectedIndex].value;
  window.frames.ResultFrame.focus();
}

</SCRIPT>
<center>
  <h1><font color=#20A040>Song Use Chart</font></h1>
  <form name="eform">
    <p>Event: <select size="1" name="event" onchange="show_event();">
      <option value="">Select an event...</option>
<?
// Build option list from event table contents
if (!$result = mysql_query("SELECT * FROM pw_event ORDER BY Active DESC, Event")) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b>");
} else {
  while ($row = mysql_fetch_object($result)) {
    echo "      <option value=$row->EventID>{$row->Event} (";
    echo ($row->Active ? "current" : "archive") . ")</option>\n";
  }
}
?>
      </select>
  </form>
<iframe name="ResultFrame" width="100%" height="400" src="blank.html">
</iframe>
</center>

<?
print_footer();
?>
