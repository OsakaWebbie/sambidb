<?php
include("functions.php");
include("accesscontrol.php");

// AJAX dispatcher: sub-action endpoints for this page
if (!empty($_REQUEST['ajax'])) {
  $action = $_REQUEST['action'] ?? '';
  switch ($action) {
    case 'usage': task_usage(); break;
    case 'pdf':   task_pdf();   break;
    case 'tag':   task_tag();   break;
  }
  exit;
}

function task_usage() {
  global $db, $sid_list;
  if ($_SESSION['access'] < 1) {
    echo '<p class="alert">'._('Access denied.').'</p>';
    return;
  }
  if (isset($_POST['save_history'])) {
    if (!isset($_POST['confirmed'])) {
      $sql = "SELECT song.Title, song.OrigTitle, history.UseOrder FROM history LEFT JOIN song".
      " ON history.SongID=song.SongID WHERE EventID=".$_POST['event_id']." AND UseDate='".$_POST['use_date']."' ORDER BY UseOrder";
      if (!$result = mysqli_query($db,$sql)) {
        echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
        return;
      }
      if (mysqli_num_rows($result) > 0) {
        ?>
        <h4><?=_('There is already a song session for this event and date that will be overwritten:')?></h4>
        <ol style="margin:0.5em 0 1em 3em;">
          <?php
          while ($row = mysqli_fetch_object($result)) {
            $title = htmlspecialchars($row->Title, ENT_QUOTES, 'UTF-8');
            $orig = htmlspecialchars($row->OrigTitle, ENT_QUOTES, 'UTF-8');
            $stripped = preg_replace('/^[[:digit:]]{3}: /', '', $row->Title);
            if ($stripped !== $row->OrigTitle) $title .= ' ('.$orig.')';
            echo "          <li>$title</li>\n";
          }
          ?>
        </ol>
        <p style="margin-bottom:1em"><?=_('If you intended a different event or date, you can click "Save as song usage" again and try again.')?></p>
        <form action="task.php" method="post">
          <input type="hidden" name="action" value="usage">
          <input type="hidden" name="ajax" value="1">
          <input type="hidden" name="sid_list" value="<?=$sid_list?>">
          <input type="hidden" name="event_id" value="<?=$_POST['event_id']?>">
          <input type="hidden" name="use_date" value="<?=$_POST['use_date']?>">
          <input type="hidden" name="confirmed" value="1">
          <input type="submit" name="save_history" class="ui-button ui-corner-all" value="<?=_('Yes, replace with new selected songs')?>">
        </form>
<?php
        return;
      }
    }

    echo "<h3>";
    if (isset($_POST['confirmed'])) {
      $sql = "DELETE FROM history WHERE EventID=".$_POST['event_id']." AND UseDate='".$_POST['use_date']."'";
      if (!$result = mysqli_query($db,$sql)) {
        echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
        return;
      }
      echo _("Old usage records deleted.").'<br>';
    }
    $sid_array = explode(",",$sid_list);
    $num_sids = count($sid_array);
    for ($i=0; $i<$num_sids; $i++) {
      $sql = "INSERT INTO history (SongID,EventID,UseDate,UseOrder) VALUES (".
      $sid_array[$i].",".$_POST['event_id'].",'".$_POST['use_date']."',".($i+1).")";
      if (!$result = mysqli_query($db,$sql)) {
        echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
        return;
      }
    }
    echo sprintf(_('%s new usage records added.'), $num_sids);
    echo "</h3>";
    return;
  }
  ?>
  <div>
    <form action="task.php" method="post" name="hform" onsubmit="return validate();">
      <input type="hidden" name="action" value="usage">
      <input type="hidden" name="ajax" value="1">
      <input type="hidden" name="sid_list" value="<?=$sid_list?>">
      <div style="display:flex; flex-wrap:wrap; gap:0.5em 2em; align-items:flex-start;">
        <div>
          <h5><?=_('Event:')?></h5>
<?php
  $result = sqlquery_checked('SELECT * FROM event WHERE Active=1 ORDER BY '.
      (isset($_SESSION['default_event'])?'IF (EventID='.$_SESSION['default_event'].',0,1), ':''). 'Event');
  while ($row = mysqli_fetch_object($result)) {
    echo '<label'.($row->Remarks!==''?' title="'.escape_quotes($row->Remarks).'"':'').
        ' style="display:block; margin-left:1em;"><input type="radio" name="event_id" value="'.$row->EventID.'"'.
        ((isset($_SESSION['default_event']) && $row->EventID==$_SESSION['default_event'])?' checked':'').'> '.
        escape_quotes($row->Event)."</label>\n";
  }
?>
        </div>
        <div>
          <h5><label><?=_('Date Used:')?> <input type="text" name="use_date" id="use_date" value="" size="12" maxlength="10"></label></h5>
          <div style="margin-top:1em"><input type="submit" name="save_history" class="ui-button ui-corner-all" value="<?=_('Save song usage')?>"></div>
        </div>
      </div>
    </form>
  </div>
  <script>
  $(function() {
    $("#use_date").datepicker({ dateFormat: "yy-mm-dd"});
  });
  function validate() {
    var date = $('#use_date');
    if (date.val() === '') {
      alert('<?=_("You must enter a date.")?>');
      date.click();
      return false;
    }
    try { $.datepicker.parseDate('yy-mm-dd', date.val()); }
    catch(error) {
      alert('<?=_("Date is invalid.")?>');
      return false;
    }
  }
  </script>
  <?php
}

