<?php
ini_set("max_execution_time","120");
set_time_limit(0);
include("functions.php");
include("accesscontrol.php");

$isAjax = !empty($_POST['ajax']);

if (!$isAjax) {
  print_header(_("Saving..."),"#FFFFFF",0);
  echo "<h3>"._('Saving...')."</h3>";
}

if ( !empty($_SERVER['CONTENT_LENGTH']) && empty($_FILES) && empty($_POST) ) {
  // Upload was too large — PHP silently dropped the entire request.
  // Note: if $isAjax was set it will also be gone, so this always takes the HTML path.
  // The AJAX client-side error handler covers this case.
  echo '<h2 style="color:red">'.sprintf(_('The uploaded file was too large. You must upload a file smaller than %s.'), ini_get("upload_max_filesize"))."<br><br>\n";
  echo _('Sorry, but you will have to redo any other edits (or new song data) that you entered at the same time as the file upload.')."<br><br>\n";
  echo _('To edit again, hit your browser\'s Back button.')."</h2>";
  exit;
}

if (!empty($_POST['sid'])) {
  $sid = $_POST['sid'];
  $sql = "UPDATE song SET Title='".mysqli_real_escape_string($db,str_replace("\n"," ",$_POST['title']))."',".
  "OrigTitle='".mysqli_real_escape_string($db,str_replace("\n"," ",trim($_POST['origtitle'])))."',".
  "Composer='".mysqli_real_escape_string($db,str_replace("\n"," ",trim($_POST['composer'])))."',".
  "Copyright='".mysqli_real_escape_string($db,str_replace("\n"," ",trim($_POST['copyright'])))."',".
  "SongKey='".mysqli_real_escape_string($db,trim($_POST['songkey']))."',".
  "Tempo='".mysqli_real_escape_string($db,trim($_POST['tempo']))."',".
  "Source='".mysqli_real_escape_string($db,trim($_POST['source']))."',".
  "Lyrics='".mysqli_real_escape_string($db,str_replace(chr(0x2019),"'",rtrim($_POST['lyrics'])))."',".
  "Pattern='".mysqli_real_escape_string($db,str_replace("\n"," ",trim($_POST['pattern'])))."',".
  "Instruction='".mysqli_real_escape_string($db,trim($_POST['instruction']))."',".
  "AudioComment='".mysqli_real_escape_string($db,str_replace("\n"," ",trim($_POST['audiocomment'])))."'".
  " WHERE SongID=$sid LIMIT 1";
  if (!$result = mysqli_query($db,$sql)) {
    $err = "SQL Error ".mysqli_errno($db).": ".mysqli_error($db);
    if ($isAjax) { header('Content-Type: application/json'); die(json_encode(['success'=>false,'error'=>$err])); }
    echo("<b>$err</b><br>($sql)");
    exit;
  }
  if (!$isAjax && mysqli_affected_rows($db) > 0) {
    echo _('The song was updated.')."<br>";
  }
} else {
  $sql = "INSERT INTO song (Title,OrigTitle,Composer,Copyright,SongKey,".
  "Tempo,Source,Lyrics,Pattern,Instruction,AudioComment) VALUES (".
  "'".mysqli_real_escape_string($db,str_replace("\n"," ",trim($_POST['title'])))."',".
  "'".mysqli_real_escape_string($db,str_replace("\n"," ",trim($_POST['origtitle'])))."',".
  "'".mysqli_real_escape_string($db,str_replace("\n"," ",trim($_POST['composer'])))."',".
  "'".mysqli_real_escape_string($db,str_replace("\n"," ",trim($_POST['copyright'])))."',".
  "'".mysqli_real_escape_string($db,trim($_POST['songkey']))."',".
  "'".mysqli_real_escape_string($db,trim($_POST['tempo']))."',".
  "'".mysqli_real_escape_string($db,trim($_POST['source']))."',".
  "'".mysqli_real_escape_string($db,str_replace(chr(0x2019),"'",trim($_POST['lyrics'])))."',".
  "'".mysqli_real_escape_string($db,str_replace("\n"," ",trim($_POST['pattern'])))."',".
  "'".mysqli_real_escape_string($db,trim($_POST['instruction']))."',".
  "'".mysqli_real_escape_string($db,str_replace("\n"," ",trim($_POST['audiocomment'])))."')";
  if (!$result = mysqli_query($db,$sql)) {
    $err = "SQL Error ".mysqli_errno($db).": ".mysqli_error($db);
    if ($isAjax) { header('Content-Type: application/json'); die(json_encode(['success'=>false,'error'=>$err])); }
    echo("<b>$err</b><br>($sql)");
    exit;
  }
  if (mysqli_affected_rows($db) > 0) {
    $sid = mysqli_insert_id($db);
    if (!$isAjax) echo _('The song was added.')."<br>";
  } else {
    if ($isAjax) { header('Content-Type: application/json'); die(json_encode(['success'=>false,'error'=>_('The song was not added for some reason.')])); }
    echo _('The song was not added for some reason.')."<br>";
    exit;
  }
}

if (is_uploaded_file($_FILES['audiofile']['tmp_name'])) {
  if (move_uploaded_file($_FILES['audiofile']['tmp_name'], CLIENT_PATH."/audio/s".$sid.".mp3")) {
    if (!$isAjax) echo _('File is valid, and was successfully uploaded.')."<br>";
    $sql = "UPDATE song SET Audio=1 WHERE SongID=$sid LIMIT 1";
    if (!$result = mysqli_query($db,$sql)) {
      $err = "SQL Error ".mysqli_errno($db).": ".mysqli_error($db);
      if ($isAjax) { header('Content-Type: application/json'); die(json_encode(['success'=>false,'error'=>$err])); }
      echo("<b>$err</b><br>($sql)");
      exit;
    }
  } else {
    if ($isAjax) { header('Content-Type: application/json'); die(json_encode(['success'=>false,'error'=>_('File upload failed.')])); }
    echo _('File upload failed.  Here\'s some debugging info:')."\n";
    print_r($_FILES);
    exit;
  }
}

if ($isAjax) {
  header('Content-Type: application/json');
  echo json_encode(['success' => true, 'sid' => $sid]);
  exit;
}

echo "<SCRIPT FOR=window EVENT=onload LANGUAGE=\"Javascript\">\n";
echo "window.location = \"song.php?sid=".$sid."\";\n";
echo "</SCRIPT>\n";

print_footer();
?>
