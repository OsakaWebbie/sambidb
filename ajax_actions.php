<?php
include("functions.php");
include("accesscontrol.php");

// Check session for AJAX requests that need it
if (!isset($_SESSION['userid']) && $_REQUEST['action'] != 'SwitchLang') {
  die(json_encode(array('alert' => 'NOSESSION')));
}

switch($_REQUEST['action']) {
  case "SwitchLang":
    if (!isset($_GET['lang']) || ($_GET['lang']!='en_US' && $_GET['lang']!='ja_JP')) die("Failed.");
    $_SESSION['lang'] = $_GET['lang'];
    setlocale(LC_ALL, $_SESSION['lang'].".utf8");
    break;

  case 'Keyword':
    if (isset($_REQUEST['kwid']) && $_REQUEST['kwid']!="") {
      $kwid = mysqli_real_escape_string($db, $_REQUEST['kwid']);
      $result = sqlquery_checked("SELECT * FROM keyword WHERE KeywordID=".$kwid);
      if (mysqli_num_rows($result)>0) {
        $row = mysqli_fetch_object($result);
        $arr = array('kwid' => $row->KeywordID, 'keyword' => $row->Keyword);
        die(json_encode($arr));
      } else {
        die(json_encode(array('alert' => 'Record not found.')));
      }
    }
    break;

  case 'Event':
    if (!isset($_REQUEST['eventid']) || $_REQUEST['eventid']=="") {
      die(json_encode(array('alert' => 'Missing eventid parameter.')));
    }
    $eventid = intval($_REQUEST['eventid']);
    $result = sqlquery_checked("SELECT * FROM event WHERE EventID=".$eventid);
    if (mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_object($result);
      $arr = array('eventid' => $row->EventID, 'event' => $row->Event,
                   'active' => $row->Active, 'remarks' => $row->Remarks);
      die(json_encode($arr));
    } else {
      die(json_encode(array('alert' => 'Event not found.')));
    }
    break;

  case 'User':
    // Admin only
    if ($_SESSION['admin'] != 2) {
      die(json_encode(array('alert' => 'Access denied.')));
    }
    if (isset($_REQUEST['userid']) && $_REQUEST['userid']!="") {
      $userid = mysqli_real_escape_string($db, $_REQUEST['userid']);
      $sql = "SELECT user.*, YEAR(LoginTime) loginyear, MAX(LoginTime) loginlast, COUNT(LoginTime) loginnum ".
             "FROM user LEFT JOIN loginlog ON user.UserID=loginlog.UserID ".
             "WHERE user.UserID='".$userid."' ".
             "GROUP BY user.UserID, YEAR(LoginTime) ORDER BY YEAR(LoginTime) DESC";
      $result = sqlquery_checked($sql);
      if (mysqli_num_rows($result)>0) {
        $arr = null;
        $totalLogins = 0;
        $yearStats = [];
        $lastLogin = null;

        while ($row = mysqli_fetch_object($result)) {
          if ($arr === null) {
            // Get user data from first row
            $arr = array('userid' => $row->UserID, 'username' => $row->UserName,
                         'language' => $row->Language, 'admin' => $row->Admin);
            $lastLogin = $row->loginlast;
          }
          if ($row->loginyear !== null) {
            $totalLogins += $row->loginnum;
            $yearStats[] = $row->loginyear . ": " . $row->loginnum;
          }
        }

        // Build login stats string
        if ($lastLogin === null) {
          $arr['loginstats'] = _("Never logged in");
        } else {
          $loginStats = sprintf(_("Last login: %s"), $lastLogin);
          $loginStats .= " &bull; " . sprintf(_("Total: %d"), $totalLogins);
          if (count($yearStats) > 1) {
            $loginStats .= " (" . implode(", ", $yearStats) . ")";
          }
          $arr['loginstats'] = $loginStats;
        }

        die(json_encode($arr));
      } else {
        die(json_encode(array('alert' => 'Record not found.')));
      }
    }
    break;

  case 'HistoryData':
    $eventid = intval($_REQUEST['eventid'] ?? 0);
    if ($eventid < 1) {
        die(json_encode(array('error' => 'Invalid event ID.')));
    }
    $result = sqlquery_checked("SELECT SongID, MAX(UseDate) AS LastUse, COUNT(UseDate) AS NumUse FROM history WHERE EventID=$eventid GROUP BY SongID");
    die(json_encode(mysqli_fetch_all($result, MYSQLI_ASSOC)));
    break;

  case 'UpdateTags':
    if ($_SESSION['admin'] < 1) {
        die(json_encode(array('error' => _('Access denied.'))));
    }
    $sid_list = $_REQUEST['sid_list'] ?? '';
    $tagged_list = $_REQUEST['tagged_list'] ?? '';

    if (empty($sid_list)) {
        die(json_encode(array('error' => 'No songs specified.')));
    }

    $all_sids = array_filter(array_map('intval', explode(',', $sid_list)));
    $tagged_sids = $tagged_list ? array_filter(array_map('intval', explode(',', $tagged_list))) : [];

    if (empty($all_sids)) {
        die(json_encode(array('error' => 'Invalid song IDs.')));
    }

    $all_ids = implode(',', $all_sids);
    sqlquery_checked("UPDATE song SET Tagged=0 WHERE SongID IN ($all_ids)");

    if (!empty($tagged_sids)) {
        $tagged_ids = implode(',', $tagged_sids);
        sqlquery_checked("UPDATE song SET Tagged=1 WHERE SongID IN ($tagged_ids)");
    }

    $totalTagged = mysqli_fetch_row(mysqli_query($db, "SELECT COUNT(SongID) FROM song WHERE Tagged=1"))[0];
    die(json_encode(array('success' => true, 'totalTagged' => $totalTagged)));
    break;

  case 'SetDisplayPref':
    if (isset($_REQUEST['show_chords'])) {
      $_SESSION['show_chords'] = ($_REQUEST['show_chords'] == '1') ? 1 : 0;
    }
    if (isset($_REQUEST['show_romaji'])) {
      $_SESSION['show_romaji'] = ($_REQUEST['show_romaji'] == '1') ? 1 : 0;
    }
    die(json_encode(array('success' => true)));
    break;

  case 'TagSong':
    if (!isset($_REQUEST['sid']) || !is_numeric($_REQUEST['sid'])) {
      die(json_encode(array('error' => 'Invalid song ID.')));
    }
    $sid = intval($_REQUEST['sid']);

    // Get current state
    $result = sqlquery_checked("SELECT Tagged FROM song WHERE SongID=$sid");
    if (mysqli_num_rows($result) == 0) {
      die(json_encode(array('error' => 'Song not found.')));
    }
    $song = mysqli_fetch_object($result);

    // Toggle state
    $newState = $song->Tagged ? 0 : 1;
    sqlquery_checked("UPDATE song SET Tagged=$newState WHERE SongID=$sid");

    // Get total tagged count
    $totalTagged = mysqli_fetch_row(mysqli_query($db, "SELECT COUNT(SongID) FROM song WHERE Tagged=1"))[0];

    die(json_encode(array('success' => true, 'tagged' => $newState, 'totalTagged' => $totalTagged)));
    break;

  default:
    die("Programming error: NO ACTION RECOGNIZED");
}
?>