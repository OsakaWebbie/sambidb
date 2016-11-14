<?php
include("functions.php");
include("accesscontrol.php");
//print_header("","#FFF0E0",0);
echo "<html><head>";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$_SESSION['pw_charset']."\">\n";
?>
<title>Edit Formatted Songs</title>

<script language = "Javascript">

function fillFromParent() {
  str = opener.document.getElementById('output').innerHTML;
  str = str.replace(/<span class="chord">([^<]*)<\/span>/gm, '[$1]');
  str = str.replace(/^<p class="([^"]*)">([^<]*)<\/p>$/gm, '{$1}$2');
  str = str.replace(/&nbsp;/gm, ' ');
  document.getElementById('editbox').value = str;
}

function saveEdit() {
  str = document.getElementById('editbox').value;
  str = str.replace(/  /gm, '&nbsp;&nbsp;');
  str = str.replace(/ $/gm, '&nbsp;');
  str = str.replace(/^{([^}]*)}(.*)$/gm, '<p class="$1">$2</p>');
  str = str.replace(/\[([^\]]*)\]/gm, '<span class="chord">$1</span>');
  opener.document.getElementById('output').innerHTML = str;
}
</script>

<body onload="fillFromParent();">
<form name="editform" action="" onSubmit="saveEdit();window.close();return false;">
<div height="30px" align="center" valign="middle">
  <input type="submit" name="Submit" value="Apply Changes and Close">&nbsp;&nbsp;&nbsp;
  <input type="button" name="Apply" value="Apply Changes" onClick="saveEdit();">&nbsp;&nbsp;&nbsp;
  <input type="button" name="Reset" value="Reset" onClick="fillFromParent();"></div>
<div align=center><textarea id="editbox" rows="5" cols="5" style="width:630px;height:600px;font-size:9pt;"></textarea></div>
</form>
</body>
</html>