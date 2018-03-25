<?php
if (isset($_GET['f'])) {
  require 'include/sql-connect.php';
  $hash_rx = $_GET['f'];

  $sql = "DELETE FROM feeding_log WHERE SHA2(id, 256) = '$hash_rx' LIMIT 1";

  if($conn->query($sql) != TRUE) {
    die("Error: $sql <br />" . $conn->error);
  }

  header("Location: /feeding/");
}
?>
