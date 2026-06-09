<?php
include("functions.php");
include("accesscontrol.php");

// AJAX: clear all tag filters (index.php is the only page where filtering is shown)
if (!empty($_REQUEST['ajax']) && ($_REQUEST['action'] ?? '') === 'ClearFilter') {
  $_SESSION['intags'] = '';
  $_SESSION['extags'] = '';
  $uid = mysqli_real_escape_string($db, $_SESSION['userid']);
  sqlquery_checked("UPDATE user SET IncludeTags='', ExcludeTags='' WHERE UserID='".$uid."'");
  header('Content-Type: application/json');
  echo json_encode(['success' => true]);
  exit;
}

if (isset($_GET['emptybasket'])) {
  $_SESSION['basket'] = [];
  saveBasket();
}
$num_basket = count($_SESSION['basket'] ?? []);

// Build array of tags for use in two places
$result = sqlquery_checked("SELECT * FROM tag ORDER BY Tag");
$tag = array();
while ($row = mysqli_fetch_object($result)) {
  $tag[$row->TagID] = $row->Tag;
}

pageheader(_('Search'), 1);
?>
<link rel="stylesheet" type="text/css" href="css/jquery.multiselect.css">
<link rel="stylesheet" type="text/css" href="css/jquery.multiselect.filter.css">
<style>
  #disclaimer { font-size:0.7em; font-style:italic; float:right; width:25%; border:1px solid grey; padding:3px; }
  @media (max-width: 900px) {
    #disclaimer { float:none; width:auto; margin:0.5em 0 1em 0; }
  }
  #filter-status { display:flex; flex-wrap:wrap; gap:0.5em 2em; align-items:flex-start; font-weight:bold; margin:10px 0 1em 0; }
  #filter-controls { display:flex; gap:0.5em; align-items:center; flex-wrap:wrap; }
  #status-msg {
    position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
    padding: 10px 16px; background: #2e7d32; color: white; border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,.2); z-index: 10000; display: none;
  }
</style>
<style>
  #submit-row { display:flex; flex-wrap:wrap; gap:1em; align-items:center; margin-top:0.5em; }
  #basket-only-label { white-space:nowrap; }
  #basket-only-label.basket-empty { color:#BBB; }
  #searchbutton { max-width:15em; width:80%; }
</style>

<div id="disclaimer">
  <?=_('Note: This database is only a tool for convenience; existence of copyrighted material (lyrics, '.
      'recordings, etc.) in it does not give you permission to use that material in ways that violate the copyright. '.
      'Users of the material bear their own responsibility for how they use the information stored here.')?>
</div>
<h1><?=(isset($_SESSION['dbtitle']) ? $_SESSION['dbtitle'].': ' : '')._('Search')?></h1>
<?php if ($_SESSION['access'] == 0)  echo '<div style="font-weight:bold; margin:10px 20px 10px 0">'.
    _('Your user status is "read-only"; if you need editing privileges, ask the administrator.').'</div>';
?>
<?php if (!empty($text)) echo "<h3 class='alert'>$text</h3>"; ?>

<div id="filter-status">
  <div id="filter-text">
    <?php
    if (empty($_SESSION['intags']) && empty($_SESSION['extags'])) {
      echo _("You are not currently filtering data (you are seeing all songs).");
    } else {
      echo _("You are currently filtering to see only songs whose tags...");
      if ($_SESSION['intags']) {
        $txt = "";
        foreach (explode(",", $_SESSION['intags']) as $tagid) {
          $txt .= ", ".$tag[$tagid];
        }
        echo "<br><span style='color:green; margin-left:2em'>"._('Include: ').substr($txt,2)."</span>";
      }
      if ($_SESSION['extags']) {
        $txt = "";
        foreach (explode(",", $_SESSION['extags']) as $tagid) {
          $txt .= ", ".$tag[$tagid];
        }
        echo "<br><span style='color:red; margin-left:2em'>"._('Do not include: ').substr($txt,2)."</span>";
      }
    }
    ?>
  </div>
  <div id="filter-controls">
    <a href="filter.php" class="ui-button ui-corner-all"><?=_('Modify filter criteria')?></a>
<?php if (!empty($_SESSION['intags']) || !empty($_SESSION['extags'])): ?>
    <button type="button" id="clear-filter" class="ui-button ui-corner-all"><?=_('Clear filters')?></button>
<?php endif; ?>
  </div>
</div>
<div class="clear"></div>

