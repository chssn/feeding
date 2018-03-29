<?php date_default_timezone_set("Europe/London"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include_once 'include/scripts.php'; require 'include/sql-connect.php'; ?>
  <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
</head>
<body>
  <div id="chart_div"></div>
  <script>
  google.charts.load('current', {packages: ['corechart', 'bar']});
  google.charts.setOnLoadCallback(drawMultSeries);

  function drawMultSeries() {
    var data = new google.visualization.DataTable();
    data.addColumn('timeofday', 'Time of Day');
    data.addColumn('number', 'Number of Feeds');
    data.addRows([
    <?php
    $sql = "SELECT HOUR(FROM_UNIXTIME(start_time)) AS time_of_day, COUNT(id) AS hour_count FROM `feeding_log` WHERE 1 GROUP BY time_of_day ORDER BY time_of_day";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
      // output data of each row
      while($row = $result->fetch_assoc()) {
        if ($row['time_of_day'] == 0) {
          echo "[[0], ".$row['hour_count']."],";
        } else {
          echo "[[".$row['time_of_day'].",0,0], ".$row['hour_count']."],";
        }
      }
    }
    ?>
    ]);

    var options = {
      title: 'Feeds per Hour',
      hAxis: {
        title: 'Time of Day',
      },
      vAxis: {
        title: 'Number of Feeds'
      }
    };

    var chart = new google.visualization.ColumnChart(
      document.getElementById('chart_div'));

    chart.draw(data, options);
  }
  </script>
</body>
</html>
