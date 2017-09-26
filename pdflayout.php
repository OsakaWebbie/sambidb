<?php
include("functions.php");
include("accesscontrol.php");
header1("Layout Prep for PDF");

$sql = "SELECT * from song WHERE SongID IN ($sid_list) ORDER BY FIELD(SongID,$sid_list)";
if (!$result = mysqli_query($db,$sql)) die("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br><pre>[$sql]</pre>");

?>
<script>
var songs = {};
<?php
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
    $html .= " (Key:".preg_replace("/^([A-G][#b]?m?).*$/","$1",$song->SongKey)."<select name=\"trans".$song->SongID."\">\n";
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
      $html .= "    <li class=\"s".$song->SongID.$letter." ui-state-default stanza\" title=\"".$stanzas[$i].
      "\">";
      $html .= "<div class=\"left\"><img src=\"graphics/print.gif\" class=\"print\" title=\"Turn printing on or off\">";
      $html .= "<img src=\"graphics/".(mb_strpos($stanzas[$i],"[")!==FALSE ? "guitar.gif\" title=\"Turn chord printing on or off\"" :
      "clear_pixel.gif\" width=\"16\"")." class=\"chords\">";
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
  
  $(".accordion").accordion({
    collapsible: true,
    active: false
  });

  $("#layout ul").sortable({
    placeholder: "ui-state-highlight"
  });
  
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
  
// PREP FOR SUBMIT
  $("#layoutform").submit(function(e) {
    /* e.preventDefault();  //need to manually submit so I can do actions afterward */
    if ($("#formatname").val() == "") {
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
<?php
header2(1);
?>
<h3><font color="SteelBlue">Set options and arrange the songs and stanzas as you want.
&nbsp;You can drag whole songs (by the title line) or parts within a song, and copy or delete parts (or whole songs) as needed.
&nbsp;Hover over any part to see the complete content.
<span style="font-weight:normal">(Note: Romaji settings and key transpose will not change what you see here, but will affect the PDF.)</span>
&nbsp;Clicking the printer icon will disable that part without deleting it (handy if you plan to use it for a different printout).
&nbsp;To force an item to the next page/column, double-click it (double-click again to turn it off).</font></h3>

<form id="layoutform" action="pdfgenerate.php" method="get">
  <input type="hidden" name="sid_list" value="<?php echo $sid_list; ?>" border="0">
  <input type="hidden" id="order" name="order" value="">
  <input type="hidden" id="copy2" name="copy2" value="0">
  <input type="hidden" id="ilong" name="ilong" value="0">
  <table border="0" cellspacing="0" cellpadding="5"><tr><td style="vertical-align:top">
    <strong>Layout:</strong> <select id="formatname" name="formatname" size="1">
      <option value="">Select...</option>
<?php
$sql = "SELECT FormatName FROM pdfformat ORDER BY ListOrder";
if (!$result = mysqli_query($db,$sql)) die("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)<br>");
while ($row = mysqli_fetch_object($result)) {
  echo  "      <option value=\"".$row->FormatName."\">".$row->FormatName."</option>\n";
}
?>
    </select><br>&nbsp;<br>
    <div class="accordion">
      <h3 style="padding-left:25px">Layout Options</h3>
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
          <div class="indented"><a id="instr-none" href="javascript:void(0)">Remove</a></div>
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
    <input type="submit" name="submit" value="Generate PDF" style="text-size:150%; font-weight:bold; padding:5px; margin-top: 20px;">
  </td>
  <td style="vertical-align:top">
    <div id="layout">
      <ul>
<?php
echo $html;
?>
      </ul>
    </div>
  </td></tr></table>
</form>
<?php print_footer(); ?>