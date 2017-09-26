<?php
ini_set("max_execution_time", "360");
ini_set("max_input_time", "240");
ini_set("memory_limit", "8M");
ini_set("upload_max_filesize","7M");
//phpinfo();
//exit;
include("functions.php");
include("accesscontrol.php");

if ($sid) {
  $sql = "SELECT * FROM song WHERE SongID=$sid";
  if (!$result = mysqli_query($db,$sql)) {
    echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b> ($sql)");
    exit;
  }
  if (mysqli_num_rows($result) == 0) {
    echo("<b>Failed to find a record for SongID $sid.</b>");
    exit;
  }
  $rec = mysqli_fetch_object($result);
  print_header("Praise & Worship DB: Edit $rec->Title","#F8F8F8",1);
} else {
  print_header("Praise & Worship DB: New Entry","#F8F8F8",1);
}

?>
<script language="JavaScript">

mp3_regexp = /\.[Mm][Pp]3$/;

function validate() {
  f = document.editform;  //just an abbreviation
  f.edit.disable = true;  //to prevent double submit

  if (f.updated.value == 0) {
    alert("No info was modified.  If you want to exit this page, just use your BACK button.");
    return false;
  }
  if (f.title.value.length == 0) {
    alert("Please enter the title!");
    f.title.select();
    return false;
  }
  if ((f.audiofile.value) && (!mp3_regexp.test(f.audiofile.value))) {
    alert("Only MP3 files can be accepted for audio.");
    f.audiofile.value = "";
    return false;
  }
<?php
if (!$rec->Audio) {
  echo "  if ((!f.audiofile.value) && (f.audiocomment.value)) {\n";
  echo "    alert(\"Audio comments only make sense if there is an audio file to comment on.\");\n";
  echo "    f.audiofile.focus;\n";
  echo "    return false;\n";
  echo "  }\n";
}
?>

  //no more show-stoppers, so fill in some defaults if needed
  if (!f.origtitle.value) {
    f.origtitle.value = f.title.value;
  }

  f.edit.disabled=true;
  return true;  //everything is cool
}
</script>

<form name=editform enctype="multipart/form-data" method=POST action="do_edit.php" onsubmit="return validate();">
<input type=hidden name=sid value="<?php echo $sid; ?>">
<input type=hidden name=updated value=0>
<table border=0 cellspacing=0 cellpadding=5><tr><td nowrap>
  Title: <input name=title type=text size=50 maxlength=50 value="<?php echo $rec->Title; ?>"
  onchange="editform.updated.value=1;" style="ime-mode:auto;">
</td><td nowrap>
  Original Title: <input name=origtitle type=text size=40 maxlength=50 value="<?php echo $rec->OrigTitle; ?>"
  onchange="editform.updated.value=1;" style="ime-mode:auto;"><br>
  <div align=left><font color=red size=-2>(Fill in if song is translated)</div>
</td></tr></table>
<table border=0 cellspacing=0 cellpadding=5><tr><td>
Composer: <input name=composer type=text size=45 maxlength=80 value="<?php echo $rec->Composer; ?>"
    onchange="editform.updated.value=1;" style="ime-mode:auto;"><br>
Copyright: <input name=copyright type=text size=45 maxlength=80 value="<?php echo $rec->Copyright; ?>"
    onchange="editform.updated.value=1;" style="ime-mode:auto;"><br>
Key: <input name=songkey type=text size=10 maxlength=8 value="<?php echo $rec->SongKey; ?>"
    onchange="editform.updated.value=1;" style="ime-mode:disabled;">&nbsp;&nbsp;&nbsp;&nbsp;
Tempo: <select name=tempo size=1 onchange="editform.updated.value=1;">
<option></option>
<option value="Fast"<?php if ($rec->Tempo=="Fast") echo " selected"; ?> >Fast</option>
<option value="Medium"<?php if ($rec->Tempo=="Medium") echo " selected"; ?> >Medium</option>
<option value="Slow"<?php if ($rec->Tempo=="Slow") echo " selected"; ?> >Slow</option>
</select>
</td><td>
Source:<br><textarea rows=4 name=source cols=50
onchange="editform.updated.value=1;"><?php echo $rec->Source; ?></textarea></td>
</tr></table><br />
<div align="center"><table border=0 cellspacing=0 cellpadding=5><tr><td valign="top"> 
    <input type=submit value="  Save Changes  " name=edit><br />&nbsp;<br />
    Pattern for printing (e.g. ABABB; sections are divided by blank lines):<br><input name=pattern type=text size=30 maxlength=80 value="<?php echo $rec->Pattern; ?>"
    onchange="editform.updated.value=1;" style="ime-mode:disabled;"><br>
    Instructions for playing (intro, etc.; put [brackets] around pattern description and other text that is unneeded when full pattern is printed):<br><textarea rows=3 name=instruction cols=30
    onchange="editform.updated.value=1;"><?php echo $rec->Instruction; ?></textarea><br />&nbsp;<br />
    Upload audio file (MP3 format only, <span style="color:darkred;font-weight:bold">no larger than <?php echo ini_get("upload_max_filesize"); ?> file size or you will lose other data you have edited!</span>):<br>
    <input name=audiofile type=file size=35 onchange="editform.updated.value=1;"><br />
    <input type=hidden name=audio value="<?php echo $rec->Audio; ?>">
<?php if ($rec->Audio == "1") {
  echo "<font size=\"-2\">(This song already has audio uploaded, but you can replace it with a new file if you want.)</font><br />\n";
}
?>
    Audio Comment: <textarea rows=2 name=audiocomment cols=30
    onchange="editform.updated.value=1;"><?php echo $rec->AudioComment; ?></textarea>
  </td><td>
    <table border=0 cellspacing=0 cellpadding=0><tr><td valign=middle>Lyrics:&nbsp;&nbsp;</td>
    <td style="color:red;textsize:0.8em"><ul><li>To add chords, enclose them in [brackets] just before the syllable they are played with.</li><li>If there are parts that are only needed to print full pattern (like a chorus with a tag), precede them with a line with only hyphens (one or more - it doesn't matter how many), like "---".</li></ul></td>
    </tr></table>
    <textarea rows=14 name=lyrics cols=70
    onchange="editform.updated.value=1;"><?php echo $rec->Lyrics; ?></textarea></td></tr></table></div>
</form>

<?php
if ($sid) {
  mysqli_free_result($result);
}
print_footer();
?>