function task_pdf() {
  global $sid_list;
  ?>
  <h3><?=_('Select your options and click the button to proceed.')?></h3>
  <form action="pdflayout.php" method="get" name="optionsform" target="_blank">
    <input type="hidden" name="sid_list" value="<?=$sid_list?>">
    <div style="display:flex; flex-wrap:wrap; gap:1em 3em; align-items:center;">
      <div>
        <label style="display:block; margin:0.3em 0;"><input type="radio" name="pattern" value="basic"> <?=_('Main sections only (no extras)')?></label>
        <label style="display:block; margin:0.3em 0;"><input type="radio" name="pattern" value="allparts" checked> <?=_('All sections, once each')?></label>
        <label style="display:block; margin:0.3em 0;"><input type="radio" name="pattern" value="pattern"> <?=_('Full arrangement according to Pattern')?></label>
      </div>
      <div>
        <label><input type="checkbox" name="multilingual" value="yes" checked> <?=_('Combine multilingual songs')?></label>
      </div>
      <div>
        <input type="submit" name="submit" class="ui-button ui-corner-all" value="<?=_('Go to Layout Page')?>">
      </div>
    </div>
  </form>
  <?php
}

function task_tag() {
  global $db, $sid_list, $tagid, $tag;
  if ($_SESSION['access'] < 1) {
    echo '<p class="alert">'._('Access denied.').'</p>';
    return;
  }
  if (!empty($_POST['save_tag'])) {
    if ($tagid == "new") {
      $sql = "INSERT INTO tag (Tag) VALUES ('".mysqli_real_escape_string($db, $tag)."')";
      if (!$result = mysqli_query($db,$sql)) {
        echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
        return;
      }
      if (mysqli_affected_rows($db) > 0) {
        $tagid = mysqli_insert_id($db);
        echo "<h3>"._('New tag successfully added.')."</h3>";
      } else {
        echo _('The tag was not added for some reason.')."<br>";
        return;
      }
    }
    $sid_array = explode(",",$sid_list);
    $num_sids = count($sid_array);
    $num_previous = 0;
    for ($i=0; $i<$num_sids; $i++) {
      $sql = "SELECT * FROM songtag WHERE SongID=".intval($sid_array[$i])." AND TagID=".intval($tagid);
      if (!$result = mysqli_query($db,$sql)) {
        echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
        return;
      }
      if (mysqli_num_rows($result) == 1) {
        $num_previous++;
      } else {
        $sql = "INSERT INTO songtag (SongID,TagID) VALUES (".
             intval($sid_array[$i]).",".intval($tagid).")";
        if (!$result = mysqli_query($db,$sql)) {
          echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
          return;
        }
      }
    }
    echo "<h3>".sprintf(_('Tag added to %s songs.'), ($num_sids - $num_previous))."</h3>";
    if ($num_previous > 0) {
      echo "<p>".sprintf(_('(%s songs in this list already included this tag.)'), $num_previous)."</p>";
    }
    return;
  }
  ?>
  <div>
    <h3><?=_('Choose existing tag, or choose New and fill in new tag name:')?></h3>
    <form action="task.php" method="post" name="tagform" onsubmit="return validate();">
      <input type="hidden" name="action" value="tag">
      <input type="hidden" name="ajax" value="1">
      <input type="hidden" name="sid_list" value="<?=$sid_list?>">
      <div style="display:flex; flex-wrap:wrap; gap:1em 2em; align-items:flex-end;">
        <div>
          <label><?=_('Tag')?>:
            <select name="tagid" id="tagid" size="1">
              <option value="" selected><?=_('Select a tag...')?></option>
              <option value="new"><?=_('New Tag (input name)')?></option>
<?php
  $result = sqlquery_checked("SELECT * FROM tag ORDER BY Tag");
  while ($row = mysqli_fetch_object($result)): ?>
              <option value="<?=$row->TagID?>"><?=htmlspecialchars($row->Tag, ENT_QUOTES, 'UTF-8')?></option>
<?php endwhile; ?>
            </select>
          </label>
          <div id="newtag-row" style="display:none; margin-top:0.5em;">
            <label><?=_('New Tag Name:')?> <input type="text" name="tag" size="35" maxlength="50"></label>
          </div>
        </div>
        <div>
          <input type="submit" name="save_tag" class="ui-button ui-corner-all" value="<?=_('Add This Tag to These Songs')?>">
        </div>
      </div>
    </form>
  </div>
  <script>
  $(function() {
    $("#tagid").on("change", function() {
      $("#newtag-row").toggle($(this).val() === "new");
    });
  });
  function validate() {
    if (document.tagform.tagid.value === "new" && document.tagform.tag.value === "") {
      alert("<?=_('Tag name cannot be blank.')?>");
      document.tagform.tag.focus();
      return false;
    }
    return true;
  }
  </script>
  <?php
}

