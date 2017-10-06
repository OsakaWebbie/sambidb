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
  if (!$result = mysqli_query($db,"UPDATE song SET Tagged=1 WHERE SongID={$_GET['sid']}")) {
    echo("<b>SQL Error ".mysqli_errno($db)." while tagging song: ".mysqli_error($db)."</b>");
    exit;
  }
} elseif (isset($_GET['untag'])) {
  if (!$result = mysqli_query($db,"UPDATE song SET Tagged=0 WHERE SongID={$_GET['sid']}")) {
    echo("<b>SQL Error ".mysqli_errno($db)." while untagging song: ".mysqli_error($db)."</b>");
    exit;
  }
}

//Keyword changes
if (isset($_POST['newkeyword'])) {
  if (!$result = mysqli_query($db,"SELECT k.KeywordID, k.Keyword, s.SongID ".
      "FROM keyword k LEFT JOIN songkey s ON k.KeywordID=s.KeywordID and s.SongID={$_POST['sid']} ".
      "ORDER BY case when s.SongID is null then 1 else 0 end, k.Keyword")) {
    echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b>");
  } else {
    while ($row = mysqli_fetch_object($result)) {
      $keyid = $row->KeywordID;
      if ($row->SongID && !($_POST[$keyid])) {
        if (!mysqli_query($db,"DELETE from songkey WHERE KeywordID=$keyid and SongID={$_POST['sid']}")) {
          echo("<b>SQL Error ".mysqli_errno($db)." during DELETE FROM songkey: ".mysqli_error($db)."</b>");
        }
      } elseif (!$row->SongID && ($_POST[$keyid])) {
        if (!mysqli_query($db,"INSERT INTO songkey(KeywordID,SongID) VALUES($keyid,{$_POST['sid']})")) {
          echo("<b>SQL Error ".mysqli_errno($db)." during INSERT INTO songkey: ".mysqli_error($db)."</b>");
        }
      }
    }
  }
  header("Location: song.php?sid=".$_POST['sid']);
  exit;
}

if (!$result = mysqli_query($db,"SELECT * FROM song WHERE SongID={$_GET['sid']}")) {
  echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b>");
  exit;
}
if (mysqli_num_rows($result) == 0) {
  echo("<b>Failed to find a record for SongID {$_GET['sid']}.</b>");
  exit;
}
$song = mysqli_fetch_object($result);
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
<?php
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
label.keyword { margin-right:2em; }
label.keyword span { white-space:nowrap; }

#audioplayer { vertical-align:middle; }
#audioloop {
  font-size: 20px;
  cursor: pointer;
  vertical-align: middle;
}
#audioloop.enabled { position: relative; }
#audioloop.enabled:after {
  content: "✔";
  color: red;
  font-weight: bold;
  position: absolute;
  right: -7px;
}
</style>

<script type="text/JavaScript" src="js/jquery.min.js"></script>
<script type="text/Javascript">
$(document).ready(function(){
  $('audio').bind('contextmenu',function() { return false; });

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
  $("#audioloop").click(function(){
    var player = $("#audioplayer")[0];
    player.loop =!player.loop;
    $(this).toggleClass('enabled', player.loop);
  });
});
</script>
<?php
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
<?php if ($haschords || $hasromaji) {
  echo '    <div style="font-weight:bold">Show:';
  if ($haschords) echo '&nbsp;&nbsp;<label><input type="checkbox" id="showchords" '.($_GET['chords']?' checked':'').'>Chords</label>';
  if ($hasromaji) echo '&nbsp;&nbsp;<label><input type="checkbox" id="showromaji" '.($_GET['romaji']?' checked':'').'>Romaji</label>';
  echo '</div>';
}
?>
    <div style="border: 2px solid #000080; width:350px; padding:5px;">
<?php
$lines = preg_split("/\r\n|\n\r\|\n|\r/", $song->Lyrics);
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
  
