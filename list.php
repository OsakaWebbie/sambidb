<?php
include("functions.php");
include("accesscontrol.php");

$eid = !empty($_GET['eid']) ? (int)$_GET['eid'] : (int)($_SESSION['default_event'] ?? 0);
$basketids = !empty($_SESSION['basket']) ? implode(',', array_map('intval', $_SESSION['basket'])) : '0';

/* BUILD WHERE CLAUSE */
$where = $criteria = '';

if (!empty($_GET['basket'])) {
  $where .= "song.SongID IN ($basketids)";
} else {
  if (!empty($_GET['title'])) {
    $criteria .= "<li>" . sprintf(_('Title contains "%s" (ignoring punctuation)'), stripslashes($_GET['title'])) . "</li>\n";
    $where .= ($where ? " AND " : "") . "(Title LIKE '%" . preg_replace('/[\W]+/u', '%', $_GET['title']) . "%' OR OrigTitle LIKE '%" .
        preg_replace('/[\W]+/u', '%', $_GET['title']) . "%')";
  }
  if (!empty($_GET['lyrics'])) {
    $criteria .= "<li>" . sprintf(_('Lyrics contains "%s" (ignoring punctuation)'), stripslashes($_GET['lyrics'])) . "</li>\n";
    $where .= ($where ? " AND " : "") . "LOWER(stripchord(Lyrics)) LIKE '%" . preg_replace('/[\W]+/u', '%', $_GET['lyrics']) . "%'";
  }
  if (!empty($_GET['source'])) {
    $criteria .= "<li>" . sprintf(_('Source contains "%s" (ignoring punctuation)'), stripslashes($_GET['source'])) . "</li>\n";
    $where .= ($where ? " AND " : "") . "Source LIKE '%" . preg_replace('/[\W]+/u', '%', $_GET['source']) . "%'";
  }
  if (!empty($_GET['credit'])) {
    $criteria .= "<li>" . sprintf(_('Composer/Copyright contains "%s" (ignoring punctuation)'), stripslashes($_GET['credit'])) . "</li>\n";
    $where .= ($where ? " AND " : "") . "(Composer LIKE '%" . preg_replace('/[\W]+/u', '%', $_GET['credit']) . "%' ".
    "OR Copyright LIKE '%" . preg_replace('/[\W]+/u', '%', $_GET['credit']) . "%')";
  }
  if (!empty($_GET['tempo'])) {
    $criteria .= "<li>" . sprintf(_('"%s" tempo'), stripslashes($_GET['tempo'])) . "</li>\n";
    $where .= ($where ? " AND " : "") . "Tempo='" . $_GET['tempo'] . "'";
  }
  if (!empty($_GET['key'])) {
    $criteria .= "<li>" . sprintf(_('Key contains "%s"'), stripslashes($_GET['key'])) . "</li>\n";
    $where .= ($where ? " AND " : "") . "SongKey LIKE '%" . $_GET['key'] . "%'";
  }
  if (!empty($_GET['tagid'])) {
    $tagids = implode(',', array_map('intval', $_GET['tagid']));
    $tags = sql_single("SELECT GROUP_CONCAT(Tag SEPARATOR ', ') FROM tag WHERE TagID IN ($tagids) ORDER BY Tag");
    $criteria .= "<li>" . sprintf(_("Contains one or more of these tags: %s"), $tags) . "</li>\n";
    $where .= ($where ? " AND " : "") . "song.SongID IN (SELECT SongID FROM songtag WHERE TagID IN ($tagids))";
  }
  if (!empty($_GET['freesql'])) {
    $criteria .= "<li>" . $_GET['freesql'] . "</li>\n";
    $where .= ($where ? " AND " : "") . $_GET['freesql'];
  }
  $criteria = "<ul id='criteria'>\n" . $criteria . "</ul>\n";
}

/* FILTERS */
if (!empty($_SESSION['intags'])) {
  $where .= ($where ? " AND " : "") . "song.SongID IN (SELECT SongID FROM songtag WHERE TagID IN (" . $_SESSION['intags'] . "))";
}
if (!empty($_SESSION['extags'])) {
  $where .= ($where ? " AND " : "") . "NOT song.SongID IN (SELECT SongID FROM songtag WHERE TagID IN (" . $_SESSION['extags'] . "))";
}

/* GET IDs (flextable re-fetches all column data from these IDs) */
$idsql = "SELECT song.SongID FROM song" . (!empty($where) ? " WHERE $where" : "") . " ORDER BY OrigTitle";
$result = sqlquery_checked($idsql);
$numrecords = mysqli_num_rows($result);

if ($numrecords == 0) {
  header("Location: index.php?text=" . urlencode(_('No songs found for this search.') . (($_SESSION['access'] == 2) ? "<br>" . $idsql : "")));
  exit;
} elseif ($numrecords == 1) {
  $single = mysqli_fetch_object($result);
  header("Location: song.php?sid=" . $single->SongID);
  exit;
}

$song_ids = [];
while ($row = mysqli_fetch_object($result)) {
  $song_ids[] = $row->SongID;
}

