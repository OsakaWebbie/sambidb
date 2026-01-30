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
header2(1);

if (!empty($_GET['tagged'])) {
  echo "<h3>".sprintf(_("%d Tagged Songs"),$numrecords)."</h3>\n";
} else {
  echo "<h3>".sprintf(_("%d results of these criteria:"), $numrecords)."</h3>\n";
  echo $criteria;
}
if ($_SESSION['admin']>1) echo '<p style="font-size:10px">'.$sql . (!empty($where)?'<br> WHERE '.$where:'') . '<br> GROUP BY ' . $groupby . ' ORDER BY OrigTitle</p>';
?>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/cupertino/jquery-ui.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/ju/dt-1.10.18/b-1.5.4/b-colvis-1.5.4/fc-3.2.5/fh-3.1.4/r-2.2.2/sl-1.2.6/datatables.min.css"/>

<label>Show history data for: </label>
<select id="event" name="event">
<?php
$events = sqlquery_checked('SELECT * FROM event WHERE Active=1 ORDER BY '.
(isset($_SESSION['default_event'])?'IF (EventID='.$_SESSION['default_event'].',0,1), ':'').'Event');
while ($ev = mysqli_fetch_object($events)) {
  echo "  <option value='".$ev->EventID."'>".$ev->Event."</option>\n";
}
?>
</select>

<button id="actionpage" class="ui-button"><?=_('Go to action page with this list in this order')?></button>

<table id="songlist" class="order-column cell-border hover stripe"></table></table>

<script src="//code.jquery.com/jquery-3.2.1.min.js"></script>
<script src="//code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script src="js/jquery.ui.touch-punch.min.js"></script>
<script src="//cdn.datatables.net/v/ju/dt-1.10.18/b-1.5.4/b-colvis-1.5.4/fc-3.2.5/fh-3.1.4/r-2.2.2/sl-1.2.6/datatables.js"></script>

<script>

var dataSet = JSON.parse('<?=$jsondata?>');

$(document).ready(function() {
  var table = $('#songlist').DataTable( {
    data: dataSet,
    columns: [
      {name:'SongID', className:'songid', title:'<?=_('ID')?>', type:'num', visible:false},
      {name:'Tagged', className:'tagged', title:'<?=_('Tagged')?>', type:'num', visible:false},
//      {name:'SongID', className:'songid', title:'<?=_('ID')?>', type:'num'},
//      {name:'Tagged', className:'tagged', title:'<?=_('Tag')?>', type:'num'},
      {name:'Title', className:'title dt-nowrap', title:'<?=_('Title')?>', type:'text',
        render: function(data, type, row, meta) {
          return '<a href="song.php?sid='+row[0]+'">'+data+'</a>' +
              (row[12] ? '&nbsp;<img src="graphics/audio.gif" height=16 width=16>' : '') +
              (row[13] ? '&nbsp;<img src="graphics/guitar.gif" height=16 width=16>' : '');
        }
      },
      {name:'OrigTitle', className:'origtitle dt-nowrap', title:'<?=_('Original Title')?>', type:'text'},
      {name:'Tempo', className:'tempo', title:'<?=_('Tempo')?>', type:'text'},
      {name:'SongKey', className:'songkey dt-nowrap', title:'<?=_('Key')?>', type:'text'},
      {name:'FirstLine', className:'firstline', title:'<?=_('First Line')?>', type:'text'},
      {name:'LastUse', className:'lastuse', title:'<?=_('Last Use')?>', type:'date'},
      {name:'NumUse', className:'numuse', title:'<?=_('# Uses')?>', type:'num'},
      {name:'Composer', className:'composer', title:'<?=_('Composer')?>', type:'text'},
      {name:'Copyright', className:'copyright', title:'<?=_('Copyright')?>', type:'text'},
      {name:'Source', className:'source', title:'<?=_('Source')?>', type:'text'}
    ],
    order: [[3, 'asc']],  //OrigTitle
    buttons: [
      'selectAll',
      'selectNone'
    ],

    language: {
<?php if($_SESSION['lang']=='ja_JP') { ?>      url: "//cdn.datatables.net/plug-ins/1.10.19/i18n/Japanese.json",
      select: {
        rows: {
          _: "%d曲が選択されています。",
          0: "選択するには、行をクリックしてください",
          1: "1曲が選択されています。"
        }
      },
<?php } ?>
      buttons: {
        selectAll: "<?=_('Tag All')?>",
        selectNone: "<?=_('Untag All')?>"
      }
    },
    responsive: true,
    paging: false,
    select: {
      items: 'row',
      style: 'multi'
    },
    dom: 'fBit'
  } );

  //pre-select the rows of tagged songs
  table.rows( function(idx, data, node) {
    return data[1] == 1;
  }).select();

  //update tag status in DB when selected
  table.on( 'select', function ( e, dt, type, indexes ) {
    var addtags = table.rows( indexes ).data().pluck( 'SongID' );
    //console.log(JSON.stringify(addtags));
  } );
  //clear tag status in DB when deselected
  table.on( 'deselect', function ( e, dt, type, indexes ) {
    var removetags = table.rows( indexes ).data().pluck( 'SongID' );
    //console.log(JSON.stringify(removetags));
  } );
  //go to action page
  $('#actionpage').click( function ( e, dt, type, indexes ) {
      sids = table.columns(0).data().eq(0).join(',');
    console.log('SIDs:'+sids);
    //location='multiselect.php?sid_list='+sids;
  } );

} );
</script>
<?php footer(); ?>