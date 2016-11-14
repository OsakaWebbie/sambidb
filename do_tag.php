<?php
include("functions.php");
print_header("Tag Processing","#FFFFFF",0);

//if ($_POST['eid'] && $_POST['ud']) {  // called from song_session.php
//  $listquery = "SELECT pw_usage.SongID,pw_song.Tagged ".
//  "FROM pw_usage LEFT JOIN pw_song ON pw_song.SongID=pw_usage.SongID ".
//  "WHERE pw_usage.EventID={$_POST['eid']} AND pw_usage.UseDate='{$_POST['ud']}'";
//} elseif (!$listquery) {  // not called by either list.php or song_session.php
//  echo("<b>This file cannot be accessed directly.</b><br>");
//  exit;
//} else {
//  $sql = stripslashes($_POST['listquery']);
//}
$sql = "SELECT SongID,Tagged FROM pw_song WHERE SongID IN (".$_POST['sid_list'].")";
if (!$result = mysql_query($sql)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
  exit;
}

while ($row = mysql_fetch_object($result)) {
  $sid = $row->SongID;
  if ($_POST[$sid] and !$row->Tagged) {
    if (!mysql_query("UPDATE pw_song SET Tagged=1 WHERE SongID=$sid")) {
      echo("<b>SQL Error ".mysql_errno()." tagging song: ".mysql_error()."</b><br>");
      exit;
    }
  } elseif (!$_POST[$sid] and $row->Tagged) {
    if (!mysql_query("UPDATE pw_song SET Tagged=0 WHERE SongID=$sid")) {
      echo("<b>SQL Error ".mysql_errno()." untagging song: ".mysql_error()."</b><br>");
      exit;
    }
  }

}
echo "<SCRIPT FOR=window EVENT=onload LANGUAGE=\"Javascript\">\n";
//echo "alert(\"Tag information successfully updated.\");\n";
echo "window.location = \"".($_POST['eid'] ? "song_session.php?eid={$_POST['eid']}&ud={$_POST['ud']}" : "index.php")."\";\n";
echo "</SCRIPT>\n";
print_footer(); ?>