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

  default:
    die("Programming error: NO ACTION RECOGNIZED");
}
?>