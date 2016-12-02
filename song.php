<?php
include("functions.php");
include("accesscontrol.php");
$nav_bar = 1;

if (!$_GET['sid'] && !$_POST['sid']) {
  echo "SongID not passed.";
  exit;
}

//Song to be tagged or untagged
if (isset($_GET['tag'])) {
  if (!$result = mysql_query("UPDATE pw_song SET Tagged=1 WHERE SongID={$_GET['sid']}")) {
    echo("<b>SQL Error ".mysql_errno()." while tagging song: ".mysql_error()."</b>");
    exit;
  }
} elseif (isset($_GET['untag'])) {
  if (!$result = mysql_query("UPDATE pw_song SET Tagged=0 WHERE SongID={$_GET['sid']}")) {
    echo("<b>SQL Error ".mysql_errno()." while untagging song: ".mysql_error()."</b>");
    exit;
  }
}

//Keyword changes
if ($newkeyword) {
  if (!$result = mysql_query("SELECT k.KeywordID, k.Keyword, s.SongID ".
      "FROM pw_keyword k LEFT JOIN pw_songkey s ON k.KeywordID=s.KeywordID and s.SongID={$_POST['sid']} ".
      "ORDER BY case when s.SongID is null then 1 else 0 end, k.Keyword")) {
    echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b>");
  } else {
    while ($row = mysql_fetch_object($result)) {
      $keyid = $row->KeywordID;
      if ($row->SongID && !($_POST[$keyid])) {
        if (!mysql_query("DELETE from pw_songkey WHERE KeywordID=$keyid and SongID={$_POST['sid']}")) {
          echo("<b>SQL Error ".mysql_errno()." during DELETE FROM pw_songkey: ".mysql_error()."</b>");
        }
      } elseif (!$row->SongID && ($_POST[$keyid])) {
        if (!mysql_query("INSERT INTO pw_songkey(KeywordID,SongID) VALUES($keyid,{$_POST['sid']})")) {
          echo("<b>SQL Error ".mysql_errno()." during INSERT INTO pw_songkey: ".mysql_error()."</b>");
        }
      }
    }
  }
  header("Location: song.php?sid=".$_POST['sid']);
  exit;
}

if (!$result = mysql_query("SELECT * FROM pw_song WHERE SongID={$_GET['sid']}")) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b>");
  exit;
}
if (mysql_num_rows($result) == 0) {
  echo("<b>Failed to find a record for SongID {$_GET['sid']}.</b>");
  exit;
}
$song = mysql_fetch_object($result);
$haschords = preg_match('/\[[^rR]/u',$song->Lyrics);
$hasromaji = preg_match('/\[r\]/iu',$song->Lyrics);
header1("Song: ".$song->Title);
?>
<style>
.lyrics {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 13px;
  margin-top: 0;
  margin-bottom: 0;
  text-indent: -30px;
  padding-left: 30px;
  white-space: pre-wrap;
}
.chordlyrics {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 13px;
  margin: 6px 0 0 0;
  white-space: pre-wrap;
  *margin-top: 0;  /* IE spreads out too much */
  text-indent: -30px;
  padding-left: 30px;
/* Note: Hanging indent messes up Ruby in IE */
  *text-indent: 0px;
  *padding-left: 0px;
}
.chordlyrics ruby {
  ruby-align: start;
  *ruby-align: left; /* For IE */
}
.chordlyrics rt span {
  font-size: 13px;
  font-weight: bold;
  color: #E00000;
  position: relative;
  width: 1px;
  top: 2px;
}
.smallspace {
  font-size: 9px;
  margin-bottom: 0;
  margin-top: 0;
  font-family: Arial, Helvetica, sans-serif;
}
<?
if ($_GET['chords']) {
  echo ".chordshidden { display:none; }\n";
} else {
  echo ".chordlyrics, .chords { display:none; }\n";
}
if (!$_GET['romaji']) {
  echo ".romaji { display:none; }\n";
}
?>
form#keywordform { padding:0; margin:0; }
div#keywordsection { border:2px solid gray; padding:5px; text-align:center; }
div.checkboxes { border-top:2px solid gray; padding:5px; margin-top:3px; text-align:left; } 
label.keyword { float:left; margin-right:2em; }
label.keyword span { white-space:nowrap; }
</style>

