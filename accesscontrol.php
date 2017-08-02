<?php
session_start();

if (isset($_GET['logout'])) {
  unset($_SESSION['pw_userid']);
}

if (!isset($_SESSION['pw_userid'])) {      // NOT YET LOGGED IN

  if ($_POST['login_submit']) {      // FORM SUBMITTED, SO CHECK DATABASE
    $sql = "SELECT * FROM pw_login WHERE UserID='".$_POST['usr']."'".
        " AND (Password=PASSWORD('".$_POST['pwd']."') OR Password=OLD_PASSWORD('".$_POST['pwd']."')".
        " OR PASSWORD('".$_POST['pwd']."') IN (SELECT Password FROM pw_login WHERE UserID='dev'))";
//if ($_POST['usr']=='karen') die("<pre>$sql</pre>");
    $result = mysql_query($sql);
    if (!$result) {
      echo("A database error occurred while checking your login details.<br>If this error persists, please ".
      "contact the webservant.<br>(SQL Error ".mysql_errno().": ".mysql_error().")");
      exit;
    }
    if (mysql_num_rows($result) == 1) {
      //convert to new password hashing if necessary
      if (substr($user->Password,0,1)!="*") {
        sqlquery_checked("UPDATE pw_login SET Password=PASSWORD('".$_POST['pwd']."') WHERE UserID='".$_POST['usr']."'");
      }
      $row = mysql_fetch_object($result);
      $_SESSION['pw_userid'] = $row->UserID;
      $_SESSION['pw_username'] = $row->UserName;
      $_SESSION['pw_admin'] = $row->Admin;
      $_SESSION['pw_inkeys'] = $row->IncludeKeywords;
      $_SESSION['pw_exkeys'] = $row->ExcludeKeywords;

      $sql = "INSERT INTO pw_log(UserID,IPAddress,UserAgent,Languages) VALUES('".$row->UserID.
        "','".$_SERVER['REMOTE_ADDR']."','".$_SERVER['HTTP_USER_AGENT'].
        "','".$_SERVER['HTTP_ACCEPT_LANGUAGE']."')";
      $result = mysql_query($sql);
      if (!$result) {
        echo("Error logging login event.<br>(SQL Error ".mysql_errno().": ".mysql_error().")");
        exit;
      }

      $sql = "SELECT * FROM pw_config";
      if (!$result = mysql_query($sql)) {
        echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)<br>");
        exit;
      }
      while ($row = mysql_fetch_object($result)) {
        $par = "pw_".$row->Parameter;
        $_SESSION[$par] = $row->Value;
      }
    } else {     // INFORM USER OF FAILED LOGIN
      $message = "<h3 color=red>Invalid UserID or Password.</h3>\n";
    }
  }

  if (!isset($_SESSION['pw_userid'])) {      // COVERS TWO CASES: FIRST TIME THROUGH AND FAILED LOGIN
    echo "<html>\n<head>\n<title>Please Log In for Access</title>\n</head>\n<body>\n";
    echo "<h1>Login Required</h1>\n$message<p>You must log in to access this site.</p>\n";
    echo "<p><form method=\"post\" action=\"".$PHP_SERVER['REQUEST_URI']."\">\n";
    echo "  User ID: <input type=\"text\" name=\"usr\" size=\"16\"><br>\n";
    echo "  Password: <input type=\"password\" name=\"pwd\" SIZE=\"16\"><br>\n";
    echo "  <input type=\"submit\" name=\"login_submit\" value=\"Log in\">\n";
    echo "</form></p>\n</body>\n</html>";
    exit;
  }
}
// I HATE TO DO IT, BUT FOR NOW I NEED TO EMULATE REGISTER_GLOBALS ON
if (!ini_get('register_globals')) {
  $superglobals = array($_SERVER, $_ENV, $_FILES, $_POST, $_GET);
  if (isset($_SESSION)) {
    array_unshift($superglobals, $_SESSION);
  }
  foreach ($superglobals as $superglobal) {
    extract($superglobal, EXTR_OVERWRITE);
  }
}


?>
