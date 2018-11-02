<?php
error_reporting(E_ERROR);

// Newer version in two parts - this first part takes title
function header1($title='') {
?>
<!DOCTYPE html>
<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
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
<?php
}

function header2($nav=0, $color="#FFFFFF", $jquery=0, $tablelayout=1) {
  if ($jquery) {
    echo '<link rel="stylesheet" type="text/css" href="//code.jquery.com/ui/1.12.1/themes/cupertino/jquery-ui.css">'."\n";
    echo '<script src="https://code.jquery.com/jquery-3.2.1.min.js" type="text/javascript"></script>'."\n";
    echo '<script src="//code.jquery.com/ui/1.12.1/jquery-ui.min.js" type="text/javascript"></script>'."\n";
    echo '<script src="js/jquery.ui.touch-punch.min.js" type="text/javascript"></script>'."\n";
  }
  echo '<link rel="stylesheet" type="text/css" href="css/style.css">'."\n";
  echo "</head>\n";
  echo '<body bgcolor="'.$color.'"><div align="center">'."\n";
  if ($nav) {
    if ($tablelayout) echo "<table id=\"layouttable\" border=0 cellpadding=0 cellspacing=0 width=750>";
    print_nav($tablelayout);
  } else {
    if ($tablelayout) echo "<table id=\"layouttable\" border=0 cellpadding=0 cellspacing=0>";
  }
  echo "<tr><td>\n";
}

function print_nav($tablelayout=1) {
  if ($tablelayout) {
    echo '<tr><td id="navbar">';
  } else {
    echo '<div id="navbar">';
  }
  echo "<a href=\"index.php\" target=\"_top\">Top (Search)</a>&nbsp;&nbsp;|&nbsp;";
  echo "<a href=\"list.php?tagged=1\">List Tagged</a>&nbsp;&nbsp;|&nbsp;";
  if ($_SESSION['admin'] > 0) {
    echo "<a href=\"edit.php\" target=\"_top\">New Song</a>&nbsp;&nbsp;|&nbsp;";
  }
  echo "<a href=\"multiselect.php\" target=\"_top\">Tagged Song Actions</a>&nbsp;&nbsp;|&nbsp;";
  echo "<a href=\"event_use.php\" target=\"_top\">Song Use Chart</a>&nbsp;&nbsp;|&nbsp;";
  echo "<a href=\"maintenance.php\" target=\"_top\">DB Maintenance</a>";
  if ($_SESSION['admin'] == 2) {
    echo "&nbsp;&nbsp;|&nbsp;<a href=\"sqlquery.php\" target=\"_top\">(Freeform SQL)</a>";
  }
  echo "&nbsp;&nbsp;|&nbsp;<a href=\"index.php?logout=1\" target=\"_top\">Log Out</a>";
  if ($tablelayout) {
    echo '</td></tr>';
  } else {
    echo '</div>';
  }
}

// For the older pages
function print_header($title,$color,$nav) {
  header1($title);
  header2($nav,$color);
}

// Function print_footer: sends final html
function print_footer($tablelayout=1) {
  global $nav_bar;
  if ($tablelayout) echo "</td></tr>";
  if ($nav_bar) {
    print_nav();
  }
  if ($tablelayout) echo "</table></body></html>";
}

// function sqlquery_checked: shorten the repeated checks for SQL errors
function sqlquery_checked($sql) {
  global $db;
  $result = mysqli_query($db, $sql);
  if ($result === false ){
    die("<pre style=\"font-size:15px;\"><strong>SQL Error in file ".$_SERVER['PHP_SELF'].": ".mysqli_error($db)."</strong><br>$sql</pre>");
  }
  return $result;
}

// Function db2table: prepares text from DB for display in table cell (or plain html text)
function db2table($text) {
  $text = str_replace(" ","&nbsp;",$text);
  return nl2br($text);
}

function d2h($text) {
  return nl2br(htmlspecialchars($text, ENT_QUOTES, mb_internal_encoding()));
}

// Function post2form: prepares text from DB for display in form
function post2form($text) {
//  $text = str_replace("\'","'",$text);
  return stripslashes($text);
}

function escape_quotes($text) {
  $text = str_replace("\"","\\\"",$text);
  return $text;
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
  $rbcounter = $rblimit = 0;
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
        if (strlen($thischar) > 1) {  //multi-byte character
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
  return $fmt;
}

// STUFF THAT GETS RUN RIGHT AWAY

$hostarray = explode(".",$_SERVER['HTTP_HOST']);
define('CLIENT',$hostarray[0]);
define('CLIENT_PATH',"/var/www/sambidb/client/".CLIENT);
// Get client login credentials and connect to client database
$configfile = CLIENT_PATH."/sambidb.ini";
if (!is_readable($configfile)) die("No configuration file. Notify the developer.");
$config = parse_ini_file($configfile);
$db = mysqli_connect("localhost", "sambi_".CLIENT, $config['password'], "sambi_".CLIENT)
    or die("Failed to connect to database. Notify the developer.");

mysqli_set_charset($db, "utf8mb4");

?>
