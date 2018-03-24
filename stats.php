<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once 'include/scripts.php'; ?>
  </head>
  <body>
    <div class="w3-container">
      <button onclick="window.history.go(-1); return false;" class="w3-btn w3-block w3-blue">Go Back!</button>
      <table class="w3-table-all">
        <tr>
          <th>Date</th>
          <th>Side</th>
          <th>Feed Duration</th>
        </tr>
        <?php
        require 'include/sql-connect.php';
        $sql = "CREATE TEMPORARY TABLE feed_by_day SELECT dayofmonth(from_unixtime(start_time)) AS day, month(from_unixtime(start_time)) AS month, year(from_unixtime(start_time)) AS year, (end_time-start_time) AS duration, side FROM `feeding_log`; SELECT SEC_TO_TIME(SUM(duration)), day, month, year, feed_side.side FROM feed_by_day LEFT JOIN feed_side ON feed_by_day.side = feed_side.id GROUP BY year ASC, month ASC, day ASC, feed_side.side ASC WITH ROLLUP;";

        if ($conn->multi_query($sql)) {
          do {
            /* store first result set */
            if ($result = $conn->store_result()) {
              while ($row = $result->fetch_row()) {
                if ($row[4] == NULL) { $row[4] = "Total"; $highlight = "class='w3-pale-green'"; } else { $highlight = NULL; }
                echo "<tr {$highlight}><td>{$row[1]}/{$row[2]}/$row[3]</td><td>{$row[4]}</td><td>{$row[0]}</td></tr>";
              }
              $result->free();
            }
          } while ($conn->next_result());
        }
        $conn->close();
        ?>
      </table>
      <?php echo $sql; ?>
  </div>
  </body>
</html>
