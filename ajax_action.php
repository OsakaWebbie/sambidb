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

// An oversized upload makes PHP drop the entire POST (incl. the action param);
// surface that as a clean error for the edit.php save handler.
if (!empty($_SERVER['CONTENT_LENGTH']) && empty($_POST) && empty($_FILES)) {
  die(json_encode(array('success' => false,
    'error' => sprintf(_('The uploaded file was too large. You must upload a file smaller than %s.'), ini_get('upload_max_filesize')))));
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

  case 'SongSave':
    if ($_SESSION['access'] < 1) {
      die(json_encode(array('success' => false, 'error' => _('Access denied.'))));
    }
    set_time_limit(0);  // allow time for a possible audio upload
    $needswork    = !empty($_POST['needswork']) ? 1 : 0;  // deliberate review flag, never auto-cleared
    $has_sections = isset($_POST['sec']);                 // converted / new-with-sections edit
    $rows         = $has_sections ? $_POST['sec'] : array();

    // Validate sections before touching the DB, so a section error can't leave the
    // metadata half-saved. Every kept section needs a type, and no two may share
    // the same type + language (matches the DB UNIQUE key).
    if ($has_sections) {
      $seen = array();
      foreach ($rows as $r) {
        if (!empty($r['delete']) || trim($r['lyrics'] ?? '') === '') continue;
        if (trim($r['type'] ?? '') === '') {
          die(json_encode(array('success' => false, 'error' => _('Please choose a type for every section.'))));
        }
        $key = ($r['type'] ?? '').'|'.($r['lang'] ?? '');
        if (isset($seen[$key])) {
          die(json_encode(array('success' => false,
            'error' => _('Two sections have the same type and language. Give each section a distinct type.'))));
        }
        $seen[$key] = true;
      }
    }

    // Save the song row. In sections mode the Lyrics blob and legacy Pattern are
    // left untouched — they are not the source of truth for a converted song.
    if (!empty($_POST['sid'])) {
      $sid = intval($_POST['sid']);
      $set = "Title='".h2d(str_replace("\n"," ",jtrim($_POST['title'])))."',".
        "OrigTitle='".h2d(str_replace("\n"," ",jtrim($_POST['origtitle'])))."',".
        "Composer='".h2d(str_replace("\n"," ",jtrim($_POST['composer'])))."',".
        "Copyright='".h2d(str_replace("\n"," ",jtrim($_POST['copyright'])))."',".
        "SongKey='".h2d(jtrim($_POST['songkey']))."',".
        "Tempo='".h2d(jtrim($_POST['tempo']))."',".
        "Source='".h2d(jtrim($_POST['source']))."',".
        "Instruction='".h2d(jtrim($_POST['instruction']))."',".
        "AudioComment='".h2d(str_replace("\n"," ",jtrim($_POST['audiocomment'])))."',".
        "NeedsWork=$needswork";
      if (!$has_sections) {
        $set .= ",Lyrics='".h2d(str_replace(chr(0x2019),"'",rtrim($_POST['lyrics']," \n\r\t\v\x00　")))."',".
                "Pattern='".h2d(str_replace("\n"," ",jtrim($_POST['pattern'])))."'";
      }
      sqlquery_checked("UPDATE song SET $set WHERE SongID=$sid LIMIT 1");
    } else {
      sqlquery_checked("INSERT INTO song (Title,OrigTitle,Composer,Copyright,SongKey,Tempo,Source,Lyrics,Pattern,Instruction,AudioComment,NeedsWork) VALUES (".
        "'".h2d(str_replace("\n"," ",jtrim($_POST['title'])))."',".
        "'".h2d(str_replace("\n"," ",jtrim($_POST['origtitle'])))."',".
        "'".h2d(str_replace("\n"," ",jtrim($_POST['composer'])))."',".
        "'".h2d(str_replace("\n"," ",jtrim($_POST['copyright'])))."',".
        "'".h2d(jtrim($_POST['songkey']))."',".
        "'".h2d(jtrim($_POST['tempo']))."',".
        "'".h2d(jtrim($_POST['source']))."',".
        "'".h2d(str_replace(chr(0x2019),"'",jtrim($_POST['lyrics'] ?? '')))."',".
        "'".h2d(str_replace("\n"," ",jtrim($_POST['pattern'] ?? '')))."',".
        "'".h2d(jtrim($_POST['instruction']))."',".
        "'".h2d(str_replace("\n"," ",jtrim($_POST['audiocomment'])))."',".
        "$needswork)");
      if (mysqli_affected_rows($db) < 1) {
        die(json_encode(array('success' => false, 'error' => _('The song was not added for some reason.'))));
      }
      $sid = mysqli_insert_id($db);
    }

    // Reconcile the section pool (metadata already saved + validated above).
    if ($has_sections) {
      // Deletes first (frees their type/language and lets a kept section take it).
      $deleted = array();
      foreach ($rows as $r) {
        $id = intval($r['id'] ?? 0);
        if (!empty($r['delete']) && $id > 0) {
          sqlquery_checked("DELETE FROM section WHERE SectionID=$id AND SongID=$sid LIMIT 1");
          $deleted[] = $id;
        }
      }

      // Existing kept sections: park each at a unique temp type first, so a type swap
      // (A:v1->v2, B:v2->v1) can't trip the UNIQUE key mid-update.
      $keep = array();
      foreach ($rows as $r) {
        $id = intval($r['id'] ?? 0);
        if (empty($r['delete']) && $id > 0 && trim($r['lyrics'] ?? '') !== '') $keep[$id] = $r;
      }
      foreach ($keep as $id => $r) {
        sqlquery_checked("UPDATE section SET SectionTypeID='~$id' WHERE SectionID=$id AND SongID=$sid LIMIT 1");
      }
      foreach ($keep as $id => $r) {
        $type  = h2d($r['type'] ?? '');
        $lang  = h2d($r['lang'] ?? '');
        $lyr   = h2d(rtrim(str_replace(array("\r\n", "\r"), "\n", $r['lyrics'] ?? '')));
        sqlquery_checked("UPDATE section SET SectionTypeID='$type',Language='$lang',Lyrics='$lyr' ".
                         "WHERE SectionID=$id AND SongID=$sid LIMIT 1");
      }

      // Map each kept row's form index -> real SectionID (existing rows keep their
      // id; new rows get theirs as we INSERT). A split child references its parent
      // by form index, so patterns can be expanded once the new id exists.
      $idx_to_id = array();
      foreach ($rows as $idx => $r) {
        if (empty($r['delete']) && intval($r['id'] ?? 0) > 0 && trim($r['lyrics'] ?? '') !== '')
          $idx_to_id[$idx] = intval($r['id']);
      }

      // New sections, in form order (a split's parent row always precedes its
      // child), recording new ids and any split relationship.
      $splits = array();   // [parent form index, new id], in order
      foreach ($rows as $idx => $r) {
        if (intval($r['id'] ?? 0) == 0 && empty($r['delete']) && trim($r['lyrics'] ?? '') !== '') {
          $type  = h2d($r['type'] ?? '');
          $lang  = h2d($r['lang'] ?? '');
          $lyr   = h2d(rtrim(str_replace(array("\r\n", "\r"), "\n", $r['lyrics'] ?? '')));
          sqlquery_checked("INSERT INTO section (SongID,SectionTypeID,Language,Lyrics) ".
                           "VALUES ($sid,'$type','$lang','$lyr')");
          $newid = mysqli_insert_id($db);
          $idx_to_id[$idx] = $newid;
          $sp = (string)($r['split_parent'] ?? '');
          if ($sp !== '') $splits[] = array($sp, $newid);
        }
      }

      // Rewrite this song's arrangements: expand each split parent into
      // parent,child (repeats preserved), then drop any deleted section ids.
      if ($splits || $deleted) {
        $pres = sqlquery_checked("SELECT PatternID, Sections FROM pattern WHERE SongID=$sid");
        $patterns = array();
        while ($p = mysqli_fetch_object($pres)) $patterns[(int)$p->PatternID] = $p->Sections;
        foreach ($patterns as $pid => $orig) {
          $ids = array_map('intval', array_filter(explode(',', $orig), 'strlen'));
          foreach ($splits as $sp) {
            $parent_id = $idx_to_id[$sp[0]] ?? 0;
            if ($parent_id < 1) continue;   // parent never saved / not in any pattern
            $expanded = array();
            foreach ($ids as $x) {
              $expanded[] = $x;
              if ($x === $parent_id) $expanded[] = $sp[1];
            }
            $ids = $expanded;
          }
          if ($deleted)
            $ids = array_values(array_filter($ids, function($x) use ($deleted) { return !in_array($x, $deleted, true); }));
          $newsec = implode(',', $ids);
          if ($newsec !== $orig)
            sqlquery_checked("UPDATE pattern SET Sections='".h2d($newsec)."' WHERE PatternID=".intval($pid)." LIMIT 1");
        }
      }
    }

    if (is_uploaded_file($_FILES['audiofile']['tmp_name'] ?? '')) {
      if (move_uploaded_file($_FILES['audiofile']['tmp_name'], CLIENT_PATH."/audio/s".$sid.".mp3")) {
        sqlquery_checked("UPDATE song SET Audio=1 WHERE SongID=$sid LIMIT 1");
      } else {
        die(json_encode(array('success' => false, 'error' => _('File upload failed.'))));
      }
    }
    die(json_encode(array('success' => true, 'sid' => $sid)));
    break;

  case 'PatternSave':
    if ($_SESSION['access'] < 1) {
      die(json_encode(array('success' => false, 'error' => _('Access denied.'))));
    }
    $sid = intval($_POST['sid'] ?? 0);
    if ($sid < 1) {
      die(json_encode(array('success' => false, 'error' => _('Invalid song ID.'))));
    }
    // Keep only section ids that belong to this song; order and repeats preserved.
    $valid = array();
    $vres = sqlquery_checked("SELECT SectionID FROM section WHERE SongID=$sid");
    while ($vr = mysqli_fetch_object($vres)) $valid[(int)$vr->SectionID] = true;
    $ids = array();
    foreach (explode(',', $_POST['sections'] ?? '') as $x) {
      $x = (int)$x;
      if ($x > 0 && isset($valid[$x])) $ids[] = $x;
    }
    if (!$ids) {
      die(json_encode(array('success' => false, 'error' => _('An arrangement needs at least one section.'))));
    }
    $seq       = implode(',', $ids);
    $label     = trim($_POST['label'] ?? '');
    if ($label === '') $label = _('Untitled');
    $isdefault = !empty($_POST['isdefault']) ? 1 : 0;
    if ($isdefault) sqlquery_checked("UPDATE pattern SET IsDefault=0 WHERE SongID=$sid");
    if (($_POST['patternid'] ?? '') === 'new') {
      sqlquery_checked("INSERT INTO pattern (SongID,Label,IsDefault,Sections) VALUES ".
                       "($sid,'".h2d($label)."',$isdefault,'".h2d($seq)."')");
      $pid = mysqli_insert_id($db);
    } else {
      $pid = intval($_POST['patternid']);
      sqlquery_checked("UPDATE pattern SET Label='".h2d($label)."',IsDefault=$isdefault,Sections='".h2d($seq)."' ".
                       "WHERE PatternID=$pid AND SongID=$sid LIMIT 1");
    }
    die(json_encode(array('success' => true,
      'pattern' => array('id' => $pid, 'label' => $label, 'is_default' => (bool)$isdefault, 'sections' => $ids))));
    break;

  case 'PatternDelete':
    if ($_SESSION['access'] < 1) {
      die(json_encode(array('success' => false, 'error' => _('Access denied.'))));
    }
    $sid = intval($_POST['sid'] ?? 0);
    $pid = intval($_POST['patternid'] ?? 0);
    if ($sid < 1 || $pid < 1) {
      die(json_encode(array('success' => false, 'error' => _('Invalid request.'))));
    }
    $wasDefault = (int) sql_single("SELECT IsDefault FROM pattern WHERE PatternID=$pid AND SongID=$sid");
    sqlquery_checked("DELETE FROM pattern WHERE PatternID=$pid AND SongID=$sid LIMIT 1");
    // If the default was deleted, promote another so section display order stays defined.
    $newDefault = 0;
    if ($wasDefault) {
      $nd = sql_single("SELECT PatternID FROM pattern WHERE SongID=$sid ORDER BY PatternID LIMIT 1");
      if ($nd) {
        $newDefault = (int)$nd;
        sqlquery_checked("UPDATE pattern SET IsDefault=1 WHERE PatternID=$newDefault LIMIT 1");
      }
    }
    die(json_encode(array('success' => true, 'newDefault' => $newDefault)));
    break;

  default:
    die(json_encode(array('alert' => 'Programming error: NO ACTION RECOGNIZED')));
}
?>
