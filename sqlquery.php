<?php
 include("functions.php");
 include("accesscontrol.php");

print_header("SQL Query","#FFFFFF",1);

if ($_POST['query']) {
  $query = stripslashes($_POST['query']);
  echo "<h2>Results of this query:</h2>";
  echo "<form action=\"sqlquery.php\" method=POST><textarea rows=4 name=\"query\" cols=100>$query</textarea>";
  echo "<br><input type=submit name=submit value=\"Do this new (modified) query!\"></form>";

  echo "Results of query <b>".$query."</b>:<hr>";
  if (!$result = mysqli_query($db,$query)) {
    echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b>");
  } else {
    if (strtoupper(substr($query,0,6)) == "UPDATE") {
      echo mysqli_affected_rows($db)." records successfully updated.";
    } elseif (strtoupper(substr($query,0,6)) == "DELETE") {
      echo mysqli_affected_rows($db)." records successfully deleted.";
    } elseif (strtoupper(substr($query,0,6)) == "SELECT") {
      $fields = mysqli_num_fields($result);
      $rows = mysqli_num_rows($result);
      echo "<table border=1 cellspacing=0 cellpadding=2><thead><tr>";
      for ($i=0; $i<$fields; $i++) {
        echo ("<th>".mysqli_fetch_field_direct($result, $i)->name."</th>");
        if (mysqli_fetch_field_direct($result, $i)->name == "SongID") $idcol = $i + 1;  //add one so that the first column is non-zero
      }
      echo "</thead><tbody>";
      while ($row_array = mysqli_fetch_row($result)) {
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
  echo "<h2>Freeform SQL Query</h2>";
  echo "<b>Warning! If you do not thoroughly know the SQL language and ".
  "the structure of this database, please do not use this!</b><br>&nbsp;<br>";
  echo "<form action=\"sqlquery.php\" method=POST><textarea rows=6 name=\"query\" cols=100></textarea>";
  echo "<br><input type=submit name=submit value=\"Do the Query!\"></form>";
}

print_footer();
?>
