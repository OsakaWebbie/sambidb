<?php
include("functions.php");
include("accesscontrol.php");
//print_header("","#FFF0E0",0);
echo "<html><head>";
if (eregi("budounoki.org",$_SERVER['HTTP_HOST']) || eregi("oicjapan.org",$_SERVER['HTTP_HOST'])) {
  echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\n";
} else {
  echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=shift-jis\">\n";
}
echo "<title>Formatted Songs</title>\n";

//$sid_array = split(",","10,11,12");
$sid_array = split(",",$sid_list);
$num_sids = count($sid_array);

$sql = "SELECT * FROM pw_outputset LEFT JOIN pw_output ON pw_outputset.Class=pw_output.Class ".
 "WHERE SetName='$outputset_name' ORDER BY OrderNum";
if (!$result = mysql_query($sql)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
  exit;
}
$num_items = 0;
echo "<style>\n";
while ($row = mysql_fetch_object($result)) {
  $class[$num_items][0] = $row->Class;
  $class[$num_items][1] = $row->OutputSQL;
  echo ".".$row->Class." { ".$row->CSS." }\n";
  $num_items++;
}
echo "</style>\n";  
echo "</head><body>";

for ($sid_index=0; $sid_index<$num_sids; $sid_index++) {
  $sql = "SELECT ";
  for ($index = 0; $index < $num_items; $index++) {
    $sql .= $class[$index][1]." AS Item".$index;
    if ($index < $num_items-1) $sql .= ",";
  }
  $sql .= " FROM pw_song WHERE SongID=".$sid_array[$sid_index];
  if (!$result = mysql_query($sql)) {
    echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
    exit;
  }
  $song = mysql_fetch_array($result);
  for ($index = 0; $index < $num_items; $index++) {
    echo $song[$index]."\n";
  }
}

echo "</body></html>";
?>
