<?php
session_start();
$hostarray = explode(".",$_SERVER['HTTP_HOST']);
$path = "/var/www/sambidb/client/".$hostarray[0]."/css/";

header("Content-type: text/css");
serve("css/reset.css");
if (is_file($path."styles.php")) {
  serve($path."styles.php");
  exit;
} elseif (is_file($path."styles.css")) {
  serve($path."styles.css");
  exit;
} else {
  if (is_file($path."colors.php")) {
    include($path."colors.php");
  } else {
    include("css/colors.php");  // default colors
  }
  // INCLUDE ALL DEFINITIONS HERE (so that colors can be applied)
?>
/* theme layout and styling */

body.full {
  text-align:center;
  background-color: <?=(!empty($bodybg)?$bodybg:"DarkGrey")?>;
}
body.simple {
  text-align:center;
  background-color: White;
}

body.full div#main-container {
  background-color:<?=(!empty($mainbg)?$mainbg:"White")?>;
  text-align:left;
  width:auto;
  border: 1px solid <?=(!empty($mainborder)?$mainborder:"Black")?>;
  margin: 10px;
}
body.simple div#main-container {
  text-align:left;
  background-color: White;
}

div#content {
  margin:0 10px 10px 10px;
  background-color: White;
  z-index: 1;
}
table { background-color: White;}

/* MAIN MENU (WIDE SCREENS) */
#nav-main {
  background:<?=(!empty($mainbg)?$mainbg:"White")?> url('graphics/sambidb-logo-small.png') no-repeat 3px center;
  min-height: 53px;
}
#nav-main ul, #scrollnav ul {
  background-color:<?=(!empty($navbg)?$navbg:"rgb(88,57,7)")?>;
  list-style-type: none;
  margin:10px 10px 0 58px;
  padding:3px 0 5px 0;
  border-radius: 15px;
  text-align: center;
  min-height: 40px;
}
#nav-main li, #scrollnav li {
  display: inline-block;
}
#nav-main a, #scrollnav a {
  display: block;
  color: <?=(!empty($navlink)?$navlink:"White")?>;
  padding: 5px 10px;
  margin: 0;
  font-family: arial, helvetica, sans-serif;
  font-weight: bold;
  white-space:nowrap;
}
#nav-main li.menu-usersettings a span { font-weight:normal; white-space:wrap; }
#nav-main a:hover {
  background-color: <?=(!empty($navbghover)?$navbghover:"rgb(132,78,12)")?>;
  color: <?=(!empty($navlinkhover)?$navlinkhover:"White")?>;
}

/* MENU THAT APPEARS WHEN SCROLLING (WIDE SCREENS) */
#scrollnav {
  position: fixed;
  top: -100px;
  transition: top 0.5s ease-in-out 0s;
  width: 100%;
  z-index: 9999;
}
#scrollnav ul {
  background-color: <?=(!empty($navbg)?rgba($navbg,"0.7"):rgba("#2C2C2C","0.7"))?>;
  margin:0;
  padding:5px;
  -moz-border-radius: 0;
  border-radius: 0;
  min-height: 0;
}
#scrollnav ul a {
  padding: 3px 10px 3px 10px;
}
#scrollnav.visible {
  top: 0;
}

/* TRIGGER (BUTTON) FOR MOBILE MENU */
#nav-trigger {
  display: none;
  text-align: center;
  background-color:<?=(!empty($navbg)?$navbg:"rgb(88,57,7)")?>;
}
#nav-trigger img {
  float:left;
  width:24px;
  padding:3px;
  background-color:White;
  border-radius:7px;
  margin:3px;
}
#nav-trigger span {
  display: inline-block;
  padding: 10px 30px;
  color: <?=(!empty($navlink)?$navlink:"LightSteelBlue")?>;
  cursor: pointer;
  font-family: arial, helvetica, sans-serif;
  font-size: 120%;
  font-weight: bold;
}
#nav-trigger span:after {
  display: inline-block;
  box-sizing: border-box;
  margin-left: 10px;
  width: 20px;
  height: 10px;
  content: "";
  border-left: solid 10px transparent;
  border-top: solid 10px <?=(!empty($navlink)?$navlink:"LightSteelBlue")?>;
  border-right: solid 10px transparent;
}
#nav-trigger.open { background-color: <?=(!empty($navbghover)?$navbghover:"rgb(132,78,12)")?>; }
#nav-trigger.open span { color:<?=(!empty($navlinkhover)?$navlinkhover:"White")?>; }
#nav-trigger.open span:after {
  border-left: solid 10px transparent;
  border-top: none;
  border-bottom: solid 10px <?=(!empty($navlinkhover)?$navlinkhover:"White")?>;
  border-right: solid 10px transparent;
}

