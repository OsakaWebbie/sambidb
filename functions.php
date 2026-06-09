<?php
error_reporting(E_ERROR);

/*** css_bundle: favicon (client-aware) + the standard stylesheet bundle. ***/
/*** Called by pageheader() and (for the login screen) accesscontrol.php. ***/
/*** jquery-ui is emitted BEFORE style.css so style.css / page <style> win the cascade. ***/
function css_bundle() {
  $client = explode(".", $_SERVER['HTTP_HOST'])[0];
  $dir = __DIR__;                       // filesystem base (public/) for filemtime/is_file
  $customfs  = "$dir/css/custom/$client";
  $customurl = "css/custom/$client";    // webroot-relative for hrefs
  $favfs  = is_file("$customfs/favicon.ico") ? "$customfs/favicon.ico" : "$dir/favicon.ico";
  $favurl = is_file("$customfs/favicon.ico") ? "$customurl/favicon.ico" : "favicon.ico";
  echo '<link rel="icon" type="image/x-icon" href="'.$favurl.'?v='.filemtime($favfs).'">'."\n";
  echo '<link rel="stylesheet" type="text/css" href="css/reset.css">'."\n";
  echo '<link rel="stylesheet" type="text/css" href="css/jquery-ui-14.css">'."\n";
  if (is_file("$customfs/jquery-ui-theme.css"))   // a client's own ThemeRoller theme
    echo '<link rel="stylesheet" type="text/css" href="'.$customurl.'/jquery-ui-theme.css?v='.filemtime("$customfs/jquery-ui-theme.css").'">'."\n";
  echo '<link rel="stylesheet" type="text/css" href="css/style.css?v='.filemtime("$dir/css/style.css").'">'."\n";
  if (is_file("$customfs/colors.css"))            // per-client palette overrides, win over style.css
    echo '<link rel="stylesheet" type="text/css" href="'.$customurl.'/colors.css?v='.filemtime("$customfs/colors.css").'">'."\n";
}

