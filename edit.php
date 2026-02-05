<?php
ini_set("max_execution_time", "360");
ini_set("max_input_time", "240");
ini_set("memory_limit", "8M");
ini_set("upload_max_filesize","7M");

include("functions.php");
include("accesscontrol.php");

if (!empty($sid)) {  // SongID was passed, so we're editing an existing record
  $sql = "SELECT * FROM song WHERE SongID=$sid";
  if (!$result = mysqli_query($db,$sql)) {
    echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b> ($sql)");
    exit;
  }
  if (mysqli_num_rows($result) == 0) {
    echo("<b>".sprintf(_('Song not found (ID: %s).'), $sid)."</b>");
    exit;
  }
  $rec = mysqli_fetch_object($result);
} else {
  $rec = (object)[
    'Title' => '',
    'OrigTitle' => '',
    'Composer' => '',
    'Copyright' => '',
    'SongKey' => '',
    'Tempo' => '',
    'Source' => '',
    'Pattern' => '',
    'Instruction' => '',
    'Audio' => '',
    'AudioComment' => '',
    'Lyrics' => ''
  ];
}

$isNew = empty($sid);
$pageTitle = $isNew ? _('New Song') : sprintf(_('Edit: %s'), $rec->Title);
$buttonText = $isNew ? _('Save Song') : _('Save Changes');

header1($pageTitle);
?>
<link rel="stylesheet" href="css/jquery-ui.css">
<style>
@media screen and (max-width: 900px) {
  .edit-form-grid { grid-template-columns: 1fr !important; }
  .form-group-lyrics { order: -1; }
  .form-group-lyrics textarea { min-height: 250px !important; }
}
</style>
<?php header2(1); ?>

<h1 id="title"><?=$pageTitle?></h1>

<div style="margin: 12px 0">
  <button type="submit" form="editform" class="edit-save-btn"><?=$buttonText?></button>
  <span class="save-status" style="margin-left: 18px; font-weight: bold"></span>
</div>

<form id="editform" name="editform" enctype="multipart/form-data" method="POST" action="do_edit.php">
<input type="hidden" name="sid" value="<?php echo $sid ?? ''; ?>">
<input type="hidden" name="audio" value="<?php echo $rec->Audio; ?>">

<!-- Grid 1: Title, OrigTitle, Key+Tempo -->
<div class="edit-form-grid" style="grid-template-columns: 1fr 1fr auto">

  <div class="form-group">
    <label for="title"><?=_('Title')?></label>
    <input type="text" name="title" id="title" maxlength="50"
      value="<?php echo htmlspecialchars($rec->Title, ENT_QUOTES, 'UTF-8'); ?>">
  </div>
  <div class="form-group">
    <label for="origtitle"><?=_('Original Title')?>
      <span class="help-icon" data-help="<?=htmlspecialchars(_('Fill in if the song is a translation from another language.'), ENT_QUOTES, 'UTF-8')?>">?</span>
    </label>
    <input type="text" name="origtitle" id="origtitle" maxlength="50"
      value="<?php echo htmlspecialchars($rec->OrigTitle, ENT_QUOTES, 'UTF-8'); ?>">
  </div>
  <!-- Key and Tempo: flex keeps them side-by-side at content size on all screen widths -->
  <div style="display: flex; gap: 20px; align-items: start">
    <div class="form-group">
      <label for="songkey"><?=_('Key')?></label>
      <input type="text" name="songkey" id="songkey" maxlength="8"
        value="<?php echo htmlspecialchars($rec->SongKey, ENT_QUOTES, 'UTF-8'); ?>"
        style="width: 5em">
    </div>
    <div class="form-group">
      <label for="tempo"><?=_('Tempo')?></label>
      <select name="tempo" id="tempo" style="width: auto">
        <option value=""></option>
        <option value="Fast"<?php if ($rec->Tempo=="Fast") echo " selected"; ?>><?=_('Fast')?></option>
        <option value="Medium"<?php if ($rec->Tempo=="Medium") echo " selected"; ?>><?=_('Medium')?></option>
        <option value="Slow"<?php if ($rec->Tempo=="Slow") echo " selected"; ?>><?=_('Slow')?></option>
      </select>
    </div>
  </div>

</div><!-- end grid 1 -->

<!-- Grid 2: Composer and Copyright -->
<div class="edit-form-grid" style="grid-template-columns: 1fr 1fr">

  <div class="form-group">
    <label for="composer"><?=_('Composer')?></label>
    <input type="text" name="composer" id="composer" maxlength="80"
      value="<?php echo htmlspecialchars($rec->Composer, ENT_QUOTES, 'UTF-8'); ?>">
  </div>
  <div class="form-group">
    <label for="copyright"><?=_('Copyright')?></label>
    <input type="text" name="copyright" id="copyright" maxlength="80"
      value="<?php echo htmlspecialchars($rec->Copyright, ENT_QUOTES, 'UTF-8'); ?>">
  </div>

</div><!-- end grid 2 -->

