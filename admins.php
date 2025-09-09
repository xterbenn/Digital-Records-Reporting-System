<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle deletion
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);

    // Prevent deleting yourself
    if ($deleteId != $_SESSION['admin_id']) {
        $stmt = $conn->prepare("DELETE FROM admin WHERE id = ?");
        $stmt->bind_param("i", $deleteId);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admins.php");
    exit();
}

// Fetch admins
$stmt = $conn->prepare("SELECT id, name, email FROM admin ORDER BY id ASC");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admins</title>
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
/* CONTAINER */
.dashboard-container {
    max-width: 900px;
    margin: 30px auto 60px;
    background: white;
    padding: 28px 32px;
    border-radius: 10px;
    box-shadow: 0 7px 20px rgba(0,0,0,0.08);
}
/* TABLE */
table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10px;
    font-size: 16px;
    color: #222;
}
thead th {
    text-align: left;
    padding: 14px 18px;
}
tbody tr {
    background: #fafafa;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgb(0 0 0 / 0.05);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
tbody tr:hover {
    transform: scale(1.02);
    box-shadow: 0 5px 16px rgb(0 0 0 / 0.20);
}
tbody td {
    padding: 14px 18px;
    vertical-align: middle;
}
.btn-delete {
    padding: 8px 14px;
    background: maroon;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
.btn-delete:hover {
    background: #a00000;
}
/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.4);
}
.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border-radius: 10px;
    width: 90%;
    max-width: 400px;
    text-align: center;
}
.modal-buttons {
    margin-top: 20px;
    display: flex;
    justify-content: space-around;
}
.modal-buttons button {
    padding: 8px 18px;
    border-radius: 5px;
    border: none;
    cursor: pointer;
}
.modal-buttons .confirm {
    background-color: maroon;
    color: white;
}
.modal-buttons .cancel {
    background-color: #ccc;
}
</style>
</head>
<body>

<header class="admin-header">
    <div class="header-title">Digital Records & Reporting System</div>
    <nav class="nav-links">
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="admin_schedule.php">Schedule</a>
        <a href="admin_profile.php">Profile</a>
         <a href="more.php">More</a>
        <a href="admin_logout.php" onclick="return confirmLogout()">Logout</a>
    </nav>
</header>

<div class="dashboard-container">
    <h2 style="color: maroon; margin-bottom: 20px;">Admins</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td>
                    <?php if($row['id'] != $_SESSION['admin_id']): ?>
                        <button class="btn-delete" onclick="confirmDelete(<?= $row['id'] ?>)">Delete</button>
                    <?php else: ?>
                        <span style="color:gray;">You</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <p>Are you sure you want to delete this admin?</p>
        <div class="modal-buttons">
            <button class="confirm" id="confirmBtn">Yes, Delete</button>
            <button class="cancel" onclick="closeModal()">Cancel</button>
        </div>
    </div>
</div>

<script>
let deleteId = 0;
function confirmDelete(id) {
    deleteId = id;
    document.getElementById('deleteModal').style.display = 'block';
}
function closeModal() {
    document.getElementById('deleteModal').style.display = 'none';
}
document.getElementById('confirmBtn').addEventListener('click', function() {
    window.location.href = 'admins.php?delete_id=' + deleteId;
});
function confirmLogout() {
    return confirm("Are you sure you want to log out?");
}
</script>

</body>
</html>
