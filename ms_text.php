<?php
include("functions.php");
include("accesscontrol.php");
header1('');
?>
<link rel="stylesheet" type="text/css" href="css/jquery-ui.css">
<?php
header2(0);
?>
    <form action="text.php" method="get" name="optionsform" target="_blank">
      <input type="hidden" name="sid_list" value="<?php echo $sid_list; ?>" border="0">
      <table width="639" border="0" cellspacing="0" cellpadding="5">
        <tr><td nowrap>
<?php
for ($i=1; $i<7; $i++) {
  echo "        ".sprintf(_('Field %s:'), $i)."<select name=\"field$i\" size=\"1\">\n";
  echo "        <option value=\"\"> </option>\n";
  echo "        <option value=\"Title\">"._('Title')."</option>\n";
  echo "        <option value=\"OrigTitle\">"._('Original Title')."</option>\n";
  echo "        <option value=\"Tempo\">"._('Tempo')."</option>\n";
  echo "        <option value=\"SongKey\">"._('Key')."</option>\n";
  echo "        <option value=\"Composer\">"._('Composer')."</option>\n";
  echo "        <option value=\"Copyright\">"._('Copyright')."</option>\n";
  echo "        <option value=\"Source\">"._('Source')."</option>\n";
  echo "        <option value=\"Pattern\">"._('Pattern')."</option>\n";
  echo "        <option value=\"Lyrics\">"._('Lyrics')."</option>\n";
  echo "        <option value=\" \">"._('Blank Line')."</option>\n";
  echo "        </select>\n";
  echo "        &nbsp; "._('Place it:')."<select name=\"layout$i\" size=\"1\">\n";
  echo "        <option value=\"<br>\">"._('On a new line')."</option>\n";
  echo "        <option value=\" (\">"._('On same line, in parentheses')."</option>\n";
  echo "        <option value=\"<br>(\">"._('On new line, in parentheses')."</option>\n";
  echo "        <option value=\"<br>&nbsp;&nbsp;&nbsp;\">"._('On new line, indented')."</option>\n";
  echo "        </select><br>\n";
}
?>
          </td>
          <td>
            <p><input type=checkbox name="xml"><?php echo _('XML Format ("Place It..." option ignored)'); ?></p>
            <h3><font color="#8b4513"><?php echo _('Select fields to output and click the button...'); ?></font></h3>
            <input type="submit" name="submit" class="ui-button ui-corner-all" value="<?php echo _('Make Page to Copy or Print'); ?>" border="0">
          </td>
        </tr>
      </table>
    </form>
<?php footer();
?>