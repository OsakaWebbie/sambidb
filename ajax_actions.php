<?php
include("functions.php");
include("accesscontrol.php");

switch($_REQUEST['action']) {
  case "SwitchLang":
    if (!isset($_GET['lang']) || ($_GET['lang']!='en_US' && $_GET['lang']!='ja_JP')) die("Failed.");
    $_SESSION['lang'] = $_GET['lang'];
    setlocale(LC_ALL, $_SESSION['lang'].".utf8");
    break;
  default:
    die("Programming error: NO ACTION RECOGNIZED");
}
?>