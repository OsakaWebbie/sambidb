<?php
include("functions.php");
include("accesscontrol.php");
//print_header("","#FFF0E0",0);
echo "<html><head>";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$_SESSION['pw_charset']."\">\n";
echo "<title>Formatted Songs</title>\n";

$sid_array = split(",",$sid_list);
$num_sids = count($sid_array);

//prep for transposing options
//$key_name = array("C","Db","D","Eb","E","F","Gb","G","Ab","A","Bb","B");
//$key_index = array("C"=>0,"Db"=>1,"D"=>2,"Eb"=>3,"E"=>4,"F"=>5,"Gb"=>6,"G"=>7,"Ab"=>8,"A"=>9,"Bb"=>10,"B"=>11);

$sql = "SELECT * FROM pw_outputset LEFT JOIN pw_output ON pw_outputset.Class=pw_output.Class ".
 "WHERE SetName='$outputset_name' ORDER BY OrderNum";
if (!$result = mysql_query($sql)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
  exit;
}
$num_items = 0;
$lyrics_index = -1;  // to distinguish index of 0 from no lyrics at all
echo "<style>\n";
while ($row = mysql_fetch_object($result)) {
  $class[$num_items][0] = $row->Class;
  $class[$num_items][1] = $row->OutputSQL;
  echo ".".$row->Class." { ".$row->CSS." }\n";
  if ($num_items == 0 && eregi("page-break-before *: *always", $row->CSS))  $fix_first_page = 1;
  if (eregi("lyrics",$row->Class )) {
    $lyrics_class = $row->Class;
    $lyrics_index = $num_items;
    $lyrics_css = substr($row->CSS, 0, strpos($row->CSS, "}"));
    if (!eregi("font-size *: *([0-9]+)([^;]*)", $lyrics_css, $lyrics_size)) {
      $lyrics_size = array("","6","pt");
    }
    echo ".chordlyrics { ".$lyrics_css." margin-top:".
    ($lyrics_size[1]*0.5).$lyrics_size[2]."; *text-indent:0; *padding-left:0;}\n";
    echo ".chords { ".$lyrics_css." font-weight:bold; }\n";
    echo ".chordlyrics ruby { ruby-align:left;}\n";
    //echo ".chordlyrics rb { font-size:10pt;  line-height:1.0em;}\n";
    echo ".chordlyrics rt span { ".$lyrics_css;
    if ($_GET['red_chords']) echo " color:#D00000;";
    echo " position:relative; width:1px; top:".$lyrics_size[1].$lyrics_size[2].";}\n";
  } elseif (eregi("title",$row->Class )) {
    $title_index = $num_items;
  } elseif (eregi("pattern",$row->Class )) {
    $pattern_index = $num_items;
  } elseif (eregi("composer",$row->Class )) {
    $composer_index = $num_items;
  } elseif (eregi("copyright",$row->Class )) {
    $copyright_index = $num_items;
  }
  $num_items++;
}  // end of loop through items in the output set
?>
.song{
  position:relative;
}
.songOptions {
  position:absolute;
  visibility:hidden;  <!-- this will be toggled later in JS -->
  border:1px black solid;
  background-color: black;
  color:white;
  font-weight:bold;
  padding:8px; 
}

<!-- next two are necessary as placeholders for Javascript to change later -->
.topchord { display:inline; }
.bass { display:inline; }

