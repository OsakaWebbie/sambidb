<?php
include("functions.php");
include("accesscontrol.php");
header1("Layout Prep for PDF or Powerpoint");

$sql = "SELECT * from song WHERE SongID IN ($sid_list) ORDER BY FIELD(SongID,$sid_list)";
if (!$result = mysqli_query($db,$sql)) die("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br><pre>[$sql]</pre>");

?>
<script>
var songs = {};
<?php
$html = '';
while ($song = mysqli_fetch_object($result)) {
  echo "songs['".$song->SongID."']={title:'".htmlspecialchars($song->Title,ENT_QUOTES)."',".
  "origtitle:'".htmlspecialchars($song->OrigTitle,ENT_QUOTES)."',".
  "songkey:'".htmlspecialchars($song->SongKey,ENT_QUOTES)."',".
  "composer:'".($song->Composer!="" ? "By ":"").htmlspecialchars($song->Composer,ENT_QUOTES)."',".
  "copyright:'".($song->Copyright!="" && $song->Copyright!="Public Domain"?"©":"").htmlspecialchars($song->Copyright,ENT_QUOTES)."',".
  "instrshort:".json_encode(htmlspecialchars(mb_ereg_replace("\[[^\[]*\]","",$song->Instruction),ENT_QUOTES)).",".
  "instrlong:".json_encode(htmlspecialchars(mb_ereg_replace("\[|]","",$song->Instruction),ENT_QUOTES))."};\n";
  if ($_GET['pattern']=="basic") {
    $tmp = preg_split("/\n-+\s*\n/u",rtrim($song->Lyrics));
    $stanzas = preg_split("/\n\s*\n/u",rtrim($tmp[0]));
  } else {
    $stanzas = preg_split("/\n-*\s*\n/u",rtrim($song->Lyrics));
  }
  $snippets = array();  //truncate
  foreach ($stanzas as &$stanza) {
    $snippets[] = mb_ereg_replace("  ","&nbsp;&nbsp;",htmlspecialchars(mb_strcut(preg_replace("#\[[^\[]*\]#","",$stanza),0,30),ENT_QUOTES));
    $stanza = trim(mb_ereg_replace("  ","&nbsp;&nbsp;",htmlspecialchars($stanza,ENT_QUOTES)));
  }
  if ($_GET['pattern']=="pattern" && $song->Pattern!="") {
    $patternarray = str_split(mb_ereg_replace("[^A-Z]","",$song->Pattern));
  } else {
    $patternarray = array();
    for ($i=0;$i<count($stanzas);$i++) $patternarray[] = chr($i+65);
  }
  $key = 65;  //the ASCII for "A"
  $html .= "<li class=\"s".$song->SongID."t ui-state-default song".(trim($song->Lyrics)==""?" empty":"")."\">".
  "<div class=\"left\"><span class=\"songnum\"></span><span class=\"title\">".$song->Title."</span><span class=\"songkey\"></span>";
  if (preg_match("/^[A-G]/",$song->SongKey)) {
    $html .= " (Key:".preg_replace("/^([A-G][#b]?m?).*$/","$1",$song->SongKey)."<select name='trans".$song->SongID."'>\n";
    for ($i=-6;$i<6;$i++) {
      $html .= ($i==0 ? "<option value=\"0\" selected> </option>" : ("<option value=\"".($i<0?$i+12:$i)."\">".($i>0 ? ("+".$i) : $i)."</option>"));
    }
    $html .= "</select>)\n";
  }
  $html .= "</div><div class=\"right\"><img src=\"graphics/copy.gif\" class=\"copy\" title=\"Duplicate\">".
  "<img src=\"graphics/delete.gif\" class=\"delete\" title=\"Remove\"></div><div class=\"clear\"></div>\n";
  if (trim($song->Lyrics) != "") {
    $html .= "  <ul>\n";
    foreach ($patternarray as $letter) {
      $i = ord($letter)-65;
      $html .= "    <li class='s".$song->SongID.$letter." ui-state-default stanza' title='".$stanzas[$i].
      "'>";
      $html .= "<div class=\"left\"><img src=\"graphics/print.gif\" class=\"print\" title=\"Turn printing on or off\">";
      $html .= "<img src='graphics/".(preg_match('*\[[^rR]*ui',$stanzas[$i]) ? "guitar.gif' title='Turn chord printing on or off'" :
      "clear_pixel.gif' width='16'")." class='chords'>";
      $html .= "[".$letter."] ".$snippets[$i]."...(".(substr_count($stanzas[$i],"\n")+1).")";
      $html .= "</div><div class=\"right\"><img src=\"graphics/copy.gif\" class=\"copy\" title=\"Duplicate\"><img src=\"graphics/delete.gif\" class=\"delete\" title=\"Remove\"></div><div class=\"clear\"></div></li>\n";
    }
    $html .= "  </ul>\n";
  }
  $html .= "</li>\n";
}
?>
</script>
<style>
  #help-section, #layoutform { visibility:hidden; } /* shown after jQuery UI effects are applied */

  #help-section { text-align:center; }
  .help h3, .help h4 { margin:0.3em 0 0.2em 0; }
  .help p { margin:0 0 0.7em 0; }
  .help-btn { margin: 5px 20px; }

  /*@media(min-width: 700px) { *** UNCOMMENT AFTER SITE IS MADE RESPONSIVE *** */
    #layout { float:right; width:60%; }
    #settings { float:left; width:38%; }
  /*} /* end of min-width */

  div.output-section {
    border:2px solid SteelBlue;
    padding:8px;
    margin-bottom: 10px;
  }

  div.adjustments { margin:0 0 1em 0; border:1px solid black; padding:5px 0;line-height:1.3em; }
  div.adjustments fieldset legend { font-weight:bold; font-style:italic; }
  div.adjustments h3 { margin-top:0; }
  div.adjustments div.indented { margin-left:1em; }
  div.adjustments div.indentedmore { margin-left:3em; }
  
  #layout ul, #layout ul li ul { list-style-type: none; margin: 0; padding: 0; }
  #layout ul li { margin: 3px 0 3px 0; padding: 4px; white-space:nowrap; background:#E0E0E0; }
  #layout ul li.empty { background:#f0c0c0; border-color:#906060; }
  #layout ul li ul li { margin: 2px 3px 0px 10px; padding: 4px; white-space:nowrap; background:White;}
  #layout ul li ul li.copyright { background:#FFF0D0;}
  #layout ul li ul li.instr { background:#D0F0D0;}
  #layout ul li img, #layout ul li ul li img { margin:0 0 0 1em; }
  #layout ul li.colbreak, #layout ul li ul li.colbreak { border-top: 3px black solid; }
  #layout img.print, #layout img.chords { margin:0 0.5em 0 0; }
  .ui-accordion .ui-accordion-content { padding: 0.5em !important; }
