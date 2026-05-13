<?php
include("functions.php");
include("accesscontrol.php");

// Handle AJAX requests
if (!empty($_REQUEST['action'])) {
  $action = $_REQUEST['action'];
  $eid = !empty($_REQUEST['eid']) ? intval($_REQUEST['eid']) : 0;

  if ($action === 'loadChart' || $action === 'loadMore') {
    if (!$eid) {
      header('Content-Type: application/json');
      echo json_encode(['error' => 'No event selected']);
      exit;
    }

    $earliestDate = !empty($_REQUEST['earliest']) ? $_REQUEST['earliest'] : null;

    // Get dates
    $dateSql = "SELECT DISTINCT UseDate FROM history WHERE EventID = ".$eid;
    if ($earliestDate) {
      $dateSql .= " AND UseDate < '".mysqli_real_escape_string($db, $earliestDate)."'";
    }
    $dateSql .= " ORDER BY UseDate DESC LIMIT 20";

    $dateResult = sqlquery_checked($dateSql);
    $dates = [];
    while ($row = mysqli_fetch_row($dateResult)) {
      $dates[] = $row[0];
    }

    $hasMore = (count($dates) == 20);
    $newEarliestDate = $hasMore ? $dates[count($dates) - 1] : null;

    if ($action === 'loadChart') {
      // Initial load - get all songs and their usages
      $songs = [];
      if (count($dates) > 0) {
        $oldestInRange = $dates[count($dates) - 1];
        $newestInRange = $dates[0];

        $sql = "SELECT h.SongID, s.Title, s.OrigTitle, h.UseDate
                FROM history h
                LEFT JOIN song s ON h.SongID = s.SongID
                WHERE h.EventID = ".$eid."
                  AND h.UseDate >= '".mysqli_real_escape_string($db, $oldestInRange)."'
                  AND h.UseDate <= '".mysqli_real_escape_string($db, $newestInRange)."'
                ORDER BY h.UseDate DESC";

        $result = sqlquery_checked($sql);
        while ($row = mysqli_fetch_assoc($result)) {
          $sid = $row['SongID'];
          if (!isset($songs[$sid])) {
            $songs[$sid] = [
              'id' => $sid,
              'title' => $row['Title'],
              'origTitle' => $row['OrigTitle'],
              'usages' => []
            ];
          }
          $songs[$sid]['usages'][] = $row['UseDate'];
        }
      }

      // Get event remarks
      $eventRemarks = sql_single("SELECT Remarks FROM event WHERE EventID = ".$eid);

      header('Content-Type: application/json');
      echo json_encode([
        'dates' => $dates,
        'songs' => array_values($songs),
        'hasMore' => $hasMore,
        'earliestDate' => $newEarliestDate,
        'eventRemarks' => $eventRemarks
      ]);
      exit;

    } else { // loadMore
      // Load more - just get usages for these dates
      $usageData = [];
      if (count($dates) > 0) {
        $oldestInRange = $dates[count($dates) - 1];
        $newestInRange = $dates[0];

        $usageSql = "SELECT h.SongID, h.UseDate
                     FROM history h
                     WHERE h.EventID = ".$eid."
                       AND h.UseDate >= '".mysqli_real_escape_string($db, $oldestInRange)."'
                       AND h.UseDate <= '".mysqli_real_escape_string($db, $newestInRange)."'
                     ORDER BY h.UseDate DESC";

        $usageResult = sqlquery_checked($usageSql);
        while ($row = mysqli_fetch_assoc($usageResult)) {
          $sid = $row['SongID'];
          if (!isset($usageData[$sid])) {
            $usageData[$sid] = [];
          }
          $usageData[$sid][] = $row['UseDate'];
        }
      }

      header('Content-Type: application/json');
      echo json_encode([
        'dates' => $dates,
        'usages' => $usageData,
        'hasMore' => $hasMore,
        'earliestDate' => $newEarliestDate
      ]);
      exit;
    }
  }
}

// Regular page load
header1(_("Song Use Chart"));
?>
<link rel="stylesheet" type="text/css" href="css/jquery-ui.css">
<style>
    td.sticky-col { text-align:left !important; }
