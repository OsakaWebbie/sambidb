<?php
include("functions.php");
include("accesscontrol.php");

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

header1(_('Search'));
?>
<link rel="stylesheet" type="text/css" href="css/jquery-ui.css">
<link rel="stylesheet" type="text/css" href="css/jquery.multiselect.css">
<link rel="stylesheet" type="text/css" href="css/jquery.multiselect.filter.css">
<?php
header2(1); ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css">

<div style="font-size:0.8em; font-style:italic; float:right; width:25%; border:1px solid grey; padding:3px">
  <?=_('Note: This database is only a tool for convenience; existence of copyrighted material (lyrics, '.
      'recordings, etc.) in it does not give you permission to use that material in ways that violate the copyright. '.
      'Users of the material bear their own responsibility for how they use the information stored here.')?>
</div>
<h1><?=(isset($_SESSION['dbtitle']) ? $_SESSION['dbtitle'].': ' : '')._('Search')?></h1>
<?php if ($_SESSION['access'] == 0)  echo '<div style="font-weight:bold; margin:10px 20px 10px 0">'.
    _('Your user status is "read-only"; if you need editing privileges, ask the administrator.').'</div>';
?>
<?php if (!empty($text)) echo "<h3 class='alert'>$text</h3>"; ?>

<p style="font-weight:bold; margin-top:10px">
  <?php
  $link = ' <a href="filter.php" class="nowrap" style="margin-left:2em;">'._('Modify filter criteria').'</a>';
  if (empty($_SESSION['intags']) && empty($_SESSION['extags'])) {
    echo _("You are not currently filtering data (you are seeing all songs).").$link;
  } else {
    echo _("You are currently filtering to see only songs whose tags...").$link;
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
</p>
<div class="clear"></div>

<form id="searchform" action="list.php" method="get">
  <fieldset>
    <legend style="padding-left:1em;padding-right:1em"><?=_('Search')?></legend>
    <div style="line-height:3em;">
      <label class="label-n-input"><?=_('Title/Original Title')?>: <input type="text" name="title" style="width:15em"></label>
      <label class="label-n-input"><?=_('Lyrics')?>: <input type="text" name="lyrics" style="width:15em"></label>

      <label class="label-n-input"><?=_('Tags')?>: <select size="3" name="tagid[]" multiple="multiple" id="tagselect">
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
  echo "  <br><label>Freeform SQL: SELECT this stuff FROM wherever WHERE...</label><br>\n";
  echo "  <textarea name='freesql' style='height:3em; width:95%; margin-bottom:1em;'></textarea>\n";
}

?>
    <input type="submit" class="bigbutton ui-button ui-corner-all" name="search" id="searchbutton" value="<?=_('Search!')?>" style="max-width:15em;width:80%">
  </fieldset>
</form>

<?php load_scripts(['jquery', 'jqueryui']); ?>
<script type="text/javascript" src="js/jquery.multiselect.min.js"></script>
<script type="text/javascript" src="js/jquery.multiselect.filter.js"></script>
<script>
  $(document).ready(function(){
    $("#tagselect").multiselect({
      noneSelectedText: '<?=_("Select...")?>',
      selectedText: '<?=_("# selected")?>',
      checkAllText: '<?=_("Check all")?>',
      uncheckAllText: '<?=_("Uncheck all")?>',
    }).multiselectfilter({
      label: '<?=_("Search:")?>'
    });

    $('#searchform').submit(function() {
      $(this).find(':input').filter(function() {
        return !this.value;
      }).attr('disabled', 'disabled');
      $('#searchbutton').attr('disabled', 'disabled');
      return true; // make sure that the form is still submitted
    });

  });
</script>
<?php footer(); ?>
