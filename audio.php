<?php
session_start();
if (!isset($_SESSION['pw_userid'])) exit("Session timed out. Please refresh the previous page to log in again.");

if (isset($_GET['playsid'])) {
  if (!isset($_SESSION['pw_audio_ok'])) exit("You can't call this directly in order to download this audio!");
  unset($_SESSION['pw_audio_ok']);
  $file = "audio/s".$_GET['playsid'].".mp3";
  if (!file_exists($file)) die($file." not found.");
  $size = filesize($file);
  header("Content-Type: audio/mpeg");
  header("Content-Transfer-Encoding: binary");
  header("Content-Length: ".$size);
  $fh = fopen ($file, "rb");
  while (!feof($fh)):
    echo fread($fh, 2046);
  endwhile;
  fclose($fh);
//  @readfile($file);

} elseif (isset($_GET['sid'])) {
  $_SESSION['pw_audio_ok'] = 1;
  echo "<html><head>\n";
  echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$_SESSION['pw_charset']."\">\n";
  echo "<title>Audio: ".$_GET['title']."</title></head>\n<body>\n";
  echo "<div align=center style=\"font-size:10pt;font-color:#00D000;font-weight:bold;\">\n".$_GET['title']."<br>";
  echo "<embed src=\"".$_SERVER['PHP_SELF']."?playsid=".$_GET['sid']."\" controls=\"console\" width=\"144\" height=\"60\""; 
  echo " vspace=\"0\" hspace=\"0\" border=\"2\" align=\"top\" autoplay=true";
  echo " pluginspage=\"http://www.apple.com/quicktime/download/?video/quicktime\"></embed>\n</div>\n";
  echo "</body></html>";

} else {
  die("No Song ID passed.");
}
?>