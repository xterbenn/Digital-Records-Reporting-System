<?php
session_start();

if (!isset($_SESSION['student_id'])) {
  header("Location: index.php");
  exit();
}

$studentId = $_SESSION['student_id'];

$conn = new mysqli("localhost", "root", "", "dr_reporting_system");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Fetch student number (not used but kept)
$student_query = $conn->prepare("SELECT student_number FROM students WHERE id = ?");
$student_query->bind_param("i", $studentId);
$student_query->execute();
$student_query->bind_result($student_number);
$student_query->fetch();
$student_query->close();

// Handle request copy submission
if (isset($_POST['request_copy'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $insert_stmt = $conn->prepare("INSERT INTO copy_requests (student_id, appointment_id) VALUES (?, ?)");
    $insert_stmt->bind_param("ii", $studentId, $appointment_id);
    $insert_stmt->execute();
    $insert_stmt->close();
    // Refresh to show updated button
    header("Location: dashboard.php");
    exit();
}

// Fetch appointments
$appt_stmt = $conn->prepare("SELECT * FROM appointments WHERE user_id = ? ORDER BY appointment_date DESC, appointment_time DESC");
$appt_stmt->bind_param("i", $studentId);
$appt_stmt->execute();
$result = $appt_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    body { background-color: #f9f9f9; }
    header { display: flex; justify-content: space-between; align-items: center; padding: 18px 30px; background-color: maroon; color: white; position: fixed; width: 100%; top: 0; left: 0; z-index: 1000; flex-wrap: wrap; }
    header img { height: 65px; }
    .logo-text { font-size: 18px; flex: 1; text-align: center; padding: 0 10px; }
    .menu-toggle { font-size: 28px; cursor: pointer; }
    .sidebar { position: fixed; top: 90px; right: -250px; height: calc(100% - 90px); width: 250px; background-color: #333; color: white; padding: 20px; transition: right 0.3s ease; z-index: 999; }
    .sidebar.active { right: 0; }
    .sidebar a { display: block; color: white; padding: 12px 0; text-decoration: none; border-bottom: 1px solid #555; }
    .sidebar a:hover { background-color: #444; }
    main { margin-top: 130px; padding: 20px; }
    .appointment-section { background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
    .appointment-section h2 { margin-bottom: 15px; font-size: 24px; color: maroon; }
    table { width: 100%; border-collapse: collapse; border-spacing: 0; }
    thead { background-color: #f0f0f0; }
    th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ccc; }
    tr:hover { background-color: #FFD700; }
    .no-appointments { padding: 20px; color: #666; font-size: 18px; }
    button { cursor: pointer; padding: 5px 10px; border: none; border-radius: 4px; background-color: maroon; color: white; }
    button:disabled { background-color: grey; cursor: not-allowed; }

    /* Mobile styles */
    @media (max-width: 768px) {
      table, thead, tbody, th, td, tr { display: block; }
      thead { display: none; }
      tr { background: white; margin-bottom: 15px; border-radius: 8px; padding: 10px; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05); }
      td { padding: 10px; text-align: right; position: relative; }
      td::before { content: attr(data-label); position: absolute; left: 10px; width: 50%; font-weight: bold; text-align: left; }
      header img { height: 50px; }
      .logo-text { font-size: 14px; text-align: center; }
      .menu-toggle { font-size: 24px; }
    }
  </style>
</head>
<body>

<header>
  <img src="logo/guidance_office_logo.png" alt="Logo" />
  <h2 class="logo-text">Digital Records & Reporting System For the Guidance Office Of ZPPSU</h2>
  <div class="menu-toggle" onclick="toggleSidebar()">☰</div>
</header>

<div class="sidebar" id="sidebar">
  <a href="appointment.php">Book Appointment</a>
  <a href="student_info.php">Student Info</a>
  <a href="schedule.php">Appointment Schedule Availability</a>
  <a href="logout.php">Log Out</a>
</div>

<main>
  <div class="appointment-section">
    <h2>Your Appointments</h2>

    <?php if ($result && $result->num_rows > 0): ?>
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Time</th>
            <th>Service</th>
            <th>Status</th>
            <th>Created</th>
            <th>Request Copy</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td data-label="Date"><?= htmlspecialchars($row['appointment_date']) ?></td>
              <td data-label="Time"><?= htmlspecialchars($row['appointment_time']) ?></td>
              <td data-label="Service"><?= htmlspecialchars($row['service']) ?></td>
              <td data-label="Status"><?= htmlspecialchars($row['status']) ?></td>
              <td data-label="Created"><?= htmlspecialchars($row['created_at']) ?></td>
              <td data-label="Request Copy">
                <?php
                  // Check copy_requests table for this appointment
                  $cr_stmt = $conn->prepare("SELECT status FROM copy_requests WHERE student_id=? AND appointment_id=?");
                  $cr_stmt->bind_param("ii", $studentId, $row['id']);
                  $cr_stmt->execute();
                  $cr_stmt->bind_result($copy_status);
                  $cr_stmt->fetch();
                  $cr_stmt->close();

                  if (empty($copy_status)) {
                      echo '<form method="post">
                              <input type="hidden" name="appointment_id" value="'.$row['id'].'">
                              <button type="submit" name="request_copy">Request Copy</button>
                            </form>';
                  } elseif ($copy_status == 'approved') {
                      echo '<form action="view_appointment.php" method="get">
                              <input type="hidden" name="appointment_id" value="'.$row['id'].'">
                              <button type="submit">View Details</button>
                            </form>';
                  } elseif ($copy_status == 'pending') {
                      echo '<button disabled>Pending</button>';
                  } elseif ($copy_status == 'rejected') {
                      echo '<button disabled>Rejected</button>';
                  }
                ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="no-appointments">You have no appointments scheduled.</div>
    <?php endif; ?>
  </div>
</main>

<script>
  function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("active");
  }
</script>

</body>
</html>