/* MOBILE MENU */
#nav-mobile {
  position: relative;
  display: none;
  margin-left:35px;
  z-index: 100;
}
#nav-mobile ul {
  display: none;
  list-style-type: none;
  position: absolute;
  left: 0;
  right: 0;
  margin-left: auto;
  margin-right: auto;
  text-align: center;
  background-color: <?=(!empty($navbg)?$navbg:"rgb(88,57,7)")?>;
}
#nav-mobile li {
  display: block;
  padding: 5px 0;
  margin: 0 5px;
  border-bottom: solid 1px <?=(!empty($primarymedium)?$primarymedium:"SteelBlue")?>;
}
nav#nav-mobile li:last-child { border-bottom: none; }
nav#nav-mobile a {
  display: block;
  color: <?=(!empty($navlink)?$navlink:"LightSteelBlue")?>;
  padding: 8px 0;
  font-family: arial, helvetica, sans-serif;
  font-size: 120%;
  font-weight: bold;
}
nav#nav-mobile li.menu-usersettings a span { font-weight:normal; white-space:wrap; }
nav#nav-mobile a:hover {
  background-color: <?=(!empty($navbghover)?$navbghover:"#583907")?>;
  color: <?=(!empty($navlinkhover)?$navlinkhover:"White")?>;
}

/* general purpose typography */

body { font-family:Arial,"ＭＳ Ｐゴシック",sans-serif; }
textarea { font-family:Arial,"ＭＳ Ｐゴシック",sans-serif; } /* because inherit doesn't work in IE <8 */

h1 {
  margin:6px 0 6px 0;
  text-align:center;
  font-size: 1.8em;
  line-height:1;
  font-weight:bold;
  color: <?=(!empty($h1)?$h1:"#CC9944")?>;
}
h2 {
  text-align:left;
  font-size: 1.5em;
  line-height:1.1;
  color: <?=(!empty($h2)?$h2:"SteelBlue")?>;
  font-weight:bold;
}
h3 {
  text-align:left;
  font-size: 1.3em;
  font-weight:bold;
  font-style:italic;
  color: <?=(!empty($h3)?$h3:"Black")?>;
  margin:10px 0 4px 0;
}
  h4 {
  text-align:left;
  font-size: 1.2em;
  font-weight:bold;
  color: <?=(!empty($h4)?$h4:"DarkRed")?>;
  margin:5px 0 3px 0;
  }
a:link,a:visited { color:<?=(!empty($link)?$link:"#333399")?>; }
a:hover,a:active { color:<?=(!empty($linkhover)?$linkhover:"DarkBlue")?>; }
a.more { cursor:pointer; color:<?=(!empty($linkmore)?$linkmore:"Black")?>; text-decoration:underline; }

.alert { color:<?=(!empty($alert)?$alert:"Red")?>; }
.comment { font-size:0.8em; font-style:italic; }
.highlight { background-color:<?=(!empty($highlight)?$highlight:"LightSteelBlue")?>; }
.validation { background-color:<?=(!empty($validation)?$validation:"Red")?>; }

.left { float:left; }
.right { float:right; }
.clear { clear:both; }
.nowrap { white-space:nowrap; }
button, submit {
  background-color:<?=(!empty($buttonbg)?$buttonbg:"LightSteelBlue")?>;
}
.bigbutton {
  font-size:1.5em;
  font-weight:bold;
  padding:0.5em;
  background-color:<?=(!empty($buttonbg)?$buttonbg:"LightSteelBlue")?>;
}

/*forms*/