</style>
<?php
header2(1);
?>

<h1><?=_("Song Use Chart")?></h1>
<form name="eform">
  <p style="display: inline-block; margin-right: 20px;">
    <?= _("Event:") ?> <select size="1" name="event" onchange="loadChart();">
      <option value=""><?= _("Select an event...") ?></option>
<?php
// Build option list from event table contents
if (!$result = mysqli_query($db,"SELECT * FROM event ORDER BY Active DESC, Event")) {
  echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b>");
} else {
  while ($row = mysqli_fetch_object($result)) {
    echo "      <option value=$row->EventID>{$row->Event} (";
    echo ($row->Active ? _("current") : _("archive")) . ")</option>\n";
  }
}
?>
    </select>
  </p>
  <span id="event-description" style="font-weight: bold; display: none;"></span>
</form>

<div id="chart-controls" class="chart-controls" style="display: none;">
  <span class="sort-controls">
    <?= _("Sort by:") ?>
    <button class="sort-btn ui-state-active ui-button ui-corner-all" data-sort="origTitle" onclick="sortSongs('origTitle')"><?= _("Original Title") ?></button>
    <button class="sort-btn ui-button ui-corner-all" data-sort="title" onclick="sortSongs('title')"><?= _("Title") ?></button>
    <button class="sort-btn ui-button ui-corner-all" data-sort="count" onclick="sortSongs('count')"><?= _("Usage Count") ?></button>
  </span>
  <button id="load-more-dates" class="ui-button ui-corner-all" style="display: none;" onclick="loadMoreDates()"><?= _("Load Earlier Dates") ?></button>
</div>

<div id="chart-container" style="display: none;">
  <!-- Chart will be built here -->
</div>

<script type="text/javascript">
// Song session popup
function song_session(eventid, usedate) {
  var left = Math.floor((screen.width - 800) / 2);
  var top = Math.floor((screen.height - 600) / 2);
  window.open('song_session.php?eid=' + eventid + '&ud=' + usedate, '',
    'top=' + top + ',left=' + left + ',WIDTH=800,HEIGHT=600,scrollbars=yes,menubar=yes');
}

// Chart state
var chartState = {
  eid: null,
  dates: [],
  songs: [],
  songsById: {},
  sortBy: 'origTitle',
  sortDir: 'asc',
  hasMore: false,
  earliestLoaded: null
};

// Load chart for selected event
function loadChart() {
  var eid = document.eform.event.value;
  if (!eid) {
    document.getElementById('chart-container').style.display = 'none';
    document.getElementById('chart-controls').style.display = 'none';
    document.getElementById('event-description').style.display = 'none';
    return;
  }

  chartState.eid = eid;

  var xhr = new XMLHttpRequest();
  xhr.open('GET', 'event_use.php?action=loadChart&eid=' + eid, true);
  xhr.onload = function() {
    if (xhr.status === 200) {
      var data = JSON.parse(xhr.responseText);

      if (data.error) {
        alert(data.error);
        return;
      }

      // Update state
      chartState.dates = data.dates;
      chartState.songs = data.songs;
      chartState.hasMore = data.hasMore;
      chartState.earliestLoaded = data.earliestDate;

      // Build songsById lookup
      chartState.songsById = {};
      chartState.songs.forEach(function(song) {
        chartState.songsById[song.id] = song;
      });

      // Show event description
      if (data.eventRemarks) {
        document.getElementById('event-description').textContent = '(' + data.eventRemarks + ')';
        document.getElementById('event-description').style.display = 'inline';
      } else {
        document.getElementById('event-description').style.display = 'none';
      }

      // Show controls and container
      document.getElementById('chart-controls').style.display = 'flex';
      document.getElementById('chart-container').style.display = 'block';

      // Show/hide load more button
      document.getElementById('load-more-dates').style.display = chartState.hasMore ? 'inline-block' : 'none';

      // Reset sort to default
      chartState.sortBy = 'origTitle';
      chartState.sortDir = 'asc';

      // Sort and render
      sortSongs('origTitle');
    }
  };
  xhr.send();
}

