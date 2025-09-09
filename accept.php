<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Handle approval action
if (isset($_GET['approve']) && is_numeric($_GET['approve'])) {
    $request_id = intval($_GET['approve']);
    $update_stmt = $conn->prepare("UPDATE copy_requests SET status='approved' WHERE id=?");
    $update_stmt->bind_param("i", $request_id);
    $update_stmt->execute();
    $update_stmt->close();
    header("Location: accept.php");
    exit;
}

// Fetch pending requests
$requests_stmt = $conn->prepare("
    SELECT cr.id AS request_id, s.full_name, a.appointment_date, a.appointment_time, a.service
    FROM copy_requests cr
    JOIN students s ON cr.student_id = s.id
    JOIN appointments a ON cr.appointment_id = a.id
    WHERE cr.status='pending'
    ORDER BY cr.created_at DESC
");
$requests_stmt->execute();
$requests_result = $requests_stmt->get_result();
$requests_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Accept Copy Requests</title>
<style>
body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f4f4f4;
    color: #333;
}
.admin-header {
    background-color: maroon;
    color: white;
    display: flex;
    align-items: center;
    padding: 12px 01px;
    position: relative;
    height: 60px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
}
.header-title {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    font-weight: 600;
    font-size: 22px;
    user-select: none;
    letter-spacing: 1px;
}
.nav-links {
    margin-left: auto;
    display: flex;
    align-items: center;
    font-weight: 500;
    font-size: 17px;
}
.nav-links a {
    color: white;
    text-decoration: none;
    padding: 10px 18px;
    position: relative;
    transition: color 0.25s ease, transform 0.2s ease;
    user-select: none;
    cursor: pointer;
}
.nav-links a + a::before {
    content: "";
    position: absolute;
    left: 0;
    top: 20%;
    height: 60%;
    width: 1.5px;
    background: rgba(255, 255, 255, 0.5);
    border-radius: 1px;
}
.nav-links a:hover {
    color: #ffb3b3;
    transform: scale(1.07);
    text-shadow: 0 0 6px #ffb3b3;
}
.table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.table-header h2 { color: maroon; font-weight: 600; font-size: 24px; margin: 0; }

table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10px;
    font-size: 16px;
    color: #222;
}
thead th { text-align: left; padding: 12px; }
tbody tr {
    background: #fafafa;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgb(0 0 0 / 0.05);
}
tbody td { padding: 14px 18px; vertical-align: middle; }
.approve-btn {
    background-color: maroon;
    color: white;
    border: none;
    padding: 6px 14px;
    border-radius: 4px;
    cursor: pointer;
    transition: 0.3s;
}
.approve-btn:hover { background-color: #a00000; }

@media (max-width: 768px) {
    table, thead, tbody, th, td, tr { display: block; }
    thead { display: none; }
    tr { margin-bottom: 15px; background: white; border-radius: 8px; padding: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
    td { padding: 10px; text-align: right; position: relative; }
    td::before { content: attr(data-label); position: absolute; left: 10px; width: 50%; font-weight: bold; text-align: left; }
}
</style>
</head>
<body>

<header class="admin-header">
  <div class="header-title">Digital Records & Reporting System </div>
  <nav class="nav-links">
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="admin_schedule.php">Schedule</a>
    <a href="admin_profile.php">Profile</a>
    <a href="more.php">More</a>
    <a href="admin_logout.php" onclick="return confirm('Are you sure you want to log out?')">Logout</a>
  </nav>
</header>

<div class="dashboard-container">
    <div class="table-header">
        <h2>Pending Copy Requests</h2>
    </div>

    <?php if ($requests_result && $requests_result->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Date</th>
                <th>Time</th>
                <th>Service</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $requests_result->fetch_assoc()): ?>
            <tr>
                <td data-label="Student Name"><?= htmlspecialchars($row['full_name']) ?></td>
                <td data-label="Date"><?= htmlspecialchars($row['appointment_date']) ?></td>
                <td data-label="Time"><?= htmlspecialchars($row['appointment_time']) ?></td>
                <td data-label="Service"><?= htmlspecialchars($row['service']) ?></td>
                <td data-label="Action">
                    <a class="approve-btn" href="accept.php?approve=<?= $row['request_id'] ?>">Approve</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No pending copy requests.</p>
    <?php endif; ?>
</div>

</body>
</html>
