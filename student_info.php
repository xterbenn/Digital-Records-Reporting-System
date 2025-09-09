<?php
session_start();
require 'db.php'; 

if (!isset($_SESSION['student_id'])) {
  header("Location: index.php");
  exit();
}

$student_id = $_SESSION['student_id'];

// Fetch all colleges
$colleges = [];
$col_query = $conn->query("SELECT id, name FROM colleges ORDER BY name");
while ($row = $col_query->fetch_assoc()) {
    $colleges[] = $row;
}

// Fetch student data
$stmt = $conn->prepare("SELECT student_number, full_name, college_id, course_id, year_level, email FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->bind_result($student_number, $full_name, $college_id, $course_id, $year_level, $email);
$stmt->fetch();
$stmt->close();

// Fetch courses for the selected college
$courses = [];
if ($college_id) {
    $course_stmt = $conn->prepare("SELECT id, name FROM courses WHERE college_id=? ORDER BY name");
    $course_stmt->bind_param("i", $college_id);
    $course_stmt->execute();
    $res = $course_stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $courses[] = $row;
    }
    $course_stmt->close();
}

// Handle form submission
$message = "";
$message_type = ""; // success or error
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_student'])) {
    $full_name_new = trim($_POST['full_name']);
    $college_id_new = intval($_POST['college_id']);
    $course_id_new = intval($_POST['course_id']);
    $year_level_new = trim($_POST['year_level']);
    $email_new = trim($_POST['email']);

    $update_stmt = $conn->prepare("UPDATE students SET full_name=?, college_id=?, course_id=?, year_level=?, email=? WHERE id=?");
    $update_stmt->bind_param("siissi", $full_name_new, $college_id_new, $course_id_new, $year_level_new, $email_new, $student_id);

    if($update_stmt->execute()){
        $message = "Information updated successfully.";
        $message_type = "success";
        $full_name = $full_name_new;
        $college_id = $college_id_new;
        $course_id = $course_id_new;
        $year_level = $year_level_new;
        $email = $email_new;
        $_SESSION['update_success'] = true;
    } else {
        $message = "Error updating information.";
        $message_type = "error";
        $_SESSION['update_success'] = false;
    }
    $update_stmt->close();

    // Refresh courses list
    $courses = [];
    $course_stmt = $conn->prepare("SELECT id, name FROM courses WHERE college_id=? ORDER BY name");
    $course_stmt->bind_param("i", $college_id);
    $course_stmt->execute();
    $res = $course_stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $courses[] = $row;
    }
    $course_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Info</title>
