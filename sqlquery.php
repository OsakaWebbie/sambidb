<?php
 include("functions.php");
 include("accesscontrol.php");

pageheader(_('SQL Query'), 1);
$query = !empty($_POST['query']) ? $_POST['query'] : '';
?>

  <h1>Freeform SQL Query</h1>
  <?=(empty($query) ? '<p style="font-weight:bold">Warning! If you do not thoroughly know the SQL language and the structure of this database, please do not use this!</p>' : '')?>
  <form action="sqlquery.php" method="POST">
    <textarea name="query" style='height:4em; width:100%; margin:15px 0;'><?=$query?></textarea>
    <input type="submit" name="submit" class="ui-button ui-corner-all" value="Do this query!">
  </form>

<?php
if (!empty($query)) {
  echo "<hr><h2>Results:</h2>";
  echo "<p class='comment'>$query</p>";
  if (!$result = mysqli_query($db,$query)) {
    echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b>");
  } else {
    if (strtoupper(substr($query,0,6)) == "UPDATE") {
      echo mysqli_affected_rows($db)." records successfully updated.";
    } elseif (strtoupper(substr($query,0,6)) == "DELETE") {
      echo mysqli_affected_rows($db)." records successfully deleted.";
    } elseif (strtoupper(substr($query,0,6)) == "SELECT") {
      $fields = mysqli_num_fields($result);
      if (mysqli_num_rows($result) == 0) {
        echo "<p>No results.</p>";
      } else {
        echo "<table><thead><tr>";
        $idcol = 0;
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
      }
    } else {
      echo "Something unknown succeeded - return value ".$result.".";
    }
  }
}

footer();
?>
