<?php
include("functions.php");
print_header("Tag Processing","#FFFFFF",0);

//if ($_POST['eid'] && $_POST['ud']) {  // called from song_session.php
//  $listquery = "SELECT history.SongID,song.Tagged ".
//  "FROM history LEFT JOIN song ON song.SongID=history.SongID ".
//  "WHERE history.EventID={$_POST['eid']} AND history.UseDate='{$_POST['ud']}'";
//} elseif (!$listquery) {  // not called by either list.php or song_session.php
//  echo("<b>This file cannot be accessed directly.</b><br>");
//  exit;
//} else {
//  $sql = stripslashes($_POST['listquery']);
//}
$sql = "SELECT SongID,Tagged FROM song WHERE SongID IN (".$_POST['sid_list'].")";
if (!$result = mysqli_query($db,$sql)) {
  echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
  exit;
}

while ($row = mysqli_fetch_object($result)) {
  $sid = $row->SongID;
  if ($_POST[$sid] and !$row->Tagged) {
    if (!mysqli_query($db,"UPDATE song SET Tagged=1 WHERE SongID=$sid")) {
      echo("<b>SQL Error ".mysqli_errno($db)." tagging song: ".mysqli_error($db)."</b><br>");
      exit;
    }
  } elseif (!$_POST[$sid] and $row->Tagged) {
    if (!mysqli_query($db,"UPDATE song SET Tagged=0 WHERE SongID=$sid")) {
      echo("<b>SQL Error ".mysqli_errno($db)." untagging song: ".mysqli_error($db)."</b><br>");
      exit;
    }
  }

}
echo "<SCRIPT FOR=window EVENT=onload LANGUAGE=\"Javascript\">\n";
//echo "alert(\"Tag information successfully updated.\");\n";
echo "window.location = \"".($_POST['eid'] ? "song_session.php?eid={$_POST['eid']}&ud={$_POST['ud']}" : "index.php")."\";\n";
echo "</SCRIPT>\n";
print_footer(); ?>