<!-- Main content: sidebar + lyrics -->
<div class="edit-form-grid" style="grid-template-columns: minmax(200px,1fr) minmax(300px,1.6fr); margin-top: 16px">

  <div class="form-group form-group-sidebar">
    <label for="pattern"><?=_('Pattern')?>
      <span class="help-icon" data-help="<?=htmlspecialchars(_('Print pattern for PDF output, e.g. ABABB. Sections in the lyrics are divided by blank lines.'), ENT_QUOTES, 'UTF-8')?>">?</span>
    </label>
    <input type="text" name="pattern" id="pattern" maxlength="80"
      value="<?php echo htmlspecialchars($rec->Pattern, ENT_QUOTES, 'UTF-8'); ?>">

    <label for="instruction" style="margin-top:10px"><?=_('Instructions')?>
      <span class="help-icon" data-help="<?=htmlspecialchars(_('Notes for playing (intro, etc.). Put [brackets] around pattern descriptions and other text not needed when the full pattern is printed.'), ENT_QUOTES, 'UTF-8')?>">?</span>
    </label>
    <textarea name="instruction" id="instruction" rows="3"><?php echo htmlspecialchars($rec->Instruction, ENT_QUOTES, 'UTF-8'); ?></textarea>

    <label for="source" style="margin-top:10px"><?=_('Sources')?>
      <span class="help-icon" data-help="<?=htmlspecialchars(_('Albums, videos, sheet music, web pages, etc. used as references'), ENT_QUOTES, 'UTF-8')?>">?</span>
    </label>
    <textarea name="source" id="source" rows="3"><?php echo htmlspecialchars($rec->Source, ENT_QUOTES, 'UTF-8'); ?></textarea>

    <div class="audio-section">
      <label for="audiofile"><?=_('Upload Audio')?>
        <span class="help-icon" data-help="<?=htmlspecialchars(sprintf(_('MP3 format only. Maximum file size: %s.'), ini_get("upload_max_filesize")), ENT_QUOTES, 'UTF-8')?>">?</span>
      </label>
      <input type="file" name="audiofile" id="audiofile" accept=".mp3,audio/mpeg">
      <div id="audio-size-warning" style="color: darkred; font-weight: bold; font-size: 0.9em"></div>
      <?php if ($rec->Audio == "1"): ?>
        <div style="font-size:0.88em;color:#666;margin-top:3px"><?=_('(This song already has audio uploaded, but you can replace it with a new file if you want.)')?></div>
      <?php endif; ?>

      <label for="audiocomment" style="margin-top:8px"><?=_('Audio Comment')?></label>
      <textarea name="audiocomment" id="audiocomment" rows="2"><?php echo htmlspecialchars($rec->AudioComment, ENT_QUOTES, 'UTF-8'); ?></textarea>
    </div>

    <button type="submit" form="editform" class="edit-save-btn" style="align-self: center; margin-top: 16px"><?=$buttonText?></button>
  </div>

  <div class="form-group form-group-lyrics">
    <label for="lyrics"><?=_('Lyrics & Chords')?>
      <span class="help-icon help-modal" data-dialog="lyrics-help">?</span>
    </label>
    <textarea name="lyrics" id="lyrics" rows="14" style="min-height: 350px; resize: vertical; font-family: monospace"><?php echo htmlspecialchars($rec->Lyrics, ENT_QUOTES, 'UTF-8'); ?></textarea>
  </div>

</div><!-- end main grid -->
</form>

