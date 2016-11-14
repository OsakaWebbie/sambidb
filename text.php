<?php
include("functions.php");
include("accesscontrol.php");

if ($xml) {
  echo "<?xml version=\"1.0\" encoding=\"".$_SESSION['pw_charset']."\" ?>\n<songlist>\n";
} else {
  echo "<html><head>";
  echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$_SESSION['charset']."\">\n";
  echo "<style type=\"text/css\">p {margin-bottom: 0; margin-top: 0;}</style>";
  echo "</head><body>";
}

$sid_array = split(",",$sid_list);
$num_sids = count($sid_array);
for ($sid_index=0; $sid_index<$num_sids; $sid_index++) {
  $sql = "SELECT * FROM pw_song WHERE SongID=$sid_array[$sid_index]";
  if (!$result = mysql_query($sql)) {
    echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
    exit;
  }
  $row = mysql_fetch_object($result);
  if ($xml) {
    echo "<song>\n";
  }
  for ($i=1; $i<7; $i++) {
    if (${"field".$i} != "") {
      if ($xml) {
        $text = ereg_replace("&","&amp;",$row->{${"field".$i}});
        $text = ereg_replace("'","&apos;",$text);
        $text = ereg_replace("\[[^\[]*\]","",$text);  //to remove the chords
        echo "<".${"field".$i}.">".$text."</".${"field".$i}.">\n";
      } else {
        echo ${"layout".$i};
        if (${"newline".$i} == "YES")  echo "<br>\n";
        $text = ereg_replace("  "," &nbsp;",$row->{${"field".$i}});
        $text = ereg_replace("\r\n|\n|\r","<br>\n",$text);
        $text = ereg_replace("\[[^\[]*\]","",$text);  //to remove the chords
        echo $text;
        if (substr(${"layout".$i},-1) == "(")  echo ")";
      }
    }
  }
  if ($xml) {
    echo "</song>\n";
  }
}
if ($xml) {
  echo "</songlist>\n";
} else {
  echo "</body></html>";
}
?>
