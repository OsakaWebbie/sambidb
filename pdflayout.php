<?php
include("functions.php");
include("accesscontrol.php");

if (isset($_GET['action']) && $_GET['action'] === 'PdfFormatData') {
  header('Content-Type: application/json;charset=utf-8');
  $name = mysqli_real_escape_string($db, $_GET['value'] ?? '');
  $sql = "SELECT TitleNumbering, TitleWithKey, Instruction, Credit, Chords, Romaji, UseColor FROM pdfformat WHERE FormatName = '$name'";
  $result = mysqli_query($db, $sql);
  if (!$result || mysqli_num_rows($result) == 0) {
    echo json_encode(['success' => false]);
  } else {
    echo json_encode(['success' => true, 'data' => mysqli_fetch_assoc($result)]);
  }
  exit;
}

header1(_("Layout Prep for PDF or Powerpoint"));
?>
  <link rel="stylesheet" type="text/css" href="css/jquery-ui.css">
<?php
header2(1);


$sql = "SELECT * from song WHERE SongID IN ($sid_list) ORDER BY FIELD(SongID,$sid_list)";
if (!$result = mysqli_query($db,$sql)) die("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br><pre>[$sql]</pre>");

?>
<script>
var songs = {};
<?php
$html = '';
$bad_songs = [];  // songs whose Pattern references nonexistent stanzas
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
  $linecounts = array();  //count lines excluding romaji ([r]...) lines
  $stripped = array();  //version with chords/[r] brackets removed, for tooltip
  foreach ($stanzas as &$stanza) {
    $nochords = preg_replace("#\[[^\[]*\]#","",$stanza);
    $snippets[] = mb_ereg_replace("  ","&nbsp;&nbsp;",htmlspecialchars(mb_strcut($nochords,0,30),ENT_QUOTES));
    $linecounts[] = count(preg_grep('/^\s*\[r\]/i', explode("\n", $stanza), PREG_GREP_INVERT));
    $stripped[] = trim(mb_ereg_replace("  ","&nbsp;&nbsp;",htmlspecialchars($nochords,ENT_QUOTES)));
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
    $html .= " (".htmlspecialchars(_('Key'),ENT_QUOTES).":".preg_replace("/^([A-G][#b]?m?).*$/","$1",$song->SongKey)."<select name='trans".$song->SongID."'>\n";
    for ($i=-6;$i<6;$i++) {
      $html .= ($i==0 ? "<option value=\"0\" selected> </option>" : ("<option value=\"".($i<0?$i+12:$i)."\">".($i>0 ? ("+".$i) : $i)."</option>"));
    }
    $html .= "</select>)\n";
  }
  $html .= "</div><div class=\"right\"><img src=\"graphics/copy.gif\" class=\"copy\" title=\"".htmlspecialchars(_('Duplicate'),ENT_QUOTES)."\">".
  "<img src=\"graphics/delete.gif\" class=\"delete\" title=\"".htmlspecialchars(_('Remove'),ENT_QUOTES)."\"></div><div class=\"clear\"></div>\n";
  if (trim($song->Lyrics) != "") {
    $html .= "  <ul>\n";
    $has_bad_pattern = false;
    foreach ($patternarray as $letter) {
      $i = ord($letter)-65;
      if (!isset($stanzas[$i])) {  // Pattern references a stanza that doesn't exist
        $has_bad_pattern = true;
        continue;
      }
      $html .= "    <li class='s".$song->SongID.$letter." ui-state-default stanza' title='".$stripped[$i].
      "'>";
      $html .= "<div class=\"left\"><img src=\"graphics/print.gif\" class=\"print\" title=\"".htmlspecialchars(_('Turn output on or off'),ENT_QUOTES)."\">";
      $html .= "<img src='graphics/".(preg_match('/\[[^rR]/ui',$stanzas[$i]) ? "guitar.gif' title=\"".htmlspecialchars(_('Turn chords on or off'),ENT_QUOTES)."\"" :
      "clear_pixel.gif' width='16'")." class='chords'>";
      $html .= "[".$letter."] ".$snippets[$i]."...(".$linecounts[$i].")";
      $html .= "</div><div class=\"right\"><img src=\"graphics/copy.gif\" class=\"copy\" title=\"".htmlspecialchars(_('Duplicate'),ENT_QUOTES)."\"><img src=\"graphics/delete.gif\" class=\"delete\" title=\"".htmlspecialchars(_('Remove'),ENT_QUOTES)."\"></div><div class=\"clear\"></div></li>\n";
    }
    $html .= "  </ul>\n";
    if ($has_bad_pattern) $bad_songs[] = $song->Title;
  }
  $html .= "</li>\n";
}
if (!empty($bad_songs)) {
  echo "alert(".json_encode(_("These song(s) have a Pattern that references more sections than exist in the Lyrics ".
      "(likely because the Lyrics were edited without updating the Pattern):")."\n\n".implode("\n", $bad_songs).
      "\n\n"._("The output will skip the missing parts but may look wrong. You can edit the song(s) in another tab and then refresh this page.")).");\n";
}
?>
</script>
<style>
  #help-section, #layoutform { visibility:hidden; } /* shown after jQuery UI effects are applied */
  .help p { text-align:left; }

  #help-section { text-align:center; }
  .help h3, .help h4 { margin:0.3em 0 0.2em 0; }
  .help p { margin:0 0 0.7em 0; }
  .help-btn { margin: 0 20px 5px 20px; background-color: var(--secondary-dark); color: white; border: 1px solid var(--secondary-medium); font-weight: bold; font-size: 90%; }

  @media(min-width: 901px) {
    #layout { float:right; width:49%; }
    #settings { float:left; width:49%; }
  }

  div.output-section {
    border:2px solid var(--primary-medium);
    padding:8px;
    margin-bottom: 10px;
  }

  div.adjustments { margin:0 0 1em 0; border:1px solid black; padding:5px 0;line-height:1.3em; }
  div.adjustments fieldset legend { font-weight:bold; font-style:italic; }
  div.adjustments h3 { margin-top:0; }
  div.adjustments div.indented { margin-left:1em; padding-left:2em; text-indent:-2em; }
  div.adjustments div.stanza-resets { text-indent:0; padding-left:0; }
  div.adjustments div.stanza-resets button { margin:2px 4px 2px 0; }
  div.adjustments div.side-by-side { display:flex; flex-wrap:wrap; column-gap:2em; }
  div.adjustments div.side-by-side>div { margin-bottom:1em; }
  div.adjustments.preset-needed { cursor:not-allowed; }
  div.adjustments.preset-needed > * { pointer-events:none; }
  
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
<div id="help-section">
  <div class="help" id="pdf-help" title="<?=_('How to make a PDF for printing or tablet')?>">
    <h4><?=_('Sections and order')?></h4>
    <p><?=_('Drag sections (or even whole songs) into the order you want. Hover over any section to see the complete content.')?></p>
    <p><?=sprintf(
      _('Click %1$s to duplicate an item, %2$s to delete it, or %3$s to disable an item temporarily without deleting it.'),
      '<img src="graphics/copy.gif">',
      '<img src="graphics/delete.gif">',
      '<img src="graphics/print.gif">'
    )?></p>
    <h4><?=_('Chords')?></h4>
    <p><?=sprintf(
      _('Selecting a layout preset will enable or disable all chords, but after that, you can show/hide them individually by clicking %s.'),
      '<img src="graphics/guitar.gif">'
    )?></p>
    <p><?=_('Next to the song title and key is a menu you can use to transpose the chords of that song in the PDF.')?></p>
    <h4><?=_('Preset and options')?></h4>
    <p><?=_('First select a PDF Layout Preset. Then you can optionally click on "PDF Layout Options" and'
      .' change any of those settings to customize your output.')?></p>
    <h4><?=_('How to use')?></h4>
    <p><?=_('When printing the PDF, for best results, turn off any setting that "fits" to the page - set to "actual size".')?></p>
    <h4><?=_('Contact Karen if you need new/different layout presets.')?></h4>
  </div>
  <div class="help" id="pp-help" title="<?=_('How to make Powerpoint slides')?>">
    <h4><?=_('Sections and order')?></h4>
    <p><?=_('Drag sections (or even whole songs) into the order you want. Hover over any section to see the complete content.')?></p>
    <p><?=sprintf(
      _('Click %1$s to duplicate an item, %2$s to delete it, or %3$s to disable an item temporarily without deleting it.'),
      '<img src="graphics/copy.gif">',
      '<img src="graphics/delete.gif">',
      '<img src="graphics/print.gif">'
    )?></p>
    <p><?=_('(Chords are never used for Powerpoint.)')?></p>
    <h4><?=_('Options')?></h4>
    <p><?=_('You can optionally click on "Powerpoint Text Options" and'
      .' change any of those settings to customize your output. The "max lines" setting controls whether multiple'
      .' short sections will fit on one slide. (NOTE: Single sections are not split between slides, so a very'
      .' long one might overfill a slide by itself.)')?></p>
    <p><?=_('The combatibility defaults are the best settings for Windows. Powerpoint on Mac might need different'
      .' settings (I cannot test that, so I\'m not sure).')?></p>
    <h4><?=_('How to use')?></h4>
    <p><?=_('Save the text file. Open Powerpoint, preferably with a template that has been prepared for your usage.'
      .' With a slide of the desired layout selected, click "New Slide" on the Home tab, click'
      .' "do "Slides from Outline" (it might be worded differently depending on the Powerpoint version), and'
      .' choose the text file you saved. It will make new slides using the styles of the master layout:'
      .' song titles will be in the Title block and everything else in the Text block -'
      .' main lyrics as Outline Level 1, romaji as Level 2, and composer/copyright as Level 3.')?></p>
    <p><?=sprintf(
      _('%1$sClick here%2$s to download a starter template you can modify for your needs.'),
      '<a href="tools/SambiDB.potx" download target="_blank">',
      '</a>'
    )?></p>
  </div>
  <button class="help-btn ui-button ui-corner-all" id="pdf-help-btn"><?=_('Floating Guide for PDFs')?></button>
  <button class="help-btn ui-button ui-corner-all" id="pp-help-btn"><?=_('Floating Guide for Powerpoint')?></button>
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
      <div style="margin-bottom:10px;"><label><strong><?=_('PDF Layout Preset')?>:</strong>
        <select id="formatname" name="formatname" size="1">
          <option value=""><?=_('Select...')?></option>
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
        <h3 style="padding-left:25px;"><?=_('PDF Layout Options')?></h3>
        <div class="adjustments preset-needed">
          <fieldset>
            <legend><?=_('Content Settings')?></legend>
            <div class="side-by-side">
              <div>
                <h5><?=_('Song numbering')?>:</h5>
                <div class="indented"><label><input type="radio" id="title-numnone" name="tnum" value="none" checked><?=_('None')?></label>
                <label><input type="radio" id="title-numbasic" name="tnum" value="basic">"1."</label>
                <label><input type="radio" id="title-numcircle" name="tnum" value="circle">"①"</label></div>
                <label style="display:block; margin-top:1em;"><input type="checkbox" id="title-key" name="tkey"><?=_('Include "[key]" after titles')?></label>
              </div>
              <div>
                <h5><?=_('Romaji (lines prefaced with "[r]")')?>:</h5>
                <div class="indented"><label><input type="radio" id="romaji-chordless" name="romaji" value="chordless" checked><?=_('Show all lines, but omit chords on romaji')?></label></div>
                <div class="indented"><label><input type="radio" id="romaji-hide" name="romaji" value="hide"><?=_('Hide romaji')?></label></div>
                <div class="indented"><label><input type="radio" id="romaji-only" name="romaji" value="only"><?=_('Show <em>only</em> romaji (in sections that have it)')?></label></div>
                <div class="indented"><label><input type="radio" id="romaji-showall" name="romaji" value="showall"><?=_('Show all content')?></label></div>
              </div>
            </div>
            <div class="side-by-side">
              <div>
                <h5><?=_('Instructions')?>:</h5>
                <div class="indented"><label><input type="radio" id="instrshort" name="instr" value="short"><?=_('Short (omit "[text in brackets]")')?></label></div>
                <div class="indented"><label><input type="radio" id="instrlong" name="instr" value="long"><?=_('Long (include "[text]")')?></label></div>
                <div class="indented"><label><input type="radio" id="instrnone" name="instr" value="none" checked><?=_('None')?></label></div>
              </div>
              <div>
                <h5><?=_('Copyright info')?>:</h5>
                <div class="indented"><label><input type="radio" id="copyright-before" name="credit" value="before"><?=_('Before lyrics, one line')?></label></div>
                <div class="indented"><label><input type="radio" id="copyright-before-twoline" name="credit" value="before-twoline"><?=_('Before lyrics, two lines')?></label></div>
                <div class="indented"><label><input type="radio" id="copyright-after" name="credit" value="after"><?=_('After lyrics, one line')?></label></div>
                <div class="indented"><label><input type="radio" id="copyright-after-twoline" name="credit" value="after-twoline"><?=_('After lyrics, two lines')?></label></div>
                <div class="indented"><label><input type="radio" id="copyright-none" name="credit" value="none" checked><?=_('None')?></label></div>
              </div>
            </div>
            <h5><?=_('All-section actions')?>:</h5>
            <div class="indented stanza-resets">
              <button type="button" id="nochords" class="ui-button ui-corner-all"><?=_('Disable all chords')?></button>
              <button type="button" id="allchords" class="ui-button ui-corner-all"><?=_('Enable all chords')?></button>
              <button type="button" id="allprint" class="ui-button ui-corner-all"><?=_('Re-enable all output')?></button>
            </div>
          </fieldset>
          <fieldset>
            <legend><?=_('PDF Settings')?></legend>
            <?=_('Override paper size (optional)')?>: <select name="papersize" size="1">
            <option value="" selected> </option>
            <option value="a4">A4</option>
            <option value="b5">B5</option>
            <option value="a5">A5</option>
            </select>
            <label style="display:block; margin-top:1em;"><input type="checkbox" id="usecolor" name="color" value="yes" checked> <?=_('Use Color')?></label>
          </fieldset>
        </div>
      </div>
      <div>
        <input type="submit" id="pdfgenerate" name="submit" value="<?=_('Generate PDF')?>"
                  class="ui-button ui-corner-all" style="font-size:120%; font-weight:bold; margin-top: 10px;">
      </div>
    </div>

    <div id="powerpoint-controls" class="output-section">
      <div class="accordion">
        <h3 style="padding-left:25px;"><?=_('Powerpoint Text Options')?></h3>
        <div class="adjustments">
          <fieldset>
            <legend><?=_('Content Settings')?></legend>
            <label><?=_('Max lines per slide')?>: <input name="pp_lines" value="8" style="width:2em"></label><br>
            <label><input type="checkbox" name="pp_trim" checked><?=_('Trim leading spaces')?></label><br>
            <label><input type="checkbox" name="pp_slidenum" checked><?=_('Include slide # ("[2/4]") after song title')?></label><br><br>
            <h5><?=_('Romaji (lines prefaced with "[r]")')?>:</h5>
            <div class="indented"><label><input type="radio" id="ppromaji-all" name="pp_romaji" value="all" checked><?=_('Show all')?></label></div>
            <div class="indented"><label><input type="radio" id="ppromaji-hide" name="pp_romaji" value="hide"><?=_('Hide romaji')?></label></div>
            <div class="indented"><label><input type="radio" id="ppromaji-only" name="pp_romaji" value="only"><?=_('Show <em>only</em> romaji (in sections that have it)')?></label></div>
          </fieldset>
          <fieldset>
            <legend><?=_('Compatibility Settings')?></legend>
            <label><input type="checkbox" name="pp_crlf" checked><?=_('Windows line endings')?></label><br>
            <label><input type="checkbox" name="pp_ms" checked><?=_('Convert to UTF-16 LE (Microsoft)')?></label><br>
          </fieldset>
        </div>
      </div>
      <div>
        <input type="submit" id="powerpoint" name="submit" value="<?=_('Generate Text for PP')?>"
                  class="ui-button ui-corner-all" style="font-size:120%; font-weight:bold; margin-top: 10px;">
      </div>
    </div>
  </div>