header1(_("Search Results"));
?>
  <link rel="stylesheet" type="text/css" href="css/jquery-ui.css">
  <link rel="stylesheet" type="text/css" href="css/tablesorter.css">
<?php
header2(1);
?>
<style>
  /* Center the data cells for the basket checkbox and the short value columns */
  #songlist-table tbody td.inbasket,
  #songlist-table tbody td.tempo,
  #songlist-table tbody td.songkey,
  #songlist-table tbody td.lastuse,
  #songlist-table tbody td.numuse { text-align: center; }
</style>
<?php
if ($_SESSION['userid'] == 'dev') {
  echo '<p style="font-size:10px">' . $idsql . '</p>';
}

if (!empty($_GET['basket'])) {
  echo "<h3>" . sprintf(_("%d songs in Basket"), $numrecords) . "</h3>\n";
} else {
  echo "<h3>" . sprintf(_("%d songs of these criteria:"), $numrecords) . "</h3>\n";
  echo $criteria;
}

/* EVENT DROPDOWN */
$eventOptions = '';
$events = sqlquery_checked('SELECT * FROM event ORDER BY ' .
  (isset($_SESSION['default_event']) ? 'IF (EventID=' . (int)$_SESSION['default_event'] . ',0,1), ' : '') . 'Active DESC, Event');
while ($ev = mysqli_fetch_object($events)) {
  $eventOptions .= "  <option class='" . ($ev->Active ? 'active' : 'inactive') . "' value='" . $ev->EventID . "'>" . $ev->Event . ($ev->Active ? '' : ' (' . _('archive') . ')') . "</option>\n";
}
echo "<div id='event-selector' style='display:none'>";
echo sprintf(_('Show usage history for: %s'), "<select id='event' name='event'>\n$eventOptions</select>");
echo "</div>\n";

/* FLEXTABLE */
require_once('flextable.php');

$showcols = ',' . ($_SESSION['list_showcols'] ?? 'title,origtitle,tempo,songkey,firstline,audio,basket,lastuse,numuse') . ',';

$tableopt = (object)[
  'ids'        => implode(',', $song_ids),
  'keyfield'   => 'song.SongID',
  'tableid'    => 'songlist',
  'heading'    => '',
  'order'      => 'song.OrigTitle',
  'showBasket' => false,
  'showCSV'    => false,
  'cols'       => []
];

$histJoin = "LEFT JOIN history ON song.SongID=history.SongID AND history.EventID=$eid";

$tableopt->cols[] = (object)[
  'key'   => 'title',
  'sel'   => "CONCAT('<a href=\"song.php?sid=',song.SongID,'\">',song.Title,'</a>'"
           . ",IF(song.Audio=1,' <img src=\"graphics/audio.gif\" height=16 width=16>','')"
           . ",IF(Lyrics REGEXP '\\\\[[^rR]',' <img src=\"graphics/guitar.gif\" height=16 width=16>',''))",
  'label' => _('Title'),
  'show'  => (stripos($showcols, ',title,') !== false)
];

$tableopt->cols[] = (object)[
  'key'   => 'origtitle',
  'sel'   => 'song.OrigTitle',
  'label' => _('Original Title'),
  'show'  => (stripos($showcols, ',origtitle,') !== false),
  'sort'  => 1
];

$tableopt->cols[] = (object)[
    'key'              => 'inbasket',
    'sel'              => "IF(song.SongID IN ($basketids),1,0)",
    'label'            => _('In Basket'),
    'show'             => (stripos($showcols, ',basket,') !== false),
    'render'           => 'checkbox',
    'checkbox_action'  => 'BasketUpdate',
    'checkbox_idfield' => 'SongID',
    'sortable'         => false
];

$tableopt->cols[] = (object)[
  'key'   => 'tempo',
  'sel'   => 'song.Tempo',
  'label' => _('Tempo'),
  'show'  => (stripos($showcols, ',tempo,') !== false)
];

$tableopt->cols[] = (object)[
  'key'   => 'songkey',
  'sel'   => 'song.SongKey',
  'label' => _('Key'),
  'show'  => (stripos($showcols, ',songkey,') !== false)
];

$tableopt->cols[] = (object)[
  'key'   => 'firstline',
  'sel'   => "stripchord(LEFT(Lyrics,INSTR(Lyrics,'\\n')-1))",
  'label' => _('First Line'),
  'show'  => (stripos($showcols, ',firstline,') !== false)
];

$tableopt->cols[] = (object)[
  'key'   => 'lastuse',
  'sel'   => 'MAX(history.UseDate)',
  'label' => _('Last Use'),
  'show'  => (stripos($showcols, ',lastuse,') !== false),
  'join'  => $histJoin
];

$tableopt->cols[] = (object)[
  'key'     => 'numuse',
  'sel'     => 'COUNT(history.UseDate)',
  'label'   => _('Uses'),
  'show'    => (stripos($showcols, ',numuse,') !== false),
  'classes' => 'sorter-digit',
  'join'    => $histJoin
];

