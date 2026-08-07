<?php
include("functions.php");
include("accesscontrol.php");

if ((!isset($_GET['sid']) && !isset($_POST['sid'])) || !(is_numeric($_GET['sid']) || is_numeric($_POST['sid']))) {
  die("SongID not passed.");
}
$sid = isset($_POST['sid']) ? $_POST['sid'] : $_GET['sid'];

//Tag changes
if (isset($_POST['newtag'])) {
  $result = sqlquery_checked("SELECT t.TagID, t.Tag, st.SongID ".
      "FROM tag t LEFT JOIN songtag st ON t.TagID=st.TagID and st.SongID=$sid ".
      "ORDER BY case when st.SongID is null then 1 else 0 end, t.Tag");
  while ($row = mysqli_fetch_object($result)) {
    $tagid = $row->TagID;
    if ($row->SongID && empty($_POST[$tagid])) sqlquery_checked("DELETE from songtag WHERE TagID=$tagid and SongID=$sid");
    elseif (!$row->SongID && !empty($_POST[$tagid])) sqlquery_checked("INSERT INTO songtag(TagID,SongID) VALUES($tagid,$sid)");
  }
  header("Location: song.php?sid=".$sid);
  exit;
}

$result = sqlquery_checked("SELECT * FROM song WHERE SongID=$sid");
if (mysqli_num_rows($result) == 0) die("<b>".sprintf(_('Song not found (ID: %s).'), $sid)."</b>");
$song = mysqli_fetch_object($result);

$isConverted = song_has_sections($sid);
$sections = $isConverted ? get_song_sections($song) : [];
$effectiveLyrics = $isConverted ? implode("\n", array_map(fn($s) => $s->lyrics, $sections)) : $song->Lyrics;
$haschords = preg_match('/\[[^rR]/u', $effectiveLyrics);
$hasromaji = preg_match('/\[r\]/iu', $effectiveLyrics);

// Arrangement editor data (editors only; patterns reference saved section ids).
$arrEnabled  = ($_SESSION['access'] > 0) && $isConverted;
$arrPool     = array();
$arrPatterns = array();
if ($arrEnabled) {
  $arrJP = ($_SESSION['lang'] ?? '') === 'ja_JP';
  $langs = array();
  foreach ($sections as $s)
    if ($s->language !== '' && !in_array($s->language, $langs, true)) $langs[] = $s->language;
  $multiLang = count($langs) > 1;
  foreach ($sections as $s) {
    if ($s->id < 1) continue;
    $label = $arrJP ? ($s->label_jp ?: $s->label_en) : ($s->label_en ?: $s->label_jp);
    if ($label === '') $label = $s->type;
    if ($multiLang && $s->language !== '') $label .= ' · '.strtoupper($s->language);
    $snip = trim(preg_replace('/\s+/', ' ', preg_replace('/\[[^\]]*\]/', '', $s->lyrics)));
    $arrPool[] = array('id' => $s->id, 'code' => $s->type, 'label' => $label, 'snippet' => mb_substr($snip, 0, 40));
  }
  foreach (get_song_patterns($song) as $p)
    $arrPatterns[] = array('id' => $p->id, 'label' => $p->label, 'is_default' => $p->is_default, 'sections' => $p->sections);
}

// Display preferences: GET params override session
$showChords = isset($_GET['chords']) ? !empty($_GET['chords']) : (!empty($_SESSION['show_chords']) ? true : false);
$showRomaji = isset($_GET['romaji']) ? !empty($_GET['romaji']) : (!empty($_SESSION['show_romaji']) ? true : false);

pageheader("Song: ".htmlspecialchars($song->Title, ENT_QUOTES, 'UTF-8'), 1);
?>
<style>
/* Main layout grid */
.song-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 10px;
}

.song-header h1 {
  margin: 10px 0;
  flex: 1 1 auto;
}

