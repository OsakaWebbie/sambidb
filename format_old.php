<?php
include("functions.php");
include("accesscontrol.php");
//print_header("","#FFF0E0",0);
echo "<html><head>";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$_SESSION['pw_charset']."\">\n";
echo "<title>Formatted Songs</title>\n";

$sid_array = split(",",$sid_list);
$num_sids = count($sid_array);

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
    echo ".chordlyrics { ".$lyrics_css." position:relative; margin-top:".
    ($lyrics_size[1]*0.2).$lyrics_size[2].";padding-top:".$lyrics_size[1].$lyrics_size[2]."; }\n";
    //echo ".chord { ".$lyrics_css." position:absolute; top: -".$lyrics_size[1].$lyrics_size[2]."; font-weight:bold; }\n";
    echo ".chord { ".$lyrics_css." position:absolute; top:0; font-weight:bold; }\n";

//    echo ".chordlyrics { ".$lyrics_css."\n  position:relative;\n";
//    echo "  margin-top:".($lyrics_size[1]/5).$lyrics_size[2].";\n";
//    echo "  margin-bottom:".$lyrics_size[1].$lyrics_size[2].";\n";
//    echo "  top: ".$lyrics_size[1].$lyrics_size[2]."; }\n";
//    echo ".chord { ".$lyrics_css."\n  position:absolute;\n  top: -".$lyrics_size[1].$lyrics_size[2].";\n  font-weight:bold; }\n";

//    echo ".chordlyrics { ".$lyrics_css."\n  position:relative;\n";
//    echo "  line-height:".($lyrics_size[1]*2.2).$lyrics_size[2]."; }\n";
//    echo ".chord { ".$lyrics_css." position:absolute; top: -".$lyrics_size[1].$lyrics_size[2]."; font-weight:bold; }\n";

    echo ".nobreak { page-break-inside: avoid; }\n";
    echo ".break { page-break-before: always; }\n";
    echo "@media screen { .break { border-top:2px green dotted; } }\n";

	//CSS for song options
	echo " .songOptions { position:absolute; visibility:hidden }\n";
	
  }
  $num_items++;
}
echo "textarea { font-size: 9pt; }\n";
echo "@media print { .noprint{ display: none; } }\n";
echo "</style>\n"; ?>
<script type="text/javascript">
re = / break /;

//initialization function
function init(){
	document.getElementById('output').contentEditable="true";
	
	var obj = getElementsByClass("songOptions", null, "div");	
	for(i=0;i<obj.length;i++){
		//move all changekey classes to the right;
		obj[i].style.left=findPosX(obj[i].parentNode.parentNode) + 300;
		obj[i].style.width=obj[i].parentNode.offsetWidth;
		obj[i].style.height=obj[i].parentNode.offsetHeight;
		
		//set it to be uneditable
		obj[i].contentEditable="false";

	}
	
}

//handle special keystrokes for editting
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

	//check for required conditions
	var chd = getElementsByClass("chord", song, "span");

    
	if(chd.length > 0){
		song.style.borderLeft="5px solid black";			//take this out when other things are added in
		var obj = getElementsByClass("songOptions", song, "div");
		obj[0].style.visibility = 'visible';
	}

}

function hideSongOptions(song, e){
	song.style.borderLeft="0px solid black";
  
	var obj = getElementsByClass("songOptions", song, "div");
	obj[0].style.visibility = 'hidden';
}

//possible chords
chrds_b = new Array( 'A', 'V', 'B', 'C', 'W', 'D', 'X', 'E', 'F', 'Y', 'G', 'Z');
enh_s = new Array('A#', 'C#', 'D#', 'G#', 'F#');
enh_f = new Array('Bb', 'Db', 'Eb', 'Ab', 'Gb');
exc = new Array('V', 'W', 'X', 'Z', 'Y');

//TODO: redo to more smartly select between "#" and "b"
function changeKey(sel){
	var song = sel;
	while(song.className!="song") song = song.parentNode;
	
	//get the selection
	var s = parseInt(sel.options[sel.selectedIndex].value);
	var chd = getElementsByClass("chord", song, "span");
	var currChord;
	var temp = 0;
		
	for(i=0; i<chd.length; i++){
		currChord = chd[i].innerHTML;
		currChord = currChord.split("/");
		
		//for each half
		for( k=0; k<currChord.length;k++){
			//make all single character
			for(j=0; j<enh_s.length;j++){
				currChord[k] = currChord[k].replace(enh_s[j], exc[j]);
				currChord[k] = currChord[k].replace(enh_f[j], exc[j]);
			}
			
			//change the current chord
			for(j=0; j<chrds_b.length; j++){
				if( currChord[k].match(chrds_b[j]) != null){
					temp = s + j;
					if( temp < 0 ) temp += 12;
					else if(temp > 11) temp -= 12;
									
					currChord[k] = currChord[k].replace(chrds_b[j], chrds_b[temp]);
					break;
				}
			}
			
			for(j=0;j<exc.length-1;j++){
				if( currChord[k].match(exc[j]) != null)
					currChord[k] = currChord[k].replace(exc[j], enh_f[j]);
			}
		
			//F#m is used more often than Gbm
			if( currChord[k].match(exc[exc.length-1] + "m") != null ){
				currChord[k] = currChord[k].replace(exc[exc.length-1], enh_s[exc.length-1]);
			}else{
				currChord[k] = currChord[k].replace(exc[exc.length-1], enh_f[exc.length-1]);
			}
			
		}
		chd[i].innerHTML = currChord.join("/");
	}
	
	//reset to 0
	sel.selectedIndex = 6;

}