<form id="searchform" action="list.php" method="get">
  <fieldset>
    <legend style="padding-left:1em;padding-right:1em"><?=_('Search')?></legend>
    <div style="line-height:2em;">
      <label class="label-n-input"><?=_('Title/Original Title')?>: <input type="text" name="title" style="width:15em"></label>
      <label class="label-n-input"><?=_('Lyrics')?>: <input type="text" name="lyrics" style="width:15em"></label>

      <label class="label-n-input"><?=_('Tags')?>: <select size="1" name="tagid[]" multiple="multiple" id="tagselect">
<?php // Build option list from tags not filtered
foreach ($tag as $tagid => $tagname) {
  if (strpos(",".$_SESSION['intags'].",",",".$tagid.",")===FALSE &&
      strpos(",".$_SESSION['extags'].",",",".$tagid.",")===FALSE) {
    echo "        <option value='$tagid'>$tagname</option>\n";
  }
}
?>
      </select></label>

      <label class="label-n-input"><?=_('Tempo')?>:
      <select size="1" name="tempo" id="temposelect" style="width:fit-content">
        <option value=""></option>
        <option value="Fast"><?=_('Fast')?></option>
        <option value="Medium"><?=_('Medium')?></option>
        <option value="Slow"><?=_('Slow')?></option>
      </select></label>

      <label class="label-n-input"><?=_('Key')?>: <input type="text" name="key" style="width:3em"></label>
      <label class="label-n-input"><?=_('Source')?>: <input type="text" name="source" style="width:15em"></label>
      <label class="label-n-input"><?=_('Composer/Copyright')?>: <input type="text" name="credit" style="width:15em"></label>
    </div>
<?php
if ($_SESSION['access'] == 2) {
  echo "  <div><label>Freeform SQL: SELECT this stuff FROM wherever WHERE...</label><br>\n";
  echo "  <textarea name='freesql' style='height:2em; width:100%; box-sizing:border-box; margin-bottom:1em;'></textarea></div>\n";
}

?>
<?php $basket_empty = empty($_SESSION['basket']); ?>
    <div id="submit-row">
      <label id="basket-only-label"<?=$basket_empty?' class="basket-empty"':''?>>
        <input type="checkbox" name="basket" value="1"<?=$basket_empty?' disabled':''?>>
        <?=_('in Basket only')?>
      </label>
      <input type="submit" class="bigbutton ui-button ui-corner-all" name="search" id="searchbutton" value="<?=_('Search!')?>">
    </div>
  </fieldset>
</form>

<?php load_scripts(['jquery', 'jqueryui']); ?>
<script type="text/javascript" src="js/jquery.multiselect.min.js"></script>
<script type="text/javascript" src="js/jquery.multiselect.filter.js"></script>
<script>
<?php
  $alltags = array();
  foreach ($tag as $tid => $tname) { $alltags[] = array('id' => (int)$tid, 'name' => $tname); }
?>
  var allTags = <?=json_encode($alltags, JSON_UNESCAPED_UNICODE)?>;
  $(document).ready(function(){
    $("#tagselect").multiselect({
      noneSelectedText: '<?=_("Select...")?>',
      selectedText: '<?=_("# selected")?>',
      checkAllText: '<?=_("Check all")?>',
      uncheckAllText: '<?=_("Uncheck all")?>',
    }).multiselectfilter({
      label: '<?=_("Search")?>:'
    });

    $('#searchform').submit(function() {
      $(this).find(':input').filter(function() {
        return !this.value;
      }).attr('disabled', 'disabled');
      $('#searchbutton').attr('disabled', 'disabled');
      return true; // make sure that the form is still submitted
    });

    function showStatus(msg) {
      var $s = $('#status-msg');
      if (!$s.length) $s = $('<div id="status-msg">').appendTo('body');
      $s.text(msg).fadeIn(100).delay(1500).fadeOut(400);
    }

    $('#clear-filter').click(function() {
      $.ajax({
        url: 'index.php',
        type: 'POST',
        data: { ajax: 1, action: 'ClearFilter' },
        dataType: 'json',
        success: function(resp) {
          if (resp && resp.success) {
            $('#filter-text').html('<?=addslashes(_("You are not currently filtering data (you are seeing all songs)."))?>');
            $('#clear-filter').remove();
            // Filtered tags were excluded from the search dropdown; restore the full list
            var $sel = $('#tagselect').empty();
            $.each(allTags, function(i, t) {
              $('<option>').attr('value', t.id).text(t.name).appendTo($sel);
            });
            $sel.multiselect('refresh');
            showStatus('<?=addslashes(_("Filters cleared."))?>');
          }
        }
      });
    });

  });
</script>
<?php footer(); ?>