/* Basket toggle button */
#basket-toggle {
  margin: 10px auto 0;
  padding: 10px 20px;
  font-weight: bold;
  border: 2px solid #51579A;
  border-radius: 5px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s ease;
}

#basket-toggle.in-basket {
  background-color: #51579A;
  color: white;
}

#basket-toggle:not(.in-basket) {
  background-color: #d7e4f9;
  color: #51579A;
}

#basket-toggle:hover {
  opacity: 0.85;
}

/* Stack both labels in one grid cell so width = wider of the two */
.basket-label-stack {
  display: inline-grid;
  text-align: center;
}
.basket-label-stack > span {
  grid-area: 1 / 1;
  visibility: hidden;
}
#basket-toggle.in-basket .basket-label-stack > .lbl-in,
#basket-toggle:not(.in-basket) .basket-label-stack > .lbl-out {
  visibility: visible;
}


/* Content grid */
.song-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 30px;
}

/* Lyrics panel */
.lyrics-panel {
  border: 2px solid #51579A;
  padding: 10px;
  background-color: #ffffff;
}

.lyrics {
  /*font-family: Arial, Helvetica, sans-serif;*/
  font-size: 16px;
  margin-top: 0;
  margin-bottom: 0;
  text-indent: -30px;
  padding-left: 30px;
  white-space: pre-wrap;
}

.chordlyrics {
  /*font-family: Arial, Helvetica, sans-serif;*/
  font-size: 16px;
  margin: 6px 0 0 0;
  white-space: pre-wrap;
  text-indent: -30px;
  padding-left: 30px;
}

.chordlyrics ruby {
  ruby-align: start;
}

.chordlyrics rt {
  margin-bottom: 3px;
}
.chordlyrics rt span {
  font-size: 16px;
  font-weight: bold;
  color: #E00000;
  position: relative;
  width: 1px;
  top: 2px;
}

.smallspace {
  font-size: 11px;
  margin-bottom: 0;
  margin-top: 0;
  /*font-family: Arial, Helvetica, sans-serif;*/
}

.section-label {
  font-size: 12px;
  font-weight: bold;
  color: #85001f;
  opacity: 0.85;
  margin: 4px 0 0;
}

<?php
if (!$showChords) {
  echo ".chordlyrics, .chords { display:none; }\n";
} else {
  echo ".chordshidden { display:none; }\n";
}
if (!$showRomaji) {
  echo ".romaji { display:none; }\n";
}
?>


/* Audio loop toggle states */
#audioloop.enabled {
  position: relative;
}

#audioloop.enabled:after {
  content: "✔";
  color: red;
  font-weight: bold;
  position: absolute;
  right: -7px;
  top: 0;
}

.checkboxes {
  border-top: 2px solid #85001f;
  padding: 10px;
}

label.tag {
  white-space: nowrap;
  margin-right: 2em;
  display: inline-block;
}

/* section wrappers (enable live reorder when the default arrangement changes) */
.song-section + .song-section { margin-top: 0.9em; }