//find the x position of an object
//width can be found by obj.offsetWidth;
function findPosX(obj){
    var curleft = 0;
    if(obj.offsetParent)
        while(1) 
        {
          curleft += obj.offsetLeft;
          if(!obj.offsetParent)
            break;
          obj = obj.offsetParent;
        }
    else if(obj.x)
        curleft += obj.x;
    return curleft;
}

//find y position of an object
function findPosY(obj){
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
	for(i=0,j=0; i<tags.length; i++) {
		var test = " " + tags[i].className + " ";
		if (test.indexOf(tcl) != -1)
			el[j++] = tags[i];
	}
	return el;
}

</script>
</head>
<body onload="init()">
<div id="console"></div>
<div class="noprint">
<!--<button onclick="window.open('format_edit.php','edit','height=650,width=650,scrollbars=yes');">Edit This Output</button>-->
<p style="width:100%;color:#C00000;font-style:italic;font-weight:bold;margin-bottom:3px;
padding-bottom:6px;border-bottom:1px lightgray solid;">You can edit the text below temporarily for printing.<br />
To add a page break (or remove one after you have put it in), click the line below and press F2.<br />
You can use Print Preview to check, and return here to edit more if necessary.<br />
To start over, just refresh the page.</p>
</div>
<div id="output" onkeydown="keystroke(event)">
<?
for ($sid_index=0; $sid_index<$num_sids; $sid_index++) {

	echo '<div class="song" onMouseover="showSongOptions(this)" onMouseout="hideSongOptions(this, event)">';	
	echo '<span class="noprint"><div class="songOptions">'. "\n";
	
	// changekey
	echo '	<div class="changeKey" style="width:600px"> Semitone shift:';
	echo '	<select name="key" onChange="changeKey(this)">' . "\n";
	for($i=-6; $i<6; $i++){
		print("<option value=".$i);
		if( $i == 0 ) print (" selected='selected'");
		print(">".$i."</option>\n");
	}
	echo '	</select></div>';

	echo '</div></span>' . "\n";

  $sql = "SELECT ";
  for ($index = 0; $index < $num_items; $index++) {
    $sql .= $class[$index][1]." AS Item".$index.",";
  }
  $sql .= "OrigTitle FROM pw_song WHERE SongID=".$sid_array[$sid_index];
  if (!$result = mysql_query($sql)) {
    echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br><pre>($sql)</pre>");
    exit;
  }
  $song = mysql_fetch_array($result);
  if ($sid_index == 0 && $fix_first_page) {
    $song[0] = ereg_replace("(<p[^>]*)>","\\1 style=\"page-break-before:auto;\">",$song[0]);
  }
  if ($lyrics_index)  echo "<div class=\"nobreak\">\n";
  for ($item_index = 0; $item_index < $num_items; $item_index++) {
    if ($item_index == $lyrics_index) {
      if (($_GET['multilingual'] == "yes") && ($song[$num_items] == $prev_orig)) {
        echo "<p class=\"{$lyrics_class}\">---</p>\n";
      }
      $song[$item_index] = ereg_replace("(<p[^>]*>&nbsp;</p>)","</div>\n\\1\n<div class=\"nobreak\">",$song[$item_index]);
      if ($_GET['showchords'] == "yes") {
        $song[$item_index] = ereg_replace("<p class=\"?".$lyrics_class."\"?>([^<]*\[[^<]*)</p>","<p class=\"chordlyrics\">\\1</p>",$song[$item_index]);
        echo ereg_replace("\[([^\[]*)\]","<span class=\"chord\">\\1</span>",$song[$item_index]);
      } else {
        echo ereg_replace("\[[^\[]*\]","",$song[$item_index]);
      }
    } else {
      if (($_GET['multilingual'] != "yes") || ($song[$num_items] != $prev_orig)) {
        echo $song[$item_index];
      }
    }
  }
  if ($lyrics_index)  echo "</div>\n";
  $prev_orig = $song[$num_items];
  
  //end song
  echo "</div>";
}
echo "</div>\n";

echo "</body></html>";
?>
