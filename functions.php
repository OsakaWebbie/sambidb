<?php
error_reporting(E_ERROR);

// Newer version in two parts - this first part takes title
function header1($title) {
?>
<!DOCTYPE HTML>
<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=<?=$_SESSION['pw_charset']?>">
<meta http-equiv="Content-Script-Type" content="text/javascript">
<link rel="apple-touch-icon" sizes="57x57" href="favicons/apple-icon-57x57.png">
<link rel="apple-touch-icon" sizes="60x60" href="favicons/apple-icon-60x60.png">
<link rel="apple-touch-icon" sizes="72x72" href="favicons/apple-icon-72x72.png">
<link rel="apple-touch-icon" sizes="76x76" href="favicons/apple-icon-76x76.png">
<link rel="apple-touch-icon" sizes="114x114" href="favicons/apple-icon-114x114.png">
<link rel="apple-touch-icon" sizes="120x120" href="favicons/apple-icon-120x120.png">
<link rel="apple-touch-icon" sizes="144x144" href="favicons/apple-icon-144x144.png">
<link rel="apple-touch-icon" sizes="152x152" href="favicons/apple-icon-152x152.png">
<link rel="apple-touch-icon" sizes="180x180" href="favicons/apple-icon-180x180.png">
<link rel="icon" type="image/png" sizes="192x192"  href="favicons/android-icon-192x192.png">
<link rel="icon" type="image/png" sizes="32x32" href="favicons/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="96x96" href="favicons/favicon-96x96.png">
<link rel="icon" type="image/png" sizes="16x16" href="favicons/favicon-16x16.png">
<link rel="manifest" href="/manifest.json">
<meta name="msapplication-TileColor" content="#ffffff">
<meta name="msapplication-TileImage" content="/ms-icon-144x144.png">
<meta name="theme-color" content="#ffffff">
<link rel="shortcut icon" type="image/x-icon" href="favicons/favicon.ico">
<title><?=$title?></title>
<style>
body,p,div,span,td,input,textarea {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 13px;
}
div.left { float:left; }
div.right { float:right; }
div.clear { clear:both; line-height:0; margin:0; padding:0; }
#layouttable { text-align:left; }
input.text {
  background-color: FFFFFF;
  border-color: 666666;
  border-style: solid;
  border-width: 1px;
}
.debug { display:none; }
-->
</style>
<?
}

function header2($nav,$color="#FFFFFF") {
  echo "</head>\n";
  echo "<body bgcolor=\"$color\"><div align=\"center\">\n";
  if ($nav) {
    echo "<table id=\"layouttable\" border=0 cellpadding=0 cellspacing=0 width=750>";
    print_nav();
  } else {
    echo "<table id=\"layouttable\" border=0 cellpadding=0 cellspacing=0>";
  }
  echo "<tr><td>\n";
}

function print_nav() {
  echo "<tr><td height=30 align=center valign=middle bgcolor=black><font face=arial size=2 color=white>";
  echo "<a href=\"index.php\" target=\"_top\"><font color=white>Top (Search)</font></a>&nbsp;&nbsp;|&nbsp;";
  if ($_SESSION['pw_admin'] > 0) {
    echo "<a href=\"edit.php\" target=\"_top\"><font color=white>New Song</font></a>&nbsp;&nbsp;|&nbsp;";
  }
  echo "<a href=\"multiselect.php\" target=\"_top\"><font color=white>Tagged Song Actions</font></a>&nbsp;&nbsp;|&nbsp;";
  echo "<a href=\"event_use.php\" target=\"_top\"><font color=white>Song Use Chart</font></a>&nbsp;&nbsp;|&nbsp;";
  echo "<a href=\"maintenance.php\" target=\"_top\"><font color=white>DB Maintenance</font></a>";
  if ($_SESSION['pw_admin'] == 2) {
    echo "&nbsp;&nbsp;|&nbsp;<a href=\"sqlquery.php\" target=\"_top\"><font color=white>(Freeform SQL)</font></a>";
  }
  echo "&nbsp;&nbsp;|&nbsp;<a href=\"index.php?logout=1\" target=\"_top\"><font color=white>Log Out</font></a>";
  echo "</font></td></tr>";
}

