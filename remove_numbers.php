<?php
include("functions.php");
print_header("Complex Song Processing","#FFFFFF",0);

error_reporting( E_ALL );

$sql = "SELECT SongID,Title FROM pw_song";
if (!$result = mysql_query($sql)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
  exit;
}
$count=0;

echo mysql_num_rows($result)." songs found.<br />";
while ($row = mysql_fetch_object($result)) {
  $sid = $row->SongID;
  $newtitle = preg_replace('/^\d{3}: /u','',$row->Title);
  //echo "Will title change from '".$row->Title."' to:<pre>".print_r($newtitle,TRUE)."'</pre>";
  if ($newtitle != $row->Title) {
    if (isset($_GET['dryrun'])) {
      echo "Title to change from '".$row->Title."' to: '<span style=\"color:red;font-weight:bold\">$newtitle</span>'<br>";
    } elseif (!mysql_query("UPDATE pw_song SET Title='".mysql_real_escape_string($newtitle)."' WHERE SongID=$sid")) {
      echo("<b>SQL Error ".mysql_errno()." updating song: ".mysql_error()."</b><br>");
      exit;
    }
    $count++;
  } else "Title left alone: ".$row->Title."<br />";
}
echo "Completed - $count changed.";
print_footer(); ?>