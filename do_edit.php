<?php
ini_set("max_execution_time","120");
set_time_limit(0);
include("functions.php");
include("accesscontrol.php");
print_header("Editing Record...","#FFFFFF",0);

echo "<h3 color=green>Editing (or adding) record...</h3>";

if ( !empty($_SERVER['CONTENT_LENGTH']) && empty($_FILES) && empty($_POST) ) {
	echo '<h2 style="color:red">The uploaded file was too large. You must upload a file smaller than '.ini_get("upload_max_filesize").".<br><br>\n";
  echo "Sorry, but you will have to redo any other edits (or new song data) that you entered at the same time as the file upload.<br><br>\n";
  echo "To edit again, hit your browser's Back button.</h2>";
  exit;
}

if ($sid) {
  //echo "Editing an existing record.<br>"; //debugging only
  $sql = "UPDATE pw_song SET Title='".mysql_real_escape_string(str_replace("\n"," ",$title))."',".
  "OrigTitle='".mysql_real_escape_string(str_replace("\n"," ",trim($origtitle)))."',".
  "Composer='".mysql_real_escape_string(str_replace("\n"," ",trim($composer)))."',".
  "Copyright='".mysql_real_escape_string(str_replace("\n"," ",trim($copyright)))."',".
  "SongKey='$songkey',Tempo='$tempo',Source='".mysql_real_escape_string(trim($source))."',".
  "Lyrics='".mysql_real_escape_string(trim($lyrics))."',".
  "Pattern='".mysql_real_escape_string(str_replace("\n"," ",trim($pattern)))."',".
  "Instruction='".mysql_real_escape_string(trim($instruction))."',".
  "AudioComment='".mysql_real_escape_string(str_replace("\n"," ",trim($audiocomment)))."'".
  " WHERE SongID=$sid LIMIT 1";
  //echo "SQL is:<pre>$sql</pre><br>"; //debugging only
  if (!$result = mysql_query($sql)) {
    echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
    exit;
  }
  if (mysql_affected_rows() > 0) {
    echo "The pw_song record was updated<br>";
  }
} else {
  //echo "Creating a new record.<br>"; //debugging only
  $sql = "INSERT INTO pw_song (Title,OrigTitle,Composer,Copyright,SongKey,".
  "Tempo,Source,Lyrics,Pattern,Instruction,AudioComment) VALUES (".
  "'".mysql_real_escape_string(str_replace("\n"," ",trim($title)))."',".
  "'".mysql_real_escape_string(str_replace("\n"," ",trim($origtitle)))."',".
  "'".mysql_real_escape_string(str_replace("\n"," ",trim($composer)))."',".
  "'".mysql_real_escape_string(str_replace("\n"," ",trim($copyright)))."',".
  "'$songkey','$tempo',".
  "'".mysql_real_escape_string(trim($source))."',".
  "'".mysql_real_escape_string(trim($lyrics))."',".
  "'".mysql_real_escape_string(str_replace("\n"," ",trim($pattern)))."',".
  "'".mysql_real_escape_string(trim($instruction))."',".
  "'".mysql_real_escape_string(str_replace("\n"," ",trim($audiocomment)))."')";
  //echo "SQL is:<pre>$sql</pre><br>"; //debugging only
  if (!$result = mysql_query($sql)) {
    echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
    exit;
  }
  if (mysql_affected_rows() > 0) {
    $sid = mysql_insert_id();
    echo "The pw_song record was inserted.<br>";
  } else {
    echo "No pw_song record was inserted for some reason.<br>";
  }
}
//echo "Moving on to the code for a possible upload. SID = $sid<br>"; //debugging only
if (is_uploaded_file($_FILES['audiofile']['tmp_name'])) {
  //echo "There is a temp file called ".$_FILES['audiofile']['tmp_name'].".<br>"; //debugging only
  if (move_uploaded_file($_FILES['audiofile']['tmp_name'], "audio/s".$sid.".mp3")) {
    echo "File is valid, and was successfully uploaded.<br>";
    $sql = "UPDATE pw_song SET Audio=1 WHERE SongID=$sid LIMIT 1";
    if (!$result = mysql_query($sql)) {
      echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
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