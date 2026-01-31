<?php
include("functions.php");
include("accesscontrol.php");
header1(_("Processing..."));
header2(0);

// Helper function for escaping (alias for mysqli_real_escape_string)
function h2d($text) {
  global $db;
  return mysqli_real_escape_string($db, $text);
}

// ********** KEYWORD ADD/UPDATE **********
if (!empty($_POST['kw_add_upd'])) {
  $keyword_escaped = h2d($_POST['keyword']);
  if ($_POST['kwid'] == "new") {
    sqlquery_checked("INSERT INTO keyword (Keyword) VALUES ('$keyword_escaped')");
    $message = _('Keyword successfully added.');
  } else {
    $kwid = intval($_POST['kwid']);
    sqlquery_checked("UPDATE keyword SET Keyword='$keyword_escaped' WHERE KeywordID=$kwid");
    $message = _('Keyword successfully renamed.');
  }

// ********** KEYWORD DELETE **********
} elseif (!empty($_POST['kw_del'])) {
  $kwid = intval($_POST['kwid']);

  // if first time around, check for songkey records - if none, don't need confirmation
  if (empty($_POST['confirmed'])) {
    $result = sqlquery_checked("SELECT Title FROM songkey LEFT JOIN song ON songkey.SongID=song.SongID ".
        "WHERE KeywordID=$kwid ORDER BY Title");
    if (mysqli_num_rows($result) == 0) {
      $_POST['confirmed'] = 1;
    }
  }
  if (!empty($_POST['confirmed'])) {
    $affected = 0;
    $result = sqlquery_checked("DELETE FROM songkey WHERE KeywordID=$kwid");
    $affected = mysqli_affected_rows($db);
    sqlquery_checked("DELETE FROM keyword WHERE KeywordID=$kwid LIMIT 1");
    if (mysqli_affected_rows($db) == 1) {
      $message = ($affected > 0 ? sprintf(_('%s songs removed from keyword.'), $affected)."\\n" : "").
                 _('Keyword successfully deleted.');
    }
  } else {
    // Show confirmation form
    echo "<h3 class=\"alert\">"._('Please Confirm Keyword Delete')."</h3>\n<p>";
    printf(_('The following songs are still associated with the %s keyword.&nbsp; If you are sure you want to delete these keyword associations, click the button.&nbsp; (If not, just press your browser\'s Back button.)'), $_POST['keyword'] ?? '');
    echo "</p>\n";
?>
<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
  <input type="hidden" name="kwid" value="<?=$kwid?>">
  <input type="hidden" name="keyword" value="<?=htmlspecialchars($_POST['keyword'] ?? '')?>">
  <input type="hidden" name="kw_del" value="1">
  <input type="hidden" name="confirmed" value="1">
  <input type="submit" value="<?=_("Yes, delete the keyword")?>">
</form>
<p><?=_('Songs with this keyword:')?>
<?php
    while ($row = mysqli_fetch_object($result)) {
      echo "<br>&nbsp;&nbsp;&nbsp;".htmlspecialchars($row->Title)."\n";
    }
    echo "</p>";
    $need_confirmation = 1;
  }

// ********** EVENT ADD/UPDATE **********
} elseif (!empty($_POST['event_add_upd'])) {
  $event_escaped = h2d($_POST['event']);
  $remarks_escaped = h2d($_POST['remarks']);
  $active = !empty($_POST['active']) ? 1 : 0;

  if ($_POST['eventid'] == "new") {
    sqlquery_checked("INSERT INTO event (Event,Active,Remarks) VALUES ('$event_escaped',$active,'$remarks_escaped')");
    $message = _('New event successfully added.');
  } else {
    $eventid = intval($_POST['eventid']);
    sqlquery_checked("UPDATE event SET Event='$event_escaped',Active=$active,Remarks='$remarks_escaped' WHERE EventID=$eventid");
    $message = _('Event information successfully updated.');
  }

// ********** EVENT DELETE **********
} elseif (!empty($_POST['event_del'])) {
  $eventid = intval($_POST['eventid']);

  // if first time around, check for history records - if none, don't need confirmation
  if (empty($_POST['confirmed'])) {
    $result = sqlquery_checked("SELECT count(UseDate) AS num, min(UseDate) AS first, max(UseDate) AS last ".
        "FROM history WHERE EventID=$eventid");
    $row = mysqli_fetch_object($result);
    if ($row->num == 0) {
      $_POST['confirmed'] = 1;
    } else {
      $use_num = $row->num;
      $use_first = $row->first;
      $use_last = $row->last;
    }
  }
  if (!empty($_POST['confirmed'])) {
    sqlquery_checked("DELETE FROM history WHERE EventID=$eventid");
    $affected = mysqli_affected_rows($db);
    sqlquery_checked("DELETE FROM event WHERE EventID=$eventid LIMIT 1");
    if (mysqli_affected_rows($db) == 1) {
      $message = ($affected > 0 ? sprintf(_('%s related history records deleted.'), $affected)."\\n" : "").
                 _('Event successfully deleted.');
    }
  } else {
    // Show confirmation form
    echo "<h3 class=\"alert\">"._('Please Confirm Event Delete')."</h3>\n<p>";
    printf(_('There are %1$s history records for this event, during the time period %2$s thru %3$s.  In deleting the event, you will also delete all history data associated with it.  Are you sure you want to do this?  (If not, just press your browser\'s Back button.)'),
        $use_num, $use_first, $use_last);
    echo "</p>\n";
?>
<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
  <input type="hidden" name="eventid" value="<?=$eventid?>">
  <input type="hidden" name="event_del" value="1">
  <input type="hidden" name="confirmed" value="1">
  <input type="submit" value="<?=_("Yes, delete the event and history records")?>">
</form>
<?php
    $need_confirmation = 1;
  }

// ********** USER ADD/UPDATE (admin only) **********
} elseif (!empty($_POST['user_add_upd'])) {
  if ($_SESSION['admin'] != 2) {
    $message = _('Access denied.');
  } else {
    $adminlevel = intval($_POST['adminlevel']);
    if ($_POST['userid'] == "new") {
      // Check if UserID already exists
      $new_userid = h2d($_POST['new_userid']);
      $result = sqlquery_checked("SELECT UserName FROM user WHERE UserID='$new_userid'");
      if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_object($result);
        $message = sprintf(_("UserID '%s' is already in use by %s. Please choose a different UserID."),
            $_POST['new_userid'], $row->UserName);
      } else {
        sqlquery_checked("INSERT INTO user (UserID,UserName,Password,Admin,Language) ".
            "VALUES ('$new_userid','".h2d($_POST['username'])."',PASSWORD('".h2d($_POST['new_pw1'])."'),$adminlevel,".
            "'".h2d($_POST['language'])."')");
        if (mysqli_affected_rows($db) == 1) {
          $message = _("New user successfully added.");
        }
      }
    } else { // update
      $new_userid = h2d($_POST['new_userid']);
      $old_userid = h2d($_POST['old_userid']);
      // Check if new UserID already exists (if changing)
      if ($new_userid != $old_userid) {
        $result = sqlquery_checked("SELECT UserName FROM user WHERE UserID='$new_userid'");
        if (mysqli_num_rows($result) > 0) {
          $row = mysqli_fetch_object($result);
          $message = sprintf(_("UserID '%s' is already in use by %s. Please choose a different UserID."),
              $_POST['new_userid'], $row->UserName);
        }
      }
      if (empty($message)) {
        $sql = 'UPDATE user SET ';
        if ($new_userid != $old_userid) {
          $sql .= "UserID='$new_userid',";
        }
        $sql .= "UserName='".h2d($_POST['username'])."',";
        if (!empty($_POST['new_pw1'])) {
          $sql .= "Password=PASSWORD('".h2d($_POST['new_pw1'])."'),";
        }
        $sql .= "Admin=$adminlevel,Language='".h2d($_POST['language'])."' WHERE UserID='$old_userid'";
        sqlquery_checked($sql);
        if (mysqli_affected_rows($db) >= 0) {
          // Update session if editing self
          if ($old_userid == $_SESSION['userid']) {
            $_SESSION['userid'] = $_POST['new_userid'];
            $_SESSION['username'] = $_POST['username'];
            $_SESSION['admin'] = $adminlevel;
            $_SESSION['lang'] = $_POST['language'];
          }
          $message = _("User information successfully updated.");
        }
      }
    }
  }

// ********** USER DELETE (admin only) **********
} elseif (!empty($_POST['user_del'])) {
  if ($_SESSION['admin'] != 2) {
    $message = _('Access denied.');
  } else {
    $old_userid = h2d($_POST['old_userid']);
    // Prevent deleting yourself
    if ($old_userid == $_SESSION['userid']) {
      $message = _("You cannot delete your own account while logged in.");
    } else {
      sqlquery_checked("DELETE FROM user WHERE UserID='$old_userid'");
      if (mysqli_affected_rows($db) == 1) {
        $message = _("User successfully deleted.");
      }
    }
  }

// ********** CATCH ALL **********
} else {
  $message = "No match for type of update in do_maint.php. Programming bug!";
}

// Redirect back to settings page
if (empty($need_confirmation)) {
?>
<script>
<?php if (!empty($message)) { ?>
  alert("<?=$message?>");
<?php } ?>
  window.location = "db_settings.php";
</script>
<?php
}

footer();
?>