form div { margin-top:0.1em; margin-bottom:0.1em; }
input.text {
  background-color: <?=(!empty($inputbg)?$inputbg:"White")?>;
  border: <?=(!empty($inputborder)?$inputborder:"DimGray")?> solid 1px;
}
fieldset,input,select,label,label textarea { vertical-align:top; }
.label-n-input { white-space:nowrap; margin:0.2em 2em 0.2em 0}
td.button-in-table { text-align:center; }

div#actions { margin:8px 0; text-align:center; }
div#actions form { display:inline; margin:2px 15px; }

/* specialized classes and IDs */

section  {
  margin: 15px 0 15px 0;
  border: 2px solid <?=(!empty($sectionborder)?$sectionborder:"DarkRed")?>;
  padding: 5px;
  background-color: White;
}
.section-title {
  margin:0 0 3px 5px;
  padding:2px 7px 2px 7px;
  border: 2px solid <?=(!empty($sectiontitleborder)?$sectiontitleborder:"DarkRed")?>;
  text-align:left;
  display:inline;
  position:relative;
  top:-12px;
  font-size:1.2em;
  font-weight:bold;
  font-style:italic;
  color: <?=(!empty($sectiontitle)?$sectiontitle:"White")?>;
  background-color: <?=(!empty($sectiontitlebg)?$sectiontitlebg:"DarkRed")?>;
}
fieldset {
  margin: 15px 0 15px 0;
  border: 2px solid <?=(!empty($fieldsetborder)?$fieldsetborder:"DarkRed")?>;
  padding: 5px 10px;
  background-color: White;
}
fieldset legend {
  margin:0 0 3px 5px;
  padding:2px 7px 2px 7px;
  font-size:1.2em;
  font-weight:bold;
  font-style:italic;
  color: <?=(!empty($legend)?$legend:"White")?>;
  background-color: <?=(!empty($legendbg)?$legendbg:"DarkRed")?>;
}

h1#title {
  margin: 0 0 0 48px;
  padding:4px 0 10px 0;
  color: <?=(!empty($title)?$title:"Red")?>;
  background-color:<?=(!empty($titlebg)?$titlebg:"White")?>;
}

span.inlinelabel {
  font-weight: bold;
  color: <?=(!empty($inlinelabel)?$inlinelabel:"DarkRed")?>;
}

option.active. li.active { background-color:<?=(!empty($activeeventbg)?$activeeventbg:"White")?>; }
option.inactive, li.inactive { background-color:<?=(!empty($inactiveeventbg)?$inactiveeventbg:"#BBBBBB")?>; }

/* MOBILE MEDIA QUERIES */

@media screen and (max-width: 900px) {
  body.full div#main-container {
    border: none;
    margin: 0;
  }
  body.full div#main-container { background-image:none; }
  #nav-trigger { display: block; }
  nav#nav-main { display: none; }
  nav#nav-mobile { display: block; }
  #scrollnav { display: none; }
  ul.nav li.menu-user a { white-space:wrap; }
  h1#title { margin:0; }
}
@media screen and (orientation:landscape) {
  #nav-trigger span, nav#nav-mobile a { font-size: 100%; }
  #nav-trigger img { width:20px; }
}

/* AJAX related */

.delconfirm { background-color: <?=(!empty($delconfirm)?$delconfirm:"#808080")?>; }
.spinner { background: <?=(!empty($delconfirm)?$delconfirm:"#808080")?> url('graphics/ajax_loader.gif'); }

/* specific to index.php */

