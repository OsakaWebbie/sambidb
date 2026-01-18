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
  <link rel="manifest" href="/manifest.json"
  <meta name="msapplication-TileColor" content="#ffffff">
  <meta name="msapplication-TileImage" content="/ms-icon-144x144.png">
  <meta name="theme-color" content="#ffffff">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="shortcut icon" type="image/x-icon" href="favicons/favicon.ico">
  <title><?=(isset($_SESSION['dbtitle']) ? $_SESSION['dbtitle'].': ' : '').$title?></title>
<?php
}

function header2($nav=0) {
  echo '<link rel="stylesheet" type="text/css" href="style.php">'."\n";
  echo "</head>\n";
  $fileroot = substr($_SERVER['PHP_SELF'],(strrpos($_SERVER['PHP_SELF'],"/")+1),(strrpos($_SERVER['PHP_SELF'],".")-strrpos($_SERVER['PHP_SELF'],"/")-1));
  echo "<body class='".$fileroot.($nav?" full":" simple")."'>\n";

  if ($nav) {
    $navmarkup = "<ul class='nav'>\n";
    $navmarkup .= "  <li><a href='index.php' target='_top'>"._("Search")."</a></li>\n";
    $navmarkup .= "  <li><form action='list.php'><input name='title' placeholder='"._('(quick search)')."' style='width:7em'></form></li>\n";
    $numtags = sql_single("SELECT COUNT(SongID) FROM song WHERE Tagged=1");
    $navmarkup .= "  <li>".($numtags>0?"<a href='list.php?tagged=1' target='_top'>":'')._('List Tagged')." ($numtags)".($numtags>0?"</a>":'')."</li>\n";
    $navmarkup .= "  <li><a href='edit.php' target='_top'>"._("New Song")."</a></li>\n";
    $navmarkup .= "  <li><a href='multiselect.php' target='_top'>"._("Tagged Song Actions")."</a></li>\n";
    $navmarkup .= "  <li><a href='event_use.php' target='_top'>"._("Song Use Chart")."</a></li>\n";
    $navmarkup .= "  <li><a href='maintenance.php' target='_top'>"._("DB Settings")."</a></li>\n";
    if (!empty($_SESSION['admin']) && $_SESSION['admin'] == 2) {
      $navmarkup .= "  <li><a href='sqlquery.php' target='_top'>"._("(Freeform SQL)")."</a></li>\n";
    }
    $navmarkup .= "  <li><a class='switchlang' href='#'>".
        ($_SESSION['lang']=='en_US'?'日本語':'English')."</a></li>\n";
    $navmarkup .= "  <li class='menu-usersettings'><a href='user_settings.php' target='_top'>"._("User Settings")."<span> (".$_SESSION['username'].")</span></a></li>\n";
    $navmarkup .= "  <li><a href='index.php?logout=1' target='_top'>"._("Log Out")."</a></li>\n</ul>\n";
    echo "<nav id='scrollnav'></nav>\n";  //only appears when scrolled

    echo "<div id='main-container'>\n";
    echo "<nav id='nav-main'>\n$navmarkup</nav>\n";  //main nav for large screens
    echo "<div id='nav-trigger'><img src='graphics/sambidb-logo.png' alt='Logo'><span>Menu</span></div>\n";  //button for narrow screens
    echo "<nav id='nav-mobile'></nav>\n";  //vertical menu for narrow screens
  }
  echo "<div id='content'>\n";
}