<script type="text/JavaScript" src="js/jquery.min.js"></script>
<script type="text/Javascript">
$(document).ready(function(){
  $("#playaudio").click(function(e) {
    if (MP3support()) {
      e.preventDefault();
      var audioPlayer = new Audio();
      audioPlayer.controls="controls";
      audioPlayer.src="sendaudio.php?sid=<? echo $_GET['sid']; ?>";
      audioPlayer.autoplay="true";
      document.getElementById("audioarea").appendChild(audioPlayer);
    } else {
      html = '<embed src="sendaudio.php?sid=<? echo $_GET['sid']; ?>" controls="console" height="20"'; 
      html += ' vspace="0" hspace="0" border="0" align="top" autoplay=true';
      html += ' pluginspage="http://www.apple.com/quicktime/download/?video/quicktime"></embed>\n';
      $("#audioarea").html(html);
//       AudioPlayer.embed("audioplayer", {soundFile: "audio.mp3"});$("#audioarea").html("No can do...");
    }
  });
  $('#showchords, #showromaji').change(function() {
    if ($('#showchords').prop("checked") && $('#showromaji').prop("checked")) {
      $('.chordlyrics, .lyrics, .chords').show();
      $('.chordshidden').hide();
    } else if ($('#showchords').prop("checked") && !$('#showromaji').prop("checked")) {
      $('.chordlyrics, .lyrics, .chords').show();
      $('.chordshidden, .romaji').hide();
    } else if (!$('#showchords').prop("checked") && $('#showromaji').prop("checked")) {
      $('.lyrics').show();
      $('.chordlyrics, .chords').hide();
    } else {  // no chords or romaji
      $('.lyrics').show();
      $('.chordlyrics, .chords, .romaji').hide();
    }
  });
});

function MP3support() {
  var a = document.createElement('audio');
  return !!(a.canPlayType && a.canPlayType('audio/mpeg;').replace(/no/, ''));
}

function play_audio(url) {
  var oWin = window.open(url,"audiowin","height=80,width=200,scrollbars=yes");
  if (oWin==null || typeof(oWin)=="undefined") {
    alert("The audio would play in a small window, but you appear to have popups blocked.  Many features of this database rely on popups, so please allow popups for this site (I promise, no annoying ads!)");
  }
}
</script>
<?
header2(1);
?>
<table width="735" border="0" cellpadding="0" cellspacing="0"><tr><td>
  <table width="730" border="0" cellpadding="0" cellspacing="0"><tr><td align="center" valign="middle">
    <h1><font color="#0000C0"><?=$song->Title?></font></h1>
  </td><td width="132" align="center" valign="middle">
    <a href="song.php?sid=<?=$_GET['sid']?>&<?=($song->Tagged?'untag':'tag')?>=1">
      <img src="graphics/<?=($song->Tagged?'tagged':'not_tagged')?>.gif" height="52" width="132" border="0">
    </a><br>
    <span style="fontsize:0.8em; color:<?=($song->Tagged?'black':'red')?>">(Click image to <?=($song->Tagged?'untag':'tag')?>)</span>
  </td></tr></table>
</td></tr><tr><td>
  <table border="0" cellspacing="0" cellpadding="5"><tr><td valign="top">
<? if ($haschords || $hasromaji) {
  echo '    <div style="font-weight:bold">Show:';
  if ($haschords) echo '&nbsp;&nbsp;<label><input type="checkbox" id="showchords" '.($_GET['chords']?' checked':'').'>Chords</label>';
  if ($hasromaji) echo '&nbsp;&nbsp;<label><input type="checkbox" id="showromaji" '.($_GET['romaji']?' checked':'').'>Romaji</label>';
  echo '</div>';
}
?>
    <div style="border: 2px solid #000080; width:350px; padding:5px;">
