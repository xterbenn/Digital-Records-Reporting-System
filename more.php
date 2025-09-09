<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>More</title>
<?php require 'refresh.php'; ?>
<style>
body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f4f4f4;
    color: #333;
}

/* HEADER (same as dashboard) */
.admin-header {
    background-color: maroon;
    color: white;
    display: flex;
    align-items: center;
    padding: 14px 25px;
    position: relative;
    height: 60px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
}
.header-title {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    font-weight: 600;
    font-size: 24px;
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

/* CONTAINER */
.dashboard-container {
    max-width: 800px;
    margin: 30px auto 60px;
    background: white;
    padding: 28px 32px;
    border-radius: 10px;
    box-shadow: 0 7px 20px rgba(0,0,0,0.08);
}
.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.table-header h2 {
    color: maroon;
    font-weight: 600;
    font-size: 24px;
    margin: 0;
}

/* TABLE */
table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10px;
    font-size: 16px;
    color: #222;
}
tbody tr {
    background: #fafafa;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgb(0 0 0 / 0.05);
    transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    cursor: pointer;
}
tbody tr:hover {
    transform: scale(1.02);
    box-shadow: 0 5px 16px rgb(0 0 0 / 0.20);
    background: #cc0000; 
    color: white;
}
tbody td {
    padding: 14px 18px;
    vertical-align: middle;
}
</style>
</head>
<body>

<header class="admin-header">
  <div class="header-title">Digital Records & Reporting System</div>
  <nav class="nav-links" aria-label="Admin navigation">
     <a href="admin_dashboard.php">Dashboard</a>
    <a href="admin_schedule.php">Schedule</a>
    <a href="admin_profile.php">Profile</a>
    <a href="admin_logout.php" onclick="return confirmLogout()">Logout</a>
  </nav>
</header>

<div class="dashboard-container">
    <div class="table-header">
        <h2>More Options</h2>
    </div>

    <table>
        <tbody>
             <tr onclick="location.href='accept.php'">
                <td>Accept Appointment Copy Requests</td>
            </tr>
            <tr onclick="location.href='students.php'">
                <td>Students</td>
            </tr>
            <tr onclick="location.href='colleges_courses.php'">
                <td>Colleges / Courses</td>
            </tr>
            <tr onclick="location.href='admins.php'">
                <td>Admins</td>
            </tr>
        </tbody>
    </table>
</div>

<script>
function confirmLogout() {
    return confirm("Are you sure you want to log out?");
}
</script>

</body>
</html>
