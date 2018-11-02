<?php
session_start();
//if (!isset($_SESSION['userid'])) exit("Session timed out. Please refresh the previous page to log in again.");

// The only bit from functions.php that I need here
$hostarray = explode(".",$_SERVER['HTTP_HOST']);
define('CLIENT',$hostarray[0]);
define('CLIENT_PATH',"/var/www/sambidb/client/".CLIENT);

if (isset($_GET['playsid'])) {
//echo "Playsid is set<br>";
  if (!isset($_SESSION['audio_ok'])) exit("You can't call this directly in order to download this audio!");
//echo "audio_ok is set correctly<br>";
  unset($_SESSION['audio_ok']);
  $file = CLIENT_PATH."/audio/s".$_GET['playsid'].".mp3";
  if (!file_exists($file)) exit;
//echo "audio file exists<br>";
//exit;
  $size = filesize($file);
  header("Content-Type: audio/mpeg");
  header("Content-Transfer-Encoding: binary");
  header("Content-Length: ".$size);
  @readfile($file);
} else {
  $_SESSION['audio_ok'] = 1;
  echo "<html><head>\n<title>Audio: $title</title></head>\n<body>\n";
echo "I would now create the embed...<br>";
echo "<a href=\"".$_SERVER['PHP_SELF']."?playsid=".$sid."\">Click here to run other part of code<br>";
  //echo "<div align=center style=\"font-size:10pt;font-color:#00D000;font-weight:bold;\">\n";
  //echo $title."<br>";
  //echo "<embed src=\"".$_SERVER['PHP_SELF']."?playsid=".$sid."\" controls=\"console\" width=\"144\" height=\"60\""; 
  //echo " vspace=\"0\" hspace=\"0\" border=\"2\" align=\"top\" autoplay=true";
  //echo " pluginspage=\"//www.apple.com/quicktime/download/?video/quicktime\"></embed>\n";
  //echo "</div>\n";
  echo "</body></html>";
}
?>