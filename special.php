<!DOCTYPE HTML>
<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="Content-Script-Type" content="text/javascript">
<link rel="shortcut icon" type="image/x-icon" href="http://l4jp.com/oicsongs/favicon.ico">
<title>Praise & Worship DB: 026: Ancient of Days</title>
<STYLE TYPE="TEXT/CSS">
body,p,div,span,td,input,textarea {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 13px;
}
.lyrics {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 13px;
  margin-top: 0;
  margin-bottom: 0;
  text-indent: -30px;
  padding-left: 30px;
}
.chordlyrics {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 13px;
  margin: 6px 0 0 0;
  text-indent: -30px;
  padding-left: 30px;
}
.chordlyrics ruby {
  ruby-align: left;
}
.chordlyrics rt {
  font-size: 13px;
  font-weight: bold;
  color: #E00000;
  position: relative;
  width: 1px;
  top: 6px;
}
.smallspace {
  font-size: 9px;
  margin-bottom: 0;
  margin-top: 0;
  font-family: Arial, Helvetica, sans-serif;
}
</STYLE>
</head>
<table border=1 bordercolor=#000080 cellspacing=0 cellpadding=5 width=150><tr><td align=left>
<div class='chordlyrics'><ruby>Blessing&nbsp;<rt>A&nbsp;</rt></ruby><span nowrap><ruby>and ho<rt>Asus&nbsp;</rt></ruby><ruby>nor,<rt>A&nbsp;</rt></ruby></span>&nbsp;glory&nbsp;<span nowrap><ruby>and pow<rt>Asus&nbsp;</rt></ruby><ruby>er<rt>A&nbsp;</rt></ruby></div>

<div class='chordlyrics'><ruby><rt>A&nbsp;</rt>Blessing&nbsp;</ruby><span nowrap><ruby><rt>Asus&nbsp;</rt>and ho</ruby><ruby><rt>A&nbsp;</rt>nor,</ruby></span>&nbsp;glory&nbsp;<span nowrap><ruby><rt>Asus&nbsp;</rt>and pow</ruby><ruby><rt>A&nbsp;</rt>er</ruby></div>

<div class='chordlyrics'><ruby>Blessing&nbsp;<rt>A&nbsp;</rt></ruby><span nowrap><ruby>and ho<rt>Asus&nbsp;</rt></ruby><ruby>nor,<rt>A&nbsp;</rt></ruby></span>&nbsp;glory&nbsp;<span nowrap><ruby>and pow<rt>Asus&nbsp;</rt></ruby><ruby>er<rt>A&nbsp;</rt></ruby></div>

</td></tr></table>

<?php
/*include("functions.php");

$sql = "SELECT SongID, Title, OrigTitle,Tagged, Tempo FROM pw_song ORDER BY Tempo, OrigTitle";
if (!$result = mysql_query($sql)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b> ($sql)");
  exit;
}
$num = 0;
$format_num = "000";
$prevtitle = "";
while ($song = mysql_fetch_object($result)) {
  if ($song->Tagged == 1) {
    if ($song->OrigTitle != $prevtitle) {
      $num++;
    }
    $plaintitle = ereg_replace("^[0-9][0-9][0-9]: ","",$song->Title);  // removes old numbering
    $sql = "UPDATE pw_song SET Title='".sprintf("%'03.3d",$num).": ".addslashes($plaintitle)."' WHERE SongID=".$song->SongID;
    echo "I will do this SQL:<br>".$sql."<br>";
    if (!$upd = mysql_query($sql)) {
      echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b> ($sql)");
      exit;
    }
    $prevtitle = $song->OrigTitle;
  }
}*/
?>