/*** pageheader: single-call replacement for header1()+header2(). ***/
/*** (Can't be named header() — that's a built-in PHP function.) ***/
function pageheader($title, $nav=0) {
  global $_nav_shown;
  $_nav_shown = $nav;
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="192x192" href="android-icon-192x192.png">
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#ffffff">
<title><?=$title.(isset($_SESSION['dbtitle']) ? ' ('.$_SESSION['dbtitle'].')' : '')?></title>
<?php if (!empty($_GET['debug'])): // eruda = on-screen JS console for debugging on phones ?>
<script src="js/eruda.min.js?v=<?=filemtime('js/eruda.min.js')?>"></script>
<script>eruda.init();</script>
<?php endif; ?>
<?php
  css_bundle();
  echo "</head>\n";
  $fileroot = substr($_SERVER['PHP_SELF'],(strrpos($_SERVER['PHP_SELF'],"/")+1),(strrpos($_SERVER['PHP_SELF'],".")-strrpos($_SERVER['PHP_SELF'],"/")-1));
  echo "<body class='".$fileroot.($nav?" full":" simple")."'>\n";

  if ($nav) {
    $numbasket = count($_SESSION['basket'] ?? []);
    $navmarkup = "<ul class='nav'>\n";
    $navmarkup .= "  <li><a href='index.php' target='_top'>"._("Search")."</a></li>\n";
    $navmarkup .= "  <li class='not-on-scroll'><form action='list.php'><input name='title' placeholder='"._('(quick search)')."' style='width:7em'></form></li>\n";
    if ($_SESSION['access'] > 0)
      $navmarkup .= "  <li><a href='edit.php' target='_top'>"._("New Song")."</a></li>\n";
    $navmarkup .= "  <li class='hassub'>\n";
    $navmarkup .= "    <a href='#'>"._('Basket/Tasks')." (<span class='basketcount'>$numbasket</span>) &#x25BC;</a>\n";
    $navmarkup .= "    <ul class='nav-sub'>\n";
    $navmarkup .= "      <li><a href='list.php?basket=1' target='_top' class='basket-list'>"._('List Basket')."</a></li>\n";
    $navmarkup .= "      <li><a href='task.php' target='_top' class='basket-tasks'>"._("Tasks")."</a></li>\n";
    $navmarkup .= "      <li><a href='#' class='emptybasket basket-empty'>"._('Empty Basket')."</a></li>\n";
    $navmarkup .= "    </ul>\n  </li>\n";
    $navmarkup .= "  <li><a href='event_use.php' target='_top'>"._("Song Use Chart")."</a></li>\n";
    if ($_SESSION['access'] > 0)
      $navmarkup .= "  <li><a href='db_settings.php' target='_top'>"._("DB Settings")."</a></li>\n";
    if (!empty($_SESSION['access']) && $_SESSION['access'] == 2) {
      $navmarkup .= "  <li><a href='sqlquery.php' target='_top'>"._("(Raw SQL)")."</a></li>\n";
    }
    $navmarkup .= "  <li><a class='switchlang' href='#'>".
        ($_SESSION['lang']=='en_US'?'日本語':'English')."</a></li>\n";
    $navmarkup .= "  <li class='hassub menu-usersettings'>\n";
    $navmarkup .= "    <a href='#'>"._('User')."<span class='username'>: ".$_SESSION['username']."</span> &#x25BC;</a>\n";
    $navmarkup .= "    <ul class='nav-sub'>\n";
    $navmarkup .= "      <li><a href='user_settings.php' target='_top'>"._("User Settings")."</a></li>\n";
    $navmarkup .= "      <li><a href='index.php?logout=1' target='_top'>"._("Log Out")."</a></li>\n";
    $navmarkup .= "    </ul>\n  </li>\n";
    $navmarkup .= "</ul>\n";
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
  global $_nav_shown;
  if ($_nav_shown) $nav = $_nav_shown;  // use value from header2() if set
  echo "  <div style='clear:both'></div>\n";
  echo "</div>\n"; //end of content div
  echo "</div>\n"; //end of main-container div

?>
<?php if ($nav) { ?>
  <script>
    if (!window.jQuery) {
      document.write('<script src="js/jquery-3.6.0.min.js"><\/script>');
    }
  </script>
  <script>
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
      $("#nav-mobile li.not-on-scroll").remove();
      $("#scrollnav li.not-on-scroll").remove();

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
          url: "ajax_request.php?action=SwitchLang&lang=<?=$_SESSION['lang']=='en_US'?'ja_JP':'en_US' ?>",
          success: function() {
            location.reload(true);
          }
        });
      });

      /* submenu (hassub) event handling */
      $(document).on("mouseenter", ".hassub:not(#nav-mobile .hassub)", function() {
        $("ul", this).show();
      })
      .on("mouseleave", ".hassub:not(#nav-mobile .hassub)", function(){
        $("ul", this).hide();
      });
      $(document).on("click", ".hassub > a", function(event) {
        event.preventDefault();
        $(this).siblings("ul").toggle();
      });
      $(document).on("click", function(event) {
        if (!$(event.target).closest(".hassub").length) {
          $(".hassub").not("#nav-mobile .hassub").find("ul").hide();
        }
      });

      // Update basket count and disabled state of basket nav links across all nav copies.
      window.updateBasketCount = function(count) {
        $('.basketcount').text(count);
        $('.basket-list, .basket-tasks, .basket-empty').toggleClass('disabledlink', count === 0);
      };

      // Set initial disabled state
      $('.basket-list, .basket-tasks, .basket-empty').toggleClass('disabledlink', ($('span.basketcount').first().text() === '0'));

      $('.emptybasket').click(function(event) {
        event.preventDefault();
        $(this).closest('ul.nav-sub').hide();
        $.post('ajax_request.php', { action: 'BasketEmpty' }, function(response) {
          if (response.success) window.updateBasketCount(0);
        }, 'json');
      });
    });
  </script>
<?php } ?>
  </body>
</html>
<?php
}

// function sqlquery_checked: shorten the repeated checks for SQL errors
function sqlquery_checked($sql) {
  global $db;

  try {
    $result = mysqli_query($db, $sql);

    // Handle PHP 7.x where mysqli_query returns false on error
    if ($result === false) {
      throw new Exception(mysqli_error($db));
    }

    return $result;

  } catch (Exception $e) {
    // This catches both:
    // - PHP 8.x: mysqli_sql_exception thrown automatically
    // - PHP 7.x: our manually thrown Exception
    die('<pre style="white-space:pre-wrap;font-size:15px;font-weight:bold">SQL Error in file '.$_SERVER['PHP_SELF'].': '.$e->getMessage().'</pre><pre style="white-space:pre-wrap">'.$sql.'</pre>');
  }
}

