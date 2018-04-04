<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once 'include/scripts.php'; ?>
  </head>
  <body>
    <div class="w3-container">
      <button onclick="window.history.go(-1); return false;" class="w3-btn w3-block w3-blue">Go Back!</button>
    </div>
    <div class="w3-panel w3-gray w3-center">
      <h3>Today's feed statistics</h3>
    </div>
    <div class="w3-container">
      <table class="w3-table-all">
        <tr>
          <th>Side</th>
          <th>Duration</th>
          <th>Qty of Fluid (ml)</th>
        </tr>
        <?php //section to display the amount ebm, formula and expressing that's been going on TODAY
        require 'include/sql-connect.php';
        $sql = "CREATE TEMPORARY TABLE feed_today
        SELECT
          (end_time - start_time) AS duration,
          side,
          fluid
        FROM
          feeding_log
        WHERE
          DATE(FROM_UNIXTIME(start_time)) = CURDATE();
        SELECT
          SEC_TO_TIME(SUM(duration)),
          feed_side.side,
          SUM(fluid)
        FROM
          feed_today
        LEFT JOIN
          feed_side ON feed_today.side = feed_side.id
        GROUP BY
          feed_side.side ASC WITH ROLLUP;";

        if ($conn->multi_query($sql)) {
          do {
            /* store first result set */
            if ($result = $conn->store_result()) {
              while ($row = $result->fetch_row()) {
                if ($row[1] == NULL) { $row[4] = "Total"; $highlight = "class='w3-pale-green'"; } else { $highlight = NULL; }
                echo "<tr {$highlight}><td>{$row[1]}</td><td>{$row[0]}</td><td>{$row[2]}</tr></tr>";
              }
              $result->free();
            }
          } while ($conn->next_result());
        }
        ?>
      </table>
    </div>
    <div class="w3-panel w3-gray w3-center">
      <h3>Historical feed statistics</h3>
    </div>
    <div class="w3-container">
      <table class="w3-table-all">
        <tr>
          <th>Date</th>
          <th>Side</th>
          <th>Duration</th>
          <th>Qty of Fluid (ml)</th>
        </tr>
        <?php
        $sql = "CREATE TEMPORARY TABLE feed_by_day
        SELECT
          DAYOFMONTH(FROM_UNIXTIME(start_time)) AS DAY,
          MONTH(FROM_UNIXTIME(start_time)) AS MONTH,
          YEAR(FROM_UNIXTIME(start_time)) AS YEAR,
          (end_time - start_time) AS duration,
          side,
          fluid
        FROM
          feeding_log;
        SELECT
          SEC_TO_TIME(SUM(duration)),
          DAY,
          MONTH,
          YEAR,
          feed_side.side,
          SUM(fluid)
        FROM
          feed_by_day
        LEFT JOIN
          feed_side ON feed_by_day.side = feed_side.id
        GROUP BY
          YEAR ASC,
          MONTH ASC,
          DAY ASC,
          feed_side.side ASC WITH ROLLUP;";

        if ($conn->multi_query($sql)) {
          do {
            /* store first result set */
            if ($result = $conn->store_result()) {
              while ($row = $result->fetch_row()) {
                if ($row[1] == NULL) { $row[4] = "Total"; $highlight = "class='w3-pale-green'"; } else { $highlight = NULL; }
                echo "<tr {$highlight}><td>{$row[1]}/{$row[2]}/$row[3]</td><td>{$row[4]}</td><td>{$row[0]}</td><td>{$row[5]}</tr></tr>";
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
