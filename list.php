<?php
include("functions.php");
include("accesscontrol.php");

$eid = !empty($_GET['eid']) ? $_GET['eid'] : $_SESSION['default_event'];
$sql = "SELECT song.SongID, Tagged, Title, OrigTitle, Tempo, SongKey, stripchord(LEFT(Lyrics,INSTR(Lyrics,'\n')-1)) AS FirstLine, ".
    "MAX(UseDate) AS LastUse, COUNT(UseDate) AS NumUse, Composer, Copyright, Source, Audio, Lyrics REGEXP '\\\\[[^rR]' AS Chords ";
$sql .= "FROM song LEFT JOIN history ON song.SongID=history.SongID AND history.EventID=$eid";
$groupby = "song.SongID,Title,Tagged,OrigTitle,Tempo,SongKey,Composer,Copyright,Source,FirstLine";
$where = $criteria = '';

/*if ($_SESSION['inkeys'] or $kwid) {
  if (ereg(",".$kwid.",", ",".$_SESSION['inkeys'])) {  // The search keyword is already in the filter
    $list = $_SESSION['inkeys'];
  } elseif ($_SESSION['inkeys'] and $kwid) {  // Both are present and unique, so combine them in one list
    $list = $_SESSION['inkeys'].",".$kwid;
  } else {  // We know one but not both is set
    $list = $_SESSION['inkeys'].$kwid;
  }
  $where .= ($where?" AND ":"")."song.SongID IN (SELECT SongID FROM songkeyword WHERE KeywordID IN ($list))";
}*/

if (!empty($_GET['tagged'])) {
  $where .= ($where?" AND ":"")."Tagged=1";
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
    $criteria .= "<li>" . sprintf(_('Source contains "%s" (ignoring punctuation)'), stripslashes($source)) . "</li>\n";
    $where .= ($where ? " AND " : "") . "Source LIKE '%" . preg_replace('/[\W]+/u', '%', $_GET['source']) . "%'";
  }
  if (!empty($_GET['credit'])) {
    $criteria .= "<li>" . sprintf(_('Composer/Copyright contains "%s" (ignoring punctuation)'), stripslashes($credit)) . "</li>\n";
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
  if (!empty($_GET['kwid'])) {
    $kwids = implode(',', $_GET['kwid']);
    $kws = sql_single("SELECT GROUP_CONCAT(Keyword SEPARATOR ', ') FROM keyword WHERE KeywordID IN ($kwids) ORDER BY Keyword");
    $criteria .= "<li>" . sprintf(_("Contains one or more of these keywords: %s"), $kws) . "</li>\n";
    $where .= ($where ? " AND " : "") . "song.SongID IN (SELECT SongID FROM songkeyword WHERE KeywordID IN ($kwids))";
  }
  if (!empty($_GET['freesql'])) {
    $criteria .= "<li>" . $_GET['freesql'] . "</li>\n";
    $where .= ($where ? " AND " : "") . $_GET['freesql'];
  }
  $criteria = "<ul id='criteria'>\n" . $criteria . "</ul>\n";
}

/* FILTERS */
if (!empty($_SESSION['inkeys'])) {
  $where .= ($where?" AND ":"")."song.SongID IN (SELECT SongID FROM songkeyword WHERE KeywordID IN (".$_SESSION['inkeys']."))";
}
if (!empty($_SESSION['exkeys'])) {
  $where .= ($where?" AND ":"")."NOT song.SongID IN (SELECT SongID FROM songkeyword WHERE KeywordID IN (".$_SESSION['exkeys']."))";
}

/* PUT IT ALL TOGETHER */
/*  header1("List dry run");
  header2(1);
  echo $sql . (!empty($where)?' WHERE '.$where:'') . ' GROUP BY ' . $groupby . ' ORDER BY OrigTitle';
  exit;*/

