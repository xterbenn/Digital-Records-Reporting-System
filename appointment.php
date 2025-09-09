<?php
session_start();
require 'db.php'; // should connect and define $conn

if (!isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['student_id'];

// Get student_number from students table
$stmt = $conn->prepare("SELECT student_number FROM students WHERE id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($student_number);
$stmt->fetch();
$stmt->close();

$user_type = 'student'; // fixed since all users are students in this case
$user_number = $student_number;

$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service = $_POST['service'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $details = $_POST['details'];

    if (strlen($details) > 55) {
        die("Additional details must not exceed 55 characters.");
    }

    $stmt = $conn->prepare("INSERT INTO appointments (user_type, user_number, appointment_date, appointment_time, service, additional_details, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("sssssss", $user_type, $user_number, $appointment_date, $appointment_time, $service, $details, $user_id);
    $success = $stmt->execute();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Appointment - Digital Records and Reporting System</title>
  <style>
    * { box-sizing: border-box; }

    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: url('images/landscape.jpg') no-repeat center center fixed;
      background-size: cover;
      position: relative;
    }

    body::before {
      content: '';
      position: fixed;
      top: 0; left: 0;
      height: 100%; width: 100%;
      background-color: rgba(255, 255, 255, 0.65);
      z-index: -1;
    }

    header {
      background-color: maroon;
      padding: 08px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      color: white;
      position: fixed;
      width: 100%;
      top: 0;
      left: 0;
      z-index: 1000;
    }

    header img { height: 50px; margin-right: 10px; }

    header h1 { font-size: 20px; margin: 0; flex-grow: 1; }

    .menu-toggle {
      font-size: 24px;
      cursor: pointer;
      margin-left: 20px;
    }

    .sidebar {
      position: fixed;
      top: 0; right: -250px;
      width: 250px; height: 100%;
      background-color: #333;
      color: white;
      padding: 60px 20px;
      transition: right 0.3s ease;
      z-index: 999;
    }

    .sidebar.active { right: 0; }

    .sidebar a {
      display: block;
      color: white;
      text-decoration: none;
      margin: 15px 0;
      border-bottom: 1px solid #555;
      padding-bottom: 5px;
    }

    .container {
      max-width: 600px;
      margin: 100px auto 40px auto;
      background-color: white;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    h2 {
      text-align: center;
      color: maroon;
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin-top: 15px;
      color: maroon;
      font-weight: bold;
    }

    select, textarea, input {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }

    textarea { resize: vertical; }

    button {
      margin-top: 20px;
      width: 100%;
      background-color: maroon;
      color: white;
      border: none;
      padding: 12px;
      font-size: 16px;
      border-radius: 5px;
      cursor: pointer;
    }

    button:hover {
      background-color: #800000;
    }

    #confirmationModal {
      display: none;
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background-color: rgba(0,0,0,0.5);
      justify-content: center;
      align-items: center;
      z-index: 9999;
    }

    #confirmationModal .modal-content {
      background: white;
      padding: 20px 30px;
      border-radius: 10px;
      text-align: center;
      box-shadow: 0 0 10px rgba(0,0,0,0.3);
    }

    @media (max-width: 640px) {
      .container {
        margin: 90px 20px 20px 20px;
        padding: 20px;
      }

      header h1 { font-size: 16px; }

      .sidebar { width: 200px; }
    }
  </style>
</head>
<body>

<header>
  <img src="logo/guidance_office_logo.png" alt="Guidance Office Logo" />
  <h1>Digital Records and Reporting System</h1>
  <div class="menu-toggle" onclick="toggleSidebar()">☰</div>
</header>

<div class="sidebar" id="sidebar">
  <a href="dashboard.php">Dashboard</a>
  <a href="student_info.php">Student Info</a>
  <a href="schedule.php">Appointment Schedule Availability</a>
  <a href="logout.php">Log Out</a>
</div>

<div class="container">
  <h2>Schedule Appointment</h2>
  <form method="POST">
    <label for="service">Service</label>
    <select id="service" name="service" required>
      <option value="" disabled selected>Select a service</option>
      <option value="Counseling">Counseling</option>
      <option value="Follow-up">Follow-up</option>
      <option value="Consultation">Consultation</option>
      <option value="Others">Others</option>
    </select>

    <label for="appointment_date">Appointment Date</label>
    <input type="date" id="appointment_date" name="appointment_date" required />

    <label for="appointment_time">Appointment Time</label>
    <select id="appointment_time" name="appointment_time" required>
      <option value="" disabled selected>-- Select time between 8:00 AM - 4:00 PM (12 PM excluded) --</option>
      <?php
        for ($hour = 8; $hour <= 16; $hour++) {
            if ($hour == 12) continue; // Skip 12 PM
            $time = sprintf("%02d:00", $hour);
            $display = date("g:i A", strtotime($time));
            echo "<option value='$time'>$display</option>";
        }
      ?>
    </select>

    <label for="details">Additional Details (optional, max 55 characters)</label>
    <textarea id="details" name="details" rows="3" maxlength="55" placeholder="Enter up to 55 characters"></textarea>
    <p id="charCount" style="font-size:12px; color:gray;">0/55 characters used</p>

    <button type="submit">Submit</button>
  </form>
</div>

<?php if ($success): ?>
  <div id="confirmationModal" style="display: flex;">
    <div class="modal-content">
      <h3 style="color: maroon;">✅ Appointment Submitted</h3>
      <p>Your request has been received. Please wait for confirmation.</p>
      <button onclick="closeModal()">OK</button>
    </div>
  </div>
<?php endif; ?>

<script>
  function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
  }

  function closeModal() {
    document.getElementById("confirmationModal").style.display = "none";
  }

  // Set min date to tomorrow
  window.addEventListener("DOMContentLoaded", function() {
    const dateInput = document.getElementById("appointment_date");
    const today = new Date();
    today.setDate(today.getDate() + 1); // tomorrow
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    dateInput.min = `${yyyy}-${mm}-${dd}`;

    // Character counter for Additional Details
    const details = document.getElementById("details");
    const charCount = document.getElementById("charCount");
    details.addEventListener("input", function() {
      charCount.textContent = details.value.length + "/55 characters used";
    });
  });
</script>

</body>
</html>
