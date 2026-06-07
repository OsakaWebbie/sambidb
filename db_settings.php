<?php
include("functions.php");
include("accesscontrol.php");

header1(_("Database Settings"));
?>
<link rel="stylesheet" href="css/jquery-ui.css">
<style>
#status-msg {
  position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
  padding: 10px 16px; background: #2e7d32; color: white; border-radius: 4px;
  box-shadow: 0 2px 8px rgba(0,0,0,.2); z-index: 10000; display: none;
}
</style>
<?php header2(1); ?>
<h1 id="title"><?=_("Database Settings")?></h1>

<?php if ($_SESSION['access'] > 0) { ?>
  <!-- TAGS -->

  <form name="tagform" id="tagform">
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
      <div class="submits">
        <button type="button" id="tag_add_upd" class="ui-button ui-corner-all"><?=_("Add or Rename")?></button>
        <button type="button" id="tag_del" class="ui-button ui-corner-all" disabled><?=_("Delete")?></button>
      </div>
    </fieldset></form>

  <!-- EVENTS -->

  <form name="eventform" id="eventform">
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
      <label class="label-n-checkbox"><input type="checkbox" id="active" name="active" checked><?=_("Currently Occurring Event")?></label>
      <div style="margin:0.4em 0;"><label for="remarks"><?=_('Description:')?></label><br>
        <textarea id="remarks" name="remarks" rows="3" style="width:100%; max-width:25em; box-sizing:border-box;"></textarea></div>
      <div class="submits">
        <button type="button" id="event_add_upd" class="ui-button ui-corner-all"><?=_("Add or Update")?></button>
        <button type="button" id="event_del" class="ui-button ui-corner-all" disabled><?=_("Delete")?></button>
      </div>
    </fieldset></form>

  <div id="event-del-dialog" title="<?=_("Caution")?>" style="display:none;">
    <p id="event-del-name" style="font-weight:bold;"></p>
    <p id="event-del-msg"></p>
  </div>
<?php } // end of if admin > 0 ?>

