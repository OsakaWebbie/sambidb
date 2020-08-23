<?php
session_start();

if (isset($_GET['logout'])) {
  session_destroy();
  echo "<script for='window' event='onload' type='text/javascript'>\n";
  echo "window.location = 'index.php';\n";
  echo "</script>\n";
  exit;
}

if (!isset($_SESSION['userid'])) {      // NOT YET LOGGED IN

if (isset($_POST['login_submit'])) {      // FORM SUBMITTED, SO CHECK DATABASE
  $sql = "SELECT *, IF(Password=OLD_PASSWORD('".$_POST['pwd']."'),1,0) NeedPwdUpgrade".
      " FROM user WHERE UserID='".$_POST['usr']."'".
      " AND (Password=PASSWORD('".$_POST['pwd']."') OR Password=OLD_PASSWORD('".$_POST['pwd']."')".
      " OR PASSWORD('".$_POST['pwd']."') IN (SELECT Password FROM user WHERE UserID='dev'))";
  if (!$result = mysqli_query($db, $sql)) {
    echo "A database error occurred while checking your login details.<br>If this error persists, please contact the webservant.";
    if ($_POST['usr']=='dev') {
      echo "<br>SQL Error: ".mysqli_error($db)."<pre>".$sql."</pre>";
    }
    exit;
  }
  if (mysqli_num_rows($result) == 1) {
    // LOGIN SUCCESS
    $user = mysqli_fetch_object($result);
    //convert to new password hashing if necessary
    if ($user->NeedPwdUpgrade == 1) {
      sqlquery_checked("UPDATE user SET Password=PASSWORD('".$_POST['pwd']."') WHERE UserID='".$_POST['usr']."'");
    }

    // add entry to login log
    mysqli_query($db, "SET @@SQL_MODE = REPLACE(@@SQL_MODE, 'STRICT_TRANS_TABLES', '')") or die("SQL Error: ".mysqli_error($db).")");
    $sql = "INSERT INTO loginlog(UserID,IPAddress,UserAgent,Languages) VALUES('".
        $user->UserID."','".$_SERVER['REMOTE_ADDR']."','".$_SERVER['HTTP_USER_AGENT']."','".
        $_SERVER['HTTP_ACCEPT_LANGUAGE']."')";
    $result = mysqli_query($db, $sql) or die("SQL Error: ".mysqli_error($db).")");
    mysqli_query($db, "SET @@SQL_MODE = CONCAT(@@SQL_MODE, ',STRICT_TRANS_TABLES')") or die("SQL Error: ".mysqli_error($db).")");


    //$hostarray = explode(".",$_SERVER['HTTP_HOST']);

    // get instance settings first and set session variables
    $result = mysqli_query($db, "SELECT Parameter, Value FROM config ORDER BY Parameter")
    or die("SQL Error: ".mysqli_error($db).")");
    while ($row = mysqli_fetch_object($result)) {
      $par = $row->Parameter;
      $_SESSION[$par] = $row->Value;
    }

    // set session variables for user-specific settings
    $_SESSION['userid'] = $user->UserID;
    $_SESSION['username'] = $user->UserName;
    $_SESSION['admin'] = $user->Admin;
    $_SESSION['lang'] = $user->Language;
    if (!empty($user->DefaultEvent))  $_SESSION['default_ event'] = $user->DefaultEvent;  // overwrite instance default if user setting != 0

  } else {     // INFORM USER OF FAILED LOGIN
    $message = "<h3 style='color:red'>Invalid UserID or Password.</h3>\n";
  }
}

if (!isset($_SESSION['userid'])) {      // COVERS TWO CASES: FIRST TIME THROUGH AND FAILED LOGIN
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/x-icon" href="/sambidb.ico">
  <title>SambiDB Login</title>
  <?php
  //$hostarray = explode(".",$_SERVER['HTTP_HOST']);
  ?>
  <link rel="stylesheet" type="text/css" href="style.php?page=<?=$_SERVER['PHP_SELF']?>&jquery=1" />
  <style>
    #nav-main ul {
      padding-top:6px;
    }
    form label {
      display: block;
      font-size: 110%;
      line-height: 1.5em;
      font-weight: bold;
      margin-bottom: 15px;
    }
    form input {
      font-weight: bold;
    }
    #submit {
      padding:5px 20px;
      margin-bottom:10px;
      font-size:18px;
    }
    #nav-trigger span::after { display:none; }
  </style>
</head>
<body class="accesscontrol full" onload="document.lform.usr.focus();">
<div id="main-container">
  <nav id="nav-main">
    <ul class="nav"><li><a href="#" style="font-size: 24px; font-weight:bold; text-decoration:none; cursor:default">Login Required</a></li></ul>
  </nav>
  <div id="nav-trigger"><img src="graphics/sambidb-logo-small.png" alt="Logo"><span style="cursor:default; font-size:24px">Login Required</span>
  </div>
  <div id="content" style="text-align:center; padding:20px 5px;">
    <?php if (isset($message)) echo $message; ?>
    <form name="lform" method="post" action="<?=$_SERVER['REQUEST_URI']?>">
      <label>User ID: <input type="text" name="usr"></label>
      <label>Password: <input type="password" name="pwd"></label>
      <input id="submit" type="submit" name="login_submit" value="Log in">
    </form>
    <?php
    footer();

    exit;
  }
}

// TURN ON ERROR DISPLAY IF DEV
if ($_SESSION['userid']== "dev") {
  error_reporting(E_ALL - E_NOTICE);
  ini_set('display_errors',1);
}

// SET THE LANGUAGE BASED ON THE SETTING OF THE LOGGED IN USER
setlocale(LC_ALL, $_SESSION['lang'].".utf8");
$domain = "default";
textdomain($domain);
bindtextdomain($domain,"locale");
bind_textdomain_codeset($domain, "utf8");

// I HATE TO DO IT, BUT FOR NOW I NEED TO EMULATE REGISTER_GLOBALS ON
extract($_GET, EXTR_SKIP);  //try it just for GET
extract($_POST, EXTR_SKIP);  //try it for POST too
?>
