<?php
include("functions.php");
include("accesscontrol.php");
print_header("Maintenance Processing","#FFFFFF",0);

if ($kw_add_upd) {
  if ($kw_select == "new") {
    $sql = "INSERT INTO keyword (Keyword) VALUES ('$keyword')";
    if (!$result = mysqli_query($db,$sql)) {
      echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
      exit;
    }
    $message = "Keyword successfully added.";

  } else {
    $sql = "UPDATE keyword SET Keyword='$keyword' WHERE KeywordID=$kw_select";
    if (!$result = mysqli_query($db,$sql)) {
      echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
      exit;
    }
    $message = "Keyword successfully renamed.";
  }
  
} elseif ($kw_del) {

  // if first time around, check for songkey records - if none, don't need confirmation
  if (!$confirmed) {
    $sql = "SELECT Title FROM songkey LEFT JOIN song ON songkey.SongID=song.SongID ".
    "WHERE KeywordID=$kw_select ORDER BY Title";
    if (!$result = mysqli_query($db,$sql)) {
      echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
      exit;
    }
    if (mysqli_num_rows($result) == 0) {
      $confirmed = "yes";
    }
  }
  if ($confirmed) {
    $sql = "DELETE FROM songkey WHERE KeywordID=$kw_select";
    if (!$result = mysqli_query($db,$sql)) {
      echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
      exit;
    }
    if (mysqli_affected_rows($db) > 0) {
      $message = mysqli_affected_rows($db)." songs removed from keyword.\\n";
    }
    $sql = "DELETE FROM keyword WHERE KeywordID=$kw_select LIMIT 1";
    if (!$result = mysqli_query($db,$sql)) {
      echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
      exit;
    }
    if (mysqli_affected_rows($db) == 1) {
      $message = $message."Keyword successfully deleted.";
    }
  } else {
  //already did query for the keyword's members - now tell the user and ask for confirmation
    echo <<<ECHOEND
<h3><font color=red>Please Confirm Keyword Delete</font></h3>
The following songs are still associated with the $keyword keyword.&nbsp;
 If you are sure you want to delete these keyword associations, click the button.&nbsp;
 (If not, just press your browser's Back button.)
<form action=$PHP_SELF method=post>
  <input type=hidden name=kw_select value="$kw_select">
  <input type=hidden name=kw_del value="$kw_del">
  <input type=hidden name=confirmed value="yes">
  <input type=submit value="Yes, delete the keyword">
</form>
Songs with this keyword:<br><font size=2>
ECHOEND;
    while ($row = mysqli_fetch_object($result)) {
      echo "&nbsp;&nbsp;&nbsp;".$row->Title."<br>\n";
    }
    echo "</font>";
    $need_confirmation = 1;
  }
  
} elseif ($event_add_upd) {

  if ($active) {
    $active = "1";
  } else {
    $active = "0";
  }
  if ($event_list == "new") {
    $sql = "INSERT INTO event (Event,Active,Remarks) VALUES ('$event','$active','$remarks')";
    if (!$result = mysqli_query($db,$sql)) {
      echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
      exit;
    }
    $message = "New event successfully added.";
  } else {
    $sql = "UPDATE event SET Active=$active,Event='$event',".
      "Remarks='$remarks' WHERE EventID=$event_id";
    if (!$result = mysqli_query($db,$sql)) {
      echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
      exit;
    }
    $message = "Event information successfully updated.";
  }

} elseif ($event_del) {

  // if first time around, check for history records - if none, don't need confirmation
  if (!$confirmed) {
    $sql = "SELECT count(UseDate) AS num, min(UseDate) AS first, max(UseDate) AS last ".
    "FROM history WHERE EventID=$event_id";
    if (!$result = mysqli_query($db,$sql)) {
      echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
      exit;
    }
    $row = mysqli_fetch_object($result);
    if ($row->num == 0) {
      $confirmed = "yes";
    } else {
      $use_num = $row->num;
      $use_first = $row->first;
      $use_last = $row->last;
    }
  }
  if ($confirmed) {
    $sql = "DELETE FROM history WHERE EventID=$event_id";
    if (!$result = mysqli_query($db,$sql)) {
      echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
      exit;
    }
    if (mysqli_affected_rows($db) > 0) {
      $message = mysqli_affected_rows($db)." related history records deleted.\\n";
    }
    $sql = "DELETE FROM event WHERE EventID=$event_id LIMIT 1";
    if (!$result = mysqli_query($db,$sql)) {
      echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
      exit;
    }
    if (mysqli_affected_rows($db) == 1) {
      $message = $message."Event record successfully deleted.";
    }
  } else {
  //ask for confirmation
    echo <<<ECHOEND
<h3><font color=red>Please Confirm Event Delete</font></h3>
There are $use_num history records for this event, during the time period $use_first
thru $use_last.  In deleting the event, you will also delete all history data associated
with it.  Are you sure you want to do this?  (If not, just press your browser's Back button.)
<form action=$PHP_SELF method=post>
  <input type=hidden name=event_id value="$event_id">
  <input type=hidden name=event_del value="$event_del">
  <input type=hidden name=confirmed value="yes">
  <input type=submit value="Yes, delete the event and history records">
</form>
ECHOEND;
    $need_confirmation = 1;
  }

} elseif ($upd) {
  $sql = "SELECT * FROM login WHERE UserID = '".$_SESSION['userid']."' AND Password = OLD_PASSWORD('$old_pw')";
  if (!$result = mysqli_query($db,$sql)) {
    echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
    exit;
  }
  if (mysqli_num_rows($result) == 0) {
    $message = "Sorry, but your old password entry was incorrect, so the password was not changed.";
  } elseif ($new_pw1 != $new_pw2) {
    $message = "Sorry, but the two entries for the new password did not match.  Password not changed.";
  } else {
    $sql = "UPDATE login set Password = OLD_PASSWORD('$new_pw1') WHERE UserID = '".$_SESSION['userid']."'";
    if (!$result = mysqli_query($db,$sql)) {
      echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
      exit;
    }
    if (mysqli_affected_rows($db) == 1) {
      $message = "Password successfully changed.";
    }
  }
}

if (!$need_confirmation) {
  echo "<SCRIPT FOR=window EVENT=onload LANGUAGE=\"Javascript\">\n";
  if ($message) {
    echo "alert(\"".$message."\");\n";
  }
  echo "window.location = \"maintenance.php\";\n";
  echo "</SCRIPT>\n";
}

print_footer();
?>