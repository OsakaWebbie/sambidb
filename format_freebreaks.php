<?php
include("functions.php");
include("accesscontrol.php");
//print_header("","#FFF0E0",0);
echo "<html><head>";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$_SESSION['charset']."\">\n";
echo "<title>Formatted Songs</title>\n";

$sid_array = split(",",$sid_list);
$num_sids = count($sid_array);

$sql = "SELECT * FROM pw_outputset LEFT JOIN pw_output ON pw_outputset.Class=pw_output.Class ".
 "WHERE SetName='$outputset_name' ORDER BY OrderNum";
if (!$result = mysql_query($sql)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
  exit;
}
$num_items = 0;
$lyrics_index = -1;  // to distinguish index of 0 from no lyrics at all
echo "<style>\n";
while ($row = mysql_fetch_object($result)) {
  $class[$num_items][0] = $row->Class;
  $class[$num_items][1] = $row->OutputSQL;
  echo ".".$row->Class." { ".$row->CSS." }\n";
  if ($num_items == 0 && eregi("page-break-before *: *always", $row->CSS))  $fix_first_page = 1;
  if (eregi("lyrics",$row->Class )) {
    $lyrics_index = $num_items;
    $lyrics_css = substr($row->CSS, 0, strpos($row->CSS, "}"));
    if (!eregi("font-size *: *([0-9]+)([^;]*)", $lyrics_css, $lyrics_size)) {
      $lyrics_size = array("","6","pt");
    }
//    echo ".chordlyrics { ".$lyrics_css." position:relative; margin-top:".($lyrics_size[1]*1.2).$lyrics_size[2]."; }\n";
//    echo ".chord { ".$lyrics_css." position:absolute; top: -".$lyrics_size[1].$lyrics_size[2]."; font-weight:bold; }\n";
//    echo ".chordlyrics { ".$lyrics_css."\n  position:relative;\n";
//    echo "  margin-top:".($lyrics_size[1]/5).$lyrics_size[2].";\n";
//    echo "  margin-bottom:".$lyrics_size[1].$lyrics_size[2].";\n";
//    echo "  top: ".$lyrics_size[1].$lyrics_size[2]."; }\n";
//    echo ".chord { ".$lyrics_css."\n  position:absolute;\n  top: -".$lyrics_size[1].$lyrics_size[2].";\n  font-weight:bold; }\n";
    echo ".chordlyrics { ".$lyrics_css."\n  position:relative;\n";
    echo "  line-height:".($lyrics_size[1]*2.2).$lyrics_size[2]."; }\n";
    echo ".chord { ".$lyrics_css." position:absolute; top: -".$lyrics_size[1].$lyrics_size[2]."; font-weight:bold; }\n";
  }
  $num_items++;
}
echo "textarea { font-size: 9pt; }\n";
echo "@media print { .noprint { display: none; } }\n";
echo "</style>\n";
echo "</head><body>";
echo "<div class=\"noprint\">\n";
echo "<button onclick=\"window.open('format_edit.php','edit','height=650,width=650,scrollbars=yes');\">Edit This Output</button>\n";
echo "<hr></div>";
echo "<div id=\"output\">\n";

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
  if ($sid_index == 0 && $fix_first_page) {
    $song[0] = ereg_replace("(<p[^>]*)>","\\1 style=\"page-break-before:auto;\">",$song[0]);
  }
  for ($item_index = 0; $item_index < $num_items; $item_index++) {
    if ($item_index == $lyrics_index) {
      if ($_GET['showchords'] == "yes") {
        $song[$item_index] = ereg_replace("<p class=lyrics>([^<]*\[[^<]*)</p>","<p class=chordlyrics>\\1</p>",$song[$item_index]);
        echo ereg_replace("\[([^\[]*)\]","<span class=chord>\\1</span>",$song[$item_index]);
      } else {
        echo ereg_replace("\[[^\[]*\]","",$song[$item_index]);
      }
    } else {
      echo $song[$item_index];
    }
  }
}
echo "</div>\n";

echo "</body></html>";
?>
