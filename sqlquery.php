<?php
 include("functions.php");
 include("accesscontrol.php");

print_header("SQL Query","#FFFFFF",1);

if ($query) {
  echo "<center><h2><font color=#40A040>Results of this query:</font></h2>";
  echo "<form action=\"sqlquery.php\" method=POST><textarea rows=4 name=\"query\" cols=100>".stripslashes($query)."</textarea>";
  echo "<br><input type=submit name=submit value=\"Do this new (modified) query!\"></form></center>";

  $query = stripslashes($query);
  echo "Results of query <b>".$query."</b>:<hr>";
  if (!$result = mysql_query($query)) {
    echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b>");
  } else {
    if (strtoupper(substr($query,0,6)) == "UPDATE") {
      echo mysql_affected_rows()." records successfully updated.";
    } elseif (strtoupper(substr($query,0,6)) == "DELETE") {
      echo mysql_affected_rows()." records successfully deleted.";
    } elseif (strtoupper(substr($query,0,6)) == "SELECT") {
      $fields = mysql_num_fields($result);
      $rows = mysql_num_rows($result);
      echo "<table border=1 cellspacing=0 cellpadding=2><thead><tr>";
      for ($i=0; $i<$fields; $i++) {
        echo ("<th>".mysql_field_name($result,$i)."</th>");
        if (mysql_field_name($result,$i) == "SongID") $idcol = $i + 1;  //add one so that the first column is non-zero
      }
      echo "</thead><tbody>";
      while ($row_array = mysql_fetch_row($result)) {
        echo "<tr>";
        for ($i=0; $i<$fields; $i++) {
          if ($i == ($idcol - 1)) {
            echo "<td><a href=\"song.php?sid=".db2table($row_array[$i])."\" target=_blank>";
            echo db2table($row_array[$i])."</a></td>";
          } else {
            echo "<td>".db2table($row_array[$i])."</td>";
          }
        }
        echo "</tr>";
      }
    } else {
      echo "Something unknown succeeded - return value ".$result.".";
    }
  }
} else {
  echo "<center><h2><font color=#40A040>Freeform SQL Query</font></h2>";
  echo "<b><font color=#FF0000>Warning! If you do not thoroughly know the SQL language and ".
  "the structure of this database, please do not use this!</font></b><br>&nbsp;<br>";
  echo "<form action=\"sqlquery.php\" method=POST><textarea rows=6 name=\"query\" cols=100></textarea>";
  echo "<br><input type=submit name=submit value=\"Do the Query!\"></form></center>";
}

print_footer();
?>
