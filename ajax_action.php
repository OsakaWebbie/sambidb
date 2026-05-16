<?php
include("functions.php");
include("accesscontrol.php");

// Check session for AJAX requests that need it
if (!isset($_SESSION['userid']) && $_REQUEST['action'] != 'SwitchLang') {
  die(json_encode(array('alert' => 'NOSESSION')));
}

