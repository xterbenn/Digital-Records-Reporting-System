<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];

// Fetch admin data
$stmt = $conn->prepare("SELECT * FROM admin WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

$message = "";
$messageType = "";

// Update profile info (name/email)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    $update = $conn->prepare("UPDATE admin SET name = ?, email = ? WHERE id = ?");
    $update->bind_param("ssi", $name, $email, $admin_id);
    $update->execute();
    $update->close();

    $_SESSION['flash'] = ["Profile updated successfully.", "success"];
    header("Location: admin_profile.php");
    exit;
}

// Update password
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];

    if (password_verify($current_password, $admin['password'])) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $update_pass = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
        $update_pass->bind_param("si", $hashed, $admin_id);
        $update_pass->execute();
        $update_pass->close();

        $_SESSION['flash'] = ["Password updated successfully.", "success"];
        header("Location: admin_profile.php");
        exit;
    } else {
        $_SESSION['flash'] = ["Current password is incorrect.", "danger"];
        header("Location: admin_profile.php");
        exit;
    }
}

// Flash message handler
if (isset($_SESSION['flash'])) {
    [$message, $messageType] = $_SESSION['flash'];
    unset($_SESSION['flash']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Admin Profile</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
  /* CONTAINER */
  .dashboard-container {
    max-width: 800px;
    margin: 30px auto 60px;
    background: white;
    padding: 28px 32px;
    border-radius: 14px;
    box-shadow: 0 7px 20px rgba(0,0,0,0.08);
  }
  h2 {
    color: maroon;
    font-weight: 600;
    margin-bottom: 25px;
    text-align: center;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 25px;
  }
  table td {
    padding: 14px 16px;
    border-bottom: 1px solid #ddd;
    font-size: 1.05rem;
  }
  table td:first-child {
    font-weight: bold;
    width: 30%;
    background: #fafafa;
  }
  .btn {
    font-size: 1.05rem;
    padding: 10px 20px;
    border-radius: 8px;
  }
</style>
</head>
<body>

<header class="admin-header">
  <div class="header-title">Digital Records & Reporting System </div>
  <nav class="nav-links">
     <a href="admin_dashboard.php">Dashboard</a>
    <a href="admin_schedule.php">Schedule</a>
 <a href="more.php">More</a>
    <a href="admin_logout.php" onclick="return confirmLogout()">Logout</a>
  </nav>
</header>

<div class="dashboard-container">
  <h2>Admin Profile</h2>

  <table class="table table-striped table-hover align-middle">
    <tr><td>ID</td><td><?= htmlspecialchars($admin['id']) ?></td></tr>
    <tr><td>Name</td><td><?= htmlspecialchars($admin['name']) ?></td></tr>
    <tr><td>Email</td><td><?= htmlspecialchars($admin['email']) ?></td></tr>
    <tr><td>Password</td><td>********</td></tr>
  </table>

  <div class="d-flex gap-3 justify-content-center">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">✏️ Edit Profile</button>
    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editPasswordModal">🔑 Change Password</button>
  </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header bg-primary text-white"><h5 class="modal-title">Edit Profile</h5></div>
      <div class="modal-body">
        <input type="hidden" name="update_profile" value="1">
        <div class="mb-3">
          <label class="form-label">Name</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($admin['name']) ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin['email']) ?>" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">💾 Save Changes</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="editPasswordModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header bg-warning"><h5 class="modal-title">Change Password</h5></div>
      <div class="modal-body">
        <input type="hidden" name="update_password" value="1">
        <div class="mb-3">
          <label class="form-label">Current Password</label>
          <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">New Password</label>
          <input type="password" name="new_password" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-warning">🔑 Update Password</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Flash Message Modal -->
<?php if (!empty($message)): ?>
<div class="modal fade" id="flashModal" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content text-center p-3 border-<?= $messageType ?>">
      <div class="modal-body">
        <div class="alert alert-<?= $messageType ?> mb-0"><?= htmlspecialchars($message) ?></div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmLogout() {
  return confirm("Are you sure you want to log out?");
}

<?php if (!empty($message)): ?>
var flashModal = new bootstrap.Modal(document.getElementById('flashModal'));
flashModal.show();
setTimeout(() => { flashModal.hide(); }, 2000);
<?php endif; ?>
</script>
</body>
</html>
