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
header2(1); ?>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/cupertino/jquery-ui.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css">

<div style="font-size:0.8em; font-style:italic; float:right; width:25%; border:1px solid grey; padding:3px">
  <?=_('Note: This database is only a tool for convenience; existence of copyrighted material (lyrics, '.
      'recordings, etc.) in it does not give you permission to use that material in ways that violate the copyright. '.
      'Users of the material bear their own responsibility for how they use the information stored here.')?>
</div>
<h1><?=(isset($_SESSION['dbtitle']) ? $_SESSION['dbtitle'].': ' : '')._('Search')?></h1>
<?php if ($_SESSION['admin'] == 0)  echo '<div style="font-weight:bold; margin:10px 20px 10px 0">'.
    _('Your user status is "read-only"; if you need editing privileges, ask the administrator.').'</div>';
?>
<?php if (!empty($text)) echo "<h3 class='alert'>$text</h3>"; ?>

<p style="font-weight:bold; margin-top:10px">
  <?php
  $link = ' <a href="filter.php" class="nowrap" style="margin-left:2em; font-size:1.2em; font-style:italic;">'._('Modify filter criteria').'</a>';
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
    <legend><?=_('Search')?></legend>
    <div style="display:grid; grid-template-columns:auto 1fr 2fr; grid-gap:10px">
      <label style="grid-column:1/2"><?=_('Title/Original Title')?>:</label><input type="text" name="title" style="grid-column:2/3">
      <label style="grid-column:1/2"><?=_('Lyrics')?>:</label><input type="text" name="lyrics" style="grid-column:2/3">
      <label style="grid-column:1/2"><?=_('Source')?>:</label><input type="text" name="source" style="grid-column:2/3">
      <label style="grid-column:1/2"><?=_('Composer/Copyright')?>:</label><input type="text" name="credit" style="grid-column:2/3">

      <label style="grid-column:1/2" for="tagselect"><?=_('Tags')?>:</label>
      <div style="grid-column:2/4;background-color:lavender"><select size="3" name="tagid[]" multiple="multiple" id="tagselect">
        <option value=""></option>
<?php // Build option list from tags not filtered
foreach ($tag as $tagid => $tagname) {
  if (strpos(",".$_SESSION['intags'].",",",".$tagid.",")===FALSE &&
      strpos(",".$_SESSION['extags'].",",",".$tagid.",")===FALSE) {
    echo "        <option value='$tagid'>$tagname</option>\n";
  }
}
?>
      </select></div>

      <label style="grid-column:1/2"><?=_('Tempo')?>:</label>
      <select size="1" name="tempo" id="temposelect" style="width:fit-content; grid-column:2/3">
        <option value=""></option>
        <option value="Fast"><?=_('Fast')?></option>
        <option value="Medium"><?=_('Medium')?></option>
        <option value="Slow"><?=_('Slow')?></option>
      </select>

      <label style="grid-column:1/2"><?=_('Key')?>:</label><input type="text" name="key" style="width:3em; grid-column:2/4">
<?php
if ($_SESSION['admin'] == 2) {
  echo "  <div style='grid-column:1/4'><label style='margin-top:1em'>Freeform SQL: SELECT this stuff FROM wherever WHERE...</label><br>\n";
  echo "  <textarea name='freesql' style='height:3em; width:90%'></textarea></div>\n";
}

?>
    <input type="submit" class="bigbutton" name="search" id="searchbutton" value="<?=_('Search!')?>" style="grid-column:1/4;width:80%;margin:10px auto">
    </div>
  </fieldset>
</form>

<script src="https://code.jquery.com/jquery-3.2.1.min.js" type="text/javascript"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" type="text/javascript"></script>
<script src="js/jquery.ui.touch-punch.min.js" type="text/javascript"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js"></script>
<script>
  $(document).ready(function(){
    $("#tagselect").select2({
      dropdownAutoWidth : true,
      width : '100%'
    });
    $("#temposelect").select2({
      dropdownAutoWidth : true,
      width : 'auto'
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
<?php print_footer(); ?>
