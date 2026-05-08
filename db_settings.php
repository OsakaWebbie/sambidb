<?php
include("functions.php");
include("accesscontrol.php");

header1(_("Database Settings"));
?>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/cupertino/jquery-ui.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css">
<?php header2(1); ?>
<h1 id="title"><?=_("Database Settings")?></h1>

<?php if ($_SESSION['admin'] > 0) { ?>
  <!-- TAGS -->

  <form action="do_maint.php" method="post" name="tagform" id="tagform" onSubmit="return validate('tag');">
    <fieldset><legend><?=_("Tag Management")?></legend>
      <p><?=_('Select a tag to rename, and type its new name.&nbsp; Or select &quot;New Tag&quot; and type a new tag name.  Or select a tag to delete, and press Delete.')?></p>
      <select id="tagid" name="tagid" size="1">
        <option value="new"><?=_("New Tag...")?></option>
        <?php
        $result = sqlquery_checked("SELECT TagID,Tag FROM tag ORDER BY Tag");
        while ($row = mysqli_fetch_object($result))  echo "    <option value=\"".$row->TagID."\">".$row->Tag."</option>\n";
        ?>
      </select>
      <label class="label-n-input"><?=_('Tag Name:')?>  <input type="text"
                                                                   id="tag" name="tag" style="width:20em" maxlength="60"></label>
      <div class="submits"><input type="submit" id="tag_add_upd" name="tag_add_upd" value="<?=_("Add or Rename")?>">
        <input type="submit" id="tag_del" name="tag_del" value="<?=_("Delete")?>" disabled></div>
    </fieldset></form>

  <!-- EVENTS -->

  <form action="do_maint.php" method="post" name="eventform" id="eventform" onSubmit="return validate('event');">
    <fieldset><legend><?=_("Event Management")?></legend>
      <p><?=_('Fill in the information to add a new event.  Or select an event, and modify its info (name, remarks, and/or active status).  Or select an event to delete and press Delete.')?></p>
      <select id="eventid" name="eventid" size="1">
        <option value="new"><?=_("New Event...")?></option>
        <?php
        $result = sqlquery_checked("SELECT EventID,Event,Active FROM event ORDER BY Event");
        while ($row = mysqli_fetch_object($result))  echo "    <option class=\"".($row->Active ? "active" : "inactive").
            "\" value=\"".$row->EventID."\">".$row->Event."</option>\n";
        ?>
      </select>
      <label class="label-n-input"><?=_('Event:')?> <input type="text" id="event" name="event" style="width:20em" maxlength="60"></label>
      <label class="label-n-input"><input type="checkbox" id="active" name="active" checked><?=_("Currently Occurring Event")?></label>
      <label class="label-n-input"><?=_('Description:')?> <textarea id="remarks" name="remarks" rows="3" cols="50"></textarea></label>
      <div class="submits"><input type="submit" id="event_add_upd" name="event_add_upd" value="<?=_("Add or Update")?>">
        <input type="submit" id="event_del" name="event_del" value="<?=_("Delete")?>" disabled></div>
    </fieldset></form>
<?php } // end of if admin > 0 ?>

<?php if ($_SESSION['admin'] == 2) { ?>
  <!-- USERS (admin only) -->

  <form action="do_maint.php" method="post" name="userform" id="userform" autocomplete="off" onSubmit="return validate('user');">
    <fieldset><legend><?=_("User Management")?></legend>
      <p><?=_("Fill in the information to add a new user. Or select an existing user to make changes or delete. NOTE: You cannot see the existing password, but you can enter a new one if the user forgot their password.")?></p>
      <select id="userid" name="userid" size="1">
        <option value="new"><?=_("New User...")?></option>
        <?php
        $result = sqlquery_checked("SELECT UserID,UserName FROM user ORDER BY UserName");
        while ($row = mysqli_fetch_object($result))  echo "    <option value=\"".$row->UserID."\">".$row->UserName."</option>\n";
        ?>
      </select>
      <input type="hidden" id="old_userid" name="old_userid" value="">
      <label class="label-n-input"><?=_("Name")?>: <input type="text"
                                                          id="username" name="username" style="width:15em" maxlength="100" autocomplete="off"></label>
      <label class="label-n-input"><?=_("UserID (to log in)")?>: <input type="text"
                                                                        id="new_userid" name="new_userid" style="width:8em" maxlength="16" autocomplete="off">
        <span class="comment"><?=_("(max. 16 characters, no spaces)")?></span></label>
      <label class="label-n-input"><?=_("Language for Interface")?>: <select id="language" name="language" size="1">
          <option value="en_US"<?php if($_SESSION['lang']=="en_US") echo " selected"; ?>><?= _("English")?></option>
          <option value="ja_JP"<?php if($_SESSION['lang']=="ja_JP") echo " selected"; ?>><?=_("Japanese")?></option>
        </select></label>
      <label class="label-n-input"><?=_("Access Level")?>: <select id="adminlevel" name="adminlevel" size="1">
          <option value="0"><?=_("Read-only")?></option>
          <option value="1" selected><?=_("Standard (can edit)")?></option>
          <option value="2"><?=_("Admin")?></option>
        </select></label>
      <label class="label-n-input"><?=_("New Password")?>: <input type="password"
                                                                  id="new_pw1" name="new_pw1" style="width:10em" autocomplete="new-password">
        <span class="comment"><?=_("(leave blank if not changing password)")?></span></label>
      <label class="label-n-input"><?=_("New Password again")?>: <input type="password"
                                                                        id="new_pw2" name="new_pw2" style="width:10em" autocomplete="new-password"></label>
      <div id="loginstats" class="comment"></div>
      <div class="submits"><input type="submit" id="user_add_upd" name="user_add_upd" value="<?=_("Add or Update")?>">
        <input type="submit" id="user_del" name="user_del" value="<?=_("Delete")?>" disabled></div>
    </fieldset></form>
<?php } // end of if admin == 2 ?>