$tableopt->cols[] = (object)[
  'key'   => 'composer',
  'sel'   => 'song.Composer',
  'label' => _('Composer'),
  'show'  => (stripos($showcols, ',composer,') !== false)
];

$tableopt->cols[] = (object)[
  'key'   => 'copyright',
  'sel'   => 'song.Copyright',
  'label' => _('Copyright'),
  'show'  => (stripos($showcols, ',copyright,') !== false)
];

$tableopt->cols[] = (object)[
  'key'   => 'source',
  'sel'   => 'song.Source',
  'label' => _('Source'),
  'show'  => (stripos($showcols, ',source,') !== false)
];

$tableopt->cols[] = (object)[
  'key'      => 'audio',
  'sel'      => "IF(song.Audio=1,CONCAT('<audio controls controlslist=\"nodownload\" preload=\"metadata\" style=\"width:250px;height:30px\"><source src=\"sendaudio.php?sid=',song.SongID,'\" type=\"audio/mpeg\"></audio>'),'')",
  'label'    => _('Audio'),
  'show'     => (stripos($showcols, ',audio,') !== false),
  'sortable' => false
];

?>
<div style="margin:10px 0">
  <button id="go-to-tasks" class="ui-button ui-corner-all"><?=_('Go to task page with this list in this order')?></button>
</div>
<?php
flextable($tableopt);
?>

<script>
$(function() {
  $('#event-selector').insertAfter('#songlist-colsel-toggle').show();
  $('audio').bind('contextmenu', function() { return false; });

  // --- Basket column: toggle-all checkbox in the header (like the popup in event_use.php) ---
  // flextable renders a "In Basket:" label + "Check All" button above the table; replace that
  // with a single toggle-all checkbox in the column header, keeping the word "Basket" for clarity.
  $('#songlist-table thead th.inbasket')
    .css('text-align', 'center')
    .html('<div style="display:flex;flex-direction:column;align-items:center;line-height:1.3">' +
          '<span><?=_('Basket')?></span>' +
          '<input type="checkbox" id="songlist-checkall-header" title="<?=htmlspecialchars(_('Check/uncheck all'), ENT_QUOTES, 'UTF-8')?>">' +
          '</div>');

  // Remove flextable's "In Basket:" label and "Check All" button from the row above the table
  $('#songlist-checkall').prev('strong').remove();
  $('#songlist-checkall').remove();

  // Reword the save button to match event_use.php
  $('#songlist-savechecks').button('option', 'label', '<?=addslashes(_('Update basket according to checkboxes'))?>');

  // Header toggle-all: check/uncheck every basket checkbox, then enable the save button
  $('#songlist-table').on('change', '#songlist-checkall-header', function() {
    $('#songlist-table .table-checkbox').prop('checked', this.checked);
    $('#songlist-savechecks').button('enable');
  });

  // Override flextable's Save Checkbox Changes handler for basket-aware JSON response
  $('#songlist-savechecks').off('click').on('click', function() {
    var checked_ids = [];
    var unchecked_ids = [];
    $('#songlist-table .table-checkbox').each(function() {
      if ($(this).is(':checked')) {
        checked_ids.push($(this).data('id'));
      } else {
        unchecked_ids.push($(this).data('id'));
      }
    });
    $.post('ajax_request.php', {
      action: 'BasketUpdate',
      checked_ids: checked_ids.join(','),
      unchecked_ids: unchecked_ids.join(',')
    }, function(response) {
      if (response.success) {
        window.updateBasketCount(response.basketCount);
        $('#songlist-savechecks').button('disable');
      } else {
        alert(response.error || '<?=_('Error updating basket.')?>');
      }
    }, 'json');
  });

  // Go to task page — sends songs in current DOM display order (respects tablesorter sort)
  $('#go-to-tasks').click(function() {
    var sids = [];
    $('#songlist-table tbody tr').each(function() {
      var keyMatch = $(this).find('td').first().attr('class').match(/key(\d+)/);
      if (keyMatch) sids.push(keyMatch[1]);
    });
    location.href = 'task.php?sid_list=' + sids.join(',');
  });

  // Event dropdown: update LastUse/Uses cells in-place via AJAX
  $('#event').on('change', function() {
    $.getJSON('ajax_request.php', {action: 'HistoryData', eventid: $(this).val()}, function(data) {
      var map = {};
      $.each(data, function(i, row) { map[row.SongID] = row; });
      $('#songlist-table tbody tr').each(function() {
        var $row = $(this);
        var keyMatch = $row.find('td').first().attr('class').match(/key(\d+)/);
        if (!keyMatch) return;
        var sid = keyMatch[1];
        var h = map[sid];
        $row.find('td.lastuse').text(h && h.LastUse ? h.LastUse : '');
        $row.find('td.numuse').text(h && h.NumUse ? h.NumUse : 0);
      });
      $('#songlist-table').trigger('update');
    });
  }).val('<?=(int)$eid?>');
});
</script>
<?php footer(); ?>
