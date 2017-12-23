<?php
ini_set("max_execution_time","120");
set_time_limit(0);
include("functions.php");
include("accesscontrol.php");
print_header("Editing Record...","#FFFFFF",0);

echo "<h3>Editing (or adding) record...</h3>";

if ( !empty($_SERVER['CONTENT_LENGTH']) && empty($_FILES) && empty($_POST) ) {
	echo '<h2 style="color:red">The uploaded file was too large. You must upload a file smaller than '.ini_get("upload_max_filesize").".<br><br>\n";
  echo "Sorry, but you will have to redo any other edits (or new song data) that you entered at the same time as the file upload.<br><br>\n";
  echo "To edit again, hit your browser's Back button.</h2>";
  exit;
}

if (!empty($_POST['sid'])) {
  $sid = $_POST['sid'];
  //echo "Editing an existing record.<br>"; //debugging only
  $sql = "UPDATE song SET Title='".mysqli_real_escape_string($db,str_replace("\n"," ",$_POST['title']))."',".
  "OrigTitle='".mysqli_real_escape_string($db,str_replace("\n"," ",trim($_POST['origtitle'])))."',".
  "Composer='".mysqli_real_escape_string($db,str_replace("\n"," ",trim($_POST['composer'])))."',".
  "Copyright='".mysqli_real_escape_string($db,str_replace("\n"," ",trim($_POST['copyright'])))."',".
  "SongKey='${_POST['songkey']}',Tempo='${_POST['tempo']}',Source='".mysqli_real_escape_string($db,trim($_POST['source']))."',".
  "Lyrics='".mysqli_real_escape_string($db,str_replace(chr(0x2019),"'",rtrim($_POST['lyrics'])))."',".
  "Pattern='".mysqli_real_escape_string($db,str_replace("\n"," ",trim($_POST['pattern'])))."',".
  "Instruction='".mysqli_real_escape_string($db,trim($_POST['instruction']))."',".
  "AudioComment='".mysqli_real_escape_string($db,str_replace("\n"," ",trim($_POST['audiocomment'])))."'".
  " WHERE SongID=$sid LIMIT 1";
  //echo "SQL is:<pre>$sql</pre><br>"; //debugging only
  if (!$result = mysqli_query($db,$sql)) {
    echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
    exit;
  }
  if (mysqli_affected_rows($db) > 0) {
    echo "The song record was updated<br>";
  }
} else {
  //echo "Creating a new record.<br>"; //debugging only
  $sql = "INSERT INTO song (Title,OrigTitle,Composer,Copyright,SongKey,".
  "Tempo,Source,Lyrics,Pattern,Instruction,AudioComment) VALUES (".
  "'".mysqli_real_escape_string($db,str_replace("\n"," ",trim($_POST['title'])))."',".
  "'".mysqli_real_escape_string($db,str_replace("\n"," ",trim($_POST['origtitle'])))."',".
  "'".mysqli_real_escape_string($db,str_replace("\n"," ",trim($_POST['composer'])))."',".
  "'".mysqli_real_escape_string($db,str_replace("\n"," ",trim($_POST['copyright'])))."',".
  "'${_POST['songkey']}','${_POST['tempo']}',".
  "'".mysqli_real_escape_string($db,trim($_POST['source']))."',".
  "'".mysqli_real_escape_string($db,str_replace(chr(0x2019),"'",trim($_POST['lyrics'])))."',".
  "'".mysqli_real_escape_string($db,str_replace("\n"," ",trim($_POST['pattern'])))."',".
  "'".mysqli_real_escape_string($db,trim($_POST['instruction']))."',".
  "'".mysqli_real_escape_string($db,str_replace("\n"," ",trim($_POST['audiocomment'])))."')";
  //echo "SQL is:<pre>$sql</pre><br>"; //debugging only
  if (!$result = mysqli_query($db,$sql)) {
    echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
    exit;
  }
  if (mysqli_affected_rows($db) > 0) {
    $sid = mysqli_insert_id($db);
    echo "The song record was inserted.<br>";
  } else {
    echo "No song record was inserted for some reason.<br>";
    exit;
  }
}
//echo "Moving on to the code for a possible upload. SID = $sid<br>"; //debugging only
if (is_uploaded_file($_FILES['audiofile']['tmp_name'])) {
  //echo "There is a temp file called ".$_FILES['audiofile']['tmp_name'].".<br>"; //debugging only
  if (move_uploaded_file($_FILES['audiofile']['tmp_name'], CLIENT_PATH."/audio/s".$sid.".mp3")) {
    echo "File is valid, and was successfully uploaded.<br>";
    $sql = "UPDATE song SET Audio=1 WHERE SongID=$sid LIMIT 1";
    if (!$result = mysqli_query($db,$sql)) {
      echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
      exit;
    }
  } else {
    echo "File upload failed.  Here's some debugging info:\n";
    print_r($_FILES);
    exit;
  }
//} else { //debugging only
  //echo "I don't see a temp file.<br>FILES:<pre>".print_r($_FILES,true)."</pre><br>"; //debugging only
}

echo "<SCRIPT FOR=window EVENT=onload LANGUAGE=\"Javascript\">\n";
echo "window.location = \"song.php?sid=".$sid."\";\n";
echo "</SCRIPT>\n";

print_footer();
?>