header1(_("Tasks"));
?>
<link rel="stylesheet" type="text/css" href="css/jquery-ui.css">
<?php
if (empty($sid_list) && empty($_SESSION['basket'])) {
  header2(1);
  echo '<p><b>'._('Your basket is empty.  Add songs to your basket from the Search page or a song detail page, then return here to put them in order and do tasks.').'</b></p>';
  footer();
  exit;
}
header2(1);
?>
<style>
  #task-container { display:flex; flex-wrap:wrap; gap:1em; align-items:flex-start; }
  #task-list-side { flex:0 0 auto; min-width:320px; }
  #task-actions   { flex:0 0 auto; min-width:240px; border:1px solid #999999; padding:0 10px; text-align:center; }
  #task-actions .action-btn { margin:0 0 10px 0; }
  #tagged { border:1px #999999 solid; margin:0; padding:3px; background-color:#EEEEEE; }
  #tagged li { border:1px #999999 solid; margin:1px; padding:2px 4px; white-space:nowrap; background-color:White; }
  #tagged li span.songid { display:none; }
  #tagged li span.tempoFast { color:#F00000; }
  #tagged li span.tempoMedium { color:#A08000; }
  #tagged li span.tempoSlow { color:#0000F0; }
  #tagged li span.songtitle { font-weight:bold; }
  #tagged li img { margin-left:5px; cursor:pointer; }
  #ResultFrame {
    min-height: 100px;
    max-height: 500px;
    overflow-y: auto;
    resize: vertical;
    border: 1px solid #999999;
    padding: 1em;
    margin-top: 1em;
  }
  @media (max-width: 899px) {
    #task-list-side, #tagged { min-width: 0; max-width: 100%; }
    #tagged li { white-space: normal; }
    #tagged li div.left { float: none; overflow: hidden; }
  }
</style>

<h1><?=_('Tasks')?></h1>
<h3><?=_('Drag songs to reorder; click the Duplicate or Remove icons as needed. Then choose tasks from the buttons.')?></h3>
<div id="task-container">
  <div id="task-list-side">
    <ul id="tagged">