<!-- Lyrics help: floating draggable guide (jQuery UI dialog, non-modal) -->
<div id="lyrics-help" title="<?=_('Chord Formatting Help')?>" style="display:none">
  <p><?=_('Chords go in [square brackets] just before the syllable where the chord is played:')?></p>
  <p><code>[G]Amazing [C]grace, how [G]sweet the [D]sound</code></p>
  <p><?=_('If a kanji character represents multiple syllables and the chord is not played with the first one, you can use spaces to control the location:')?></p>
  <p><code>[ E]驚[E7/G#]くば[A]かり[E]の[ C#m]恵み[F#]なり[B B7]き</code></p>
  <p><?=_('To indicate Japanese lyrics written in romaji, start the line with [r]:')?></p>
  <p><code>[r]o[E]doro[E7/G#]ku ba[A]kari [E]no me[C#m]gumi [F#]nari[B B7]ki</code></p>
  <p><?=_('To mark a section that is only needed when the full pattern is printed (e.g. chorus with repeated line for the song ending), precede it with a line containing only hyphens:')?></p>
  <p><code>---</code></p>
</div>

<script src="js/jquery-3.6.0.min.js" type="text/javascript"></script>
<script src="js/jquery-ui.min.js" type="text/javascript"></script>
<script type="text/javascript">
var originalBtnText = '<?=addslashes($buttonText)?>';
var maxUploadSize = <?=intval(ini_get("upload_max_filesize")) * 1024 * 1024?>;
var formChanged = false;
var mp3_regexp = /\.[Mm][Pp]3$/;

function formatBytes(bytes) {
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / 1048576).toFixed(1) + ' MB';
}

function autoGrow(el) {
  var style = window.getComputedStyle(el);
  var minH = parseInt(style.minHeight) || 350;
  var borders = parseFloat(style.borderTopWidth) + parseFloat(style.borderBottomWidth) || 0;
  // Temporarily clear min-height and height so scrollHeight reflects true content size,
  // not the rendered size imposed by min-height
  var savedMin = el.style.minHeight;
  el.style.minHeight = '';
  el.style.height = '';
  var scrollH = el.scrollHeight;
  el.style.minHeight = savedMin;
  el.style.height = Math.max(minH, scrollH + borders) + 'px';
}

function validateForm() {
  var f = document.editform;
  if (!f.title.value.trim()) {
    alert('<?=addslashes(_("Please enter the title!"))?>');
    f.title.focus();
    return false;
  }
  if (f.audiofile.files.length && !mp3_regexp.test(f.audiofile.files[0].name)) {
    alert('<?=addslashes(_("Only MP3 files can be accepted for audio."))?>');
    f.audiofile.value = '';
    return false;
  }
  if (f.audiofile.files.length && f.audiofile.files[0].size > maxUploadSize) {
    alert('<?=addslashes(sprintf(_("The selected file is too large (%1\$s, limit: %2\$s). Please choose a smaller file."), "{SIZE}", ini_get("upload_max_filesize")))?>'.replace('{SIZE}', formatBytes(f.audiofile.files[0].size)));
    f.audiofile.value = '';
    $('#audio-size-warning').text('');
    return false;
  }
  <?php if (empty($rec->Audio)): ?>
  if (!f.audiofile.files.length && f.audiocomment.value.trim()) {
    alert('<?=addslashes(_("Audio comments only make sense if there is an audio file to comment on."))?>');
    f.audiofile.focus();
    return false;
  }
  <?php endif; ?>
  if (!f.origtitle.value.trim()) {
    f.origtitle.value = f.title.value;
  }
  return true;
}

$(document).ready(function() {

  // --- Style save buttons with jQuery UI ---
  $('.edit-save-btn').button();

  // --- Auto-grow lyrics textarea ---
  autoGrow(document.getElementById('lyrics'));
  $('#lyrics').on('input', function() { autoGrow(this); });

  // --- Track changes for beforeunload warning ---
  $('#editform').on('change input', 'input, textarea, select', function() {
    formChanged = true;
  });
  window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
      e.preventDefault();
      return '';
    }
  });

  // --- Client-side audio file size pre-check (warning only) ---
  $('#audiofile').on('change', function() {
    if (this.files.length && this.files[0].size > maxUploadSize) {
      $('#audio-size-warning').text('<?=addslashes(sprintf(_("The selected file is too large (%1\$s, limit: %2\$s). Please choose a smaller file."), "{SIZE}", ini_get("upload_max_filesize")))?>'.replace('{SIZE}', formatBytes(this.files[0].size)));
    } else {
      $('#audio-size-warning').text('');
    }
  });

  // --- Help icon tooltips (click to toggle) ---
  // preventDefault stops <label> from focusing the input when the ? icon is clicked
  $('.help-icon:not(.help-modal)').on('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
    var $this = $(this);
    if ($this.find('.help-tooltip').length) {
      $this.find('.help-tooltip').remove();
      return;
    }
    $('.help-tooltip').remove();
    $this.append('<div class="help-tooltip">' + $this.data('help') + '</div>');
  });
  $(document).on('click', function() { $('.help-tooltip').remove(); });

  // --- Lyrics help: floating draggable guide ---
  $('#lyrics-help').dialog({
    autoOpen: false,
    modal: false,
    width: 450
  });
  $('.help-modal').on('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
    $('#lyrics-help').dialog('open');
  });

  // --- AJAX form submission ---
  $('#editform').on('submit', function(e) {
    e.preventDefault();
    if (!validateForm()) return false;

    var $btns = $('.edit-save-btn');
    var $statuses = $('.save-status');
    $btns.button('option', { label: '<?=addslashes(_("Saving..."))?>', disabled: true });
    $statuses.text('').css('color', '');

    var formData = new FormData(this);
    formData.append('ajax', '1');

    $.ajax({
      url: 'do_edit.php',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json',
      success: function(r) {
        if (r.success) {
          formChanged = false;
          $statuses.text('<?=addslashes(_("Saved!"))?>').css('color', 'green');
          setTimeout(function() {
            window.location.href = 'song.php?sid=' + r.sid;
          }, 1000);
        } else {
          $statuses.text(r.error).css('color', 'red');
          $btns.button('option', { label: originalBtnText, disabled: false });
        }
      },
      error: function(xhr) {
        $statuses.text('<?=addslashes(sprintf(_("Upload failed. The file may exceed the %s limit."), ini_get("upload_max_filesize")))?>').css('color', 'red');
        $btns.button('option', { label: originalBtnText, disabled: false });
      }
    });
  });

});
</script>
<?php
if (!empty($sid)) {
  mysqli_free_result($result);
}
footer();
?>
