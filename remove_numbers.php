<?php
include("functions.php");
print_header("Complex Song Processing","#FFFFFF",0);

error_reporting( E_ALL );

$sql = "SELECT SongID,Title FROM song";
if (!$result = mysqli_query($db,$sql)) {
  echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
  exit;
}
$count=0;

echo mysqli_num_rows($result)." songs found.<br />";
while ($row = mysqli_fetch_object($result)) {
  $sid = $row->SongID;
  $newtitle = preg_replace('/^\d{3}: /u','',$row->Title);
  //echo "Will title change from '".$row->Title."' to:<pre>".print_r($newtitle,TRUE)."'</pre>";
  if ($newtitle != $row->Title) {
    if (isset($_GET['dryrun'])) {
      echo "Title to change from '".$row->Title."' to: '<span style=\"color:red;font-weight:bold\">$newtitle</span>'<br>";
    } elseif (!mysqli_query($db,"UPDATE song SET Title='".mysqli_real_escape_string($db,$newtitle)."' WHERE SongID=$sid")) {
      echo("<b>SQL Error ".mysqli_errno($db)." updating song: ".mysqli_error($db)."</b><br>");
      exit;
    }
    $count++;
  } else "Title left alone: ".$row->Title."<br />";
}
echo "Completed - $count changed.";
print_footer(); ?>