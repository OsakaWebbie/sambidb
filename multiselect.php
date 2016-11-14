<?php
include("functions.php");
include("accesscontrol.php");
header1("Multiple Selection");

if (!$sid_list) {
  if (!$result = mysql_query("SELECT count(*) AS Num FROM pw_song WHERE Tagged=1")) {
    echo("<b>SQL Error ".mysql_errno()." while counting tagged songs: ".mysql_error()."</b>");
    exit;
  }
  $row = mysql_fetch_object($result);
  if ($row->Num == 0) {
    echo "&nbsp;<br><b>There are no tagged songs.  Please use the tools on the Top(Search) page to
    select songs for tagging, then use this page to put them in order and/or take actions.</b><br>";
    exit;
  }
}
?>
<link rel="stylesheet" type="text/css" href="css/jquery-ui.css" />
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
<script type="text/JavaScript" src="js/jquery.min.js"></script>
<script type="text/JavaScript" src="js/jquery-ui.min.js"></script>

<script type="text/JavaScript">
$(document).ready(function(){

  $("#tagged").sortable({
    placeholder: "ui-state-highlight",
    forcePlaceholderSize: true,
    update: function() { $("#actionframe").attr("src","blank.html"); }
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
<?
header2(1);
?>
<h3>Drag songs to reorder; click the Duplicate or Remove icons as needed.  Then choose an action from the buttons on the right.</h3>
<form action="ms_usage.php" method="get" id="sform" target="actionframe">
  <input type="hidden" name="sid_list" id="sid_list" value="" border="0">
  <table border="0" cellspacing="0" cellpadding="8"><tr>
    <td valign="top" align="center">
      <table border="0" cellspacing="0" cellpadding="5" bgcolor="white"><tr><td>
        <ul id="tagged">
<?
//get list of songs from database and create list
if ($sid_list) {
  $sql = "SELECT * FROM pw_song WHERE SongID In (".$sid_list.") ORDER BY FIND_IN_SET(SongID,'".$sid_list."')";
} else {
  $sql = "SELECT * FROM pw_song WHERE tagged=1 ORDER BY OrigTitle";
}
if (!$result = mysql_query($sql)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b> ($sql)");
  exit;
}
while ($song = mysql_fetch_object($result)) {
  echo "          <li><div class=\"left\"><span class=\"songid\">".$song->SongID."</span>[".$song->SongKey."]";
  echo "<span class=\"tempo".$song->Tempo."\">[".$song->Tempo."]</span> <span class=\"songtitle\">".$song->Title;
  if (ereg_replace("^[[:digit:]]{3}: ","",$song->Title) != $song->OrigTitle) echo " (".$song->OrigTitle.")";
  echo "</span></div><div class=\"right\"><img src=\"graphics/copy.gif\" class=\"copy\" title=\"Duplicate\">";
  echo "<img src=\"graphics/delete.gif\" class=\"delete\" title=\"Remove\"></div><div class=\"clear\"></div>";
  echo "</li>\n";
}
?>
        </ul>
      </td></tr></table>
    </td>
    <td valign="top" align="center">
      <div style="float:left; border:3px solid gray; text-align:center; margin:10px; padding:0 10px;">
        <h3 style="border:0">Choose an Action:</h3>
        <p><input type="submit" name="ms_usage" value="Record As Event Song Session"<? if ($_SESSION['pw_admin']==0) echo " disabled"; ?>></p>
        <p><input type="submit" name="ms_pdf" value="Output Songs (PDF)"></p>
        <p><input type="submit" name="ms_text" value="Output Songs (text)"></p>
        <p><input type="submit" name="ms_keyword" value="Add a Keyword"<? if ($_SESSION['pw_admin']==0) echo " disabled"; ?>></p>
        <p><input type="submit" name="ms_format" value="[Output Songs (old)]"></p>
      </div>
    </td></tr>
  </table>
</form>
<iframe id= "actionframe" name="actionframe" style="width:100%;height:300px" src="blank.html">
</iframe>
<? print_footer();?>