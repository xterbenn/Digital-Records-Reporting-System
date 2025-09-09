<?php
require 'db.php';

$result = $conn->query("SELECT id, name FROM colleges");
$colleges = [];
while ($row = $result->fetch_assoc()) {
  $colleges[] = $row;
}
echo json_encode($colleges);
