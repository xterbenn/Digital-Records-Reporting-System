<?php
date_default_timezone_set('Asia/Manila');
$mysqli = new mysqli("localhost", "root", "", "dr_reporting_system");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$currentMonth = date('n');
$currentYear = date('Y');

$month = isset($_GET['month']) ? (int)$_GET['month'] : $currentMonth;
$year = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;

if ($year < 2025 || ($year == 2025 && $month < 6)) {
    $year = 2025;
    $month = 6;
}

if ($month > 12) {
    $month = 1;
    $year++;
} elseif ($month < 1) {
    $month = 12;
    $year--;
}

$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDay);
$startDay = date('w', $firstDay);
$monthName = date('F', $firstDay);

$prevMonth = $month - 1;
$prevYear = $year;
$nextMonth = $month + 1;
$nextYear = $year;

if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

$prevMonthDays = date('t', mktime(0, 0, 0, $prevMonth, 1, $prevYear));

// Fetch booked dates using appointment_date
$startDate = "$year-" . str_pad($month, 2, "0", STR_PAD_LEFT) . "-01";
$endDate = "$year-" . str_pad($month, 2, "0", STR_PAD_LEFT) . "-$daysInMonth";

$result = $mysqli->query("SELECT appointment_date, status FROM appointments WHERE appointment_date BETWEEN '$startDate' AND '$endDate'");

$bookedDates = [];
while ($row = $result->fetch_assoc()) {
    if ($row['status'] !== 'rejected') {
        $d = date('j', strtotime($row['appointment_date']));
        $bookedDates[] = (int)$d;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Schedule Availability</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; background-color: #f5f5f5; }
    header {
      background-color: maroon;
      color: white;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 20px;
      position: sticky;
      top: 0;
      z-index: 1000;
    }
    header img { height: 40px; }
    .menu-toggle { font-size: 24px; cursor: pointer; }
    .sidebar {
      position: fixed;
      top: 60px;
      right: -250px;
      width: 250px;
      height: calc(100% - 60px);
      background-color: #333;
      padding-top: 20px;
      transition: right 0.3s;
      z-index: 999;
    }
    .sidebar a {
      display: block;
      padding: 12px 20px;
      color: white;
      text-decoration: none;
      border-bottom: 1px solid #444;
    }
    .sidebar a:hover { background-color: #555; }
    .sidebar.show { right: 0; }

    .main-content {
      padding: 20px;
      background-color: white;
      min-height: 100vh;
    }

    .calendar-container {
      max-width: 1000px;
      margin: 0 auto;
      animation: fadeIn 0.4s ease-in-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    h2 { text-align: center; margin-bottom: 15px; }
    .nav-buttons {
      text-align: center;
      margin-bottom: 20px;
    }
    .nav-buttons a {
      display: inline-block;
      background-color: maroon;
      color: white;
      padding: 10px 20px;
      margin: 0 10px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: bold;
      transition: background-color 0.3s, transform 0.2s;
    }
    .nav-buttons a:hover {
      background-color: #a40000;
      transform: scale(1.05);
    }
    .calendar {
      width: 100%;
      border: 1px solid #ccc;
      border-collapse: collapse;
      font-size: 16px;
    }
    .calendar th, .calendar td {
      border: 1px solid #ccc;
      width: 14.28%;
      height: 100px;
      text-align: left;
      vertical-align: top;
      padding: 5px;
    }
    .calendar th { background-color: #eee; text-align: center; }
    .weekday { background-color: pink; }
    .dimmed { color: #999; font-size: 0.8em; }
    .note {
      margin-top: 15px;
      font-style: italic;
      font-size: 14px;
      text-align: center;
    }
    .note span {
      display: inline-block;
      width: 12px;
      height: 12px;
      background-color: pink;
      margin-right: 5px;
      border: 1px solid #aaa;
      vertical-align: middle;
    }
    @media screen and (max-width: 600px) {
      header img { height: 30px; }
      .calendar th, .calendar td {
        height: 80px;
        font-size: 12px;
        padding: 5px;
      }
      .nav-buttons a {
        padding: 8px 15px;
        font-size: 14px;
      }
    }
  </style>
</head>
<body>

<header>
  <img src="logo/guidance_office_logo.png" alt="Logo" />
  <div class="menu-toggle" onclick="toggleSidebar()">☰</div>
</header>

<div class="sidebar" id="sidebar">
  <a href="dashboard.php">Dashboard</a>
  <a href="appointment.php">Book Appointment</a>
  <a href="student_info.php">Student Info</a>
  <a href="index.php">Log Out</a>
</div>

<div class="main-content">
  <div class="calendar-container">
    <h2>Appointment Schedule Availability — <?= $monthName . " " . $year ?></h2>

    <div class="nav-buttons">
      <?php if (!($prevYear < 2025 || ($prevYear == 2025 && $prevMonth < 6))): ?>
        <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>">&larr; Previous</a>
      <?php endif; ?>
      <a href="?month=<?= $currentMonth ?>&year=<?= $currentYear ?>">Today</a>
      <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>">Next &rarr;</a>
    </div>

    <table class="calendar">
      <div class="note">
        <span></span> Available slots for appointment are shown on pink-filled (Monday–Friday) dates.
      </div>
      <tr>
        <th>Sun</th>
        <th>Mon</th>
        <th>Tue</th>
        <th>Wed</th>
        <th>Thu</th>
        <th>Fri</th>
        <th>Sat</th>
      </tr>
      <tr>
        <?php
        $day = 1;
        $cell = 0;

        // Previous month's tail days
        $prevMonthDayStart = $prevMonthDays - $startDay + 1;
        for ($i = 0; $i < $startDay; $i++) {
          $weekday = $cell % 7;
          $class = ($weekday >= 1 && $weekday <= 5) ? 'weekday dimmed' : 'dimmed';
          $prevMonthLabel = date('M', mktime(0, 0, 0, $prevMonth, 1, $prevYear));
          echo "<td class='{$class}'>{$prevMonthDayStart}<br><small>{$prevMonthLabel}</small></td>";
          $prevMonthDayStart++;
          $cell++;
        }

        // Current month
        while ($day <= $daysInMonth) {
          $weekday = $cell % 7;
          $isWeekday = ($weekday >= 1 && $weekday <= 5);
          $class = ($isWeekday && !in_array($day, $bookedDates)) ? 'weekday' : '';
          echo "<td class='{$class}'>{$day}</td>";
          $day++;
          $cell++;
          if ($cell % 7 === 0 && $day <= $daysInMonth) echo "</tr><tr>";
        }

        // Next month's start days
        $nextDay = 1;
        while ($cell % 7 !== 0) {
          $weekday = $cell % 7;
          $class = ($weekday >= 1 && $weekday <= 5) ? 'weekday dimmed' : 'dimmed';
          $nextMonthLabel = date('M', mktime(0, 0, 0, $nextMonth, 1, $nextYear));
          echo "<td class='{$class}'>{$nextDay}<br><small>{$nextMonthLabel}</small></td>";
          $nextDay++;
          $cell++;
        }
        ?>
      </tr>
    </table>
  </div>
</div>

<script>
  function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("show");
  }
</script>

</body>
</html>