</style>
<link rel="stylesheet" type="text/css" href="css/jquery-ui.css" />
<script type="text/JavaScript" src="js/jquery.min.js"></script>
<script type="text/JavaScript" src="js/jquery-ui.min.js"></script>
<script type="text/JavaScript" src="js/jquery.ui.touch-punch.min.js"></script>

<script type="text/JavaScript">
$(document).ready(function(){
<?php if ($_GET['multilingual']) { //JS code to consolidate songs with same original title ?>
  var mlsong = [];
  var origtitle = "";
  $("#layout ul li.song").each(function() {  //loop through songs, collecting info in mlsong array
    thisid = this.className.match(/s([0-9]+)/)[1];
    if (songs[thisid].origtitle != origtitle) {  //take necessary action for the previous set, and start next set
      if (mlsong.length > 1) {
        after = 0;
        $.each(mlsong,function() { //loop through mlsong array, moving stanzas and adding transpose form field
          if (this != origsong) {
            if (after)  $(origsong).children("ul").append($(this).children("ul").children("li"));  //put after original song stanzas
            else  $(origsong).children("ul").prepend($(this).children("ul").children("li"));  //put before original song stanzas
            $(origsong).find("select").after('<input type="hidden" id="'+$(origsong).find("select").attr('name')+
                '" name="trans'+this.className.match(/s([0-9]+)/)[1]+'" value="0">');  //add a hidden field for translation
            $(this).remove();
          } else {
            after = 1;
          }
        });
      }
      origtitle = songs[thisid].origtitle;
      mlsong = [];
      origsong = this;  //if we don't find an exact match, we'll just use the first one
    }
    if (songs[thisid].title.indexOf(origtitle) != -1)  origsong = this;  //found a match, so this is the original
    mlsong.push(this);
  });
  if (mlsong.length > 1) {  //process the last remaining mlsong, if it exists
    after = 0;
    $.each(mlsong,function() {  //loop through mlsong array, moving stanzas and adding transpose form field
      if (this != origsong) {
        if (after)  $(origsong).children("ul").append($(this).children("ul").children("li"));  //put after original song stanzas
        else  $(origsong).children("ul").prepend($(this).children("ul").children("li"));  //put before original song stanzas
        $(origsong).find("select").after('<input type="hidden" id="'+$(origsong).find("select").attr('name')+
            '" name="trans'+this.className.match(/s([0-9]+)/)[1]+'" value="0">');  //add a hidden field for translation
        $(this).remove();
      } else {
        after = 1;  //we found the original, so anything after this needs to be appended, not prepended
      }
    });
  }
<?php } //end handling of multilingual song consolidation ?>
  
});
</script>
<?php
header2(1);
?>