// Function footer: sends final html
function footer($nav=0) {
  echo "  <div style='clear:both'></div>\n";
  echo "</div>\n"; //end of content div
  echo "</div>\n"; //end of main-container div

?>
  <script>
    if (window.jQuery) { //really simple files that don't have jQuery don't need this stuff either
      $(function() {
        $(window).scroll(function() {
          if ($(this).scrollTop() > 150 && !$('#scrollnav').hasClass('visible')) {
            $('#scrollnav').addClass('visible');
          } else if ($(this).scrollTop() <= 150 && $('#scrollnav').hasClass('visible')) {
            $('#scrollnav').removeClass('visible');
          }
        });

        $("#nav-mobile").html($("#nav-main").html());
        $("#scrollnav").html($("#nav-main").html());
        $("#nav-trigger").click(function(){
          if ($("nav#nav-mobile ul").hasClass("expanded")) {
            $("nav#nav-mobile ul.expanded").removeClass("expanded").slideUp(250);
            $(this).removeClass("open");
          } else {
            $("nav#nav-mobile ul").addClass("expanded").slideDown(250);
            $(this).addClass("open");
          }
        });

        $('.switchlang').click(function(event) {
          event.preventDefault();
          $.ajax({
            type: "POST",
            url: "ajax_actions.php?action=SwitchLang&lang=<?=$_SESSION['lang']=='en_US'?'ja_JP':'en_US' ?>",
            success: function() {
              location.reload(true);
            }
          });
        });
      });
    }
  </script>
  </body>
</html>
<?php
}

//DEPRECATED
function print_header($title,$color,$nav) {
  header1($title);
  header2($nav);
  echo "<table border='0' cellspacing='0' cellpadding='0' bgcolor='white'><tr><td>";
}

//DEPRECATED
function print_footer($nav=0) {
  echo "</td></tr></table>";
  footer();
}

// function sqlquery_checked: shorten the repeated checks for SQL errors
function sqlquery_checked($sql) {
  global $db;
  $result = mysqli_query($db, $sql);
  if ($result === false ){
    die("<pre style='font-size:15px;'><strong>SQL Error in file ".$_SERVER['PHP_SELF'].": ".mysqli_error($db)."</strong><br>$sql</pre>");
  }
  return $result;
}

// Function sql_single: expects SQL query that returns one row and one column and simply returns the resulting value
function sql_single($sql) {
  $result = sqlquery_checked($sql);
  $row = mysqli_fetch_row($result);
  return $row[0];
}

// Function db2table: prepares text from DB for display in table cell (or plain html text)
function db2table($text) {
  $text = str_replace(" ","&nbsp;",$text);
  return nl2br($text);
}

function d2h($text) {
  return nl2br(htmlspecialchars($text, ENT_QUOTES, mb_internal_encoding()));
}

function escape_quotes($text) {
  $text = str_replace('"','\"',$text);
  return $text;
}

function url2link($text) {
  //return preg_replace('@(https?://([-\w\.]+)+(:\d+)?(/([-\w/_\.]*(\?[^\s<]+)?)?)?)@', '<a href="$1">$1</a>', $text);

  /*** copied from KizunaDB (should probably be in a "common" function file) ***/
  // I have no idea how this works - I got it from https://gist.github.com/winzig/8894715 (2017/11/07)
  // removing the part that looks for URLs with no protocol (because that was too greedy).
  // I don't know why this matches on a multibyte domain name, but it does.
  return preg_replace('~\b((?:https?:(?:/{1,3}|[a-z0-9%])|[a-z0-9.\-]+[.](?:[a-z]{2,13})/)'.
      '(?:[^\s()<>{}\[\]]+|\([^\s()]*?\([^\s()]+\)[^\s()]*?\)|\([^\s]+?\))+(?:\([^\s()]*?\([^\s()]+\)[^\s()]*?\)|'.
      '\([^\s]+?\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))~iu',
      '<a href="$1">$1</a>', $text);
}

//Function parseLyrics: takes in lyrics (in the format from the database) and parses to use ruby
function chordsToRuby($unfmt){
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
    if ($insideword && (mb_substr($oldrb,-1)==" " || mb_strpos($nonruby," ") !== false)) {  //found a space, so let it wrap
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
  if ($insideword && (mb_substr($oldrb,-1)==" " || mb_strpos($nonruby," ") !== false)) {  //found a space, so let it wrap
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
if (!is_readable($configfile)) die("No SambiDB configuration file. Notify the developer.");
$config = parse_ini_file($configfile);
$db = mysqli_connect("localhost", "sambi_".CLIENT, $config['password'], "sambi_".CLIENT)
    or die("Failed to connect to database. Notify the developer.");

mysqli_set_charset($db, "utf8mb4");

?>