body.index div#disclaimer { color:A03030; font-size:0.9em; font-weight:bold; margin:20px; }
body.index div#content {
  margin-top:0px;
}
body.index ul.nav {
  margin-bottom:0px;
}
body.index div#opening h1.title { text-align:left; margin-left:280px; margin-top:0px; padding-top:5px;}
body.index div#opening h3 { text-align:left; margin-left:330px; color:white; }
body.index .advanced { display:none; }
body.index .comment { text-align:center; }
body.index div.criteria,body.index div.criteria select {
  vertical-align: middle;
  margin-bottom:3px;
}
body.index span.radiogroup, body.index span.inputgroup {
  display:inline-block;
  vertical-align: middle;
}
body.index h2 span.radiogroup {
  border:1px solid <?=(!empty($h2)?$h2:"SteelBlue")?>;
  font-size: 0.8em;
}
body.index h2 span.radiogroup label { display:inline-block; margin:3px 5px; }
body.index fieldset span.radiogroup label, body.index fieldset span.inputgroup label { display:block; }
body.index fieldset span.plus {
  font-size:2em;
  width:20px;
  display:inline-block;
}
body.index #showadvanced,body.index #index { display:block; }
body.index #buttonsection {
  border: 1px solid Black;
  padding: 10px 8px;
  background: white;
  position: fixed;
  top: 100px;
  right: 18px;
  text-align: center;
}
body.index #buttonsection label.label-n-input { margin-right:0; }
body.index #index {
  margin:0 auto;
  padding:5px 40px 5px 40px;
  font-size: 1.5em;
  font-weight:bold;
}
@media screen and (max-width: 900px) {
  body.index fieldset {
    margin: 8px 0;
  }
  body.index div.criteria,body.index div.criteria select { line-height: 3em; }
  body.index div.criteria span.radiogroup label { line-height: 1.5em; }
  body.index #showadvanced {
    display:inline-block;
    margin-bottom: 10px;
  }
  body.index #buttonsection {
    display:inline-block;
    position: static;
    margin-bottom: 10px;
  }
  body.index #search {
    display:inline-block;
  }
}

/* specific to list.php */
body.list.full div#main-container { width:auto; }
body.list h3 { margin-bottom:0; }
body.list ul#criteria {
  margin-left:30px;
  padding-left:12px;
  list-style-type: disc;
}
body.list table { margin-right:auto; margin-left:auto; }
body.list td.categories { white-space:nowrap; }

/* specific to edit.php */
body.edit input { margin-top:5px;}
body.edit div#name_section,body.edit div#furigana_section,body.edit div#title_section {
  float:left;
  vertical-align:top;
  margin:0 15px 10px 0;
}
body.edit #submit_button,body.edit #remarks_label { display:inline-block; vertical-align:top; margin-top:5px; }
body.edit #remarks { width:420px; height:6em; vertical-align:top; margin-bottom:5px; }
body.edit #submit_button {
  margin:20px;
  padding:5px 15px;
  font-size: 1.3em;
  font-weight:bold;
} 
body.edit #duplicates { text-align:left; }
body.edit #duplicates .dup { border:2px solid LightGray; padding:5px;margin:3px 0; }
body.edit #duplicates .name { font-size:1.2em; font-weight:bold; }
body.edit #duplicates .button_section { white-space:nowrap; }
body.edit #duplicates .button_section form { display:inline; }

/* specific to attendance charts (also used in dashboards, so "body" omitted to allow div also) */
body.attend_detail.full div#main-container { width:auto; }
body.attend_detail .weekdaydate, body.attend_datesums .weekdaydate {
  white-space:nowrap;
  background-color: <?=(!empty($weekdaybg)?$weekdaybg:"#FFFFD0")?>;
  font-size:0.8em;
  text-align:center;
}
body.attend_detail .saturdaydate, body.attend_datesums .saturdaydate {
  white-space:nowrap;
  background-color: <?=(!empty($saturdaybg)?$saturdaybg:"#C0C0E0")?>;
  font-size:0.8em;
  text-align:center;
}
body.attend_detail .sundaydate, body.attend_datesums .sundaydate {
  white-space:nowrap;
  background-color: <?=(!empty($sundaybg)?$sundaybg:"#FF8080")?>;
  font-size:0.8em;
  text-align:center;
}
body.attend_detail td.photocell, body.attend_detail .photohead {
  text-align:center;
  background-color: <?=(!empty($photocellbg)?$photocellbg:"#FFFFD0")?>;
}

/* specific to sqlquery.php */
body.sqlquery h2 {
  text-align:center;
  margin-bottom:10px;
}
body.sqlquery input#submit {
  margin:10px auto 5px auto;
  padding:5px 40px 5px 40px;
  font-size: 1.5em;
  font-weight:bold;
}
body.sqlquery form {
  margin:10px auto 5px auto;
}
body.sqlquery #mainTable tbody td { vertical-align:top; }