// Load more dates
function loadMoreDates() {
  if (!chartState.hasMore) return;

  var btn = document.getElementById('load-more-dates');
  btn.disabled = true;
  btn.textContent = '<?= _("Loading...") ?>';

  var xhr = new XMLHttpRequest();
  xhr.open('GET', 'event_use.php?action=loadMore&eid=' + chartState.eid +
    '&earliest=' + encodeURIComponent(chartState.earliestLoaded), true);
  xhr.onload = function() {
    if (xhr.status === 200) {
      var data = JSON.parse(xhr.responseText);

      // Add new dates
      chartState.dates = chartState.dates.concat(data.dates);

      // Update song usages
      for (var sid in data.usages) {
        if (chartState.songsById[sid]) {
          chartState.songsById[sid].usages = chartState.songsById[sid].usages.concat(data.usages[sid]);
        }
      }

      chartState.hasMore = data.hasMore;
      chartState.earliestLoaded = data.earliestDate;

      // Re-render
      renderTable();

      if (!chartState.hasMore) {
        btn.style.display = 'none';
      } else {
        btn.disabled = false;
        btn.textContent = '<?= _("Load Earlier Dates") ?>';
      }
    }
  };
  xhr.send();
}

// Calculate usage count
function getUsageCount(song) {
  return song.usages ? song.usages.length : 0;
}

// Sort songs
function sortSongs(by) {
  if (chartState.sortBy === by) {
    chartState.sortDir = (chartState.sortDir === 'asc') ? 'desc' : 'asc';
  } else {
    chartState.sortBy = by;
    chartState.sortDir = (by === 'count') ? 'desc' : 'asc';
  }

  chartState.songs.sort(function(a, b) {
    var valA, valB;

    if (by === 'count') {
      valA = getUsageCount(a);
      valB = getUsageCount(b);
    } else if (by === 'title') {
      valA = a.title || '';
      valB = b.title || '';
    } else { // origTitle
      valA = a.origTitle || '';
      valB = b.origTitle || '';
    }

    var result;
    if (by === 'count') {
      result = valA - valB;
    } else {
      result = valA.localeCompare(valB, undefined, {sensitivity: 'base'});
    }

    return chartState.sortDir === 'asc' ? result : -result;
  });

  renderTable();
}

// Render the table
function renderTable() {
  var container = document.getElementById('chart-container');

  // Build date headers
  var headerHTML = '<th class="sticky-corner"><?= _("Song (Count)") ?></th>';
  chartState.dates.forEach(function(date) {
    headerHTML += '<th class="sticky-header"><a href="javascript:song_session(' +
      chartState.eid + ', \'' + date + '\');">' +
      date.substr(0, 4) + '<br>' + date.substr(5) + '</a></th>';
  });

  // Build table rows
  var rowsHTML = '';
  chartState.songs.forEach(function(song) {
    var count = getUsageCount(song);
    var usageSet = {};
    if (song.usages) {
      song.usages.forEach(function(date) {
        usageSet[date] = true;
      });
    }

    rowsHTML += '<tr><td class="sticky-col"><a href="song.php?sid=' + song.id +
      '" target="_blank">' + (song.title || song.origTitle || 'Untitled') +
      ' (' + count + ')</a></td>';

    chartState.dates.forEach(function(date) {
      if (usageSet[date]) {
        rowsHTML += '<td class="used">*</td>';
      } else {
        rowsHTML += '<td></td>';
      }
    });

    rowsHTML += '</tr>';
  });

  container.innerHTML =
    '<div class="use-chart-container">' +
    '<table class="use-chart">' +
    '<thead><tr id="date-header-row">' + headerHTML + '</tr></thead>' +
    '<tbody>' + rowsHTML + '</tbody>' +
    '</table></div>';

  // Update sort button states
  $('.sort-btn').each(function() {
    if ($(this).attr('data-sort') === chartState.sortBy) {
      $(this).addClass('ui-state-active');
    } else {
      $(this).removeClass('ui-state-active');
    }
  });
}
</script>

<script src="js/jquery-3.6.0.min.js" type="text/javascript"></script>
<script src="js/jquery-ui.min.js" type="text/javascript"></script>
<?php
footer();
?>
