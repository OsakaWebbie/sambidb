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
echo "<style type=\"text/css\">p {margin-bottom: 0; margin-top: 0;}</style>";
echo "</head><body>";

//if ($copy_songs) {
  $sid_array = split(",",$sid_list);
  $num_sids = count($sid_array);
  for ($i=0; $i<$num_sids; $i++) {
    $sql = "SELECT Title, Lyrics, Composer, Copyright, Pattern FROM pw_song WHERE SongID=$sid_array[$i]";
    if (!$result = mysql_query($sql)) {
      echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
      exit;
    }
    $row = mysql_fetch_object($result);
    echo $row->Title."<br>\n";
    echo $row->Pattern."<br>\n";
    $text = ereg_replace("  "," &nbsp;",$row->Lyrics);
    $text = ereg_replace("\r\n|\n|\r","<br>\n",$text);
    echo $text."<br>\n";
    echo "By ".$row->Composer."<br>\n";
    echo "Copyright ".$row->Copyright."<br>\n";
  }
//}

//print_footer();
echo "</body></html>";
?>
