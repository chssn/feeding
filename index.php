<?php
date_default_timezone_set("Europe/London");
if (isset($_POST['finish'])) {
  require 'include/sql-connect.php';

  //get variables
  $side = $_POST['side'];
  $start = $_POST['start_unix'];
  $finish = $_POST['finish_unix'];
  $fluid = $_POST['fluid'];

  //check to see if bottle feed
  if ($side == 2) {
    $sql = "INSERT INTO feeding_log (side, start_time, end_time, fluid) VALUES ($side, $start, $finish, $fluid)";
  } else {
    $sql = "INSERT INTO feeding_log (side, start_time, end_time) VALUES ($side, $start, $finish)";
  }
  if ($conn->query($sql) === TRUE) {
      $last_id = $conn->insert_id;
      $send = base64_encode($key . "-" . time());
      ?><script>alert('Feed Saved'); window.location.replace("?nopop");</script><?php
  } else {
      echo "Error: " . $sql . "<br>" . $conn->error;
  	exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include_once 'include/scripts.php'; ?>
  <link rel="stylesheet" href="css/flipclock.css">
  <script src="js/flipclock.js"></script>
</head>
<body onload="document.getElementById('lastfeed').style.display='block'">
   <?php
   //no popup on reloads
   /*if (!isset($_GET['nopop'])) {
     echo ?><body onload="document.getElementById('lastfeed').style.display='block'"><?php ;
   } else {
      echo "<body>";
   }*/
   //get last feed information
   require 'include/sql-connect.php';
   $sql = "SELECT * FROM feeding_log ORDER BY id DESC LIMIT 1";
   $result = $conn->query($sql);
   $detail = $result->fetch_assoc();
   $timenow = time();
   $timelastfeed = $timenow - $detail['end_time'];
   if (($detail['side']) == 1) {
     $side = "from the <b>left</b> side";
   } elseif (($detail['side']) == 3) {
     $side = "from the <b>right</b> side";
   } elseif (($detail['side']) == 2) {
     $side = $detail['fluid'] . "ml from a <b>bottle</b>";
   }
   $duration = $detail['end_time'] - $detail['start_time'];
   ?>
   <div id="lastfeed" class="w3-modal" style="z-index:100">
    <div class="w3-modal-content">
      <header class="w3-container w3-blue">
        <span onclick="document.getElementById('lastfeed').style.display='none'" class="w3-button w3-display-topright"><i class="fas fa-times-circle fa-2x"></i></span>
        <h2>Prepare to feed...</h2>
      </header>
      <div class="w3-container">
        <p><?php echo "It has been ".gmdate('H:i', $timelastfeed)." since the last feed. This was at ".date('H:i', $detail['end_time'])." when Abigail was fed $side for ".gmdate('H:i', $duration); ?></p>
        <p><i>(Times are in hours and minutes)</i></p>
      </div>
      <button class="w3-button w3-block w3-blue w3-section w3-padding" onclick="document.getElementById('lastfeed').style.display='none'">Ok</button>
    </div>
  </div>
  <div class="w3-container">
    <button onclick="myFunction('feed_hx')" class="w3-btn w3-block w3-blue"><i class="fas fa-hand-point-right"></i> Feed History (Last 10) <i class="fas fa-hand-point-left"></i></button>
    <div id="feed_hx" class="w3-hide w3-container">
      <table class="w3-table-all w3-mobile">
        <tr>
          <th>Side</th>
          <th>Date</th>
          <th>Start</th>
          <th>Finish</th>
          <th>Duration</th>
          <th>Qty</th>
        </tr>
        <?php
        //get all feeds in reverse chronological order
        $sql = "SELECT start_time, end_time, fluid, feed_side.side FROM feeding_log LEFT JOIN feed_side ON feeding_log.side = feed_side.id ORDER BY feeding_log.id DESC LIMIT 10";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
          ?>
          <tr>
            <td><?php echo $row['side']; ?></td>
            <td><?php echo date('d/m', $row['start_time']); ?></td>
            <td><?php echo date('H:i', $row['start_time']); ?></td>
            <td><?php echo date('H:i', $row['end_time']); ?></td>
            <td><?php $diff = $row['end_time'] - $row['start_time']; echo gmdate('H:i', $diff); ?></td>
            <td><?php if ($row['fluid'] != NULL) { echo $row['fluid']."ml"; } else { echo "Breast"; }?></td>
          </tr>
          <?php
        }
        ?>
      </table>
    </div>
  </div>
  <div class="w3-container w3-center">
    <br />
    <div class="timer" style="display: inline-block; width: auto;"></div>
  </div>
  <div class="w3-container w3-center">
    <button onclick="startClock(); startUnix()" class="w3-button w3-green w3-third">Start Feed Now</button>
    <button onclick="location.reload()" class="w3-button w3-blue w3-third">Reset Page</button>
    <button onclick="stopClock(); endUnix()" class="w3-button w3-red w3-third">Stop Feed</button>
  </div>
  <form class="w3-container w3-card-4 w3-mobile" name="feed" id="feed" action="index.php" method="post">
    <div class="w3-panel w3-pale-blue w3-center">Side</div>
    <table class="w3-table w3-centered">
      <tr><td colspan="5"><i class="fas fa-beer fa-2x"></i></td></tr>
      <tr>
        <td style="width:20%"><i class="fas fa-arrow-left fa-2x w3-text-red"></i></td>
        <td style="width:20%"><input class="w3-radio" type="radio" name="side" value="1" onclick="fluidIntake('hide')" required></td>
        <td style="width:20%"><input class="w3-radio" type="radio" name="side" value="2" onclick="fluidIntake('show')" required></td>
        <td style="width:20%"><input class="w3-radio" type="radio" name="side" value="3" onclick="fluidIntake('hide')" required></td>
        <td style="width:20%"><i class="fas fa-arrow-right fa-2x w3-text-green"></i></td>
      </tr>
    </table>
    <div class="w3-panel w3-pale-green w3-center">Start Time</div>
    <input class="w3-input" type="datetime-local" step="1" name="start" id="start" onchange="startUnix()" required>
    <div class="w3-panel w3-pale-red w3-center">Finish Time</div>
    <input class="w3-input" type="datetime-local" step="1" name="finish" id="finish" onchange="endUnix()" required>
    <input hidden type="number" name="start_unix" id="start_unix">
    <input hidden type="number" name="finish_unix" id="finish_unix">
    <div id="bottle_feed" style="display:none">
      <div class="w3-panel w3-pale-blue w3-center">Bottle Feed Amount (ml)</div>
      <input class="w3-input" type="number" name="fluid" id="fluid" min="0" max="500" step="10">
    </div>
    <input class="w3-button w3-block w3-green w3-section w3-padding" type="submit" value="Save Feed">
  </form>
  <div id="duration"></div>
  <script src="js/moment.js"></script>
  <script type="text/javascript">
  //section for timer
  var clock = $('.timer').FlipClock({
    clockFace: 'MinuteCounter',
    autoStart: false,
  });
  function startClock() {
    clock.setTime(0);
    clock.start();
    var timeStart = moment().format('YYYY-MM-DDTHH:mm:ss');
    document.getElementById('start').value=timeStart;
  }
  function stopClock() {
    clock.stop();
    var timeStop = moment().format('YYYY-MM-DDTHH:mm:ss');
    document.getElementById('finish').value=timeStop;
  }
  //function to convert HTML time to unix timestamp amd enter it into hidden fields
  function startUnix() {
    var time = document.getElementById('start').value;
    var unix = moment(time, moment.HTML5_FMT.DATETIME_LOCAL_SECONDS).format('X');
    document.getElementById('start_unix').value = unix;
  }
  function endUnix() {
    var time = document.getElementById('finish').value;
    var unix = moment(time, moment.HTML5_FMT.DATETIME_LOCAL_SECONDS).format('X');
    document.getElementById('finish_unix').value = unix;
  }
  //function to hide or display fluid intake fields
  function fluidIntake(input) {
    //define the location of the field
    var feedInput = document.getElementById('bottle_feed');
    if (input === 'show') {
      feedInput.style.display = 'block';
      document.getElementById('fluid').setAttribute("required", true);
    } else if (input === 'hide') {
      feedInput.style.display = 'none';
      document.getElementById('fluid').removeAttribute("required");
    }
  }
  //function for accordion button
  function myFunction(id) {
    var x = document.getElementById(id);
    if (x.className.indexOf("w3-show") == -1) {
        x.className += " w3-show";
    } else {
        x.className = x.className.replace(" w3-show", "");
    }
  }
  </script>
</body>
</html>
