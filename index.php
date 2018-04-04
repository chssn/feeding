<?php
date_default_timezone_set("Europe/London");
if (isset($_POST['finish'])) {
  require 'include/sql-connect.php';

  //get variables
  $side = $_POST['side'];
  $start = $_POST['start_unix'];
  $finish = $_POST['finish_unix'];
  $fluid = $_POST['fluid'];
  $express = $_POST['express'];

  if ($express === 'on') {
    //insert expressed milk into a different table
    $sql = "INSERT INTO express_log (start_time, end_time, qty) VALUES ($start, $finish, $fluid)";
    if ($conn->query($sql) === TRUE) {
        $last_id = $conn->insert_id;
        ?><script>alert('Feed Saved'); window.location.replace("/feeding/");</script><?php
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    	exit;
    }
  }
  if ($side == 2) { //check to see if formula feed
    $sql = "INSERT INTO feeding_log (side, start_time, end_time, fluid, ebm) VALUES ($side, $start, $finish, $fluid, 0)";
  } elseif ($side == 4) { //check to see if expressed breast milk feed
    $sql = "INSERT INTO feeding_log (side, start_time, end_time, fluid, ebm) VALUES ($side, $start, $finish, $fluid, 1)";
  } else {
    $sql = "INSERT INTO feeding_log (side, start_time, end_time) VALUES ($side, $start, $finish)";
  }
  if ($conn->query($sql) === TRUE) {
      $last_id = $conn->insert_id;
      ?><script>alert('Feed Saved'); window.location.replace("/feeding/");</script><?php
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
   <?php
   //check for existance of a start time
   if (isset($_GET['start'])) {
     $start_time = $_GET['start'];
     $diff = time() - $start_time;
     if ($diff > 7200 OR $diff < 0) { //if the start time provided is more than 2 hours ago or a time in the future is provided
       header("Location: /feeding/");
       exit;
     }
     ?><body onload="safariSafe(<?php echo $start_time; ?>)"><?php
   } else {
     ?><body onload="document.getElementById('lastfeed').style.display='block'"><?php
   }
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
     $side = $detail['fluid'] . "ml from a <b>bottle of formula</b>";
   } elseif (($detail['side']) == 4) {
     $side = $detail['fluid'] . "ml from a <b>bottle of breast milk</b>";
   }
   $duration = $detail['end_time'] - $detail['start_time'];

   //get last express information
   $sql = "SELECT * FROM express_log ORDER BY id DESC LIMIT 1";
   $result = $conn->query($sql);
   $dexpress = $result->fetch_assoc();
   $durationexpress = $dexpress['end_time'] - $dexpress['start_time'];
   $timelastexpress = $timenow - $dexpress['end_time'];

   ?>
   <div id="lastfeed" class="w3-modal" style="z-index:100">
    <div class="w3-modal-content">
      <header class="w3-container w3-blue">
        <span onclick="document.getElementById('lastfeed').style.display='none'" class="w3-button w3-display-topright"><i class="fas fa-times-circle fa-2x"></i></span>
        <h2>Prepare to feed...</h2>
      </header>
      <div class="w3-container">
        <p><?php echo "It has been ".gmdate('H:i', $timelastfeed)." since Abigail's last feed. This was at ".date('H:i', $detail['end_time'])." when Abigail was fed $side for ".gmdate('H:i', $duration); ?>.</p>
        <p><?php echo "You last expressed milk ".gmdate('H:i', $timelastexpress)." ago at ".date('H:i', $dexpress['end_time']).". This was for ".gmdate('H:i', $durationexpress); ?>. Don't forget that you only need to put a tick in the "Expressing" box and then record start, finish and amount to log expressed milk.</p>
        <p><i>(Times are in hours and minutes - full statistics available <a href="stats.php">here</a>)</i></p>
      </div>
      <button class="w3-button w3-block w3-blue w3-section w3-padding" onclick="document.getElementById('lastfeed').style.display='none'">Ok</button>
    </div>
  </div>
  <div class="w3-container">
    <button onclick="myFunction('feed_hx')" class="w3-btn w3-block w3-blue"><i class="fas fa-hand-point-right"></i> Feed History (Last 10) <i class="fas fa-hand-point-left"></i></button>
    <div id="feed_hx" class="w3-hide w3-container">
      <p class="w3-center">Full statistics available <a href="stats.php">here</a></p>
      <table class="w3-table-all w3-mobile">
        <tr>
          <th>Side</th>
          <th>Date</th>
          <th>Start</th>
          <th>Finish</th>
          <th>Duration</th>
          <th>Qty</th>
          <th>Delete</th>
        </tr>
        <?php
        //get all feeds in reverse chronological order
        $sql = "SELECT feeding_log.id, start_time, end_time, fluid, feed_side.side FROM feeding_log LEFT JOIN feed_side ON feeding_log.side = feed_side.id ORDER BY feeding_log.id DESC LIMIT 10";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
          $hash = hash('sha256', $row['id']); //sec_key is defined in include/sql-connect.php
          ?>
          <tr>
            <td><?php echo $row['side']; ?></td>
            <td><?php echo date('d/m', $row['start_time']); ?></td>
            <td><?php echo date('H:i', $row['start_time']); ?></td>
            <td><?php echo date('H:i', $row['end_time']); ?></td>
            <td><?php $diff = $row['end_time'] - $row['start_time']; echo gmdate('H:i', $diff); ?></td>
            <td><?php if ($row['fluid'] != NULL) { echo $row['fluid']."ml"; } else { echo "Breast"; }?></td>
            <td><button class="w3-button" onclick="deleteRecord('<?php echo $hash; ?>');"><i class="fas fa-trash-alt"></i></button></td>
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
    <button onclick="startFeed()" class="w3-button w3-green w3-third">Start</button>
    <button onclick="location.href='/feeding/'" class="w3-button w3-blue w3-third">Reset</button>
    <button onclick="stopClock(); endUnix()" class="w3-button w3-red w3-third">Stop</button>
  </div>
  <form class="w3-container w3-card-4 w3-mobile" name="feed" id="feed" action="index.php" method="post">
  <div class="w3-panel w3-pale-blue w3-center">Side</div>
    <table class="w3-table w3-centered">
      <tr>
        <td><i class="fas fa-arrow-left fa-2x w3-text-red"></i></td>
        <td><i class="fas fa-beer fa-2x"></i></td>
        <td><i class="fas fa-female fa-2x"></i></td>
        <td><i class="fas fa-arrow-right fa-2x w3-text-green"></i></td>
      </tr>
      <tr>
        <td><input class="w3-radio" type="radio" id="side1" name="side" value="1" onclick="fluidIntake('hide')" required></td>
        <td><input class="w3-radio" type="radio" id="side2" name="side" value="2" onclick="fluidIntake('show')" required></td>
        <td><input class="w3-radio" type="radio" id="side4" name="side" value="4" onclick="fluidIntake('show')" required></td>
        <td><input class="w3-radio" type="radio" id="side3" name="side" value="3" onclick="fluidIntake('hide')" required></td>
      </tr>
    </table>
    <div class="w3-whole w3-center"><input class="w3-check" type="checkbox" name="express" id="express" onclick="checkExpress()"><label> Expressing</label></div>
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
    <input class="w3-button w3-block w3-green w3-section w3-padding" type="submit" value="Save">
  </form>
  <script src="js/moment.js"></script>
  <script type="text/javascript">
  //section for timer
  var clock = $('.timer').FlipClock({
    clockFace: 'MinuteCounter',
    autoStart: false,
  });
  function safariSafe(start) { //function so that even if safari decides to reload the whole page, the start time will be retained
    var diff = moment().format('X') - start;
    startClock(diff, start);
  }
  function startFeed() {
    var now = moment().format('X')
    location.href='?start=' + now;
  }
  function startClock(diff, start) {
    clock.setTime(diff);
    clock.start();
    var timeStart = moment(start, 'X').format('YYYY-MM-DDTHH:mm:ss');
    document.getElementById('start').value=timeStart;
    startUnix();
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
  //checks if expressing has been checked which will modify the input sql query on submission
  function checkExpress() {
    var express = document.getElementById('express');
    // If the checkbox is checked, display the output text
    if (express.checked == true){
      fluidIntake('show');
      document.getElementById('side1').removeAttribute("required");
      document.getElementById('side2').removeAttribute("required");
      document.getElementById('side3').removeAttribute("required");
      document.getElementById('side4').removeAttribute("required");
    } else {
      fluidIntake('hide');
      document.getElementById('side1').setAttribute("required", true);
      document.getElementById('side2').setAttribute("required", true);
      document.getElementById('side3').setAttribute("required", true);
      document.getElementById('side4').setAttribute("required", true);
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
  //delete confirmation
  function deleteRecord(hash) {
    if (confirm('Are you sure you want to delete this record?')) {
      location.href='remove.php?f=' + hash;
    }
  }
  </script>
</body>
</html>
