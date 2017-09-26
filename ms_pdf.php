<?php
include("functions.php");
include("accesscontrol.php");
print_header("","#FFF0E0",0);
?>
    <h3><font color="#8b4513">Select your options and click the button to proceed...</font></h3>
    <form action="pdflayout.php" method="get" name="optionsform" target="_blank">
      <input type="hidden" name="sid_list" value="<?php echo $sid_list; ?>" border="0">
      <div style="float:left; margin-right:10px">
        <input type="radio" name="pattern" value="basic" border="0" checked>Basic song content (stuff before ---)<br>
        <input type="radio" name="pattern" value="allparts" border="0">Basic + Extras<br>
        <input type="radio" name="pattern" value="pattern" border="0">Repeat parts according to Pattern
      </div>
      <div style="float:left; margin-right:10px">
        <input type="checkbox" name="multilingual" value="yes" checked> Combine Multilingual Songs
      </div>
      <div style="float:left">
        <input type="submit" name="submit" value="Go to Layout Page" border="0">
      </div>
    </form>
<?php print_footer();
?>