<script type="text/JavaScript" src="js/jquery-3.6.0.min.js"></script>
<script type="text/JavaScript" src="js/jquery-ui.min.js"></script>

<script type="text/javascript">

  function stopRKey(evt) {
    var evt = (evt) ? evt : ((event) ? event : null);
    var node = (evt.target) ? evt.target : ((evt.srcElement) ? evt.srcElement : null);
    // Only block Enter key for text inputs within this page's forms
    if ((evt.keyCode == 13) && (node.type=="text")) {
      var form = node.form || $(node).closest('form')[0];
      if (form && ["tagform","eventform","userform"].includes(form.id)) {
        return false;
      }
    }
  }
  document.onkeypress = stopRKey;

  function showSpinner(el) {
    el.after('<span class="spinner"><img src="css/images/ajax-loader.gif" alt="Loading..." /></span>');
  }

  function hideSpinner(el) {
    el.siblings(".spinner").remove();
  }

  $(document).ready(function(){

// AJAX call for Tags
    $("#tagid").change(function(){
      if ($("#tagid").val() == "new") {
        $("#tag").val("");
        $("#tag_del").prop('disabled', true);
      } else {
        showSpinner($('#tagid'));
        $.getJSON("ajax_actions.php?action=Tag&tagid="+$('#tagid').val())
            .done(function(data) {
              hideSpinner($('#tagid'));
              if (data.alert) {
                alert(data.alert);
              } else {
                $('#tag').val(data.tag);
                $("#tag_del").prop('disabled', false);
              }
            })
            .fail(function(jqxhr, textStatus, error) {
              hideSpinner($('#tagid'));
              alert("AJAX Error: " + textStatus + ", " + error + "\n" + jqxhr.responseText);
            });
      }
    });

// AJAX call for Events
    $("#eventid").change(function(){
      if ($("#eventid").val() == "new") {
        $("#event, #remarks").val("");
        $("#active").prop("checked", true);
        $("#event_del").prop('disabled', true);
      } else {
        showSpinner($('#eventid'));
        $.getJSON("ajax_actions.php?action=Event&eventid="+$('#eventid').val())
            .done(function(data) {
              hideSpinner($('#eventid'));
              if (data.alert) {
                alert(data.alert);
              } else {
                $("#active").prop("checked", false);
                $('#event').val(data.event);
                if (data.active == 1) $('#active').prop('checked', true);
                $('#remarks').val(data.remarks);
                $("#event_del").prop('disabled', false);
              }
            })
            .fail(function(jqxhr, textStatus, error) {
              hideSpinner($('#eventid'));
              alert("AJAX Error: " + textStatus + ", " + error + "\n" + jqxhr.responseText);
            });
      }
    });

    <?php if ($_SESSION['admin'] == 2) { ?>
// AJAX call for Users (admin only)
    $("#userid").change(function(){
      if ($("#userid").val() == "new") {
        $("#username, #new_userid, #old_userid, #new_pw1, #new_pw2").val("");
        $("#language").val("<?=$_SESSION['lang']?>");
        $("#adminlevel").val("1");
        $("#user_del").prop('disabled', true);
        $("#loginstats").html("");
      } else {
        showSpinner($('#userid'));
        $.getJSON("ajax_actions.php?action=User&userid="+$('#userid').val())
            .done(function(data) {
              hideSpinner($('#userid'));
              if (data.alert) {
                alert(data.alert);
              } else {
                $('#username').val(data.username);
                $('#new_userid').val(data.userid);
                $('#old_userid').val(data.userid);
                $('#language').val(data.language);
                $('#adminlevel').val(data.admin);
                $("#loginstats").html(data.loginstats);
                $("#user_del").prop('disabled', false);
              }
            })
            .fail(function(jqxhr, textStatus, error) {
              hideSpinner($('#userid'));
              alert("AJAX Error: " + textStatus + ", " + error + "\n" + jqxhr.responseText);
            });
      }
    });
    <?php } ?>
  });

  function validate(form) {
    switch(form) {
      case "tag":
        if (document.tagform.tag.value == "") {
          alert("<?=_("Tag name cannot be blank.")?>");
          return false;
        }
        break;
      case "event":
        if (document.eventform.event.value == "") {
          alert("<?=_("Event name cannot be blank.")?>");
          return false;
        }
        break;
      case "user":
        if (document.userform.username.value == "") {
          alert("<?=_("User Name cannot be blank.")?>");
          return false;
        } else if (document.userform.new_userid.value == "") {
          alert("<?=_("UserID cannot be blank.")?>");
          return false;
        } else if (document.userform.userid.selectedIndex == 0 && document.userform.new_pw1.value == "") {
          alert("<?=_("You must enter a password for a new user.")?>");
          return false;
        } else if (document.userform.new_pw1.value != "" && document.userform.new_pw1.value != document.userform.new_pw2.value) {
          alert("<?=_("The two password entries do not match.")?>");
          return false;
        }
        break;
    }
  }
</script>
<?php footer(); ?>
