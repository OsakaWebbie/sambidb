<?php
include("functions.php");
include("accesscontrol.php");

// ********** MY USER **********
if (!empty($_POST['user_lang_upd'])) {
  sqlquery_checked("UPDATE user set Language = '".$_POST['language']."' WHERE UserID = '".$_SESSION['userid']."'");
  if (mysqli_affected_rows($db) == 1) {
    $_SESSION['lang'] = $_POST['language'];
    setlocale(LC_ALL, $_SESSION['lang'].".utf8");
    header("Location: ".$_SERVER['PHP_SELF'].'?msg='._("Language successfully changed."));
  }

// ********** MY PASSWORD **********
} elseif (!empty($_POST['user_pwd_upd'])) {
  $result = sqlquery_checked("SELECT * FROM user WHERE UserID = '" . $_SESSION['userid'] . "'" .
      " AND Password = PASSWORD('" . $_POST['old_pw'] . "')");
  if (mysqli_num_rows($result) == 0) {
    header('Location: '.$_SERVER['PHP_SELF'].'?err='._('The old password entry was incorrect. Password was not changed.'));
  } elseif ($_POST['new_pw1'] != $_POST['new_pw2']) {
    header('Location: '.$_SERVER['PHP_SELF'].'?err='._('The two new password entries do not match. Password was not changed.'));
  } else {
    sqlquery_checked("UPDATE user SET Password = PASSWORD('" . $_POST['new_pw1'] . "') WHERE UserID = '" . $_SESSION['userid'] . "'");
    if (mysqli_affected_rows($db) == 1) {
      header('Location: '.$_SERVER['PHP_SELF'].'?msg='._("Password successfully changed."));
    }
  }
}

$result = sqlquery_checked("SELECT Language FROM user WHERE UserID='".$_SESSION['userid']."'");
$row = mysqli_fetch_object($result);
$default_lang = $row->Language;
header1(_("User Settings"));
?>
<link rel="stylesheet" href="css/jquery-ui.css">
<?php header2(1); ?>
<h1><?=_("User Settings")?></h1>
<?php if (!empty($_GET['err'])) echo '<h4>'.$_GET['err'].'</h4>';
    elseif (!empty($_GET['msg'])) echo '<h4>'.$_GET['msg'].'</h4>';
?>

<!-- USER LANGUAGE -->

<form method="post" name="myuserform" id="myuserform" onsubmit="return validate('lang');">
  <fieldset><legend><?=_("My User Settings")?></legend>
  <label class="label-n-input"><?=_("Default Language")?>: <select id="mylanguage" name="language" size="1">
    <option value="en_US"<?php if($default_lang=="en_US") echo " selected"; ?>><?=_("English")?></option>
    <option value="ja_JP"<?php if($default_lang=="ja_JP") echo " selected"; ?>><?=_("Japanese")?></option>
  </select></label>
  <input type="submit" name="user_lang_upd" class="ui-button ui-corner-all" value="<?=_("Save Changes")?>">
</fieldset></form>

<!-- PASSWORD -->

<form method="post" name="pwform" autocomplete="off" onsubmit="return validate('pwd');">
  <fieldset><legend><?=_("Change My Password")?></legend>
  <label class="label-n-input"><?=_("Old:")?> <input type="password" id="old_pw" name="old_pw" style="width:8em"></label>
  <label class="label-n-input"><?=_("New:")?> <input type="password" id="new_pw1" name="new_pw1" style="width:8em"></label>
  <label class="label-n-input"><?=_("New again:")?> <input type="password" id="new_pw2" name="new_pw2" style="width:8em"></label>
  <input type="submit" name="user_pwd_upd" class="ui-button ui-corner-all" value="<?=_("Change Password")?>">
</fieldset></form>

<script type="text/javascript">
function validate(form) {
  switch(form) {
  case "pwd":
    if (document.pwform.old_pw.value == "") {
      alert("<?=_("You must enter your current password for validation.")?>");
      return false;
    }
    if (document.pwform.new_pw1.value != document.pwform.new_pw2.value) {
      alert("<?=_("The two new password entries do not match.")?>");
      return false;
    }
    break;
  }
}
</script>

<?php
footer();
?>