<?php
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
echo "    Key: <span style='color:#00C000'>".($song->SongKey ? $song->SongKey : "?")."</span>";
if ($song->Tempo) echo (" &nbsp;&nbsp;Tempo: <span style='color:#C00000'>$song->Tempo</span>");
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
  ?>
    <br>
    <div><b>Audio for learning:</b><br>
    <audio id="audioplayer" controls controlsList="nodownload">
      <source src="sendaudio.php?sid=<?=$_GET['sid']?>">
    </audio>
    <span id="audioloop">&#x1f501;</span>
    </div>
  <?php
  if ($song->AudioComment) {
    echo "<br>\n    <b>Comment about audio recording:</b> ".$song->AudioComment."\n";
  }
}
if ($_SESSION['admin'] > 0)  echo "<br>\n    <h2><a href=\"edit.php?sid=".$_GET['sid']."\">Edit This Record</a></h2>";

echo "  </td></tr></table>\n";

// ********** KEYWORDS **********

echo "<form id=\"keywordsform\" action=\"song.php\" method=\"POST\"><div id=\"keywordsection\">";
echo "<h3 style=\"margin-bottom:0; color:#0000C0;\"><b><i>Keywords</i></b>";
if ($_SESSION['admin'] > 0) {
  echo "&nbsp;&nbsp;&nbsp;&nbsp;<input type=submit value=\"Save Keyword Changes\" name=newkeyword>";
}
echo "<input type=hidden name=sid value={$_GET['sid']}></h3>";

if (!$result = mysqli_query($db,"SELECT k.KeywordID, k.Keyword, s.SongID ".
    "FROM keyword k LEFT JOIN songkey s ON k.KeywordID=s.KeywordID and s.SongID={$_GET['sid']} ".
    "ORDER BY case when s.SongID is null then 1 else 0 end, k.Keyword")) {
  echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b>");
} else {
  echo "<div class=\"checkboxes\">\n";
  while ($row = mysqli_fetch_object($result)) {
    if (!($row->SongID)) {
      if ($_SESSION['admin'] > 0) {
        echo "<div class=\"clear\"></div></div><div class=\"checkboxes\">\n<label class=\"keyword\"><span><input type=checkbox name=$row->KeywordID>$row->Keyword</span></label>\n";
      }
      break;
    }
    echo "<label class=\"keyword\"><span><input type=checkbox name=\"".$row->KeywordID."\" checked>".$row->Keyword."</span></label>\n";
  }
  if ($_SESSION['admin'] > 0) {
    while ($row = mysqli_fetch_object($result)) {
      echo "<label class=\"keyword\"><span><input type=checkbox name=\"".$row->KeywordID."\">".$row->Keyword."</span></label>\n";
    }
  }
  echo "<div class=\"clear\"></div></div>";
}
echo "</td></tr></table></form>";

// ********** USAGE **********

$sql = "SELECT e.Event, min(u.UseDate) AS first, max(u.UseDate) AS last,".
    " COUNT(u.UseDate) AS times, e.Remarks FROM event e, history u WHERE e.EventID = u.EventID".
    " AND u.SongID = ".$_GET['sid']." GROUP BY e.Event ORDER BY last";
if (!$result = mysqli_query($db,$sql)) {
  echo ("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
} elseif (mysqli_num_rows($result) == 0) {
  echo ("<p>No history records.<br>&nbsp;</p>");
} else {
  echo "<table width=735 border=2 cellpadding=5 cellspacing=0 bordercolor=#0000C0 bgcolor=#F0F0FF>";
  echo "<tr><td align=center><h3 style=\"margin-bottom:0; color:#0000C0;\"><b><i>Usage History</i></b></h3>";
  echo ("<table width=730 border=1 cellspacing=0 cellpadding=2 bgcolor=#FFFFFF>");
  while ($row = mysqli_fetch_object($result)) {
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