<?php
if (!empty($sid_list)) {
  $sql = "SELECT * FROM song WHERE SongID In (".$sid_list.") ORDER BY FIND_IN_SET(SongID,'".$sid_list."')";
} else {
  $basket_ids = implode(',', array_map('intval', $_SESSION['basket']));
  $sql = "SELECT * FROM song WHERE SongID IN ($basket_ids) ORDER BY OrigTitle";
}
if (!$result = mysqli_query($db,$sql)) {
  echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b> ($sql)");
  exit;
}
while ($song = mysqli_fetch_object($result)) {
  echo '      <li>';
  echo '<div class="right"><img src="graphics/copy.gif" class="copy" alt="" title="'._('Duplicate').'">';
  echo '<img src="graphics/delete.gif" class="delete" alt="" title="'._('Remove').'"></div>';
  echo '<div class="left"><span class="songid">'.$song->SongID.'</span>['.$song->SongKey.']';
  echo '<span class="tempo'.$song->Tempo.'">['.$song->Tempo.']</span> <span class="songtitle">'.$song->Title;
  if (preg_replace('/^[[:digit:]]{3}: /','',$song->Title) != $song->OrigTitle) echo ' ('.$song->OrigTitle.')';
  echo '</span></div>';
  echo '<div class="clear"></div>';
  echo "</li>\n";
}
?>
    </ul>
  </div>
  <div id="task-actions">
    <h3><?=_('Choose a task:')?></h3>
    <?php if ($_SESSION['access'] > 0): ?>
    <p><button type="button" class="action-btn ui-button ui-corner-all" data-action="usage"><?=_('Save as song usage')?></button></p>
    <?php endif; ?>
    <p><button type="button" class="action-btn ui-button ui-corner-all" data-action="pdf"><?=_('Create PDF/Powerpoint')?></button></p>
    <?php if ($_SESSION['access'] > 0): ?>
    <p><button type="button" class="action-btn ui-button ui-corner-all" data-action="tag"><?=_('Add a Tag to all')?></button></p>
    <?php endif; ?>
  </div>
</div>
<div id="ResultFrame"></div>

<?php load_scripts(['jquery', 'jqueryui']); ?>
<script src="js/jquery.ui.touch-punch.min.js"></script>
<script>
$(document).ready(function(){

  // Lock the song list to its initial content-based width so dragging
  // or removing songs doesn't cause the box to shrink (avoids the jitter
  // when the longest title is the one being moved). Skip on narrow viewports
  // so the box can fit the screen and titles can wrap.
  var $tagged = $("#tagged");
  if (window.matchMedia('(min-width: 900px)').matches) {
    $tagged.css("min-width", $tagged.outerWidth() + "px");
  }

  function buildSidList() {
    var items = [];
    $("#tagged li span.songid").each(function() { items.push($(this).text()); });
    return items.join(",");
  }

  $("#tagged").sortable({
    placeholder: "ui-state-highlight",
    forcePlaceholderSize: true,
    update: function() { $("#ResultFrame").empty(); }
  });

  $("#tagged").on("click", "img.copy", function() {
    var original = $(this).closest("li");
    $(original).after($(original).clone(true, true));
    $("#ResultFrame").empty();
  });
  $("#tagged").on("click", "img.delete", function() {
    $(this).closest("li").remove();
    $("#ResultFrame").empty();
  });

  $(".action-btn").on("click", function() {
    var sid_list = buildSidList();
    if (!sid_list) { alert("<?=_('Your list is empty.')?>"); return; }
    var action = $(this).data("action");
    $("#ResultFrame").html("<p><?=_('Loading...')?></p>");
    $.post("task.php", {sid_list: sid_list, action: action, ajax: 1}, function(r) {
      $("#ResultFrame").html(r);
    });
  });

  // Event-delegated AJAX resubmit of child forms loaded into #ResultFrame;
  // forms with target="_blank" pass through to native submit (new tab).
  // Track which submit button was clicked since .serialize() drops submit names.
  var submittedBtn = null;
  $("#ResultFrame").on("click", "input[type=submit], button[type=submit]", function() {
    submittedBtn = this;
  });
  $("#ResultFrame").on("submit", "form", function(e) {
    var target = $(this).attr("target") || "";
    if (target === "_blank" || target === "_top") return;
    e.preventDefault();
    var url  = $(this).attr("action") || "task.php";
    var data = $(this).serialize();
    if (submittedBtn && submittedBtn.name) {
      data += "&" + encodeURIComponent(submittedBtn.name) + "=" + encodeURIComponent(submittedBtn.value);
    }
    submittedBtn = null;
    data += "&ajax=1";
    $.post(url, data, function(r) { $("#ResultFrame").html(r); });
  });
});
</script>
<?php footer();?>
