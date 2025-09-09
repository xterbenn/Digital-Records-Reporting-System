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

// Get appointment ID
if (!isset($_GET['appointment_id']) || !is_numeric($_GET['appointment_id'])) {
    die("Invalid appointment ID.");
}
$appointmentId = intval($_GET['appointment_id']);

// Fetch appointment and student full name
$appt_stmt = $conn->prepare("
    SELECT a.*, s.full_name 
    FROM appointments a 
    JOIN students s ON a.user_id = s.id 
    WHERE a.id=? AND a.user_id=?
");
$appt_stmt->bind_param("ii", $appointmentId, $studentId);
$appt_stmt->execute();
$result = $appt_stmt->get_result();
if ($result->num_rows === 0) {
    die("Appointment not found or permission denied.");
}
$appointment = $result->fetch_assoc();
$appt_stmt->close();

// Check copy request approval
$cr_stmt = $conn->prepare("SELECT status FROM copy_requests WHERE student_id=? AND appointment_id=?");
$cr_stmt->bind_param("ii", $studentId, $appointmentId);
$cr_stmt->execute();
$cr_stmt->bind_result($copy_status);
$cr_stmt->fetch();
$cr_stmt->close();

if ($copy_status !== 'approved') {
    die("Copy request not approved.");
}

// Fetch appointment images
$img_stmt = $conn->prepare("SELECT image_path FROM appointment_images WHERE appointment_id=?");
$img_stmt->bind_param("i", $appointmentId);
$img_stmt->execute();
$img_result = $img_stmt->get_result();
$images = [];
while ($row = $img_result->fetch_assoc()) {
    $images[] = basename($row['image_path']);
}
$img_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>View Appointment</title>
<style>
* { box-sizing: border-box; margin:0; padding:0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;}
body { background-color: #f9f9f9; }
header { display:flex; justify-content: space-between; align-items:center; padding:15px 30px; background-color:maroon; color:white; position:fixed; width:100%; top:0; left:0; z-index:1000; flex-wrap:wrap; }
header img { height:65px; }
.logo-text { font-size:18px; flex:1; text-align:center; padding:0 10px; }
.menu-toggle { font-size:28px; cursor:pointer; }
.sidebar { position: fixed; top:90px; right:-250px; height:calc(100% - 90px); width:250px; background-color:#333; color:white; padding:20px; transition:right 0.3s ease; z-index:999; }
.sidebar.active { right:0; }
.sidebar a { display:block; color:white; padding:12px 0; text-decoration:none; border-bottom:1px solid #555; }
.sidebar a:hover { background-color:#444; }
main { margin-top:130px; padding:20px; }
.container { max-width:900px; margin:0 auto; background:white; padding:30px; border-radius:10px; box-shadow:0 8px 20px rgba(0,0,0,0.1);}
h2 { color: maroon; margin-bottom:20px; text-align:center; }
table { width:100%; border-collapse:collapse; margin-top:20px;}
th, td { padding:15px; text-align:left; border-bottom:1px solid #ddd;}
tr:hover { background-color:#FFD700; }
.appointment-images img { max-width:100%; height:auto; margin-bottom:15px; border:1px solid #ccc; border-radius:5px; }
.btn { display:inline-block; margin-top:20px; text-decoration:none; color:white; background:maroon; padding:10px 20px; border-radius:5px; border:none; cursor:pointer; transition:0.3s; }
.btn:hover { background:#a00000; }

@media (max-width:768px) {
    header img { height:50px; }
    .logo-text { font-size:14px; text-align:center; }
    table, thead, tbody, th, td, tr { display:block; }
    thead { display:none; }
    tr { margin-bottom:15px; background:white; border-radius:8px; padding:10px; box-shadow:0 2px 6px rgba(0,0,0,0.05);}
    td { padding:10px; text-align:right; position:relative; }
    td::before { content: attr(data-label); position:absolute; left:10px; width:50%; font-weight:bold; text-align:left; }
}

.appointment-images img { max-width:100%; width:auto; height:auto; max-height:400px; margin-bottom:15px; border:1px solid #ccc; border-radius:5px; display:block; object-fit:contain; }

/* Print */
@media print {
    .sidebar, header, .btn { display: none; }
    body { background:white; }
    main { margin:0; padding:0; }
    .appointment-images img { max-width:100%; height:auto; page-break-inside:avoid; }
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
    <a href="dashboard.php">Dashboard</a>
    <a href="appointment.php">Book Appointment</a>
    <a href="student_info.php">Student Info</a>
    <a href="schedule.php">Appointment Schedule Availability</a>
    <a href="logout.php">Log Out</a>
</div>

<main>
<div class="container">
    <h2>Appointment Details</h2>
    <p><strong>Student Name:</strong> <?= htmlspecialchars($appointment['full_name']) ?></p>

    <table>
        <tr><th>Date</th><td data-label="Date"><?= htmlspecialchars($appointment['appointment_date']) ?></td></tr>
        <tr><th>Time</th><td data-label="Time"><?= htmlspecialchars($appointment['appointment_time']) ?></td></tr>
        <tr><th>Service</th><td data-label="Service"><?= htmlspecialchars($appointment['service']) ?></td></tr>
        <tr><th>Status</th><td data-label="Status"><?= htmlspecialchars($appointment['status']) ?></td></tr>
        <tr><th>Created At</th><td data-label="Created At"><?= htmlspecialchars($appointment['created_at']) ?></td></tr>
        <tr><th>Additional Details</th><td data-label="Additional Details"><?= nl2br(htmlspecialchars($appointment['additional_details'])) ?></td></tr>
    </table>

    <?php if(!empty($images)): ?>
    <div class="appointment-images">
        <h3>Appointment Images</h3>
        <?php foreach($images as $img_file): ?>
            <img src="report_images/<?= htmlspecialchars($img_file) ?>" alt="Appointment Image">
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <button class="btn" onclick="window.print()">Print to PDF</button>
    <a class="btn" href="dashboard.php">Back to Dashboard</a>
</div>
</main>

<script>
function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("active");
}
</script>

</body>
</html>
