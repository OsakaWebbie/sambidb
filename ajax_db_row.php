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
$sql = "SELECT * FROM $table WHERE $key = " . ($keyquoted?"'":"") . mysqli_real_escape_string($db,$value) . ($keyquoted?"'":"");
if (!$result = mysqli_query($db,$sql)) {
  $return->errorMessage = "SQL Error ".mysqli_errno($db).": ".mysqli_error($db);
  die(json_encode($return));
}
if (mysqli_num_rows($result) == 0) {
  $return->errorMessage = "No matching records found";
  die(json_encode($return));
}
$return->success = true;
$return->data = mysqli_fetch_assoc($result); //there should only be one row
echo(json_encode($return));
?>