<div id="help-section">
  <div class="help" id="pdf-help" title="How to make a PDF for printing or tablet">
    <h4>Stanzas and order</h4>
    <p>Drag stanzas (or even whole songs) into the order you want.&nbsp;Hover over any part to see the complete content.</p>
    <p>Click <img src="graphics/copy.gif"> to duplicate an item, <img src="graphics/delete.gif"> to delete it,
      or <img src="graphics/print.gif"> to disable an item temporarily without deleting it.</p>
    <h4>Chords</h4>
    <p>Selecting a layout preset will enable or disable all chords, but after that, you can show/hide
      them individually by clicking <img src="graphics/guitar.gif">.</p>
    <p>Next to the song title, you can transpose the chords for that song.
      (You won't see the change when hovering here, but it will affect the PDF.)</p>
    <h4>Preset and options</h4>
    <p>First select a PDF Layout Preset. Then you can optionally click on "PDF Layout Options" and
      change any of those settings to customize your output.</p>
    <h4>Usage</h4>
    <p>Important: When printing the PDF, turn off any setting that "fits" to the page - set to "actual size".</p>
    <h4 style="color:darkred">Contact me if you need new/different layout presets.</h4>
  </div>
  <div class="help" id="pp-help" title="How to make Powerpoint slides">
    <h4>Stanzas and order</h4>
    <p>Drag stanzas (or even whole songs) into the order you want.&nbsp;Hover over any part to see the complete content.</p>
    <p>Click <img src="graphics/copy.gif"> to duplicate an item, <img src="graphics/delete.gif"> to delete it,
      or <img src="graphics/print.gif"> to disable an item temporarily without deleting it.</p>
    <p>(Chords are never used for Powerpoint.)</p>
    <h4>Options</h4>
    <p>You can optionally click on "Powerpoint Text Options" and
      change any of those settings to customize your output. The "max lines" setting controls whether multiple
      short stanzas will fit on one slide. (NOTE: Single stanzas are not split between slides, so a very
      long one might overfill a slide by itself.)</p>
    <p>The combatibility defaults are the best settings for Windows. Powerpoint on Mac might need different
    settings (I cannot test that, so I'm not sure).</p>
    <h4>Usage</h4>
    <p>Save the text file. Open Powerpoint, preferably with a template that has been prepared for your usage.
      With a slide of the desired layout selected, click "New Slide" on the Home tab, click
      "do "Slides from Outline" (it might be worded differently depending on the Powerpoint version), and
      choose the text file you saved. It will make new slides using the styles of the master layout:
      song titles will be in the Title block and everything else in the Text block -
      main lyrics as Outline Level 1, romaji as Level 2, and composer/copyright as Level 3.</p>
    <p><a href="tools/SambiDB.potx" download target="_blank">Click here</a> to download a starter template you can modify for your needs.</p>
    <h4 style="color:darkred">This is a new feature - please report any bugs or suggestions.</h4>
  </div>
  <button class="help-btn" id="pdf-help-btn"><?=_('Floating Guide for PDFs')?></button>
  <button class="help-btn" id="pp-help-btn"><?=_('Floating Guide for Powerpoint')?></button>
</div>

<form id="layoutform" action="pdfgenerate.php" method="get">
  <input type="hidden" name="sid_list" value="<?php echo $sid_list; ?>">
  <input type="hidden" id="order" name="order" value="">
  <input type="hidden" id="copy2" name="copy2" value="0">
  <input type="hidden" id="ilong" name="ilong" value="0">

  <div id="layout">
    <ul><?=$html?></ul>
  </div>

  <div id="settings">
    <div id="pdf-controls" class="output-section">
      <div style="margin-bottom:10px;"><label><strong>PDF Layout Preset:</strong>
        <select id="formatname" name="formatname" size="1">
          <option value="">Select...</option>
<?php
$sql = "SELECT FormatName FROM pdfformat ORDER BY ListOrder";
if (!$result = mysqli_query($db,$sql)) die("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)<br>");
while ($row = mysqli_fetch_object($result)) {
  echo  "          <option value=\"".$row->FormatName."\">".$row->FormatName."</option>\n";
}
?>
        </select></div>
      </label>
      <div class="accordion">
        <h3 style="padding-left:25px;">PDF Layout Options</h3>
        <div class="adjustments">
          <fieldset>
            <legend>Title Line Settings</legend>
            <div style="display:none"><strong>Multilingual Song Titles:</strong><br>
            <div class="indented"><label><input type="radio" id="title-main" name="ttype" value="main"  checked>Actual Title (first if group)</label></div>
            <div class="indented"><label><input type="radio" id="title-orig" name="ttype" value="orig">Original Title</label></div>
            <div class="indented"><label><input type="radio" id="title-paren" name="ttype" value="paren">"Actual (Original)"</label></div></div>
            <strong>Numbering:</strong><br>
            <div class="indented"><label><input type="radio" id="title-numnone" name="tnum" value="none" checked>None</label>&nbsp;
            <label><input type="radio" id="title-numbasic" name="tnum" value="basic">"1."</label>&nbsp;
            <label><input type="radio" id="title-numcircle" name="tnum" value="circle">"①"</label></div>
            <label><input type="checkbox" id="title-key" name="tkey">Append "[Song Key]"</label>
          </fieldset>
          <fieldset>
            <legend>Extra Elements</legend>
            <strong>Instructions:</strong><br>
            <div class="indented"><a id="instrshort" href="javascript:void(0)">Short (omit "[text in brackets]")</a></div>
            <div class="indented"><a id="instrlong" href="javascript:void(0)">Long (include "[text]")</a></div>
            <div class="indented"><a id="instrnone" href="javascript:void(0)">Remove</a></div>
            <br>
            <strong>Credits:</strong><br>
            <div class="indented">Before lyrics: <a id="copyright-before" href="javascript:void(0)">One line</a> |
            <a id="copyright-before-twoline" href="javascript:void(0)">Two lines</a></div>
            <div class="indented">After lyrics: <a id="copyright-after" href="javascript:void(0)">One line</a> |
            <a id="copyright-after-twoline" href="javascript:void(0)">Two lines</a></div>
            <div class="indented"><a id="copyright-none" href="javascript:void(0)">Remove Credits</a></div>
            <br>
            <strong>Chords:</strong> <a id="nochords" href="javascript:void(0)">Disable all</a> |
            <a id="allchords" href="javascript:void(0)">Enable all</a><br>
            <br>
            <a id="allprint" href="javascript:void(0)">Re-enable all printing</a><br>
            <br>
            <strong>Romaji</strong> (lines prefaced with "[r]")<strong>:</strong><br>
            <div class="indented"><label><input type="radio" id="romaji-chordless" name="romaji" value="chordless" checked>Show all lines, but omit</div>
            <div class="indentedmore">chords on romaji</label></div>
            <div class="indented"><label><input type="radio" id="romaji-hide" name="romaji" value="hide">Hide romaji</label></div>
            <div class="indented"><label><input type="radio" id="romaji-only" name="romaji" value="only">Show <u>only</u> romaji</div>
            <div class="indentedmore">(in stanzas that have some)</label></div>
            <div class="indented"><label><input type="radio" id="romaji-showall" name="romaji" value="showall">Show all content</label></div>
          </fieldset>
          <fieldset>
            <legend>PDF Settings</legend>
            Override paper size (optional): <select name="papersize" size="1">
            <option value="" selected>Default</option>
            <option value="a4">A4</option>
            <option value="b5">B5</option>
            <option value="a5">A5</option>
            </select><br>&nbsp;<br>
            <label><input type="checkbox" id="usecolor" name="color" value="yes" checked> Use Color</label>
          </fieldset>
        </div>
      </div>
      <div>
        <input type="submit" id="pdfgenerate" name="submit" value="Generate PDF"
                  style="font-size:120%; font-weight:bold; padding:5px; margin-top: 10px;">
      </div>
    </div>

    <div id="powerpoint-controls" class="output-section">
      <div class="accordion">
        <h3 style="padding-left:25px;">Powerpoint Text Options</h3>
        <div class="adjustments">
          <fieldset>
            <legend>Content Settings</legend>
            <label>Max lines per slide: <input name="pp_lines" value="8" style="width:2em"></label><br>
            <label><input type="checkbox" name="pp_trim" checked>Trim leading spaces</label><br>
            <label><input type="checkbox" name="pp_slidenum" checked>Include slide # ("[2/4]") after song title</label><br><br>
            <strong>Romaji</strong> (lines prefaced with "[r]")<strong>:</strong><br>
            <div class="indented"><label><input type="radio" id="ppromaji-all" name="pp_romaji" value="all" checked>Show all</div>
            <div class="indented"><label><input type="radio" id="ppromaji-hide" name="pp_romaji" value="hide">Hide romaji</label></div>
            <div class="indented"><label><input type="radio" id="ppromaji-only" name="pp_romaji" value="only">Show <u>only</u> romaji (where exists)</div>
          </fieldset>
          <fieldset>
            <legend>Compatibility Settings</legend>
            <label><input type="checkbox" name="pp_crlf" checked>Windows line endings</label><br>
            <label><input type="checkbox" name="pp_sjis" checked>Convert to Shift-JIS</label><br>
          </fieldset>
        </div>
      </div>
      <div>
        <input type="submit" id="powerpoint" name="submit" value="Generate Text for PP"
                  style="font-size:120%; font-weight:bold; padding:5px; margin-top: 10px;">
      </div>
    </div>
  </div>
</form>

<script type="text/JavaScript">
$(document).ready(function() {

  $(".help").dialog({ autoOpen:false, width:450 });
  $("#pdf-help-btn").click(function() {
    $("#pdf-help").dialog("open");
  });
  $("#pp-help-btn").click(function() {
    $("#pp-help").dialog("open");
  });

  $(".accordion").accordion({
    collapsible: true,
    active: false
  });

  $("#layout ul").sortable({
    placeholder: "ui-state-highlight"
  });

  // Reveal all the stuff that was twitching during setup
  $("#help-section").css("visibility","visible");
  $("#layoutform").css("visibility","visible");

  $("#formatname").change(function(e) {
    // Get options based on newly selected format, and change as needed on page
    $.getJSON("ajax_db_row.php", {
      table:'pdfformat',
      key:'FormatName',
      keyquoted:1,
      value:$(this).val()
    }).done(function(r) {
      $('#title-num'+r.data.TitleNumbering).click();
      $('#title-key').prop('checked',(r.data.TitleWithKey=="1"));
      $('#title-key').change();
      $('#instr'+r.data.Instruction).click();
      $('#copyright-'+r.data.Credit).click();
      if (r.data.Chords=="1") $('#allchords').click(); else $('#nochords').click();
      $('#romaji-'+r.data.Romaji).click();
      $('#usecolor').prop('checked',(r.data.UseColor=="1"));
    });
  });

// ACTIONS RELATED TO SONG TITLES
  $("[id^='title']").click(function(e) {
    linkid = this.id;
    //e.preventDefault();
    $("#layout ul li.song").each(function() {
      thisid = this.className.match(/s([0-9]+)/)[1];
      switch(linkid) {
        case "title-main":
          $(this).children("div.left").children("span.title").html(songs[thisid].title);
          //$("#ttype").val(linkid.substr(6));
          break;
        case "title-orig":
          $(this).children("div.left").children("span.title").html(songs[thisid].origtitle);
          //$("#ttype").val(linkid.substr(6));
          break;
        case "title-paren":
          $(this).children("div.left").children("span.title").html(songs[thisid].title + " (" + songs[thisid].origtitle + ")");
          //$("#ttype").val(linkid.substr(6));
          break;
        case "title-numnone":
          $(this).children("div.left").children("span.songnum").html("");
          break;
        case "title-numcircle":
          $(this).children("div.left").children("span.songnum").html("(#) ");
          break;
        case "title-numbasic":
          $(this).children("div.left").children("span.songnum").html("#. ");
      }
    });
  });
  $('#title-key').change(function(e) {
    $("#layout ul li.song").each(function() {
      thisid = this.className.match(/s([0-9]+)/)[1];
      $(this).children("div.left").children("span.songkey").html( $('#title-key').is(':checked') && songs[thisid].songkey.length ? (" ["+songs[thisid].songkey+"]") : "");
    });
  });


// ACTIONS RELATED TO ADDING/REMOVING INSTRUCTIONS
  $("a[id^='instr']").click(function(e) {
    linkid = this.id;
    e.preventDefault();
    $("#layout ul li ul li.instr").remove();
    if (linkid != "instrnone") {
      $("#layout ul li.song").each(function() {
        thisid = this.className.match(/s([0-9]+)/)[1];
        if (songs[thisid][linkid].length) {
          $(this).children("ul").prepend('<li class="s'+thisid+'i ui-state-default instr" title="'+songs[thisid][linkid]+
              '"><div class="left"><img src="graphics/print.gif" class="print" title="Turn printing on or off">Instructions ('+
              songs[thisid].title+')</div><div class="right"><img src="graphics/delete.gif" class="delete" title="Remove"></div><div class="clear"></div></li>');
        }
      });
    }
    $("#ilong").val(linkid=='instrlong'?'1':'0');
  });

// ACTIONS RELATED TO ADDING/REMOVING CREDITS
  $("a[id^='copyright']").click(function(e) {
    linkid = this.id;
    e.preventDefault();
    $("#layout ul li ul li.copyright").remove();
    if (linkid != "copyright-none") {
      $("#layout ul li.song").each(function() {
        thisid = this.className.match(/s([0-9]+)/)[1];
        if (songs[thisid].composer.length || songs[thisid].copyright.length) {
          html = '<li class="s'+thisid+'c ui-state-default copyright" title="'+
              songs[thisid].composer+(linkid.match(/twoline/)?'\n':'; ')+songs[thisid].copyright+
              '"><div class="left"><img src="graphics/print.gif" class="print" title="Turn printing on or off">Copyright Info ('+
              songs[thisid].title+')</div><div class="right"><img src="graphics/delete.gif" class="delete" title="Remove"></div><div class="clear"></div></li>';
          if (linkid.match(/before/)) $(this).children("ul").prepend(html); else $(this).children("ul").append(html);
        }
      });
    }
    $("#copy2").val(linkid.match(/twoline/)?'1':'0');
  });

// ACTIONS RELATED TO GUITAR CHORD TOGGLE ICONS
  $("#layout").delegate("img.chords","click",function() {
    if ($(this).attr("src") == "graphics/guitar.gif")  $(this).attr("src","graphics/noguitar.gif");
    else  $(this).attr("src","graphics/guitar.gif");
  });
  $("#allchords").click(function(e) {
    e.preventDefault();
    $("img[src='graphics/noguitar.gif']").attr("src","graphics/guitar.gif");
  });
  $("#nochords").click(function(e) {
    e.preventDefault();
    $("img[src='graphics/guitar.gif']").attr("src","graphics/noguitar.gif");
  });

// ACTIONS RELATED TO PRINT TOGGLE ICONS
  $("#layout").delegate("img.print","click",function() {
    if ($(this).attr("src") == "graphics/print.gif")  $(this).attr("src","graphics/noprint.gif");
    else  $(this).attr("src","graphics/print.gif");
  });
  $("#allprint").click(function(e) {
    e.preventDefault();
    $("img[src='graphics/noprint.gif']").attr("src","graphics/print.gif");
  });

// ACTIONS RELATED TO COPY ICONS
  $("#layout").delegate("img.copy","click",function() {
    var original = $(this).closest("li");
    var cloned = $(original).clone(true,true);
    $(original).after($(cloned));
  });
// ACTIONS RELATED TO DELETE ICONS
  $("#layout").delegate("img.delete","click",function() {
    $(this).closest("li").remove();
  });
// DOUBLE-CLICK TO TOGGLE COLUMN BREAK
  $("#layout").delegate("li","dblclick",function(e) {
    e.stopPropagation();
    $(this).toggleClass("colbreak");
  });

// DIFFERENTIATE BETWEEN PDF AND TXT GENERATION
  $("#layoutform input[type='submit']").click(function(e) {
    $("#layoutform").attr("action", $(this).attr("id")+'.php');
    if ($(this).attr("id") == 'powerpoint')  $("#layoutform").attr("target", '_blank');
  });

// PREP FOR SUBMIT
  $("#layoutform").submit(function(e) {
    /* e.preventDefault();  //need to manually submit so I can do actions afterward */
    action = $(this).find("input[type=submit]:focus").attr("id");

    if (action == 'pdfgenerate' && $("#formatname").val() == "") {
      alert("Please select a format.");
      return false;
    }
    var items = [];
    $('#layout ul li').each(function() { // For each song title
      if (!$(this).children("div").children("img[src='graphics/noprint.gif']").length) {  //build the form field with the parts in order
        items.push(($(this).hasClass("colbreak") ? "br-" : "")+
            this.className.match(/(s[0-9]+[A-Za-z-]*)/)[1]+
            ($(this).children("div").children("img[src='graphics/guitar.gif']").length ? "-ch" : ""));
      }
    });
    $("#order").val(items);
    // MAKE HIDDEN TRANSPOSE SETTINGS (BILINGUAL SONGS) MATCH VISIBLE ONES
    $("select[name^='trans']").each(function() {
      $(this).siblings("input[id^='trans']").val($(this).val());
    });
    // DISABLE TRANSPOSE SETTINGS THAT ARE SIMPLY ZERO (to save space in URL)
    $("select[name^=trans],input[name^=trans]").each(function(){
      if ($(this).val() == 0)  $(this).prop('disabled', true);
    });

    /* $(this).submit();  //now go ahead and send the URL */

    // REENABLE THE TRANSPOSE SETTINGS AFTER SUBMISSION (in case the user wants to use this page again)
    setTimeout(function(){
      $("select[name^=trans],input[name^=trans]").prop('disabled', false);
    },1000);
  })
});
</script>
<?php print_footer(); ?>