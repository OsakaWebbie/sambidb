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
$haschords = preg_match('/\[[^rR]/u',$song->Lyrics);
$hasromaji = preg_match('/\[r\]/iu',$song->Lyrics);

// Display preferences: GET params override session
$showChords = isset($_GET['chords']) ? !empty($_GET['chords']) : (!empty($_SESSION['show_chords']) ? true : false);
$showRomaji = isset($_GET['romaji']) ? !empty($_GET['romaji']) : (!empty($_SESSION['show_romaji']) ? true : false);

header1("Song: ".htmlspecialchars($song->Title, ENT_QUOTES, 'UTF-8'));
?>
<link rel="stylesheet" href="css/jquery-ui.css">
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
  font-family: Arial, Helvetica, sans-serif;
  font-size: 13px;
  margin-top: 0;
  margin-bottom: 0;
  text-indent: -30px;
  padding-left: 30px;
  white-space: pre-wrap;
}

.chordlyrics {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 13px;
  margin: 6px 0 0 0;
  white-space: pre-wrap;
  text-indent: -30px;
  padding-left: 30px;
}

.chordlyrics ruby {
  ruby-align: start;
}

.chordlyrics rt span {
  font-size: 13px;
  font-weight: bold;
  color: #E00000;
  position: relative;
  width: 1px;
  top: 2px;
}

.smallspace {
  font-size: 9px;
  margin-bottom: 0;
  margin-top: 0;
  font-family: Arial, Helvetica, sans-serif;
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
  text-align: left;
}

label.tag {
  white-space: nowrap;
  margin-right: 2em;
  display: inline-block;
}

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
<?php header2(1); ?>

<div class="song-header">
  <h1><?=d2h($song->Title)?></h1>
  <div>
    <?php $inBasket = in_array((int)$sid, $_SESSION['basket'] ?? [], true); ?>
    <button type="button" id="basket-toggle" class="<?=($inBasket ? 'in-basket' : '')?>" data-sid="<?=$sid?>">
      <span id="basket-icon" style="font-size: 18px; font-weight: bold;"><?=($inBasket ? '✓' : '☐')?></span>
      <span id="basket-label"><?=($inBasket ? _('In Basket') : _('Not in Basket'))?></span>
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
$lines = preg_split("/\r\n|\n\r|\n|\r/", $song->Lyrics);
foreach ($lines as $line) {
  $romajiclass = preg_match('/\[r\]/iu',$line) ? ' romaji' : '';
  $line = preg_replace('/\[r\]/iu','',$line);
  if ($line == "") {  //blank line
    echo "      <div class='smallspace".$romajiclass."'>&nbsp;</div>\n";
  } elseif (strpos($line, "[")===FALSE) {  //no chords in this line
    echo '      <div class="lyrics'.$romajiclass.'">'.htmlspecialchars($line, ENT_QUOTES, 'UTF-8')."</div>\n";
  } elseif (substr_count($line,"[")==1 && substr($line,0,1)=="[" && substr($line,strlen($line)-1,1)=="]") {  //chords only in this line
    echo '      <div class="chords">'.htmlspecialchars(substr($line,1,strlen($line)-2), ENT_QUOTES, 'UTF-8')."</div>\n";
  } else {
    echo '      <div class="chordlyrics'.$romajiclass.'">'.chordsToRuby($line)."</div>\n";
    echo '      <div class="lyrics chordshidden'.$romajiclass.'">'.htmlspecialchars(preg_replace('/\[[^\[]*\]/u','',$line), ENT_QUOTES, 'UTF-8')."</div>\n";
  }
}
?>
    </div>
  </div>

  <div class="info-sidebar">
    <?php if ($song->Title != $song->OrigTitle) { ?>
      <b><?=_('Original Title:')?></b> <?=d2h($song->OrigTitle)?><br><br>
    <?php } ?>

    <b><?=_('Key:')?></b> <span style="color: #00C000;"><?=($song->SongKey ? d2h($song->SongKey) : "?")?></span>
    <?php if ($song->Tempo) { ?>
      &nbsp;&nbsp;<b><?=_('Tempo:')?></b> <span style="color: #C00000;"><?=$song->Tempo?></span>
    <?php } ?>
    <br>

    <?php if ($song->Composer) { ?>
      <b><?=_('Composer:')?></b> <?=d2h($song->Composer)?><br>
    <?php } ?>

    <?php if ($song->Copyright) { ?>
      <b><?=_('Copyright:')?></b> <?=d2h($song->Copyright)?><br>
    <?php } ?>

    <?php if ($song->Source) { ?>
      <br><b><?=_('Source(s):')?></b>
      <div style="margin-left: 20px;"><?=url2link(d2h($song->Source))?></div>
    <?php } ?>

    <?php if ($song->Pattern) { ?>
      <br><b><?=_('Pattern of Stanzas:')?></b> <?=d2h($song->Pattern)?>
    <?php } ?>

    <?php if ($song->Instruction) { ?>
      <br><b><?=_('Instruction (intro, etc.):')?></b> <?=d2h($song->Instruction)?>
    <?php } ?>

    <?php if ($song->Audio) { ?>
      <br><br>
      <b><?=_('Audio for learning:')?></b><br>
      <div style="display: flex; align-items: center; gap: 5px; max-width: 500px;">
        <audio id="audioplayer" controls controlsList="nodownload" style="flex: 1 1 auto; min-width: 0;">
          <source src="sendaudio.php?sid=<?=$_GET['sid']?>">
        </audio>
        <span id="audioloop" title="<?=_('Play in loop')?>" style="font-size: 20px; cursor: pointer; flex-shrink: 0;">&#x1f501;</span>
      </div>

      <?php if ($song->AudioComment) { ?>
        <br><br><b><?=_('Comment about audio recording:')?></b> <?=d2h($song->AudioComment)?>
      <?php } ?>
    <?php } ?>

    <?php if ($_SESSION['access'] > 0) { ?>
      <div style="margin-top: 15px;">
        <button type="button" class="ui-button ui-corner-all" onclick="window.location='edit.php?sid=<?=$sid?>'">
          <?=_('Edit This Song')?>
        </button>
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

<script src="js/jquery-3.6.0.min.js"></script>
<script src="js/jquery-ui.min.js"></script>
<script>
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
      url: 'ajax_actions.php',
      type: 'POST',
      data: { action: action, sid: sid },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          if (action === 'BasketAdd') {
            $btn.addClass('in-basket');
            $btn.find('#basket-icon').text('✓');
            $btn.find('#basket-label').text('<?=addslashes(_('In Basket'))?>');
          } else {
            $btn.removeClass('in-basket');
            $btn.find('#basket-icon').text('☐');
            $btn.find('#basket-label').text('<?=addslashes(_('Not in Basket'))?>');
          }
          window.updateBasketCount(response.basketCount);
        } else if (response.error) {
          alert(response.error);
        }
      },
      error: function() {
        alert('<?=addslashes(_('Error updating basket.'))?>');
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
      url: 'ajax_actions.php',
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
        .prop('title', player.loop ? '<?=addslashes(_('Disable looping'))?>' : '<?=addslashes(_('Play in loop'))?>');
  });
});
</script>

<?php footer(); ?>