<?php if ($_SESSION['access'] == 2) { ?>
  <!-- USERS (admin only) -->

  <form name="userform" id="userform" autocomplete="off">
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
      <label class="label-n-input"><?=_("Access Level")?>: <select id="accesslevel" name="accesslevel" size="1">
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
      <div class="submits">
        <button type="button" id="user_add_upd" class="ui-button ui-corner-all"><?=_("Add or Update")?></button>
        <button type="button" id="user_del" class="ui-button ui-corner-all" disabled><?=_("Delete")?></button>
      </div>
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

  function showStatus(msg) {
    var $s = $('#status-msg');
    if (!$s.length) $s = $('<div id="status-msg">').appendTo('body');
    $s.text(msg).fadeIn(100).delay(1500).fadeOut(400);
  }
  function selectUpsertOption($select, id, text, extraAttrs) {
    var $opt = $select.find('option[value="' + id + '"]');
    if ($opt.length) $opt.text(text);
    else $opt = $('<option>').val(id).text(text).appendTo($select);
    if (extraAttrs) $opt.attr(extraAttrs);
    selectSortOptions($select);
    $select.val(id);
  }
  function selectRemoveOption($select, id) {
    $select.find('option[value="' + id + '"]').remove();
    $select.val('new');
  }
  function selectSortOptions($select) {
    var $first = $select.find('option[value="new"]').detach();
    var opts = $select.find('option').get().sort(function(a, b) {
      return a.text.localeCompare(b.text);
    });
    $select.empty().append($first).append(opts);
  }
  function resetEntityForm($select) { $select.val('new').trigger('change'); }

  $(document).ready(function(){

// AJAX call for Tags
    $("#tagid").change(function(){
      if ($("#tagid").val() == "new") {
        $("#tag").val("");
        $("#tag_del").prop('disabled', true);
      } else {
        $('#tag').addClass('is-loading');
        $.getJSON("ajax_request.php?action=Tag&tagid="+$('#tagid').val())
            .done(function(data) {
              $('#tag').removeClass('is-loading');
              if (data.alert) {
                alert(data.alert);
              } else {
                $('#tag').val(data.tag);
                $('#tag_del').data('name', data.tag).data('songtag-count', data.songtag_count);
                $("#tag_del").prop('disabled', false);
              }
            })
            .fail(function(jqxhr, textStatus, error) {
              $('#tag').removeClass('is-loading');
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
        $('#event').addClass('is-loading');
        $.getJSON("ajax_request.php?action=Event&eventid="+$('#eventid').val())
            .done(function(data) {
              $('#event').removeClass('is-loading');
              if (data.alert) {
                alert(data.alert);
              } else {
                $("#active").prop("checked", false);
                $('#event').val(data.event);
                if (data.active == 1) $('#active').prop('checked', true);
                $('#remarks').val(data.remarks);
                $('#event_del').data({
                  name:         data.event,
                  historyCount: data.history_count,
                  dateCount:    data.date_count,
                  useFirst:     data.use_first,
                  useLast:      data.use_last
                });
                $("#event_del").prop('disabled', false);
              }
            })
            .fail(function(jqxhr, textStatus, error) {
              $('#event').removeClass('is-loading');
              alert("AJAX Error: " + textStatus + ", " + error + "\n" + jqxhr.responseText);
            });
      }
    });

    $('#tag_add_upd').click(function() {
      if (validate('tag') === false) return;
      var $tag = $('#tag');
      $tag.addClass('is-loading');
      $.post('ajax_action.php', {
        action: 'TagSave',
        tagid: $('#tagid').val(),
        tag: $tag.val()
      }, function(data) {
        $tag.removeClass('is-loading');
        if (data.alert || data.error) { alert(data.alert || data.error); return; }
        selectUpsertOption($('#tagid'), data.tagid, data.tag);
        resetEntityForm($('#tagid'));
        showStatus(data.message);
      }, 'json').fail(function(jqxhr, textStatus, error) {
        $tag.removeClass('is-loading');
        alert('AJAX Error: ' + textStatus + ', ' + error);
      });
    });

    $('#tag_del').click(function() {
      var name = $(this).data('name');
      var n    = $(this).data('songtag-count');
      var msg = '<?=_("Are you sure you want to delete tag \"%s\"?")?>'.replace('%s', name);
      if (n > 0) msg += '\n\n' + '<?=_("It is currently applied to %d songs, which will also lose this tag.")?>'.replace('%d', n);
      if (!confirm(msg)) return;
      var tagid = $('#tagid').val();
      var $tag = $('#tag');
      $tag.addClass('is-loading');
      $.post('ajax_action.php', {
        action: 'TagDelete',
        tagid: tagid
      }, function(data) {
        $tag.removeClass('is-loading');
        if (data.alert || data.error) { alert(data.alert || data.error); return; }
        selectRemoveOption($('#tagid'), tagid);
        resetEntityForm($('#tagid'));
        showStatus(data.message);
      }, 'json').fail(function(jqxhr, textStatus, error) {
        $tag.removeClass('is-loading');
        alert('AJAX Error: ' + textStatus + ', ' + error);
      });
    });

    $('#event_add_upd').click(function() {
      if (validate('event') === false) return;
      var $event = $('#event');
      $event.addClass('is-loading');
      $.post('ajax_action.php', {
        action: 'EventSave',
        eventid: $('#eventid').val(),
        event: $event.val(),
        active: $('#active').is(':checked') ? 1 : 0,
        remarks: $('#remarks').val()
      }, function(data) {
        $event.removeClass('is-loading');
        if (data.alert || data.error) { alert(data.alert || data.error); return; }
        selectUpsertOption($('#eventid'), data.eventid, data.event,
                           {'class': data.active ? 'active' : 'inactive'});
        resetEntityForm($('#eventid'));
        showStatus(data.message);
      }, 'json').fail(function(jqxhr, textStatus, error) {
        $event.removeClass('is-loading');
        alert('AJAX Error: ' + textStatus + ', ' + error);
      });
    });

    $('#event_del').click(function() {
      var name = $(this).data('name');
      var n    = $(this).data('historyCount');
      if (n > 0) {
        var first     = $(this).data('useFirst');
        var last      = $(this).data('useLast');
        var dateCount = $(this).data('dateCount');
        var dateRange = (first === last) ? first : first + ' – ' + last;
        $('#event-del-name').text('<?=_("Are you sure you want to delete event \"%s\"?")?>'.replace('%s', name));
        $('#event-del-msg').text(
          '<?=_('This event has usage records for %1$d dates (%2$s), for a total of %3$d song usages.')?>'
            .replace('%1$d', dateCount).replace('%2$s', dateRange).replace('%3$d', n)
        );
        $('#event-del-dialog').dialog('open');
        return;
      }
      if (!confirm('<?=_("Are you sure you want to delete event \"%s\"?")?>'.replace('%s', name))) return;
      doEventDelete($('#eventid').val());
    });

    function doEventDelete(eventid) {
      var $event = $('#event');
      $event.addClass('is-loading');
      $.post('ajax_action.php', {
        action: 'EventDelete',
        eventid: eventid
      }, function(data) {
        $event.removeClass('is-loading');
        if (data.alert || data.error) { alert(data.alert || data.error); return; }
        selectRemoveOption($('#eventid'), eventid);
        resetEntityForm($('#eventid'));
        showStatus(data.message);
      }, 'json').fail(function(jqxhr, textStatus, error) {
        $event.removeClass('is-loading');
        alert('AJAX Error: ' + textStatus + ', ' + error);
      });
    }

    $('#event-del-dialog').dialog({
      autoOpen: false,
      modal: true,
      width: Math.min(500, $(window).width() - 40),
      buttons: [
        {
          text: '<?=_("Yes, delete the event and all usage history")?>',
          click: function() {
            var $dlg = $(this);
            var eventid = $('#eventid').val();
            doEventDelete(eventid);
            $dlg.dialog('close');
          }
        },
        {
          text: '<?=_("Cancel")?>',
          click: function() { $(this).dialog('close'); }
        }
      ]
    });

    <?php if ($_SESSION['access'] == 2) { ?>
// AJAX call for Users (admin only)
    $("#userid").change(function(){
      if ($("#userid").val() == "new") {
        $("#username, #new_userid, #old_userid, #new_pw1, #new_pw2").val("");
        $("#language").val("<?=$_SESSION['lang']?>");
        $("#accesslevel").val("1");
        $("#user_del").prop('disabled', true);
        $("#loginstats").html("");
      } else {
        $('#username').addClass('is-loading');
        $.getJSON("ajax_request.php?action=User&userid="+$('#userid').val())
            .done(function(data) {
              $('#username').removeClass('is-loading');
              if (data.alert) {
                alert(data.alert);
              } else {
                $('#username').val(data.username);
                $('#new_userid').val(data.userid);
                $('#old_userid').val(data.userid);
                $('#language').val(data.language);
                $('#accesslevel').val(data.access);
                $("#loginstats").html(data.loginstats);
                $('#user_del').data('name', data.username).data('userid', data.userid);
                $("#user_del").prop('disabled', false);
              }
            })
            .fail(function(jqxhr, textStatus, error) {
              $('#username').removeClass('is-loading');
              alert("AJAX Error: " + textStatus + ", " + error + "\n" + jqxhr.responseText);
            });
      }
    });
    $('#user_add_upd').click(function() {
      if (validate('user') === false) return;
      var $username = $('#username');
      $username.addClass('is-loading');
      $.post('ajax_action.php', {
        action:      'UserSave',
        userid:      $('#userid').val(),
        new_userid:  $('#new_userid').val(),
        username:    $username.val(),
        accesslevel: $('#accesslevel').val(),
        language:    $('#language').val(),
        old_userid:  $('#old_userid').val(),
        new_pw1:     $('#new_pw1').val(),
        new_pw2:     $('#new_pw2').val()
      }, function(data) {
        $username.removeClass('is-loading');
        if (data.alert || data.error) { alert(data.alert || data.error); return; }
        var sentOldUserid = $('#old_userid').val();
        selectUpsertOption($('#userid'), data.userid, data.username);
        if (sentOldUserid && sentOldUserid !== data.userid) {
          $('#userid').find('option[value="' + sentOldUserid + '"]').remove();
        }
        if (data.sessionUpdated) { window.location.reload(); return; }
        resetEntityForm($('#userid'));
        showStatus(data.message);
      }, 'json').fail(function(jqxhr, textStatus, error) {
        $username.removeClass('is-loading');
        alert('AJAX Error: ' + textStatus + ', ' + error);
      });
    });
    $('#user_del').click(function() {
      var name   = $(this).data('name');
      var userid = $(this).data('userid');
      if (!confirm('<?=_('Are you sure you want to delete user "%1$s" (UserID: %2$s)?')?>'.replace('%1$s', name).replace('%2$s', userid))) return;
      var $username = $('#username');
      $username.addClass('is-loading');
      $.post('ajax_action.php', {
        action:     'UserDelete',
        old_userid: userid
      }, function(data) {
        $username.removeClass('is-loading');
        if (data.alert || data.error) { alert(data.alert || data.error); return; }
        selectRemoveOption($('#userid'), userid);
        resetEntityForm($('#userid'));
        showStatus(data.message);
      }, 'json').fail(function(jqxhr, textStatus, error) {
        $username.removeClass('is-loading');
        alert('AJAX Error: ' + textStatus + ', ' + error);
      });
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
