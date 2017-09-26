<?php
session_start();
/*
if (!isset($_SESSION['userid'])) exit("no session");
if (isset($_GET['songid'])) {
  $file = "path/to/file.mp3";
  $size = filesize($file);
  header("Content-Type: audio/mpeg");
  header("Content-Transfer-Encoding: binary");
  header("Content-Length: ".$size);
  @readfile($file);
} else {
  echo "<embed loop=false volume=100 autostart=true src="<?=$_SERVER['PHP_SELF']?>?songid=1" hidden=false />
}
*/


$okplaces = array ("song.php", "edit.php");
//$comp1 = $_SESSION['urlpath']."song.php";
//$comp2 = $_SESSION['urlpath']."edit.php";

if (!isset($_SESSION['userid'])) exit("no session");

//$referer = parse_url($_SERVER['HTTP_REFERER']);
//$referer = pathinfo($referer['path']);
//$referer = strtolower($referer['basename']);
//if (!in_array($referer, $okplaces)) exit("bad referer: $referer vs. ".$okplaces[0]);

//if (!isset($_SERVER['HTTP_REFERER'])) exit("no referer");
//$refer_noargs = $_SERVER['HTTP_REFERER'];
//if (strpos($refer_noargs,"?"))  $refer_noargs = substr($refer_noargs, 0, strpos($refer_noargs,"?"));
//$refer_noargs = ereg_replace("http://www.","http://",$refer_noargs);
//if (!($refer_noargs == $comp1) && !($refer_noargs == $comp2)) exit("wrong referer: $refer_noargs vs. $comp1");

if (!isset($_GET['sid']))  exit("no sid");

$file = "audio/s".$_GET['sid'].".mp3";
if (!file_exists($file)) exit();
$size = filesize($file);
header("Content-Type: audio/mpeg");
header("Content-Transfer-Encoding: binary");
header("Content-Length: ".$size);
@readfile($file);
?>