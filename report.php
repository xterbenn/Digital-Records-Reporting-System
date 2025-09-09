<?php
session_start();
require 'db.php';

// --- Check admin session ---
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// --- Get appointment ID ---
$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;
if ($appointment_id <= 0) die("Invalid appointment ID.");

// --- Handle appointment status update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {
    $new_status = $_POST['change_status'];
    $valid_statuses = ['pending','approved','rejected','completed'];
    if (in_array($new_status, $valid_statuses)) {
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $appointment_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: report.php?appointment_id=" . $appointment_id);
    exit;
}

// --- Handle guidance status update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_guidance_status'])) {
    $new_guidance_status = $_POST['change_guidance_status'];
    $valid_guidance = ['Monitored','Active Case','Closed Case','Referred','None'];
    if (in_array($new_guidance_status, $valid_guidance)) {
        $stmt = $conn->prepare("
            UPDATE students 
            SET guidance_status = ? 
            WHERE student_number = (
                SELECT user_number FROM appointments WHERE id = ?
            )
        ");
        $stmt->bind_param("si", $new_guidance_status, $appointment_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: report.php?appointment_id=" . $appointment_id);
    exit;
}

// --- Fetch appointment details ---
$stmt = $conn->prepare("
    SELECT a.id, a.user_number, a.appointment_date, a.appointment_time,
           a.service, a.status, a.created_at, a.additional_details,
           s.full_name, s.guidance_status, c.name AS college_name, co.name AS course_name
    FROM appointments a
    LEFT JOIN students s ON a.user_type = 'student' AND s.student_number = a.user_number
    LEFT JOIN colleges c ON s.college_id = c.id
    LEFT JOIN courses co ON s.course_id = co.id
    WHERE a.id = ?
");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$appointment) die("Appointment not found.");

// --- Format times ---
$appointment_time = date("g:i A", strtotime($appointment['appointment_time']));
$created_at = date("M d, Y g:i A", strtotime($appointment['created_at']));

// --- Fetch images ---
$stmt = $conn->prepare("SELECT id, image_path FROM appointment_images WHERE appointment_id = ?");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// --- Handle image upload ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $uploadDir = 'report_images/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
        $fileName = time() . '_' . basename($_FILES['images']['name'][$i]);
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $targetPath)) {
            $stmt = $conn->prepare("INSERT INTO appointment_images (appointment_id, image_path) VALUES (?, ?)");
            $stmt->bind_param("is", $appointment_id, $targetPath);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: report.php?appointment_id=" . $appointment_id);
    exit;
}

// --- Handle image delete ---
if (isset($_GET['delete_image'])) {
    $image_id = intval($_GET['delete_image']);
    $stmt = $conn->prepare("SELECT image_path FROM appointment_images WHERE id = ?");
    $stmt->bind_param("i", $image_id);
    $stmt->execute();
    $stmt->bind_result($image_path);
    if ($stmt->fetch() && file_exists($image_path)) unlink($image_path);
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM appointment_images WHERE id = ?");
    $stmt->bind_param("i", $image_id);
    $stmt->execute();
    $stmt->close();

    header("Location: report.php?appointment_id=" . $appointment_id);
    exit;
}

// --- Labels ---
$status_labels = [
    'pending' => 'Pending',
    'approved' => 'Approve',
    'rejected' => 'Reject',
    'completed' => 'Complete'
];

$guidance_labels = [
    'Monitored' => 'Monitored',
    'Active Case' => 'Active Case',
    'Closed Case' => 'Closed Case',
    'Referred' => 'Referred',
    'None' => 'None'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Appointment Report</title>
<style>
body {font-family:'Segoe UI', Tahoma, Geneva, Verdana,sans-serif; background:#f5f5f5; margin:0; padding:0;}
.admin-header {background-color:maroon;color:#fff;display:flex;align-items:center;padding:12px 24px; box-shadow:0 2px 8px rgba(0,0,0,0.12); position:relative;}
.header-title {position:absolute; left:50%; transform:translateX(-50%); font-weight:600; font-size:24px;}
.nav-links {margin-left:auto; display:flex; align-items:center; font-weight:500;}
.nav-links a {color:#fff; text-decoration:none; padding:8px 14px; position:relative; transition:color 0.25s ease, transform 0.2s ease;}
.nav-links a + a::before {content:""; position:absolute; left:0; top:25%; height:50%; width:2px; background:rgba(255,255,255,0.6);}
.nav-links a:hover {color:#ffb3b3; transform:scale(1.1); text-shadow:0 0 6px #ffb3b3;}
.container {margin:20px;}
.card {background:#fff; padding:15px; border-radius:8px; margin-bottom:20px; box-shadow:0 2px 6px rgba(0,0,0,0.12);}
.card-header {background:maroon; color:#fff; padding:10px 15px; border-radius:6px 6px 0 0;}
table {width:100%; border-collapse:collapse;}
th {text-align:left; padding:8px; width:180px; vertical-align:top;}
td {padding:8px;}
.img-grid {display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:12px;}
.img-grid img {width:100%; height:150px; object-fit:cover; border-radius:6px; box-shadow:0 2px 6px rgba(0,0,0,0.12);}

/* --- Modern Buttons --- */
.button {
  padding: 10px 18px;
  border: none;
  border-radius: 10px;
  font-size: 0.95em;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.25s ease;
  box-shadow: 0 4px 10px rgba(0,0,0,0.08);
  margin: 4px;
}

.button-primary {
  background: linear-gradient(135deg, #800000, #a00000);
  color: #fff;
}
.button-primary:hover {
  transform: translateY(-4px) scale(1.03);
  box-shadow: 0 8px 18px rgba(128,0,0,0.35);
}

.button-danger {
  background: linear-gradient(135deg, #dc2626, #b91c1c);
  color: #fff;
}
.button-danger:hover {
  transform: translateY(-4px) scale(1.03);
  box-shadow: 0 8px 18px rgba(220,38,38,0.35);
}

.modal {display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.5);}
.modal-content {background:#fff; margin:10% auto; padding:20px; border-radius:8px; width:300px; text-align:center; box-shadow:0 6px 20px rgba(0,0,0,0.2);}
.modal-content .button {width:100%; margin-bottom:8px;}
.close {float:right; font-size:20px; font-weight:bold; cursor:pointer;}
</style>
<script>
function openModal(id) { document.getElementById(id).style.display='block'; }
function closeModal(id) { document.getElementById(id).style.display='none'; }
window.onclick = function(event) {
    if(event.target.classList.contains('modal')) event.target.style.display='none';
}
</script>
</head>
<body>

<header class="admin-header">
    <div class="header-title">Digital Reporting System</div>
    <nav class="nav-links">
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="admin_schedule.php">Schedule</a>
        <a href="admin_profile.php">Profile</a>
         <a href="more.php">More</a>
        <a href="admin_logout.php" onclick="return confirm('Are you sure you want to logout?')">Logout</a>
    </nav>
</header>

<div class="container">
    <h2 style="color:maroon;">Appointment Report</h2>

    <div class="card">
        <div class="card-header">Appointment Information</div>
        <table>
            <tr><th>User Number</th><td><?= htmlspecialchars($appointment['user_number']); ?></td></tr>
            <?php if(!empty($appointment['full_name'])): ?>
                <tr><th>Full Name</th><td><?= htmlspecialchars($appointment['full_name']); ?></td></tr>
                <tr><th>Guidance Status</th>
                    <td>
                        <strong>Current:</strong> <?= htmlspecialchars($appointment['guidance_status']); ?>
                        <br><br>
                        <button class="button button-primary" onclick="openModal('guidanceModal')">Update Student's Guidance Status</button>
                    </td>
                </tr>
                <tr><th>College</th><td><?= htmlspecialchars($appointment['college_name']); ?></td></tr>
                <tr><th>Course</th><td><?= htmlspecialchars($appointment['course_name']); ?></td></tr>
            <?php endif; ?>
            <tr><th>Appointment Date</th><td><?= htmlspecialchars($appointment['appointment_date']); ?></td></tr>
            <tr><th>Appointment Time</th><td><?= $appointment_time; ?></td></tr>
            <tr><th>Service</th><td><?= htmlspecialchars($appointment['service']); ?></td></tr>
            <tr><th>Status</th>
                <td>
                    <strong>Current:</strong> <?= $status_labels[$appointment['status']]; ?>
                    <br><br>
                    <button class="button button-primary" onclick="openModal('statusModal')">Update Appointment Status</button>
                </td>
            </tr>
            <tr><th>Created At</th><td><?= $created_at; ?></td></tr>
            <tr><th>Additional Details</th><td><?= nl2br(htmlspecialchars($appointment['additional_details'])); ?></td></tr>
        </table>
    </div>

    <!-- Uploaded Images -->
    <div class="card">
        <div class="card-header">Uploaded Images</div>
        <?php if(!empty($images)): ?>
            <div class="img-grid">
                <?php foreach($images as $img): ?>
                    <div>
                        <img src="<?= htmlspecialchars($img['image_path']); ?>" alt="Report Image">
                        <div style="text-align:center; margin-top:5px;">
                            <a href="report.php?appointment_id=<?= $appointment_id; ?>&delete_image=<?= $img['id']; ?>"
                               class="button button-danger" onclick="return confirm('Delete this image?')">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No images uploaded yet.</p>
        <?php endif; ?>
    </div>

    <!-- Upload Form -->
    <div class="card">
        <div class="card-header">Upload New Images</div>
        <form method="POST" enctype="multipart/form-data" style="padding:15px;">
            <input type="file" name="images[]" multiple required style="width:100%; margin-bottom:10px;">
            <button type="submit" class="button button-primary">Upload Images</button>
        </form>
    </div>
</div>

<!-- Appointment Status Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('statusModal')">&times;</span>
        <h3>Select Status</h3>
        <form method="POST">
            <?php
            foreach($status_labels as $key=>$label){
                if($key !== $appointment['status']){
                    echo "<button type='submit' name='change_status' value='$key' class='button button-primary'>$label</button>";
                }
            }
            ?>
        </form>
    </div>
</div>

<!-- Guidance Status Modal -->
<div id="guidanceModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('guidanceModal')">&times;</span>
        <h3>Select Guidance Status</h3>
        <form method="POST">
            <?php
            foreach($guidance_labels as $key=>$label){
                if($key !== $appointment['guidance_status']){
                    echo "<button type='submit' name='change_guidance_status' value='$key' class='button button-primary'>$label</button>";
                }
            }
            ?>
        </form>
    </div>
</div>

</body>
</html>