/* arrangements editor (sidebar) */
#arrangements { border-top: 2px solid #85001f; margin-top: 1em; padding-top: 0.6em; }
#arrangements h3 { margin: 0 0 4px; font-size: 1em; }
#arr-list { list-style: none; margin: 4px 0 8px; padding: 0; }
#arr-list .arr-row { display: flex; align-items: center; gap: 5px; padding: 3px 0; }
#arr-list .arr-star { cursor: pointer; color: #51579A; width: 1.2em; text-align: center; font-size: 16px; flex: 0 0 auto; }
#arr-list .arr-main { flex: 1 1 auto; min-width: 0; }
#arr-list .arr-name { font-weight: bold; }
#arr-list .arr-codes { margin-left: 8px; color: #666; font-size: 0.85em; word-break: break-word; }
#arr-list .arr-edit, #arr-list .arr-del { font-size: 11px; padding: 1px 7px; flex: 0 0 auto; }
#arr-list .arr-empty { color: #777; font-style: italic; padding: 2px 0; }
.arr-oldpattern { font-size: 0.85em; color: #666; margin-top: 4px; }
/* arrangement dialog */
.ui-dialog { max-width: 96vw; }
.arr-dialog-head { margin-bottom: 6px; }
.arr-default-row { display: block; margin-top: 6px; }
#arr-name { width: 12em; max-width: 60vw; }
.arr-hint { font-size: 12px; color: #555; margin: 4px 0 6px; }
#arr-seq { list-style: none; margin: 0 0 8px; padding: 0; max-height: 60vh; overflow-y: auto; }
#arr-seq .arr-item { display: flex; align-items: center; gap: 4px; padding: 2px 6px; border: 1px solid #ccc; border-radius: 4px; background: #fff; margin-bottom: 4px; }
#arr-seq .arr-item-label { flex: 1 1 auto; }
#arr-seq .drag-handle { display: inline-block; padding: 6px; margin: -2px 2px -2px -2px; cursor: grab; color: #888; user-select: none; font-size: 22px; line-height: 1; vertical-align: middle; }
#arr-seq .drag-handle:active { cursor: grabbing; }
#arr-seq img.arr-copy, #arr-seq img.arr-remove { cursor: pointer; flex: 0 0 auto; }
.arr-placeholder { border: 1px dashed #999; border-radius: 4px; height: 30px; margin-bottom: 4px; }

/* Mobile responsive */
@media (max-width: 900px) {
  .song-content {
    grid-template-columns: 1fr;
  }

  .lyrics-panel {
    width: 100%;
    box-sizing: border-box;
  }

  .info-sidebar {
    margin-top: 20px;
  }
}
</style>

<div class="song-header">
  <h1><?=d2h($song->Title)?></h1>
  <div>
    <?php $inBasket = in_array((int)$sid, $_SESSION['basket'] ?? [], true); ?>
    <button type="button" id="basket-toggle" class="<?=($inBasket ? 'in-basket' : '')?>" data-sid="<?=$sid?>">
      <span id="basket-icon" style="font-size: 18px; font-weight: bold;"><?=($inBasket ? '✓' : '☐')?></span>
      <span class="basket-label-stack">
        <span class="lbl-in"><?=_('In Basket')?></span>
        <span class="lbl-out"><?=_('Not in Basket')?></span>
      </span>
    </button>
    <div style="font-size: 0.75em; color: #666; text-align: center; margin-top: 2px;"><?=_('Click to toggle')?></div>
  </div>
</div>

<div class="song-content">
  <div>
    <?php if ($haschords || $hasromaji) { ?>
    <div style="margin-bottom: 15px; font-weight: bold;">
      <?=_('Show:')?>
      <?php if ($haschords) { ?>
        <label style="margin-right: 15px; cursor: pointer;"><input type="checkbox" id="showchords" <?=($showChords ? 'checked' : '')?>><?=_('Chords')?></label>
      <?php } ?>
      <?php if ($hasromaji) { ?>
        <label style="margin-right: 15px; cursor: pointer;"><input type="checkbox" id="showromaji" <?=($showRomaji ? 'checked' : '')?>><?=_('Romaji')?></label>
      <?php } ?>
    </div>
    <?php } ?>

    <div class="lyrics-panel">
<?php
function render_song_line($line) {
  $romajiclass = preg_match('/\[r\]/iu', $line) ? ' romaji' : '';
  $line = preg_replace('/\[r\]/iu', '', $line);
  if ($line == "") {  //blank line
    return "      <div class='smallspace".$romajiclass."'>&nbsp;</div>\n";
  } elseif (strpos($line, "[") === FALSE) {  //no chords in this line
    return '      <div class="lyrics'.$romajiclass.'">'.htmlspecialchars($line, ENT_QUOTES, 'UTF-8')."</div>\n";
  } elseif (substr_count($line, "[")==1 && substr($line,0,1)=="[" && substr($line,-1)=="]") {  //chords only
    return '      <div class="chords">'.htmlspecialchars(substr($line,1,-1), ENT_QUOTES, 'UTF-8')."</div>\n";
  }
  return '      <div class="chordlyrics'.$romajiclass.'">'.chordsToRuby($line)."</div>\n".
         '      <div class="lyrics chordshidden'.$romajiclass.'">'.htmlspecialchars(preg_replace('/\[[^\[]*\]/u','',$line), ENT_QUOTES, 'UTF-8')."</div>\n";
}

if ($isConverted) {
  // $sections is in the song's default-arrangement order (get_song_sections), each once. Each
  // section is wrapped with its id so the editor can reorder them live when the default changes.
  $jp = ($_SESSION['lang'] ?? '') === 'ja_JP';
  foreach ($sections as $s) {
    echo '      <div class="song-section" data-secid="'.(int)$s->id.'">'."\n";
    $label = $jp ? ($s->label_jp ?: $s->label_en) : ($s->label_en ?: $s->label_jp);
    if ($label !== '') echo '      <div class="section-label">'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8')."</div>\n";
    foreach (preg_split("/\r\n|\n\r|\n|\r/", $s->lyrics) as $line) echo render_song_line($line);
    echo "      </div>\n";
  }
} else {
  foreach (preg_split("/\r\n|\n\r|\n|\r/", $song->Lyrics) as $line) echo render_song_line($line);
}
?>
    </div>
  </div>

  <div class="info-sidebar">
    <?php if ($song->Title != $song->OrigTitle) { ?>
      <p style="margin-bottom:1em"><strong><?=_('Original Title:')?></strong> <?=d2h($song->OrigTitle)?></p>
    <?php } ?>

    <p style="margin-bottom:1em"><strong><?=_('Key:')?></strong> <span style="color: var(--secondary-dark);"><?=($song->SongKey ? d2h($song->SongKey) : "?")?></span>
    <?php if ($song->Tempo) { ?>
      &nbsp;&nbsp;<strong><?=_('Tempo:')?></strong> <span style="color: var(--secondary-dark);"><?=$song->Tempo?></span>
    <?php } ?>
    </p>

    <div style="margin-bottom:1em">
    <?php if ($song->Composer) { ?>
      <div><strong><?=_('Composer:')?></strong> <?=d2h($song->Composer)?></div>
    <?php } ?>

    <?php if ($song->Copyright) { ?>
      <div><strong><?=_('Copyright:')?></strong> <?=d2h($song->Copyright)?></div>
    <?php } ?>

    <?php if ($song->Source) { ?>
      <strong><?=_('Source(s):')?></strong>
      <div style="margin-left: 20px;"><?=url2link(d2h($song->Source))?></div>
    <?php } ?>
    </div>

    <?php if ($song->Pattern && !$isConverted) { ?>
      <div style="margin-bottom:1em"><strong><?=_('Pattern of Sections:')?></strong> <?=d2h($song->Pattern)?></div>
    <?php } ?>

    <?php if ($song->Instruction) { ?>
      <div style="margin-bottom:1em"><strong><?=_('Instruction (intro, etc.):')?></strong> <?=d2h($song->Instruction)?></div>
    <?php } ?>

    <?php if ($song->Audio) { ?>
      <strong><?=_('Audio for learning:')?></strong><br>
      <div style="display: flex; align-items: center; gap: 5px; max-width: 500px;">
        <audio id="audioplayer" controls controlsList="nodownload" style="flex: 1 1 auto; min-width: 0;">
          <source src="sendaudio.php?sid=<?=$_GET['sid']?>">
        </audio>
        <span id="audioloop" title="<?=_('Play in loop')?>" style="font-size: 20px; cursor: pointer; flex-shrink: 0;">&#x1f501;</span>
      </div>

      <?php if ($song->AudioComment) { ?>
        <div style="margin-top:0.5em"><strong><?=_('Comment about audio recording:')?></strong> <?=d2h($song->AudioComment)?></div>
      <?php } ?>
    <?php } ?>

    <?php if ($_SESSION['access'] > 0) { ?>
      <div style="margin-top: 1em;">
        <button type="button" class="ui-button ui-corner-all" onclick="window.location='edit.php?sid=<?=$sid?>'">
          <?=_('Edit This Song')?><?=($song->NeedsWork ? ' ⚠' : '')?>
        </button>
      </div>
    <?php } ?>

    <?php if ($arrEnabled) { ?>
      <div id="arrangements">
        <h3 title="<?=htmlspecialchars(_('Named orderings of this song\'s sections (with repeats) for output — e.g. English-only or bilingual. The default (★) sets the section display order.'), ENT_QUOTES, 'UTF-8')?>"><?=_('Arrangements')?></h3>
        <ul id="arr-list"></ul>
        <button type="button" id="arr-new" class="ui-button ui-corner-all"><?=_('New arrangement')?></button>
        <?php if ($song->Pattern) { ?>
          <div class="arr-oldpattern"><?=_('Old pattern (reference):')?> <?=d2h($song->Pattern)?></div>
        <?php } ?>
      </div>
    <?php } ?>
  </div>
</div>

<!-- Tags Section -->
<form action="song.php" method="POST">
  <section>
    <h2 class="section-title"><?=_('Tags')?></h2>
    <?php if ($_SESSION['access'] > 0) { ?>
      <input type="submit" value="<?=_('Save Tag Changes')?>" name="newtag" class="ui-button ui-corner-all" style="margin:0 0 0 20px;">
    <?php } ?>
    <input type="hidden" name="sid" value="<?=$sid?>">

    <?php
    $result = mysqli_query($db,"SELECT t.TagID, t.Tag, st.SongID ".
        "FROM tag t LEFT JOIN songtag st ON t.TagID=st.TagID and st.SongID=$sid ".
        "ORDER BY case when st.SongID is null then 1 else 0 end, t.Tag");
    if (!$result) {
      echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b>");
    } else {
      echo '<div class="checkboxes" style="margin-top:10px;">'."\n";
      while ($row = mysqli_fetch_object($result)) {
        if (!($row->SongID)) {
          if ($_SESSION['access'] > 0) {
            echo '<div class="clear"></div></div><div class="checkboxes">'."\n".
                '<label class="tag"><input type="checkbox" name="'.$row->TagID.'">'.d2h($row->Tag).'</label>'."\n";
          }
          break;
        }
        echo '<label class="tag"><input type="checkbox" name="'.$row->TagID.'" checked>'.d2h($row->Tag).'</label>'."\n";
      }
      if ($_SESSION['access'] > 0) {
        while ($row = mysqli_fetch_object($result)) {
          echo '<label class="tag"><input type="checkbox" name="'.$row->TagID.'">'.d2h($row->Tag).'</label>'."\n";
        }
      }
      echo '<div class="clear"></div></div>';
    }
    ?>
  </section>
</form>

<!-- Usage History -->
<?php
$sql = "SELECT e.Event, min(u.UseDate) AS first, max(u.UseDate) AS last,".
    " COUNT(u.UseDate) AS times, e.Remarks FROM event e, history u WHERE e.EventID = u.EventID".
    " AND u.SongID = ".$sid." GROUP BY e.Event ORDER BY last";
$result = mysqli_query($db,$sql);
if (!$result) {
  echo ("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
} elseif (mysqli_num_rows($result) == 0) {
  echo ("<p>"._('No history records.')."</p>");
} else {
?>
  <section>
    <h2 class="section-title"><?=_('Usage History')?></h2>
    <table>
      <?php while ($row = mysqli_fetch_object($result)) { ?>
        <tr>
          <td nowrap><?=d2h($row->Event)?></td>
          <td nowrap>
            <?php if ($row->first == $row->last) {
              echo $row->first;
            } else {
              echo sprintf(_('%s to<br>%s (%sx)'), $row->first, $row->last, $row->times);
            } ?>
          </td>
          <td><?=d2h($row->Remarks)?>&nbsp;</td>
        </tr>
      <?php } ?>
    </table>
  </section>
<?php } ?>

<?php if ($arrEnabled) { ?>
<!-- Arrangement editor popup (drag to order, duplicate for repeats) -->
<div id="arr-dialog" title="<?=_('Edit Arrangement')?>" style="display:none">
  <div class="arr-dialog-head">
    <label><?=_('Name')?> <input type="text" id="arr-name" maxlength="60"></label>
    <label class="arr-default-row"><input type="checkbox" id="arr-default"> <?=_('Make default')?></label>
  </div>
  <p class="arr-hint"><?=_('Drag to reorder; use the icons to repeat or remove a section.')?></p>
  <ul id="arr-seq"></ul>
  <button type="button" id="arr-add-omitted" class="ui-button ui-corner-all"><?=_('Add omitted sections')?></button>
</div>
<?php } ?>

<?php load_scripts(['jquery', 'jqueryui']); ?>
<?php if ($arrEnabled) { ?>
<script src="js/jquery.ui.touch-punch.min.js?v=<?=filemtime('js/jquery.ui.touch-punch.min.js')?>"></script>
<?php } ?>
<script>
var arrEnabled = <?= $arrEnabled ? 'true' : 'false' ?>;
var SID = <?= (int)$sid ?>;
var arrPool = <?= json_encode($arrPool, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
var arrPatterns = <?= json_encode($arrPatterns, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
$(document).ready(function(){
  // Audio player context menu prevention
  $('audio').bind('contextmenu',function() { return false; });

  // Basket toggle
  $('#basket-toggle').click(function() {
    var $btn = $(this);
    var sid = $btn.data('sid');
    var inBasket = $btn.hasClass('in-basket');
    var action = inBasket ? 'BasketRemove' : 'BasketAdd';

    $.ajax({
      url: 'ajax_request.php',
      type: 'POST',
      data: { action: action, sid: sid },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          if (action === 'BasketAdd') {
            $btn.addClass('in-basket');
            $btn.find('#basket-icon').text('✓');
          } else {
            $btn.removeClass('in-basket');
            $btn.find('#basket-icon').text('☐');
          }
          window.updateBasketCount(response.basketCount);
        } else if (response.error) {
          alert(response.error);
        }
      },
      error: function() {
        alert('<?=_('Error updating basket.')?>');
      }
    });
  });

  // Chords/Romaji display toggle
  $('#showchords, #showromaji').change(function() {
    var showChords = $('#showchords').prop("checked");
    var showRomaji = $('#showromaji').prop("checked");

    // Update display
    if (showChords && showRomaji) {
      $('.chordlyrics, .lyrics, .chords').show();
      $('.chordshidden').hide();
    } else if (showChords && !showRomaji) {
      $('.chordlyrics, .lyrics, .chords').show();
      $('.chordshidden, .romaji').hide();
    } else if (!showChords && showRomaji) {
      $('.lyrics').show();
      $('.chordlyrics, .chords').hide();
    } else {  // no chords or romaji
      $('.lyrics').show();
      $('.chordlyrics, .chords, .romaji').hide();
    }

    // Save preference via AJAX (fire and forget)
    $.ajax({
      url: 'ajax_request.php',
      type: 'POST',
      data: {
        action: 'SetDisplayPref',
        show_chords: showChords ? '1' : '0',
        show_romaji: showRomaji ? '1' : '0'
      },
      dataType: 'json'
    });
  });

  // Audio loop toggle
  $("#audioloop").click(function(){
    var player = $("#audioplayer")[0];
    player.loop = !player.loop;
    $(this).toggleClass('enabled', player.loop)
        .prop('title', player.loop ? '<?=_('Disable looping')?>' : '<?=_('Play in loop')?>');
  });

  // --- Arrangement editor (patterns) ---
  if (arrEnabled) {
    var poolById = {};
    arrPool.forEach(function(s) { poolById[s.id] = s; });
    var secLabel   = function(id) { return poolById[id] ? poolById[id].label   : ('#' + id); };
    var secCode    = function(id) { return poolById[id] ? poolById[id].code    : ('#' + id); };
    var secSnippet = function(id) { return poolById[id] ? poolById[id].snippet : ''; };
    var editingIndex = null;   // index into arrPatterns, or null when adding

    // Section display order for an arrangement: its sections (first occurrence), then any
    // leftover sections by ascending id — matching get_song_sections() server-side.
    function displayOrder(seq) {
      var seen = {}, order = [];
      (seq || []).forEach(function(id) { if (!seen[id]) { seen[id] = 1; order.push(id); } });
      arrPool.map(function(s) { return s.id; }).sort(function(a, b) { return a - b; })
        .forEach(function(id) { if (!seen[id]) { seen[id] = 1; order.push(id); } });
      return order;
    }

    // Live-reorder the rendered section blocks to match the current default arrangement.
    function applyDefaultOrder() {
      var def = null;
      arrPatterns.forEach(function(p) { if (p.is_default) def = p; });
      var panel = document.querySelector('.lyrics-panel');
      if (!panel) return;
      displayOrder(def ? def.sections : []).forEach(function(id) {
        var el = panel.querySelector('.song-section[data-secid="' + id + '"]');
        if (el) panel.appendChild(el);
      });
    }

    function renderArrList() {
      var $ul = $('#arr-list').empty();
      if (!arrPatterns.length) $ul.append('<li class="arr-empty"><?=_('No arrangements yet.')?></li>');
      arrPatterns.forEach(function(p, i) {
        var $li = $('<li class="arr-row"></li>').attr('data-i', i);
        $li.append($('<span class="arr-star"></span>')
          .attr('title', p.is_default ? '<?=_('Default arrangement')?>' : '<?=_('Make default')?>')
          .html(p.is_default ? '&#9733;' : '&#9734;'));
        var $main = $('<span class="arr-main"></span>');
        $main.append($('<span class="arr-name"></span>').text(p.label || '<?=_('(untitled)')?>'));
        $main.append($('<span class="arr-codes"></span>').text(p.sections.map(secCode).join(' ')));
        $li.append($main);
        $li.append('<button type="button" class="arr-edit ui-button ui-corner-all"><?=_('Edit')?></button>');
        $li.append('<button type="button" class="arr-del ui-button ui-corner-all" title="<?=_('Delete')?>">&#215;</button>');
        $ul.append($li);
      });
    }

    function seqItem(id) {
      return $('<li class="arr-item"></li>').attr('data-secid', id).attr('title', secSnippet(id))
        .append('<span class="drag-handle">&#9776;</span>')
        .append($('<span class="arr-item-label"></span>').text(secLabel(id)))
        .append('<img src="graphics/copy.gif" class="arr-copy" alt="" title="<?=_('Duplicate')?>">')
        .append('<img src="graphics/delete.gif" class="arr-remove" alt="" title="<?=_('Remove')?>">');
    }

    function openArrDialog(pattern) {
      $('#arr-name').val(pattern ? pattern.label : '');
      $('#arr-default').prop('checked', pattern ? pattern.is_default : (arrPatterns.length === 0));
      var seq = pattern ? pattern.sections.slice() : arrPool.map(function(s) { return s.id; });
      var $seq = $('#arr-seq').empty();
      seq.forEach(function(id) { $seq.append(seqItem(id)); });
      $seq.sortable('refresh');
      $('#arr-dialog').dialog('open');
    }

    function currentSeq() {
      var ids = [];
      $('#arr-seq li').each(function() { ids.push(parseInt($(this).attr('data-secid'), 10)); });
      return ids;
    }

    function saveArrangement() {
      var ids = currentSeq();
      if (!ids.length) { alert('<?=_('An arrangement needs at least one section.')?>'); return; }
      $.post('ajax_action.php', {
        action: 'PatternSave', sid: SID,
        patternid: editingIndex === null ? 'new' : arrPatterns[editingIndex].id,
        label: $('#arr-name').val(),
        isdefault: $('#arr-default').is(':checked') ? 1 : 0,
        sections: ids.join(',')
      }, function(r) {
        if (!r || !r.success) { alert(r && r.error ? r.error : '<?=_('Save failed. Please try again or check with the administrator.')?>'); return; }
        if (r.pattern.is_default) arrPatterns.forEach(function(p) { p.is_default = false; });
        if (editingIndex === null) arrPatterns.push(r.pattern);
        else arrPatterns[editingIndex] = r.pattern;
        renderArrList();
        applyDefaultOrder();
        $('#arr-dialog').dialog('close');
      }, 'json');
    }

    $('#arr-dialog').dialog({
      autoOpen: false, modal: true, width: 330,
      buttons: [
        { text: '<?=_('Save arrangement')?>', click: saveArrangement },
        { text: '<?=_('Cancel')?>', click: function() { $(this).dialog('close'); } }
      ]
    });
    $('#arr-seq').sortable({ placeholder: 'arr-placeholder', forcePlaceholderSize: true, handle: '.drag-handle' });
    $('#arr-seq').on('click', 'img.arr-copy', function() {
      var $li = $(this).closest('li');
      $li.after($li.clone(true, true));
      $('#arr-seq').sortable('refresh');
    });
    $('#arr-seq').on('click', 'img.arr-remove', function() { $(this).closest('li').remove(); });
    $('#arr-add-omitted').on('click', function() {
      var present = {};
      $('#arr-seq li').each(function() { present[$(this).attr('data-secid')] = true; });
      arrPool.forEach(function(s) { if (!present[s.id]) $('#arr-seq').append(seqItem(s.id)); });
      $('#arr-seq').sortable('refresh');
    });

    $('#arr-new').on('click', function() { editingIndex = null; openArrDialog(null); });
    $('#arr-list').on('click', '.arr-edit', function() {
      editingIndex = $(this).closest('.arr-row').data('i');
      openArrDialog(arrPatterns[editingIndex]);
    });
    $('#arr-list').on('click', '.arr-del', function() {
      var i = $(this).closest('.arr-row').data('i');
      var p = arrPatterns[i];
      if (!confirm('<?=_('Delete arrangement')?> "' + (p.label || '') + '"?')) return;
      $.post('ajax_action.php', { action: 'PatternDelete', sid: SID, patternid: p.id }, function(r) {
        if (!r || !r.success) { alert(r && r.error ? r.error : '<?=_('Delete failed.')?>'); return; }
        arrPatterns.splice(i, 1);
        if (r.newDefault) arrPatterns.forEach(function(pp) { pp.is_default = (pp.id === r.newDefault); });
        renderArrList();
        applyDefaultOrder();
      }, 'json');
    });
    $('#arr-list').on('click', '.arr-star', function() {
      var p = arrPatterns[$(this).closest('.arr-row').data('i')];
      if (p.is_default) return;   // already the default
      $.post('ajax_action.php', {
        action: 'PatternSave', sid: SID, patternid: p.id,
        label: p.label, isdefault: 1, sections: p.sections.join(',')
      }, function(r) {
        if (!r || !r.success) { alert(r && r.error ? r.error : '<?=_('Save failed. Please try again or check with the administrator.')?>'); return; }
        arrPatterns.forEach(function(pp) { pp.is_default = false; });
        p.is_default = true;
        renderArrList();
        applyDefaultOrder();
      }, 'json');
    });

    renderArrList();
  }
});
</script>

<?php footer(); ?>
