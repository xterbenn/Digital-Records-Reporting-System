<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Get status filter from URL
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$valid_statuses = ['pending','approved','rejected','completed'];

// Fetch appointments joined with students
$sql = "
    SELECT 
        a.id,
        s.full_name,
        a.appointment_date,
        TIME_FORMAT(a.appointment_time, '%h:%i %p') AS appointment_time,
        a.service,
        a.status,
        DATE_FORMAT(a.created_at, '%b %d, %Y %h:%i %p') AS created_at,
        a.additional_details
    FROM appointments a
    LEFT JOIN students s ON a.user_number = s.student_number
";
if (in_array($filter_status, $valid_statuses)) {
    $sql .= " WHERE a.status = '".$conn->real_escape_string($filter_status)."'";
}
$sql .= " ORDER BY a.created_at DESC";

$result = $conn->query($sql);
$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Admin Dashboard</title>
<?php require 'refresh.php'; ?>
<style>
body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f4f4f4;
    color: #333;
}

/* HEADER */
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

/* DASHBOARD CONTAINER */
.dashboard-container {
    max-width: 1200px;
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
.arrow-controls {
    display: flex;
    gap: 12px;
}
.arrow-btn {
    background: maroon;
    color: white;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    font-size: 19px;
    box-shadow: 0 5px 12px rgba(0,0,0,0.2);
    transition: background 0.3s ease, transform 0.2s ease;
}
.arrow-btn:hover {
    background-color: #c04040;
    transform: scale(1.1);
}

/* STATUS FILTER */
.status-select {
    margin-bottom: 15px;
}
.status-select select {
    padding: 6px 10px;
    font-size: 15px;
    border-radius: 4px;
    border: 1px solid #ccc;
    cursor: pointer;
}

/* TABLE */
table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10px;
    font-size: 15px;
    color: #222;
}
thead {
    background-color: maroon;
    color: white;
    font-weight: 600;
    font-size: 17px;
    border-radius: 10px;
}
thead th {
    padding: 13px 18px;
    text-align: left;
}
tbody tr {
    background: #fafafa;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgb(0 0 0 / 0.05);
    transition: box-shadow 0.3s ease, background 0.3s ease;
    cursor: pointer;
}
tbody tr:hover {
    box-shadow: 0 5px 16px rgb(0 0 0 / 0.1);
    background: #f9ecec;
}
tbody td {
    padding: 14px 18px;
    vertical-align: middle;
}

.status-label {
    padding: 5px 12px;
    border-radius: 18px;
    font-size: 13px;
    color: white;
    font-weight: 600;
    text-transform: capitalize;
    display: inline-block;
    min-width: 85px;
    text-align: center;
}

.table-container {
    max-height: 360px; /* roughly 5 rows */
    overflow-y: auto;
    padding-right: 10px; /* avoids scrollbar overlapping content */
}

/* Optional: nicer scrollbar for modern browsers */
.table-container::-webkit-scrollbar {
    width: 8px;
}
.table-container::-webkit-scrollbar-thumb {
    background-color: rgba(0,0,0,0.2);
    border-radius: 4px;
}
.table-container::-webkit-scrollbar-track {
    background: transparent;
}

</style>
</head>
<body>

<header class="admin-header">
  <div class="header-title">Digital Records & Reporting System </div>
  <nav class="nav-links" aria-label="Admin navigation">
    <a href="admin_schedule.php">Schedule</a>
    <a href="admin_profile.php">Profile</a>
     <a href="more.php">More</a>
    <a href="admin_logout.php" onclick="return confirmLogout()">Logout</a>
  </nav>
</header>

<div class="dashboard-container">
    <div class="table-header">
        <h2>Appointments</h2>
        <div class="arrow-controls">
            <button class="arrow-btn" onclick="prevPage()">&#8592;</button>
            <button class="arrow-btn" onclick="nextPage()">&#8594;</button>
        </div>
    </div>

    <!-- Status Filter (dropdown) -->
    <div class="status-select">
        <form method="GET" id="statusForm">
            <select name="status" onchange="document.getElementById('statusForm').submit()">
                <option value="all" <?= $filter_status==='all'?'selected':'' ?>>Show All</option>
                <?php foreach ($valid_statuses as $st): ?>
                    <option value="<?= $st ?>" <?= $filter_status===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Service</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody id="appointment-body"></tbody>
        </table>
    </div>
</div>

<script>
function confirmLogout() {
    return confirm("Are you sure you want to log out?");
}

const appointments = <?php echo json_encode($appointments); ?>;
const rowsPerPage = 7;
let currentPage = 0;

function renderTable() {
    const tbody = document.getElementById("appointment-body");
    tbody.innerHTML = "";

    const start = currentPage * rowsPerPage;
    const end = start + rowsPerPage;
    const pageItems = appointments.slice(start, end);

    pageItems.forEach(row => {
        const statusColor =
            row.status === 'approved' ? 'green' :
            row.status === 'rejected' ? 'red' :
            row.status === 'completed' ? 'blue' : 'orange';

        const tr = document.createElement("tr");
        tr.onclick = () => {
            window.location.href = "report.php?appointment_id=" + row.id;
        };
        tr.innerHTML = `
            <td>${row.full_name ?? 'Unidentified'}</td>
            <td>${row.appointment_date}</td>
            <td>${row.appointment_time}</td>
            <td>${row.service}</td>
            <td><span class="status-label" style="background-color: ${statusColor};">
                ${row.status.charAt(0).toUpperCase() + row.status.slice(1)}
            </span></td>
            <td>${row.created_at}</td>
            <td>${row.additional_details ?? ''}</td>
        `;
        tbody.appendChild(tr);
    });
}

function prevPage() {
    if (currentPage > 0) {
        currentPage--;
        renderTable();
    }
}
function nextPage() {
    if ((currentPage + 1) * rowsPerPage < appointments.length) {
        currentPage++;
        renderTable();
    }
}

renderTable();
</script>

</body>
</html>