<? 
$lines = split("\r\n|\n\r\|\n|\r", $song->Lyrics);
$lyrics = "";
foreach ($lines as $line) {
  $romajiclass = preg_match('/\[r\]/iu',$line) ? ' romaji' : '';
  $line = preg_replace('/\[r\]/iu','',$line);
  if ($line == "") {  //blank line
    echo "      <div class='smallspace".$romajiclass."'>&nbsp;</div>\n";
  } elseif (strpos($line, "[")===FALSE) {  //no chords in this line
    echo '      <div class="lyrics'.$romajiclass.'">'.$line."</div>\n";
  } elseif (substr_count($line,"[")==1 && substr($line,0,1)=="[" && substr($line,strlen($line),1)=="]") {  //chords only in this line
    echo '      <div class="chords">'.substr($line,1,strlen($line)-2)."</div>\n";
  } else {
    echo '      <div class="chordlyrics'.$romajiclass.'">'.chordsToRuby($line)."</div>\n";
    echo '      <div class="lyrics chordshidden'.$romajiclass.'">'.preg_replace('/\[[^\[]*\]/u','',$line)."</div>\n";
  }
}
?>
    </div>  
  
<?
/*} else {  //no chords
  echo "<font color=#0000C0><b>Lyrics:</b></font>";
  if (preg_match('/\[[^rR]/u',$song->Lyrics)) {
    echo "&nbsp;&nbsp;<a href=\"song.php?sid={$_GET['sid']}&chords=yes\"><font color=#E00000>";
    echo "<b>(Click here to show chords)</b></font></a>";
  }
  echo "<br>\n<table border=1 bordercolor=#000080 cellspacing=0 cellpadding=5 width=350>";
  $lyrics = ereg_replace("\[[^\[]*\]","",$song->Lyrics);
  $lyrics = str_replace("  ","&nbsp;&nbsp;",$lyrics);
  $lyrics = ereg_replace("\r\n|\n\r","</div><div class=lyrics>",$lyrics); //catch occurrences of the pair (Win)
  $lyrics = ereg_replace("[\n\r]","</div><div class=lyrics>",$lyrics); //catch occurrences of either (Unix/Mac)
  $lyrics = "<div class=lyrics>" . $lyrics . "</div>";
  $lyrics = str_replace("<div class=lyrics></div>","<div class=smallspace>&nbsp;</div>",$lyrics);
  $lyrics = str_replace("</div>","</div>\n",$lyrics);
  echo "<tr><td align=left>{$lyrics}</td></tr></table>\n";
}*/
echo "  <td>\n<td valign=top><b>";
if ($song->Title != $song->OrigTitle) echo ("    Original Title: $song->OrigTitle<br>&nbsp;<br>\n");
echo "    Key: <font color=#00C000>".($song->SongKey ? $song->SongKey : "?")."</font>";
if ($song->Tempo) echo (" &nbsp;&nbsp;Tempo: <font color=#C00000>$song->Tempo</font>");
echo "<br>\n";
if ($song->Composer) echo ("    Composer: $song->Composer");
if ($song->Copyright) echo ("<br>\n    Copyright: $song->Copyright");
echo "</b>";
if ($song->Source) {
  echo "<br>\n    <b>Source(s):</b>";
  echo "<table border=0 cellspacing=0 cellpadding=0><tr><td width=20></td>";
  echo "<td align=left>".url2link(d2h($song->Source))."</td></tr></table>\n";
}
if ($song->Pattern) {
  echo "<br>\n    <b>Pattern of Stanzas:&nbsp;</b>$song->Pattern\n";
}
if ($song->Instruction) {
  echo "<br>\n    <b>Instruction (intro, etc.):&nbsp;</b>$song->Instruction\n";
}
if ($song->Audio) {
  echo "    <div><b>Audio for learning:&nbsp;</b><a id=\"playaudio\" href=\"#\">Click to Listen</a></div>";
  echo "<div id=\"audioarea\"></div>\n";
  if ($song->AudioComment) {
    echo "<br>\n    <b>Comment about audio recording:</b> ".$song->AudioComment."\n";
  }
}
if ($_SESSION['pw_admin'] > 0)  echo "<br>\n    <h2><a href=\"edit.php?sid=".$_GET['sid']."\">Edit This Record</a></h2>";

