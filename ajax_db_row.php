<?php
include("functions.php");
include("accesscontrol.php");
header('Content-Type: application/json;charset=utf-8');

parse_str($_SERVER['QUERY_STRING']);
$return = new stdClass();
$return->success = false;
if (!isset($table) || $table=='') {
  $return->errorMessage = "No table specified";
  die(json_encode($return));
}
$sql = "SELECT * FROM $table WHERE $key = " . ($keyquoted?"'":"") . mysql_real_escape_string($value) . ($keyquoted?"'":"");
if (!$result = mysql_query($sql)) {
  $return->errorMessage = "SQL Error ".mysql_errno().": ".mysql_error();
  die(json_encode($return));
}
if (mysql_numrows($result) == 0) {
  $return->errorMessage = "No matching records found";
  die(json_encode($return));
}
$return->success = true;
$return->data = mysql_fetch_assoc($result); //there should only be one row
echo(json_encode($return));
?>