</form>

<script type="text/JavaScript" src="js/jquery-3.6.0.min.js"></script>
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

  // Block interaction with PDF Layout Options until a preset is selected
  $("#pdf-controls .adjustments").click(function() {
    if ($(this).hasClass('preset-needed')) {
      alert(<?=json_encode(_('Please select a layout preset first.'))?>);
    }
  });

  $("#formatname").change(function(e) {
    var preset = $(this).val();
    $("#pdf-controls .adjustments").toggleClass('preset-needed', preset === '');
    if (preset === '') return;
    // Get options based on newly selected format, and change as needed on page
    $.getJSON("pdflayout.php", {
      action:'PdfFormatData',
      value:preset
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
  $("input[id^='instr']").click(function() {
    linkid = this.id;
    $("#layout ul li ul li.instr").remove();
    if (linkid != "instrnone") {
      $("#layout ul li.song").each(function() {
        thisid = this.className.match(/s([0-9]+)/)[1];
        if (songs[thisid][linkid].length) {
          $(this).children("ul").prepend('<li class="s'+thisid+'i ui-state-default instr" title="'+songs[thisid][linkid]+
              '"><div class="left"><img src="graphics/print.gif" class="print" title="<?=htmlspecialchars(_('Turn output on or off'),ENT_QUOTES)?>"><?=htmlspecialchars(_('Instructions'),ENT_QUOTES)?> ('+
              songs[thisid].title+')</div><div class="right"><img src="graphics/delete.gif" class="delete" title="<?=htmlspecialchars(_('Remove'),ENT_QUOTES)?>"></div><div class="clear"></div></li>');
        }
      });
    }
    $("#ilong").val(linkid=='instrlong'?'1':'0');
  });

// ACTIONS RELATED TO ADDING/REMOVING CREDITS
  $("input[id^='copyright']").click(function() {
    linkid = this.id;
    $("#layout ul li ul li.copyright").remove();
    if (linkid != "copyright-none") {
      $("#layout ul li.song").each(function() {
        thisid = this.className.match(/s([0-9]+)/)[1];
        if (songs[thisid].composer.length || songs[thisid].copyright.length) {
          html = '<li class="s'+thisid+'c ui-state-default copyright" title="'+
              songs[thisid].composer+(linkid.match(/twoline/)?'\n':'; ')+songs[thisid].copyright+
              '"><div class="left"><img src="graphics/print.gif" class="print" title="<?=htmlspecialchars(_('Turn output on or off'),ENT_QUOTES)?>"><?=htmlspecialchars(_('Copyright info'),ENT_QUOTES)?> ('+
              songs[thisid].title+')</div><div class="right"><img src="graphics/delete.gif" class="delete" title="<?=htmlspecialchars(_('Remove'),ENT_QUOTES)?>"></div><div class="clear"></div></li>';
          if (linkid.match(/before/)) $(this).children("ul").prepend(html); else $(this).children("ul").append(html);
        }
      });
    }
    $("#copy2").val(linkid.match(/twoline/)?'1':'0');
  });

// ACTIONS RELATED TO GUITAR CHORD TOGGLE ICONS
  $("#layout").on("click","img.chords",function() {
    if ($(this).attr("src") == "graphics/guitar.gif")  $(this).attr("src","graphics/noguitar.gif");
    else  $(this).attr("src","graphics/guitar.gif");
  });
  $("#allchords").click(function() {
    $("img[src='graphics/noguitar.gif']").attr("src","graphics/guitar.gif");
  });
  $("#nochords").click(function() {
    $("img[src='graphics/guitar.gif']").attr("src","graphics/noguitar.gif");
  });

// ACTIONS RELATED TO PRINT TOGGLE ICONS
  $("#layout").on("click","img.print",function() {
    if ($(this).attr("src") == "graphics/print.gif")  $(this).attr("src","graphics/noprint.gif");
    else  $(this).attr("src","graphics/print.gif");
  });
  $("#allprint").click(function() {
    $("img[src='graphics/noprint.gif']").attr("src","graphics/print.gif");
  });

// ACTIONS RELATED TO COPY ICONS
  $("#layout").on("click","img.copy",function() {
    var original = $(this).closest("li");
    var cloned = $(original).clone(true,true);
    $(original).after($(cloned));
  });
// ACTIONS RELATED TO DELETE ICONS
  $("#layout").on("click","img.delete",function() {
    $(this).closest("li").remove();
  });
// DOUBLE-CLICK TO TOGGLE COLUMN BREAK
  $("#layout").on("dblclick","li",function(e) {
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
      alert(<?=json_encode(_('Please select a layout preset first.'))?>);
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
<?php footer(); ?>