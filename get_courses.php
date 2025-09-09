<?php
require 'db.php';

if (isset($_GET['college_id'])) {
  $college_id = intval($_GET['college_id']);
  $stmt = $conn->prepare("SELECT id, name FROM courses WHERE college_id = ?");
  $stmt->bind_param("i", $college_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $courses = [];
  while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
  }
  echo json_encode($courses);
}
