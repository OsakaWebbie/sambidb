<?php
include("functions.php");
include("accesscontrol.php");
header1(_("Tasks"));

if (empty($sid_list) && empty($_SESSION['basket'])) {
  header2(1);
  echo "&nbsp;<br><b>"._('Your basket is empty.  Add songs to your basket from the Search page or a song detail page, then return here to put them in order and do tasks.')."</b><br>";
  print_footer();
  exit;
}
header2(1);
?>
<link rel="stylesheet" type="text/css" href="//code.jquery.com/ui/1.12.1/themes/cupertino/jquery-ui.css">
<style>
  #tagged { border:1px #999999 solid; margin:0; padding:3px; background-color:#EEEEEE; }
  #tagged li { list-style-type:none; border:1px #999999 solid; margin:1px; padding:2px 4px; white-space:nowrap; background-color:White; }
  #tagged li span.songid { display:none; }
  #tagged li span.tempoFast { color:#F00000; }
  #tagged li span.tempoMedium { color:#A08000; }
  #tagged li span.tempoSlow { color:#0000F0; }
  #tagged li span.songtitle { font-weight:bold; }
  #tagged li img { margin-left:5px; }
</style>

<h3><?php echo _('Drag songs to reorder; click the Duplicate or Remove icons as needed.  Then choose a task from the buttons on the right.'); ?></h3>
<form action="ms_history.php" method="get" id="sform" target="taskframe">
  <input type="hidden" name="sid_list" id="sid_list" value="">
  <div style="float:left">
    <table border="0" cellspacing="0" cellpadding="5" bgcolor="white">
      <tr>
        <td>
          <ul id="tagged">
<?php
//get list of songs from database and create list
if (!empty($sid_list)) {
  $sql = "SELECT * FROM song WHERE SongID In (".$sid_list.") ORDER BY FIND_IN_SET(SongID,'".$sid_list."')";
} else {
  $basket_ids = implode(',', array_map('intval', $_SESSION['basket']));
  $sql = "SELECT * FROM song WHERE SongID IN ($basket_ids) ORDER BY OrigTitle";
}
if (!$result = mysqli_query($db,$sql)) {
  echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b> ($sql)");
  exit;
}
while ($song = mysqli_fetch_object($result)) {
  echo '          <li><div class="left"><span class="songid">'.$song->SongID.'</span>['.$song->SongKey.']';
  echo '<span class="tempo'.$song->Tempo.'">['.$song->Tempo.']</span> <span class="songtitle">'.$song->Title;
  if (preg_replace('/^[[:digit:]]{3}: /','',$song->Title) != $song->OrigTitle) echo ' ('.$song->OrigTitle.')';
  echo '</span></div><div class="right"><img src="graphics/copy.gif" class="copy" title="'._('Duplicate').'">';
  echo '<img src="graphics/delete.gif" class="delete" title="'._('Remove').'"></div><div class="clear"></div>';
  echo "</li>\n";
}
?>
          </ul>
        </td>
      </tr>
    </table>
  </div>
  <div style="float:left; border:3px solid gray; text-align:center; margin:10px; padding:0 10px;">
    <h3><?php echo _('Choose a task:'); ?></h3>
    <p><input type="submit" name="ms_history" value="<?php echo _('Record As Event Song Session'); ?>"<?php if ($_SESSION['admin']==0) echo " disabled"; ?>></p>
    <p><input type="submit" name="ms_pdf" value="<?php echo _('Output Songs (PDF)'); ?>"></p>
    <p><input type="submit" name="ms_text" value="<?php echo _('Output Songs (text)'); ?>"></p>
    <p><input type="submit" name="ms_tag" value="<?php echo _('Add a Tag'); ?>"<?php if ($_SESSION['admin']==0) echo " disabled"; ?>></p>
  </div>
</form>
<iframe id= "taskframe" name="taskframe" style="width:90%;height:300px" src="blank.html">
</iframe>

<script src="https://code.jquery.com/jquery-3.2.1.min.js" type="text/javascript"></script>
<script src="//code.jquery.com/ui/1.12.1/jquery-ui.min.js" type="text/javascript"></script>
<script src="js/jquery.ui.touch-punch.min.js" type="text/javascript"></script>
<script type="text/JavaScript">
  $(document).ready(function(){

    $("#tagged").sortable({
      placeholder: "ui-state-highlight",
      forcePlaceholderSize: true,
      update: function() { $("#taskframe").attr("src","blank.html"); }
    });
// ACTIONS RELATED TO COPY ICONS
    $("#tagged").delegate("img.copy","click",function() {
      var original = $(this).closest("li");
      var cloned = $(original).clone(true,true);
      $(original).after($(cloned));
    });
// ACTIONS RELATED TO DELETE ICONS
    $("#tagged").delegate("img.delete","click",function() {
      $(this).closest("li").remove();
    });

// PREP FOR SUBMIT
    $("#sform input[type=submit]").click(function() {
      $("#sform").attr("action",$(this).attr("name")+".php");
    });
    $("#sform").submit(function(e) {
      var items = "";
      $("#tagged li span.songid").each(function() { // For each song title
        if (items!="") items += ",";
        items += $(this).text();
      });
      $("#sid_list").val(items);
    });

  });

  function make_list() {
    var f = document.sform;
    f.sid_list.value = "";
    for (var index = 0; index < f.tagged.length; index++) {
      if (f.sid_list.value == "") {
        f.sid_list.value = f.tagged[index].value;
      } else {
        f.sid_list.value = f.sid_list.value + "," + f.tagged[index].value;
      }
    }
  }
</script>
<?php print_footer();?>