// For the older pages
function print_header($title,$color,$nav) {
  header1($title);
  header2($nav,$color);
}

// Function print_footer: sends final html
function print_footer() {
  global $nav_bar;
  echo "</td></tr>";
  if ($nav_bar) {
    print_nav();
  }
  echo "</table></body></html>";
}

// Function showfile: for "including" a non-PHP file (HTML, JS, etc.)
function showfile($filename) {
  if (!$file = fopen($filename,"r")) {
    echo "<br><font color=red>Could not open file '$filename'!</font><br>";
  } else {
    fpassthru($file);
  }
}

// Function db2table: prepares text from DB for display in table cell (or plain html text)
function db2table($text) {
//  $text = ereg_replace("\r\n|\n|\r","<br>",$text);
//  $text = ereg_replace("<br> ","<br>&nbsp;",$text);
  $text = ereg_replace(" ","&nbsp;",$text);
  return nl2br($text);
}

function d2h($text) {
  return nl2br(htmlspecialchars($text, ENT_QUOTES, mb_internal_encoding()));
}

// Function post2form: prepares text from DB for display in form
function post2form($text) {
//  $text = ereg_replace("\'","'",$text);
  return stripslashes($text);
}

function escape_quotes($text) {
  $text = ereg_replace("\"","\\\"",$text);
  return $text;
}

// Function readable_name: returns name with furigana if Japanese, without if not
function readable_name($name,$furigana) {
  if (ereg("^[a-zA-Z]",$name)) {  //name is in English letters
    return $name;
  } else {
    return $name." (".$furigana.")";
  }
}

// Function readable_name_2line: returns name with furigana on next line if Japanese, without if not
function readable_name_2line($name,$furigana) {
  if (ereg("^[a-zA-Z]",$name)) {  //name is in English letters
    return $name;
  } else {
    return $name."<br>(".$furigana.")";
  }
}

// Function age: takes birthdate in the form YYYY-MM-DD as argument, returns age
function age($birthdate) {
  $ba = split("-",$birthdate);
  $ta = split("-",date("Y-m-d",mktime(gmdate("H")+9)));
  $age = $ta[0] - $ba[0];
  if (($ba[1] > $ta[1]) || (($ba[1] == $ta[1]) && ($ba[2] > $ta[2]))) --$age;
  return $age;
}

function url2link($text) {
  return preg_replace('@(https?://([-\w\.]+)+(:\d+)?(/([-\w/_\.]*(\?[^\s<]+)?)?)?)@', '<a href="$1">$1</a>', $text);
}

