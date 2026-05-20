<?php
include("functions.php");
include("accesscontrol.php");

function h2d($text) {
  global $db;
  return mysqli_real_escape_string($db, $text);
}

if (!isset($_SESSION['userid'])) {
  die(json_encode(array('alert' => 'NOSESSION')));
}

switch ($_REQUEST['action'] ?? '') {

  case 'TagSave':
    if ($_SESSION['access'] < 1) {
      die(json_encode(array('success' => false, 'error' => _('Access denied.'))));
    }
    $tag = trim($_REQUEST['tag'] ?? '');
    if ($tag === '') {
      die(json_encode(array('success' => false, 'error' => _('Tag name cannot be blank.'))));
    }
    $tag_escaped = h2d($tag);
    if (($_REQUEST['tagid'] ?? '') == 'new') {
      sqlquery_checked("INSERT INTO tag (Tag) VALUES ('$tag_escaped')");
      $newid = mysqli_insert_id($db);
      die(json_encode(array('success' => true, 'tagid' => $newid, 'tag' => $tag,
                            'message' => _('New tag successfully added.'))));
    } else {
      $tagid = intval($_REQUEST['tagid']);
      sqlquery_checked("UPDATE tag SET Tag='$tag_escaped' WHERE TagID=$tagid");
      die(json_encode(array('success' => true, 'tagid' => $tagid, 'tag' => $tag,
                            'message' => _('Tag successfully renamed.'))));
    }
    break;

  case 'TagDelete':
    if ($_SESSION['access'] < 1) {
      die(json_encode(array('success' => false, 'error' => _('Access denied.'))));
    }
    $tagid = intval($_REQUEST['tagid'] ?? 0);
    if ($tagid < 1) {
      die(json_encode(array('success' => false, 'error' => _('Invalid tag ID'))));
    }
    sqlquery_checked("DELETE FROM songtag WHERE TagID=$tagid");
    $songtag_removed = mysqli_affected_rows($db);
    sqlquery_checked("DELETE FROM tag WHERE TagID=$tagid LIMIT 1");
    if (mysqli_affected_rows($db) == 1) {
      die(json_encode(array('success' => true, 'tagid' => $tagid,
                            'songtag_removed' => $songtag_removed,
                            'message' => _('Tag successfully deleted.'))));
    }
    die(json_encode(array('success' => false, 'error' => _('Tag not found.'))));
    break;

  case 'EventSave':
    if ($_SESSION['access'] < 1) {
      die(json_encode(array('success' => false, 'error' => _('Access denied.'))));
    }
    $event = trim($_REQUEST['event'] ?? '');
    if ($event === '') {
      die(json_encode(array('success' => false, 'error' => _('Event name cannot be blank.'))));
    }
    $event_escaped = h2d($event);
    $remarks_escaped = h2d($_REQUEST['remarks'] ?? '');
    $active = !empty($_REQUEST['active']) ? 1 : 0;
    if (($_REQUEST['eventid'] ?? '') == 'new') {
      sqlquery_checked("INSERT INTO event (Event,Active,Remarks) VALUES ('$event_escaped',$active,'$remarks_escaped')");
      $newid = mysqli_insert_id($db);
      die(json_encode(array('success' => true, 'eventid' => $newid, 'event' => $event, 'active' => $active,
                            'message' => _('New event successfully added.'))));
    } else {
      $eventid = intval($_REQUEST['eventid']);
      sqlquery_checked("UPDATE event SET Event='$event_escaped',Active=$active,Remarks='$remarks_escaped' WHERE EventID=$eventid");
      die(json_encode(array('success' => true, 'eventid' => $eventid, 'event' => $event, 'active' => $active,
                            'message' => _('Event information successfully updated.'))));
    }
    break;

  case 'EventDelete':
    if ($_SESSION['access'] < 1) {
      die(json_encode(array('success' => false, 'error' => _('Access denied.'))));
    }
    $eventid = intval($_REQUEST['eventid'] ?? 0);
    if ($eventid < 1) {
      die(json_encode(array('success' => false, 'error' => _('Invalid event ID'))));
    }
    sqlquery_checked("DELETE FROM history WHERE EventID=$eventid");
    $history_deleted = mysqli_affected_rows($db);
    sqlquery_checked("DELETE FROM event WHERE EventID=$eventid LIMIT 1");
    if (mysqli_affected_rows($db) == 1) {
      die(json_encode(array('success' => true, 'eventid' => $eventid,
                            'history_deleted' => $history_deleted,
                            'message' => _('Event successfully deleted.'))));
    }
    die(json_encode(array('success' => false, 'error' => _('Event not found.'))));
    break;

  case 'UserSave':
    if ($_SESSION['access'] != 2) {
      die(json_encode(array('success' => false, 'error' => _('Access denied.'))));
    }
    $username   = trim($_REQUEST['username'] ?? '');
    $new_userid = trim($_REQUEST['new_userid'] ?? '');
    if ($username === '') {
      die(json_encode(array('success' => false, 'error' => _('User Name cannot be blank.'))));
    }
    if ($new_userid === '') {
      die(json_encode(array('success' => false, 'error' => _('UserID cannot be blank.'))));
    }
    $adminlevel        = intval($_REQUEST['accesslevel'] ?? 1);
    $lang_in           = $_REQUEST['language'] ?? '';
    $language          = in_array($lang_in, array('en_US', 'ja_JP')) ? $lang_in : 'en_US';
    $new_pw1           = $_REQUEST['new_pw1'] ?? '';
    $new_userid_esc    = h2d($new_userid);

    if (($_REQUEST['userid'] ?? '') == 'new') {
      if ($new_pw1 === '') {
        die(json_encode(array('success' => false, 'error' => _('You must enter a password for a new user.'))));
      }
      $result = sqlquery_checked("SELECT UserName FROM user WHERE UserID='$new_userid_esc'");
      if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_object($result);
        die(json_encode(array('success' => false, 'error' => sprintf(
          _("UserID '%s' is already in use by %s. Please choose a different UserID."),
          $new_userid, $row->UserName))));
      }
      sqlquery_checked("INSERT INTO user (UserID,UserName,Password,Access,Language) VALUES ".
                       "('$new_userid_esc','".h2d($username)."',PASSWORD('".h2d($new_pw1)."'),$adminlevel,'".h2d($language)."')");
      die(json_encode(array('success' => true, 'userid' => $new_userid, 'username' => $username,
                            'sessionUpdated' => false,
                            'message' => _('New user successfully added.'))));
    } else {
      $old_userid     = $_REQUEST['old_userid'] ?? '';
      $old_userid_esc = h2d($old_userid);
      if ($new_userid !== $old_userid) {
        $result = sqlquery_checked("SELECT UserName FROM user WHERE UserID='$new_userid_esc'");
        if (mysqli_num_rows($result) > 0) {
          $row = mysqli_fetch_object($result);
          die(json_encode(array('success' => false, 'error' => sprintf(
            _("UserID '%s' is already in use by %s. Please choose a different UserID."),
            $new_userid, $row->UserName))));
        }
      }
      $sql = 'UPDATE user SET ';
      if ($new_userid !== $old_userid) $sql .= "UserID='$new_userid_esc',";
      $sql .= "UserName='".h2d($username)."',";
      if ($new_pw1 !== '') $sql .= "Password=PASSWORD('".h2d($new_pw1)."'),";
      $sql .= "Access=$adminlevel,Language='".h2d($language)."' WHERE UserID='$old_userid_esc'";
      sqlquery_checked($sql);
      if ($new_userid !== $old_userid) {
        sqlquery_checked("UPDATE loginlog SET UserID='$new_userid_esc' WHERE UserID='$old_userid_esc'");
      }
      $sessionUpdated = false;
      $newLang        = null;
      if ($old_userid == $_SESSION['userid']) {
        if ($_SESSION['lang'] !== $language) $newLang = $language;
        $_SESSION['userid']   = $new_userid;
        $_SESSION['username'] = $username;
        $_SESSION['access']   = $adminlevel;
        $_SESSION['lang']     = $language;
        $sessionUpdated       = true;
      }
      $resp = array('success' => true, 'userid' => $new_userid, 'username' => $username,
                    'sessionUpdated' => $sessionUpdated,
                    'message' => _('User information successfully updated.'));
      if ($newLang !== null) $resp['newLang'] = $newLang;
      die(json_encode($resp));
    }
    break;

  case 'UserDelete':
    if ($_SESSION['access'] != 2) {
      die(json_encode(array('success' => false, 'error' => _('Access denied.'))));
    }
    $old_userid = $_REQUEST['old_userid'] ?? '';
    if ($old_userid === '') {
      die(json_encode(array('success' => false, 'error' => _('Invalid user ID'))));
    }
    if ($old_userid == $_SESSION['userid']) {
      die(json_encode(array('success' => false, 'error' => _('You cannot delete your own user.'))));
    }
    $old_userid_esc = h2d($old_userid);
    sqlquery_checked("DELETE FROM user WHERE UserID='$old_userid_esc'");
    if (mysqli_affected_rows($db) == 1) {
      die(json_encode(array('success' => true, 'userid' => $old_userid,
                            'message' => _('User successfully deleted.'))));
    }
    die(json_encode(array('success' => false, 'error' => _('User not found.'))));
    break;

  default:
    die(json_encode(array('alert' => 'Programming error: NO ACTION RECOGNIZED')));
}
?>
