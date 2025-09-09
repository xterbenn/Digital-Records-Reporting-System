<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Handle update guest
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update' && isset($_POST['guest_number'])) {
        $guest_number = intval($_POST['guest_number']);
        $full_name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';

        $stmt = $conn->prepare("UPDATE guests SET full_name = ?, email = ? WHERE guest_number = ?");
        $stmt->bind_param("ssi", $full_name, $email, $guest_number);
        $stmt->execute();
        $stmt->close();

        header("Location: guest.php");
        exit;
    }

    // Handle delete guest
    if ($_POST['action'] === 'delete' && isset($_POST['guest_number'])) {
        $guest_number = intval($_POST['guest_number']);
        $stmt = $conn->prepare("DELETE FROM guests WHERE guest_number = ?");
        $stmt->bind_param("i", $guest_number);
        $stmt->execute();
        $stmt->close();

        header("Location: guest.php");
        exit;
    }
}

// Fetch guests
$sql = "SELECT * FROM guests ORDER BY created_at DESC";
$result = $conn->query($sql);

$guests = [];
while ($row = $result->fetch_assoc()) {
    $guests[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Guests List</title>
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
    transition:
      color 0.25s ease,
      transform 0.2s ease;
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

  /* CONTENT */
  .dashboard-container {
    max-width: 1000px;
    margin: 30px auto 60px;
    background: white;
    padding: 28px 32px;
    border-radius: 10px;
    box-shadow: 0 7px 20px rgba(0,0,0,0.08);
    overflow-x: auto;
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
    white-space: nowrap;
  }
  tbody tr {
    background: #fafafa;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgb(0 0 0 / 0.05);
    transition: box-shadow 0.3s ease;
  }
  tbody tr:hover {
    box-shadow: 0 5px 16px rgb(0 0 0 / 0.1);
  }
  tbody td {
    padding: 10px 15px;
    vertical-align: middle;
  }

  button.action-btn {
    background: none;
    border: none;
    color: maroon;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    padding: 5px 12px;
    border-radius: 5px;
    transition: 
      background-color 0.3s ease,
      color 0.3s ease,
      box-shadow 0.3s ease;
  }
  button.action-btn:hover {
    background-color: #a00000;
    color: white;
    box-shadow: 0 0 6px #a00000;
  }

  /* MODAL */
  .modal-bg {
    display: none;
    position: fixed;
    z-index: 1500;
    left: 0; top: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.48);
    justify-content: center;
    align-items: center;
  }
  .modal-bg.active {
    display: flex;
  }
  .modal-box {
    background: white;
    max-width: 420px;
    width: 90%;
    padding: 28px 32px 32px;
    border-radius: 14px;
    box-shadow: 0 12px 35px rgba(0,0,0,0.16);
    font-size: 15px;
  }
  .modal-box h3 {
    color: maroon;
    margin-top: 0;
    margin-bottom: 22px;
    font-weight: 700;
    font-size: 24px;
    user-select: none;
  }
  .modal-box label {
    display: block;
    margin-top: 16px;
    font-weight: 600;
    color: #444;
  }
  .modal-box input[type=text], .modal-box input[type=email] {
    width: 100%;
    padding: 10px 14px;
    margin-top: 8px;
    font-size: 16px;
    border-radius: 8px;
    border: 1.8px solid #ccc;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    box-sizing: border-box;
  }
  .modal-box input[type=text]:focus, .modal-box input[type=email]:focus {
    outline: none;
    border-color: maroon;
    box-shadow: 0 0 8px #b35959aa;
  }
  .modal-actions {
    margin-top: 28px;
    display: flex;
    justify-content: flex-end;
    gap: 16px;
  }
  .btn-cancel, .btn-save {
    font-weight: 700;
    font-size: 16px;
    padding: 11px 26px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    user-select: none;
    transition: background-color 0.3s ease, box-shadow 0.3s ease;
  }
  .btn-cancel {
    background-color: #ddd;
    color: #444;
  }
  .btn-cancel:hover, .btn-cancel:focus, .btn-cancel:active {
    background-color: #bbb;
  }
  .btn-save {
    background-color: maroon;
    color: white;
    box-shadow: 0 0 10px #8b0000cc;
  }
  .btn-save:hover, .btn-save:focus, .btn-save:active {
    background-color: #a00000;
    box-shadow: 0 0 14px #a00000cc;
  }
</style>
</head>
<body>

<header class="admin-header">
  <div class="header-title">Digital Reporting System</div>
  <nav class="nav-links" aria-label="Admin navigation">
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="students.php">Students</a>
    <a href="admin_schedule.php">Schedule</a>
    <a href="profile.php">Profile</a>
    <a href="admin_logout.php" onclick="return confirmLogout()">Logout</a>
  </nav>
</header>

<div class="dashboard-container">
  <div class="table-header">
    <h2>Guests List</h2>
  </div>

  <table>
    <thead>
      <tr>
        <th>Guest Number</th>
        <th>Full Name</th>
        <th>Email</th>
        <th>Created At</th>
        <th style="min-width: 140px;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if(count($guests) === 0): ?>
        <tr><td colspan="5" style="text-align:center; padding:20px;">No guests found.</td></tr>
      <?php else: ?>
        <?php foreach($guests as $guest): ?>
          <tr>
            <td><?php echo htmlspecialchars($guest['guest_number']); ?></td>
            <td><?php echo htmlspecialchars($guest['full_name']); ?></td>
            <td><?php echo htmlspecialchars($guest['email']); ?></td>
            <td><?php echo htmlspecialchars($guest['created_at']); ?></td>
            <td>
              <button class="action-btn" onclick='openEditModal(<?php echo json_encode($guest); ?>)'>Edit</button>

              <form method="post" style="display:inline;" onsubmit="return confirm('Confirm delete this guest?');">
                <input type="hidden" name="guest_number" value="<?php echo $guest['guest_number']; ?>" />
                <button type="submit" name="action" value="delete" class="action-btn" style="color:#a00;">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- EDIT MODAL -->
<div id="editModal" class="modal-bg" role="dialog" aria-modal="true" aria-labelledby="modalTitle" tabindex="-1">
  <div class="modal-box" role="document">
    <h3 id="modalTitle">Edit Guest</h3>
    <form method="post" id="editForm">
      <input type="hidden" name="guest_number" id="edit-guest_number" />
      
      <label for="edit-full_name">Full Name</label>
      <input type="text" name="full_name" id="edit-full_name" autocomplete="off" />

      <label for="edit-email">Email</label>
      <input type="email" name="email" id="edit-email" autocomplete="off" />

      <div class="modal-actions">
        <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
        <button type="submit" name="action" value="update" class="btn-save">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openEditModal(guestJson) {
    const guest = typeof guestJson === 'string' ? JSON.parse(guestJson) : guestJson;

    document.getElementById('edit-guest_number').value = guest.guest_number;
    document.getElementById('edit-full_name').value = guest.full_name || "";
    document.getElementById('edit-email').value = guest.email || "";

    document.getElementById('editModal').classList.add('active');
    document.getElementById('edit-full_name').focus();
  }

  function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
  }

  // Close modal on clicking outside modal box
  document.getElementById('editModal').addEventListener('click', function(e) {
    if(e.target === this) {
      closeEditModal();
    }
  });

  // Confirm logout prompt
  function confirmLogout() {
    return confirm("Are you sure you want to log out?");
  }
</script>

</body>
</html>