<style>
* { box-sizing: border-box; margin:0; padding:0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;}
body { background-color:#f9f9f9; }

/* Header */
header { 
    display:flex; 
    justify-content:space-between; 
    align-items:center; 
    padding:15px 20px; 
    background-color:maroon; 
    color:white; 
    position:fixed; 
    width:100%; 
    top:0; 
    left:0; 
    z-index:1000; 
    flex-wrap:wrap;
}
header img { height:50px; }
.logo-text { font-size:16px; flex:1; text-align:center; }
.menu-toggle { font-size:28px; cursor:pointer; }

/* Sidebar */
.sidebar { 
    position:fixed; 
    top:65px; 
    right:-250px; 
    width:250px; 
    height:calc(100% - 65px); 
    background:#333; 
    color:white; 
    padding:20px; 
    transition:right 0.3s ease; 
    z-index:999; 
    overflow-y:auto;
}
.sidebar.active { right:0; }
.sidebar a { display:block; color:white; padding:12px 0; text-decoration:none; border-bottom:1px solid #555; }
.sidebar a:hover { background:#444; }

/* Main content */
main { margin-top:100px; padding:15px; }
.container { max-width:700px; margin:0 auto; background:white; padding:20px; border-radius:10px; box-shadow:0 5px 15px rgba(0,0,0,0.1);}
h2 { color: maroon; margin-bottom:20px; text-align:center; word-wrap:break-word; }
table { width:100%; border-collapse:collapse; margin-top:10px; table-layout:fixed; word-wrap:break-word;}
th, td { text-align:left; padding:8px; border-bottom:1px solid #ccc; font-size:14px; }

/* Edit button */
.edit-btn { display:inline-block; margin-top:15px; padding:10px 20px; background:maroon; color:white; border:none; border-radius:5px; cursor:pointer; transition:0.3s; width:100%; text-align:center; }
.edit-btn:hover { background:#a00000; transform:scale(1.05); }

/* Toast modal */
.toast { display:none; position:fixed; top:20px; left:50%; transform:translateX(-50%); padding:12px 20px; border-radius:10px; color:white; font-weight:bold; z-index:3000; font-size:14px;}
.toast.success { background:green; }
.toast.error { background:red; }

/* Modal */
.modal { display:none; position:fixed; z-index:2000; left:0; top:0; width:100%; height:100%; overflow:auto; background:rgba(0,0,0,0.5); }
.modal-content { background:white; margin:5% auto; padding:20px; border-radius:10px; width:95%; max-width:450px; position:relative; }
.close { position:absolute; top:10px; right:15px; font-size:24px; font-weight:bold; cursor:pointer; color:#333; }
.close:hover { color:red; }
.modal form label { display:block; margin-top:10px; font-weight:bold; font-size:14px; }
.modal form input, .modal form select { width:100%; padding:8px; margin-top:5px; border-radius:5px; border:1px solid #ccc; font-size:14px; }
.modal form button { margin-top:15px; width:100%; padding:10px; background:maroon; color:white; border:none; border-radius:5px; cursor:pointer; font-size:14px; }
.modal form button:hover { background:#a00000; }

/* Responsive */
@media(max-width:768px){
    header img { height:40px; }
    .logo-text { font-size:14px; text-align:center; }
    .menu-toggle { font-size:24px; }
    .container { padding:15px; }
    th, td { font-size:13px; padding:6px; }
    .edit-btn { font-size:14px; }
}

@media(max-width:480px){
    header { padding:10px 15px; }
    .logo-text { font-size:12px; }
    .sidebar { width:100%; top:50px; height:calc(100% - 50px); right:-100%; }
    .sidebar.active { right:0; }
    .modal-content { width:95%; margin:10% auto; padding:15px; }
    th, td { font-size:12px; padding:5px; }
    .edit-btn { font-size:13px; padding:8px; }
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
    <a href="schedule.php">Appointment Schedule Availability</a>
    <a href="logout.php">Log Out</a>
</div>

<main>
<div class="container">
    <h2>Student Info</h2>
    <table>
        <tr><th>Student Number</th><td><?= htmlspecialchars($student_number) ?></td></tr>
        <tr><th>Full Name</th><td><?= htmlspecialchars($full_name) ?></td></tr>
        <tr><th>College</th><td><?= htmlspecialchars($college_id ? array_column($colleges, 'name', 'id')[$college_id] : '') ?></td></tr>
        <tr><th>Course</th><td><?= htmlspecialchars($course_id ? array_column($courses, 'name', 'id')[$course_id] : '') ?></td></tr>
        <tr><th>Year Level</th><td><?= htmlspecialchars($year_level) ?></td></tr>
        <tr><th>Email</th><td><?= htmlspecialchars($email) ?></td></tr>
    </table>
    <button class="edit-btn" onclick="openModal()">Edit Info</button>
</div>
</main>

<!-- Modal -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Edit Student Info</h2>
        <form method="post">
            <input type="hidden" name="edit_student" value="1">
            <label>Full Name</label>
            <input type="text" name="full_name" value="<?= htmlspecialchars($full_name) ?>" required>
            
            <label>College</label>
            <select name="college_id" id="college-select" onchange="updateCourses()" required>
                <option value="">-- Select College --</option>
                <?php foreach($colleges as $col): ?>
                    <option value="<?= $col['id'] ?>" <?= $college_id==$col['id']?'selected':'' ?>><?= htmlspecialchars($col['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Course</label>
            <select name="course_id" id="course-select" required>
                <option value="">-- Select Course --</option>
                <?php foreach($courses as $course): ?>
                    <option value="<?= $course['id'] ?>" <?= $course_id==$course['id']?'selected':'' ?>><?= htmlspecialchars($course['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Year Level</label>
            <input type="text" name="year_level" value="<?= htmlspecialchars($year_level) ?>" required>

            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

            <button type="submit">Update Info</button>
        </form>
    </div>
</div>

<!-- Toast -->
<?php if($message && (!isset($_SESSION['shown_toast']) || !$_SESSION['shown_toast'])): $_SESSION['shown_toast'] = true; ?>
<div class="toast <?= $message_type ?>" id="toast"><?= $message ?></div>
<script>
    const toast = document.getElementById('toast');
    toast.style.display='block';
    setTimeout(()=>{ toast.style.display='none'; },2000);
</script>
<?php endif; ?>

<script>
function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("active");
}
function openModal(){ document.getElementById('editModal').style.display='block'; }
function closeModal(){ document.getElementById('editModal').style.display='none'; }
window.onclick = function(e){ if(e.target==document.getElementById('editModal')) closeModal(); }

// Dynamic courses update
function updateCourses(){
    const collegeId = document.getElementById('college-select').value;
    const courseSelect = document.getElementById('course-select');
    courseSelect.innerHTML = '<option>Loading...</option>';
    if(!collegeId){ courseSelect.innerHTML='<option value="">-- Select Course --</option>'; return; }
    fetch('get_courses.php?college_id='+collegeId)
        .then(res=>res.json())
        .then(data=>{
            courseSelect.innerHTML='<option value="">-- Select Course --</option>';
            data.forEach(c=>{
                let opt = document.createElement('option');
                opt.value=c.id; opt.text=c.name;
                courseSelect.add(opt);
            });
        });
}
</script>

</body>
</html>
