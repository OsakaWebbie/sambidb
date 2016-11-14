<?php
include("functions.php");
include("accesscontrol.php");
print_header("Maintenance Processing","#FFFFFF",0);

if ($kw_add_upd) {
  if ($kw_select == "new") {
    $sql = "INSERT INTO pw_keyword (Keyword) VALUES ('$keyword')";
    if (!$result = mysql_query($sql)) {
      echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
      exit;
    }
    $message = "Keyword successfully added.";

  } else {
    $sql = "UPDATE pw_keyword SET Keyword='$keyword' WHERE KeywordID=$kw_select";
    if (!$result = mysql_query($sql)) {
      echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
      exit;
    }
    $message = "Keyword successfully renamed.";
  }
  
} elseif ($kw_del) {

  // if first time around, check for pw_songkey records - if none, don't need confirmation
  if (!$confirmed) {
    $sql = "SELECT Title FROM pw_songkey LEFT JOIN pw_song ON pw_songkey.SongID=pw_song.SongID ".
    "WHERE KeywordID=$kw_select ORDER BY Title";
    if (!$result = mysql_query($sql)) {
      echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
      exit;
    }
    if (mysql_num_rows($result) == 0) {
      $confirmed = "yes";
    }
  }
  if ($confirmed) {
    $sql = "DELETE FROM pw_songkey WHERE KeywordID=$kw_select";
    if (!$result = mysql_query($sql)) {
      echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
      exit;
    }
    if (mysql_affected_rows() > 0) {
      $message = mysql_affected_rows()." songs removed from keyword.\\n";
    }
    $sql = "DELETE FROM pw_keyword WHERE KeywordID=$kw_select LIMIT 1";
    if (!$result = mysql_query($sql)) {
      echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
      exit;
    }
    if (mysql_affected_rows() == 1) {
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
    while ($row = mysql_fetch_object($result)) {
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
    $sql = "INSERT INTO pw_event (Event,Active,Remarks) VALUES ('$event','$active','$remarks')";
    if (!$result = mysql_query($sql)) {
      echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
      exit;
    }
    $message = "New event successfully added.";
  } else {
    $sql = "UPDATE pw_event SET Active=$active,Event='$event',".
      "Remarks='$remarks' WHERE EventID=$event_id";
    if (!$result = mysql_query($sql)) {
      echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
      exit;
    }
    $message = "Event information successfully updated.";
  }

} elseif ($event_del) {

  // if first time around, check for usage records - if none, don't need confirmation
  if (!$confirmed) {
    $sql = "SELECT count(UseDate) AS num, min(UseDate) AS first, max(UseDate) AS last ".
    "FROM pw_usage WHERE EventID=$event_id";
    if (!$result = mysql_query($sql)) {
      echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
      exit;
    }
    $row = mysql_fetch_object($result);
    if ($row->num == 0) {
      $confirmed = "yes";
    } else {
      $use_num = $row->num;
      $use_first = $row->first;
      $use_last = $row->last;
    }
  }
  if ($confirmed) {
    $sql = "DELETE FROM pw_usage WHERE EventID=$event_id";
    if (!$result = mysql_query($sql)) {
      echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
      exit;
    }
    if (mysql_affected_rows() > 0) {
      $message = mysql_affected_rows()." related usage records deleted.\\n";
    }
    $sql = "DELETE FROM pw_event WHERE EventID=$event_id LIMIT 1";
    if (!$result = mysql_query($sql)) {
      echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
      exit;
    }
    if (mysql_affected_rows() == 1) {
      $message = $message."Event record successfully deleted.";
    }
  } else {
  //ask for confirmation
    echo <<<ECHOEND
<h3><font color=red>Please Confirm Event Delete</font></h3>
There are $use_num usage records for this event, during the time period $use_first
thru $use_last.  In deleting the event, you will also delete all usage data associated
with it.  Are you sure you want to do this?  (If not, just press your browser's Back button.)
<form action=$PHP_SELF method=post>
  <input type=hidden name=event_id value="$event_id">
  <input type=hidden name=event_del value="$event_del">
  <input type=hidden name=confirmed value="yes">
  <input type=submit value="Yes, delete the event and usage records">
</form>
ECHOEND;
    $need_confirmation = 1;
  }

} elseif ($pw_upd) {
  $sql = "SELECT * FROM pw_login WHERE UserID = '".$_SESSION['pw_userid']."' AND Password = OLD_PASSWORD('$old_pw')";
  if (!$result = mysql_query($sql)) {
    echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
    exit;
  }
  if (mysql_num_rows($result) == 0) {
    $message = "Sorry, but your old password entry was incorrect, so the password was not changed.";
  } elseif ($new_pw1 != $new_pw2) {
    $message = "Sorry, but the two entries for the new password did not match.  Password not changed.";
  } else {
    $sql = "UPDATE pw_login set Password = OLD_PASSWORD('$new_pw1') WHERE UserID = '".$_SESSION['pw_userid']."'"; 
    if (!$result = mysql_query($sql)) {
      echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
      exit;
    }
    if (mysql_affected_rows() == 1) {
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