.nobreak { page-break-inside: avoid; }
.break { page-break-before: always; }
@media screen { .break { border-top:2px green dotted; } }
textarea { font-size: 9pt; }
@media print { .noprint{ display: none; } }
</style>
<?
if ($_GET['showchords'] == "yes") {
?>
<script type="text/javascript">
re = / break /;
var songObjects;
var usingSongOptions = false;

//possible chords
chrdsM = new Array( 'A', 'Bb', 'B', 'C', 'Db', 'D', 'Eb', 'E', 'F', 'F#', 'G', 'Ab');
chrdsm = new Array( 'Am', 'Bbm', 'Bm', 'Cm', 'C#m', 'Dm', 'D#m', 'Em', 'Fm', 'F#m', 'Gm', 'G#m');
chrds_b = new Array( 'A', 'V', 'B', 'C', 'W', 'D', 'X', 'E', 'F', 'Y', 'G', 'Z');
chrds_z = new Array('H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'S', 'T');  //temporary listing 
sharpkeys = new Array('D', 'G', 'A', 'E', 'B', 'Y'); //keys with sharp as key signature... that are commonly used?
enh_s = new Array('A#', 'C#', 'D#', 'G#', 'F#');
enh_f = new Array('Bb', 'Db', 'Eb', 'Ab', 'Gb');
exc = new Array('V', 'W', 'X', 'Z', 'Y');

//initialization function
function init() {
//  document.getElementById('output').contentEditable="true";

  songObjects = getElementsByClass("songOptions", null, "div");
  usingSongOptions = false;

  for(var i=0;i<songObjects.length;i++){
    //set it to be uneditable
    songObjects[i].contentEditable="false";
    
    //get the chords
    chrdsel = getElementsByClass("changeKey", songObjects[i], "div")[0]; //to ensure that we get the correct <select> if we have many dropdowns in the future
    chrdsel = chrdsel.getElementsByTagName("select")[0];
    songkey = toSingleCharMode(chrdsel.options[0].text);
    keyindex = getKeyIndex(songkey);
    
    var isMinor = false;
    if( songkey.length > 1 && songkey.charAt(1) == 'm')
      isMinor = true;
      
    for(j=0; j<chrdsel.options.length;j++){
      if(isMinor) chrdsel.options[j].text = chrdsm[j];
      else chrdsel.options[j].text = chrdsM[j];
      chrdsel.options[j].value = j-keyindex;
    }

    chrdsel.selectedIndex = keyindex; 
    if(isMinor) chrdsel.options[keyindex].text = "("+chrdsm[keyindex] + ")";
    else chrdsel.options[keyindex].text ="("+chrdsM[keyindex] + ")";
  }
}

//called by button at top of page to enable or disable the song options
function toggleSongOptions(btn) {
//  for(var i=0;i<songObjects.length;i++){
    if (btn.value == "Show") {
      usingSongOptions = true;
      //songObjects[i].onmouseover = function() {showSongOptions(this);};
      //songObjects[i].onmouseout = function() {hideSongOptions(this, event);};
      btn.value = "Hide";
    } else {
      usingSongOptions = false;
      //songObjects[i].onmouseover = "";
      //songObjects[i].onmouseout = "";
      btn.value = "Show";
    }
//  }
}

//As of 2009-05-21, used to specialize chord display for guitarists or bassists (hide part before or after /)
function classDisplay(className,displayValue) {
  var theRules = new Array();
  if (document.styleSheets[0].cssRules) {
    theRules = document.styleSheets[0].cssRules;
  } else if (document.styleSheets[0].rules) {
    theRules = document.styleSheets[0].rules;
  }
  for(var i=0; i<theRules.length; i++) {
    if (theRules[i].selectorText == className) {
      theRules[i].style.display = displayValue;
      break;
    }
  }
}

//handle special keystrokes for editing
function keystroke(e) {
  var keynum;
  if(window.event) { // IE
    keynum = e.keyCode;
  } else if(e.which) { // Netscape/Firefox/Opera
    keynum = e.which;
  }

  if (keynum==113) {  //the F2 key - we will use it to toggle the presence of the break class
    var sel = window.getSelection();
    var node = sel.anchorNode.parentNode;
    var nodeclass = ' '+node.className+' ';
    if (re.test(nodeclass)) {
      nodeclass = nodeclass.replace(re,' ');
    } else {
      nodeclass += 'break';
    }
    nodeclass.replace(/\s*$/, '');
    nodeclass.replace(/^\s*/, '');
    node.className = nodeclass;
    return false;
  }
}

//show options with the songs
//2009-05-09:   song key
function showSongOptions(song){
  if (usingSongOptions) {
    //check for required conditions
    var chd = getElementsByClass("chord", song, "span");
    
    if(chd.length > 0){
      song.style.left = -5;
      song.style.borderLeft="5px solid black";  //take this out when other things are added in
      var obj = getElementsByClass("songOptions", song, "div");
      obj[0].style.visibility = 'visible';
    }
  }
}

function hideSongOptions(song, e){
  if (usingSongOptions || song.style.borderLeftWidth>0) {
    song.style.left = 0;
    song.style.borderLeft="0px solid black";
  
    var obj = getElementsByClass("songOptions", song, "div");
    obj[0].style.visibility = 'hidden';
  }
}

function changeKey(sel){
  var song = sel;
  while(song.className!="song") song = song.parentNode;

  //get the selection
  var s = parseInt(sel.options[sel.selectedIndex].value);
  var chd = getElementsByClass("chord", song, "span");
  var currChord;
  var temp = 0;
  var i,j;  //loop counters
  
   //get the current key and previous key
  var songkey = toSingleCharMode(sel.options[sel.selectedIndex].text);
  var keyindex = getKeyIndex(songkey);
  var relMaj = getRelMaj(songkey);
  //var prevkey = sel.options[sel.selectedIndex-s].text;   
        
  for(i=0; i<chd.length; i++){
     currChord = chd[i].innerHTML;

     //change all E# back to F before trying to process
     //if( prevkey.match("F#") != null ){
     // currChord = currChord.replace(new RegExp("E#", "g"), "F");
     //}
     
     currChord = toSingleCharMode(currChord);
     
    for(j=0; j<chrds_b.length;j++){
      if( currChord.match(chrds_b[j]) != null){
        temp = s + j;
        if( temp < 0 ) temp += 12;
        else if(temp > 11) temp -= 12;
        
        //just if it is F# key, and if the chord is now "F" (since F does not exist in F#)
        //if( songkey.match("Y") != null && chrds_b[temp].match("F") != null){
        //  currChord = currChord.replace(new RegExp(chrds_b[j], "g"), "E#");
        //}else{        
          currChord = currChord.replace(new RegExp(chrds_b[j], "g"), chrds_z[temp]);
        //}
      }
    }
     
    for(j=0;j<chrds_b.length;j++){
      currChord = currChord.replace(new RegExp(chrds_z[j], "g"), chrds_b[j]);
    }

    var excind;
    var guitarChord;
    for(j=0;j<exc.length;j++){
      excind = currChord.search(exc[j]);
      while(excind != -1) { //if there is a sharp or flat
        if( excind >= 2 && currChord.charAt(excind-1) == '/'){
          // if it is 'v', 'w', 'x', 'y', 'z'
          if( exc.toString().match(currChord.charAt(excind-2)) != null){
            guitarChord = relMaj;
            //the 'v', 'w', 'x', 'y', 'z' will be flat or sharp depending on the song
            //so the bass note will be sharp type of flat type also depending on the chord
          }else{
            //otherwise, the key of the corr guitar chord has been set as either a sharp or flat chord
            //so get the chord
            if( currChord.charAt(excind-2) == '#' || currChord.charAt(excind-2) == 'b' ){
              guitarChord = currChord.substring(excind-3, excind-1);
            }else{
              guitarChord = currChord.substring(excind-2, excind-1);
            }
          }
        }else{
          guitarChord = relMaj;
        }
        
        if( !isSharpKey(guitarChord) ){
          currChord = currChord.replace(exc[j], enh_f[j]);
        }else{
          currChord = currChord.replace(exc[j], enh_s[j]);      
        }
        excind = currChord.search(exc[j]);
      }
      
    }

    chd[i].innerHTML = currChord;
  }

  //change the option  values
  for(j=0; j<sel.options.length;j++){
    sel.options[j].value = j-keyindex;
  }

}

//returns true if the songkey is a sharp key signature
function isSharpKey(songkey){
  if( songkey.length > 1){
    if(songkey.charAt(1) == '#') return true;
    if( songkey.charAt(1) == 'b') return false;
  }

  for(var i=0;i<sharpkeys.length;i++){
    if( songkey.match(sharpkeys[i]) != null){
      return true;
    }
  }
  return false;
}

function getKeyIndex(songkey){
  for(var j=0; j<chrds_b.length;j++){
    if( songkey.match(chrds_b[j]) != null) {
      return j;
    }
  }
  return null;
}

function toSingleCharMode(key){
  for(var j=0; j<enh_s.length;j++){
    key = key.replace(new RegExp(enh_s[j], "g"), exc[j]);
    key = key.replace(new RegExp(enh_f[j], "g"), exc[j]);
  }
  return key;
}

//should be in single char format
function getRelMaj(minor){
  var i = minor.search(/[A-Z]/);
  if( minor.length < i+2 ) return minor; // not long enough
  if( minor.charAt( i+1 ) != 'm') return minor;

  keyindex = getKeyIndex(minor.substring(i,i+1));
  keyindex += 3;
  if( keyindex >= 12) keyindex -= 12;
        
  return chrds_b[keyindex];
}

//find the x position of an object
//width can be found by obj.offsetWidth;
function findPosX(obj) {
    var curleft = 0;
    if (obj.offsetParent)
        while (1) 
        {
          curleft += obj.offsetLeft;
          if (!obj.offsetParent)
            break;
          obj = obj.offsetParent;
        }
    else if (obj.x)
        curleft += obj.x;
    return curleft;
}

//find y position of an object
function findPosY(obj) {
    var curtop = 0;
    if(obj.offsetParent)
        while(1)
        {
          curtop += obj.offsetTop;
          if(!obj.offsetParent)
            break;
          obj = obj.offsetParent;
        }
    else if(obj.y)
        curtop += obj.y;
    return curtop;
}

//get element by class function
function getElementsByClass(searchClass, domNode, tagName) {
  if (domNode == null) domNode = document;
  if (tagName == null) tagName = '*';
  var el = new Array();
  var tags = domNode.getElementsByTagName(tagName);
  var tcl = " "+searchClass+" ";
  var test,i,j;
  for(i=0, j=0; i<tags.length; i++) {
    test = " " + tags[i].className + " ";
    if (test.indexOf(tcl) != -1)
      el[j++] = tags[i];
  }
  return el;
}

</script>
<?
} // end of it showchords
?>
</head>
<body onload="init()">
<div id="console"></div>
<div class="noprint" style="width:100%;margin-bottom:3px;padding-bottom:6px;border-bottom:2px lightgray solid;">
<div style="color:#C00000;font-style:italic;font-weight:bold;">You can edit the text below temporarily for printing.<br />
To add a page break (or remove one after you have put it in), click the line below and press F2.<br />
You can use Print Preview to check, and return here to edit more if necessary.<br />
To start over, just refresh the page.</div>
<? if ($_GET['showchords']) {
  echo <<<END
  <span style="border:1px #8080FF solid; padding:3px; top-margin:5px; color:#0000C0; font-weight:bold">Chords:&nbsp;
  <input type="radio" name="chordDisplay" id="both"
  onClick="classDisplay('topchord','inline');classDisplay('bass','inline');" checked /><label
  for="both">Show All</label>&nbsp;
  <input type="radio" name="chordDisplay" id="guitar"
  onClick="classDisplay('topchord','inline');classDisplay('bass','none');" /><label
  for="guitar">Guitar Only</label>&nbsp;
  <input type="radio" name="chordDisplay" id="bass"
  onClick="classDisplay('topchord','none');classDisplay('bass','inline');" /><label
  for="bass">Bass Only</label>
</span>&nbsp;&nbsp;&nbsp;
<span style="border:1px #80FF80 solid; padding:3px; top-margin:5px; color:#008000;
font-weight:bold">Options for transposition: <input type="button"
id="toggleSongOptions" onClick="toggleSongOptions(this);" value="Show" /></span>
END;
} ?>
</div>
<div id="output" contenteditable="true" onkeydown="keystroke(event)">
<?
for ($sid_index=0; $sid_index<$num_sids; $sid_index++) {

  $sql = "SELECT ";
  for ($index = 0; $index < $num_items; $index++) {
    $sql .= $class[$index][1]." AS Item".$index.",";
  }
  $sql .= "SongKey,OrigTitle FROM pw_song WHERE SongID=".$sid_array[$sid_index];
  if (!$result = mysql_query($sql)) {
    echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br><pre>($sql)</pre>");
    exit;
  }
  $song = mysql_fetch_array($result);
  $songkey = $song[$num_items];
  $origtitle = $song[$num_items+1];
  
  echo "<div class=\"song\"";
  if ($_GET['showchords'] == "yes") {
    echo " onmouseover=\"showSongOptions(this);\" onmouseout=\"hideSongOptions(this, event);\"";
  }
  echo ">\n";
  if ($_GET['showchords'] == "yes") {
    echo "<span class=\"noprint\">\n<div class=\"songOptions\">\n";
    echo "<div class=\"changeKey\">Transpose:<select name=\"key\" onChange=\"changeKey(this)\">\n";
    for($i=-6; $i<6; $i++){
      print("<option value=".$i);
      if( $i == 0 ) print (" selected='selected'");
      print(">$songkey</option>\n");
    }
    echo "</select></div></div></span>\n";
  }

  if ($sid_index == 0 && $fix_first_page) {
    $song[0] = ereg_replace("(<p[^>]*)>","\\1 style=\"page-break-before:auto;\">",$song[0]);
  }
  if ($lyrics_index)  echo "<div class=\"nobreak\">\n";
  for ($item_index = 0; $item_index < $num_items; $item_index++) {
    if ($item_index == $lyrics_index) {
      if (($_GET['multilingual'] == "yes") && ($origtitle == $prev_orig)) {
        echo "<p class=\"{$lyrics_class}\">---</p>\n";
      }
      $song[$item_index] = ereg_replace("(<p[^>]*>&nbsp;</p>)","</div>\n\\1\n<div class=\"nobreak\">",$song[$item_index]);
      if ($_GET['showchords'] == "yes") {
        echo stripslashes(preg_replace("/<p class=\"?".$lyrics_class."\"?>([^<]*\[[^<]*)<\/p>/e","'<p class=\"chordlyrics\">'.chordsToRuby('$1').'</p>'",$song[$item_index]));
      } else {
        echo ereg_replace("\[[^\[]*\]","",$song[$item_index]);
      }
    } else {
      if (($_GET['multilingual'] != "yes") || ($origtitle != $prev_orig)) {
        echo $song[$item_index];
      }
    }
  }
  if ($lyrics_index)  echo "</div>\n";
  $prev_orig = $origtitle;
  
  //end song
  echo "</div>";
}
echo "</div>\n";

echo "</body></html>";
?>
