<?php
session_start();

// The only bit from functions.php that I need here
$hostarray = explode(".",$_SERVER['HTTP_HOST']);
define('CLIENT',$hostarray[0]);
define('CLIENT_PATH',"/var/www/sambidb/client/".CLIENT);

/* Eventually I should figure out how to use this, but I don't know how */
//$okplaces = array ("song.php", "edit.php");
//if (!in_array($_SERVER['referrer'],$okplaces)) die("You can't call this directly.");

if (!isset($_SESSION['userid'])) exit("no session");

if (!isset($_GET['sid']))  exit("no sid");

$file = CLIENT_PATH."/audio/s".$_GET['sid'].".mp3";
if (!file_exists($file)) exit();
$size = filesize($file);
header("Content-Type: audio/mpeg");
header("Content-Transfer-Encoding: binary");
header("Content-Length: ".$size);
@readfile($file);
?>