// Function sql_single: expects SQL query that returns one row and one column and simply returns the resulting value
function sql_single($sql) {
  $result = sqlquery_checked($sql);
  $row = mysqli_fetch_row($result);
  return $row[0];
}

/*** LOAD SCRIPTS - common location for version #, and makes sure only loaded once ***/
/*** pass array of script name roots ***/
$scripts_loaded = array();
function load_scripts($scripts) {
  global $scripts_loaded;
  foreach ($scripts as $script) {
    if (empty($scripts_loaded[$script])) {
      switch ($script) {
        case 'jquery':
          echo '<script type="text/JavaScript" src="js/jquery-3.6.0.min.js"></script>'."\n";
          break;
        case 'jqueryui':
          echo '<script type="text/JavaScript" src="js/jquery-ui-14.min.js"></script>'."\n";
          break;
        case 'tablesorter':
          echo '<script type="text/JavaScript" src="js/jquery.tablesorter.min.js?v='.filemtime('js/jquery.tablesorter.min.js').'"></script>'."\n";
          break;
        case 'table2csv':
          echo '<script type="text/JavaScript" src="js/table2CSV.js?v='.filemtime('js/table2CSV.js').'"></script>'."\n";
          break;
        case 'expanding':
          echo '<script type="text/JavaScript" src="js/expanding.js?v='.filemtime('js/expanding.js').'"></script>'."\n";
          break;
        case 'multiselect':
          echo '<script type="text/JavaScript" src="js/jquery.multiselect.js?v='.filemtime('js/jquery.multiselect.js').'"></script>'."\n";
          echo '<script type="text/JavaScript" src="js/jquery.multiselect.filter.js?v='.filemtime('js/jquery.multiselect.filter.js').'"></script>'."\n";
          break;
        case 'multiselect-classes':
          echo '<script type="text/JavaScript" src="js/jquery.multiselect-classes.js?v='.filemtime('js/jquery.multiselect-classes.js').'"></script>'."\n";
          echo '<script type="text/JavaScript" src="js/jquery.multiselect.filter.js?v='.filemtime('js/jquery.multiselect.filter.js').'"></script>'."\n";
          break;
        case 'datepicker-ja':
          echo '<script type="text/JavaScript" src="js/i18n/datepicker-ja.js?v='.filemtime('js/i18n/datepicker-ja.js').'"></script>'."\n";
          break;
        case 'readmore':
          echo '<script type="text/JavaScript" src="js/readmore.js?v='.filemtime('js/readmore.js').'"></script>'."\n";
          break;
        case 'functions':
          echo '<script type="text/JavaScript" src="js/functions.js?v='.filemtime('js/functions.js').'"></script>'."\n";
          break;
      }
      $scripts_loaded[$script] = 1;
    }
  }
}

// Persist the in-session basket to user.Basket so it survives logout/expiry.
function saveBasket() {
  global $db;
  $userid = mysqli_real_escape_string($db, $_SESSION['userid']);
  $basket = implode(',', $_SESSION['basket']);
  sqlquery_checked("UPDATE user SET Basket='$basket' WHERE UserID='$userid'");
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

// trim() plus the Japanese ideographic space U+3000.
// trim() is byte-based and corrupts multibyte chars when the trim mask contains
// multibyte chars, so use a Unicode-aware regex instead.
function jtrim($text) {
  return preg_replace('/^[\s\x{3000}]+|[\s\x{3000}]+$/u', '', $text) ?? $text;
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
    $parts = explode("]", $rbpair, 2);  //tolerate a malformed "[" with no closing "]"
    $rt = $parts[0];
    $rb = $parts[1] ?? "";
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

try {
  $db = mysqli_connect("localhost", "sambi_".CLIENT, $config['password'], "sambi_".CLIENT);
} catch (mysqli_sql_exception $e) {
  // Log the real reason for the developer; show users a generic message (no credentials leaked).
  error_log('SambiDB DB connect failed for client "'.CLIENT.'": '.$e->getMessage());
  die("Failed to connect to database. Notify the developer.");
}

mysqli_set_charset($db, "utf8mb4");

?>