$result = sqlquery_checked($sql . (!empty($where)?' WHERE '.$where:'') . ' GROUP BY ' . $groupby . ' ORDER BY OrigTitle');
$numrecords = mysqli_num_rows($result);
if ($numrecords == 0) {
  header("Location: index.php?text=".urlencode(_('No songs found for this search.').(($_SESSION['admin'] == 2)?"<br>".$sql:"")));
  exit;
} elseif ($numrecords == 1) {
  $single = mysqli_fetch_object($result);
  header("Location: song.php?sid=".$single->SongID);
  exit;
}

$alldata = mysqli_fetch_all($result);
$jsondata = json_encode($alldata, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$jsondata = str_replace('\r','',$jsondata);
$jsondata = str_replace('\n','<br>',$jsondata);
$jsondata = str_replace("\\","\\\\",$jsondata);
$jsondata = str_replace("\u0022","\\\\\"",$jsondata);
//$jsondata = str_replace("'",'&#39;',$jsondata);

header1(_("Search Results"));
?>
  <link rel="stylesheet" type="text/css" href="css/jquery-ui.css">
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/ju/dt-1.10.18/fc-3.2.5/fh-3.1.4/r-2.2.2/datatables.min.css"/>
<?php
header2(1);

if (!empty($_GET['tagged'])) {
  echo "<h3>".sprintf(_("%d Tagged Songs"),$numrecords)."</h3>\n";
} else {
  echo "<h3>".sprintf(_("%d results of these criteria:"), $numrecords)."</h3>\n";
  echo $criteria;
}
if ($_SESSION['admin']>1) echo '<p style="font-size:10px">'.$sql . (!empty($where)?'<br> WHERE '.$where:'') . '<br> GROUP BY ' . $groupby . ' ORDER BY OrigTitle</p>';
?>

<div style="margin: 10px 0;">
  <button id="updateTags" class="ui-button"><?=_('Update tags according to checkboxes below')?></button>
  <button id="actionpage" class="ui-button"><?=_('Go to action page with this list in this order')?></button>
</div>

<?php
$eventOptions = '';
$events = sqlquery_checked('SELECT * FROM event WHERE Active=1 ORDER BY '.
(isset($_SESSION['default_event'])?'IF (EventID='.$_SESSION['default_event'].',0,1), ':'').'Event');
while ($ev = mysqli_fetch_object($events)) {
  $eventOptions .= "  <option value='".$ev->EventID."'>".$ev->Event."</option>\n";
}
echo sprintf(_('Show usage history for: %s'), "<select id=\"event\" name=\"event\">\n$eventOptions</select>");
?>

<table id="songlist" class="order-column cell-border hover stripe"></table>

<script src="js/jquery-3.6.0.min.js" type="text/javascript"></script>
<script src="js/jquery-ui.min.js" type="text/javascript"></script>
<script src="js/jquery.ui.touch-punch.min.js"></script>
<script src="//cdn.datatables.net/v/ju/dt-1.10.18/fc-3.2.5/fh-3.1.4/r-2.2.2/datatables.js"></script>

<script>

var dataSet = JSON.parse('<?=$jsondata?>');

$(document).ready(function() {
  $('#updateTags, #actionpage').button();

  var table = $('#songlist').DataTable( {
    data: dataSet,
    columns: [
      {name:'SongID', className:'songid', data:0, visible:false},
      {name:'Tagged', className:'tagged', data:1, visible:false},
      {name:'Title', className:'title dt-nowrap', title:'<?=_('Title')?>', type:'text', data:2,
        render: function(data, type, row, meta) {
          return '<a href="song.php?sid='+row[0]+'">'+data+'</a>' +
              (row[12] ? '&nbsp;<img src="graphics/audio.gif" height=16 width=16>' : '') +
              (row[13] ? '&nbsp;<img src="graphics/guitar.gif" height=16 width=16>' : '');
        }
      },
      {name:'Select', className:'select-checkbox', orderable:false, data:1,
        title:'<input type="checkbox" id="selectAll" class="song-tag-cb" title="<?=_('Select All')?>">',
        render: function(data, type, row) {
          return '<input type="checkbox" class="song-tag-cb" data-sid="'+row[0]+'"'+(row[1]==1?' checked':'')+' title="<?=_('Tag/Untag')?>">';
        }
      },
      {name:'OrigTitle', className:'origtitle dt-nowrap', title:'<?=_('Original Title')?>', type:'text', data:3},
      {name:'Tempo', className:'tempo dt-nowrap', title:'<?=_('Tempo')?>', type:'text', data:4},
      {name:'SongKey', className:'songkey dt-nowrap', title:'<?=_('Key')?>', type:'text', data:5},
      {name:'FirstLine', className:'firstline', title:'<?=_('First Line')?>', type:'text', data:6},
      {name:'LastUse', className:'lastuse', title:'<?=_('Last Use')?>', type:'date', data:7},
      {name:'NumUse', className:'numuse', title:'<?=_('# Uses')?>', type:'num', data:8},
      {name:'Composer', className:'composer', title:'<?=_('Composer')?>', type:'text', data:9},
      {name:'Copyright', className:'copyright', title:'<?=_('Copyright')?>', type:'text', data:10},
      {name:'Source', className:'source', title:'<?=_('Source')?>', type:'text', data:11}
    ],
    order: [[4, 'asc']],  //OrigTitle

    language: {
      info: '<?=_("Showing _TOTAL_ entries")?>',
      infoFiltered: '<?=_(" (filtered from _MAX_ total)")?>'
<?php if($_SESSION['lang']=='ja_JP') { ?>,
      search: '検索：',
      zeroRecords: '該当するデータがありません。',
      infoEmpty: 'データがありません'
<?php } ?>
    },
    responsive: true,
    paging: false,
    dom: 'fit'
  } );

  // Select All checkbox in header
  $('#songlist').on('click', '#selectAll', function() {
    $('.song-tag-cb').prop('checked', $(this).is(':checked'));
  });

  // Bind directly to each checkbox (not delegated) so stopPropagation fires
  // before the event reaches td.control and triggers the accordion toggle.
  // Must rebind after every draw because checkboxes are recreated.
  function bindCheckboxEvents() {
    $('#songlist tbody .song-tag-cb').off('click.cbfix').on('click.cbfix', function(e) {
      e.stopPropagation();
    });
  }
  bindCheckboxEvents();
  table.on('draw', bindCheckboxEvents);

  // Update Tags button - commits checkbox state to database
  $('#updateTags').click(function() {
    var allSids = [];
    var taggedSids = [];

    table.rows().every(function() {
      allSids.push(this.data()[0]);
      if ($(this.node()).find('.song-tag-cb').is(':checked')) {
        taggedSids.push(this.data()[0]);
      }
    });

    $.post('ajax_actions.php', {
      action: 'UpdateTags',
      sid_list: allSids.join(','),
      tagged_list: taggedSids.join(',')
    }, function(response) {
      if (response.success) {
        $('.tagcount').text(response.totalTagged);
      } else {
        alert(response.error || '<?=_('Error updating tags.')?>');
      }
    }, 'json').fail(function() {
      alert('<?=_('Error updating tags.')?>');
    });
  });

  // Go to action page - sends ALL songs in current display order
  $('#actionpage').click(function() {
    var sids = [];
    table.rows({order:'current'}).every(function() {
      sids.push(this.data()[0]);
    });
    location.href = 'multiselect.php?sid_list=' + sids.join(',');
  });

  // Event dropdown - AJAX update of LastUse/NumUse columns
  $('#event').on('change', function() {
    $.getJSON('ajax_actions.php', {action: 'HistoryData', eventid: $(this).val()}, function(data) {
      var map = {};
      $.each(data, function(i, row) {
        map[row.SongID] = row;
      });
      table.rows().every(function() {
        var d = this.data();
        var h = map[d[0]];
        d[7] = h ? h.LastUse : '';
        d[8] = h ? h.NumUse : 0;
        this.invalidate();
      });
      table.draw(false);
    });
  }).val('<?=$eid?>');

} );
</script>
<?php footer(); ?>