echo "  </td></tr></table>\n";

// ********** KEYWORDS **********

echo "<form id=\"keywordsform\" action=\"song.php\" method=\"POST\"><div id=\"keywordsection\">";
echo "<h3 style=\"margin-bottom:0; color:#0000C0;\"><b><i>Keywords</i></b>";
if ($_SESSION['pw_admin'] > 0) {
  echo "&nbsp;&nbsp;&nbsp;&nbsp;<input type=submit value=\"Save Keyword Changes\" name=newkeyword>";
}
echo "<input type=hidden name=sid value={$_GET['sid']}></h3>";

if (!$result = mysql_query("SELECT k.KeywordID, k.Keyword, s.SongID ".
    "FROM pw_keyword k LEFT JOIN pw_songkey s ON k.KeywordID=s.KeywordID and s.SongID={$_GET['sid']} ".
    "ORDER BY case when s.SongID is null then 1 else 0 end, k.Keyword")) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b>");
} else {
  echo "<div class=\"checkboxes\">\n";
  while ($row = mysql_fetch_object($result)) {
    if (!($row->SongID)) {
      if ($_SESSION['pw_admin'] > 0) {
        echo "<div class=\"clear\"></div></div><div class=\"checkboxes\">\n<label class=\"keyword\"><span><input type=checkbox name=$row->KeywordID>$row->Keyword</span></label>\n";
      }
      break;
    }
    echo "<label class=\"keyword\"><span><input type=checkbox name=\"".$row->KeywordID."\" checked>".$row->Keyword."</span></label>\n";
  }
  if ($_SESSION['pw_admin'] > 0) {
    while ($row = mysql_fetch_object($result)) {
      echo "<label class=\"keyword\"><span><input type=checkbox name=\"".$row->KeywordID."\">".$row->Keyword."</span></label>\n";
    }
  }
  echo "<div class=\"clear\"></div></div>";
}
echo "</td></tr></table></form>";

// ********** USAGE **********

$sql = "SELECT e.Event, min(u.UseDate) AS first, max(u.UseDate) AS last,".
    " COUNT(u.UseDate) AS times, e.Remarks FROM pw_event e, pw_usage u WHERE e.EventID = u.EventID".
    " AND u.SongID = ".$_GET['sid']." GROUP BY e.Event ORDER BY last";
if (!$result = mysql_query($sql)) {
  echo ("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
} elseif (mysql_num_rows($result) == 0) {
  echo ("<p>No usage records.<br>&nbsp;</p>");
} else {
  echo "<table width=735 border=2 cellpadding=5 cellspacing=0 bordercolor=#0000C0 bgcolor=#F0F0FF>";
  echo "<tr><td align=center><h3 style=\"margin-bottom:0; color:#0000C0;\"><b><i>Usage History</i></b></h3>";
  echo ("<table width=730 border=1 cellspacing=0 cellpadding=2 bgcolor=#FFFFFF>");
  while ($row = mysql_fetch_object($result)) {
    echo ("<tr><td nowrap>".$row->Event."</td><td nowrap>");
    if ($row->first == $row->last) {
      echo ($row->first);
    } else {
      echo ($row->first." to<br>".$row->last." (".$row->times."x)");
    }
    echo ("</td><td>".$row->Remarks."&nbsp;</td></tr>");
  }
  echo "  </table></table>&nbsp;<br>";
}

print_footer();
?>