/* specific to event_use.php (usage chart) */
.chart-controls {
  margin: 10px 0;
  text-align: center;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
}
.chart-controls .sort-controls {
  margin: 5px;
}
.chart-controls .sort-btn {
  margin: 0 5px;
}
.use-chart-container {
  max-height: 70vh;
  overflow: auto;
  position: relative;
  border: 1px solid <?=(!empty($mainborder)?$mainborder:"Black")?>;
}
.use-chart {
  border-collapse: collapse;
  border-spacing: 0;
  width: auto;
}
.use-chart thead th {
  position: sticky;
  top: 0;
  z-index: 2;
  background: <?=(!empty($tableheaderbg)?$tableheaderbg:"#d7e4f9")?>;
  border: 1px solid <?=(!empty($mainborder)?$mainborder:"Black")?>;
  padding: 5px;
  font-size: 0.85em;
  text-align: center;
  white-space: nowrap;
  box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4);
}
.use-chart .sticky-col {
  position: sticky;
  left: 0;
  z-index: 1;
  background: <?=(!empty($tableheaderbg)?$tableheaderbg:"#d7e4f9")?>;
  border: 1px solid <?=(!empty($mainborder)?$mainborder:"Black")?>;
  padding: 5px;
  font-size: 0.85em;
  font-weight: 700;
  white-space: nowrap;
  box-shadow: 2px 0 2px -1px rgba(0, 0, 0, 0.4);
}
.use-chart .sticky-corner {
  position: sticky;
  left: 0;
  top: 0;
  z-index: 3;
  background: <?=(!empty($tableheaderbg)?$tableheaderbg:"#d7e4f9")?>;
  border: 1px solid <?=(!empty($mainborder)?$mainborder:"Black")?>;
  padding: 5px;
  font-weight: bold;
  box-shadow: 2px 2px 2px -1px rgba(0, 0, 0, 0.4);
}
.use-chart tbody td {
  border: 1px solid <?=(!empty($mainborder)?$mainborder:"Black")?>;
  padding: 5px;
  text-align: center;
}
.use-chart tbody td.used {
  background-color: #8080FF;
  font-weight: bold;
}

@media print {
  body.full {
    background:none;
  }
  body.full div#main-container {
    background:none;
    border:none;
  }
  ul.nav { display:none; }
}

/* Repeated here because the earlier instance doesn't work for some reason */
body.full {
  text-align:center;
  background-color: <?=(!empty($bodybg)?$bodybg:"DarkGrey")?>;
}

  <?php
} // end IF USING THIS FILE

/*if (isset($_GET['jquery'])) {
  serve(is_file($path."jquery-ui.css") ? $path."jquery-ui.css" : "css/jquery-ui.css");
}
if (isset($_GET['table'])) {
  serve(is_file($path."tablesorter.css") ? $path."tablesorter.css" : "css/tablesorter.css");
  serve(is_file($path."clickmenu4colman.css") ? $path."clickmenu4colman.css" : "css/clickmenu4colman.css");
}*/
if (isset($_GET['multiselect'])) {
  serve(is_file($path."jquery.multiselect.css") ? $path."jquery.multiselect.css" : "css/jquery.multiselect.css");
  serve(is_file($path."jquery.multiselect.filter.css") ? $path."jquery.multiselect.filter.css" : "css/jquery.multiselect.filter.css");
}

function serve($source) {
  $stuff = file_get_contents($source);
  echo preg_replace('#url\( *["\']?([^"\'\)]*)["\']? *\)#', 'url("clientfile.php?f=css/$1")', $stuff);
}
function rgba($color,$alpha) {
  if (strtolower(substr($color,0,4)) == "rgba") return preg_replace("/,[0-9\.]+\)$/",",".$alpha.")",$color);
  elseif (strtolower(substr($color,0,3)) == "rgb") return str_replace("rgb","rgba",preg_replace("/(,[0-9\.]+)\)$/","$1,".$alpha.")",$color));
  else {
    if ($color[0] == '#') $color = substr($color,1);
    if (strlen($color) == 6) list($r,$g,$b) = array($color[0].$color[1],$color[2].$color[3],$color[4].$color[5]);
    elseif (strlen($color) == 3) list($r,$g,$b) = array($color[0].$color[0],$color[1].$color[1],$color[2].$color[2]);
    else return false;
    return "rgba(".hexdec($r).",".hexdec($g).",".hexdec($b).",".$alpha.")";
  }
}