//Function parseLyrics: takes in lyrics (in the format from the database) and parses to use ruby
function chordsToRuby($unfmt){
  //For Firefox, preset the parameters for the XHTML Ruby Support plugin
  /*$ruby = (strpos($_SERVER['HTTP_USER_AGENT'],'Firefox'))?"<ruby moz-ruby-align=\"start\" ".
  "moz-ruby-line-edge=\"none\" style=\"vertical-align: -2px ! important;\" moz-ruby-reformed=\"done\" ".
  "moz-ruby-mode=\"block\" moz-ruby-parsed=\"done\">" : "<ruby>";*/
  $ruby = "<ruby>";
  $fmt = "";
  $insideword = FALSE;
  $oldrb = $oldrt = "";
  //Note: Strtok ignores empty sections, so I put the * on the front to make sure it stops at the first chord
  $nonruby = substr(strtok("*".$unfmt,"["),1);  //lyrics text preceding the next <ruby> tag
  while ($rbpair = strtok("[")) {  //keep walking through the rest of the line
    list($rt, $rb) = explode("]", $rbpair);
    if ($rb=="") $rb="&nbsp;";
    //if ASCII and no spaces on either side of the chord, then treat as inside a word (needs nowrap span)
    if (!$insideword && mb_strlen($rb)==strlen($rb) && mb_substr($rb,0,1)!=" ") {
      if ($nonruby && mb_substr($nonruby,mb_strlen($nonruby)-1)!=" ") {
        $nonruby = substr_replace($nonruby,"<span nowrap>",(strrpos(" ",$nonruby)?strrpos(" ",$nonruby):0),0);
        $insideword = TRUE;
      } elseif ($oldrb && mb_substr($oldrb,mb_strlen($oldrb)-1)!=" ") {
        $fmt .= "<span nowrap>";
        $insideword = TRUE;
        //if the chords are extra long (and it's not the end of the line), add a hyphen
        if (($rbcounter+1 < $rblimit) && ($rb!="&nbsp;")) $oldrb .= "-";
      }
    }
    //add old stuff with markup to formatted string (non-breaking space is to spread chords out - margin-right not honored in FF extension)
    if ($oldrb) $fmt .= $ruby."<rb>$oldrb</rb><rt><span class='chord'>$oldrt&nbsp;</span></rt></ruby>";
    if ($insideword && (mb_substr($oldrb,mb_strlen($oldrb))==" " || mb_strpos($nonruby," ")==" ")) {  //found a space, so let it wrap
      $fmt .= "</span>";
      $insideword = FALSE;
    }
    $fmt .= $nonruby;
    //if inside a word, only ASCII, and no spaces, we can't finish the nowrap, so we don't need to do the fancy calculation
    if ($insideword && strlen($rb)==mb_strlen($rb) && strpos($rb," ")===FALSE) {
      $oldrb = $rb;
      $nonruby = "";
    } else {
      // figure out about how much of the lyrics piece should be inside <ruby>, to prevent overlap but still allow wrapping
      $rblimit = strlen($rt);
      $rbcounter = 0.0;
      for ($index=0; $index < mb_strlen($rb); $index++)  {
        $thischar = mb_substr($rb, $index, 1);
        if (strlen($this_char) > 1) {  //multi-byte character
          $rbcounter += 1.0;  //adjust this value to balance between allowing wrapping and avoiding unneeded spacing
          if ($rbcounter >= $rblimit) {
            break;
          }
        } else {  //single-byte character
          $rbcounter += 0.4;  //adjust this value to balance between allowing wrapping and avoiding unneeded spacing
          if ($rbcounter >= $rblimit && $thischar == " ") {
            break;
          }
        }
      } //end of for loop that determines end of <rb>
      if ($insideword && $thischar==" ") {  //keep the space out of the nowrap
        $oldrb = mb_substr($rb,0,$index);
        $nonruby = mb_substr($rb,$index);
      } else {
        $oldrb = mb_substr($rb,0,$index+1);
        $nonruby = mb_substr($rb,$index+1);
      }
    }
    $oldrt = $rt;
  }  //end of while loop that goes through each ruby group in the line
  //add final stuff left over (same as code inside for loop)
  if ($oldrb) $fmt .= "<ruby><rb>$oldrb</rb><rt><span class='chord'>$oldrt&nbsp;</span></rt></ruby>";
  if ($insideword && (mb_substr($oldrb,mb_strlen($oldrb))==" " || mb_strpos($nonruby," ")==" ")) {  //found a space, so let it wrap
    $fmt .= "</span>";
    $insideword = FALSE;
  }
  $fmt .= $nonruby;
  //other stuff that we can do to the whole thing at once with regexp
  $fmt = str_replace("  ","&nbsp;&nbsp;",$fmt);
  $fmt = str_replace("> ",">&nbsp;",$fmt);
  $fmt = str_replace(" <","&nbsp;<",$fmt);
  return $fmt;
}

// Call file to connect to database
include("main_connect.php");

/* for certain versions of SQL, this next is needed, but others would give an error, */
/* so error checking is commented out */
if (!$result = mysql_query("set names 'utf8'")) {
//  echo "<b>SQL Error trying to set names to utf8: ".mysql_errno().": ".mysql_error()."</b>";
//  exit;
}
/* Set internal character encoding to UTF-8 */
mb_internal_encoding("UTF-8");

?>
