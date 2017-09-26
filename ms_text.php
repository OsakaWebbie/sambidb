<?php
include("functions.php");
include("accesscontrol.php");
print_header("","#FFF0E0",0);
?>
    <form action="text.php" method="get" name="optionsform" target="_blank">
      <input type="hidden" name="sid_list" value="<?php echo $sid_list; ?>" border="0">
      <table width="639" border="0" cellspacing="0" cellpadding="5">
        <tr><td nowrap>
<?php
for ($i=1; $i<7; $i++) {
  echo "        Field $i:<select name=\"field$i\" size=\"1\">\n";
  echo "        <option value=\"\"> </option>\n";
  echo "        <option value=\"Title\">Title</option>\n";
  echo "        <option value=\"OrigTitle\">Original Title</option>\n";
  echo "        <option value=\"Tempo\">Tempo</option>\n";
  echo "        <option value=\"SongKey\">Key</option>\n";
  echo "        <option value=\"Composer\">Composer</option>\n";
  echo "        <option value=\"Copyright\">Copyright</option>\n";
  echo "        <option value=\"Source\">Source</option>\n";
  echo "        <option value=\"Pattern\">Pattern</option>\n";
  echo "        <option value=\"Lyrics\">Lyrics</option>\n";
  echo "        <option value=\" \">Blank Line</option>\n";
  echo "        </select>\n";
  echo "        &nbsp; Place it:<select name=\"layout$i\" size=\"1\">\n";
  echo "        <option value=\"<br>\">On a new line</option>\n";
  echo "        <option value=\" (\">On same line, in parentheses</option>\n";
  echo "        <option value=\"<br>(\">On new line, in parentheses</option>\n";
  echo "        <option value=\"<br>&nbsp;&nbsp;&nbsp;\">On new line, indented</option>\n";
  echo "        </select><br>\n";
}
?>
          </td>
          <td>
            <p><input type=checkbox name="xml">XML Format ("Place It..." option ignored)</p>
            <h3><font color="#8b4513">Select fields to output and click the button...</font></h3>
            <input type="submit" name="submit" value="Make Page to Copy or Print" border="0">
          </td>
        </tr>
      </table>
    </form>
<?php print_footer();
?>