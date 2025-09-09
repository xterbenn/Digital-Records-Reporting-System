<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// --- Handle Add College ---
if (isset($_POST['add_college'])) {
    $name = trim($_POST['college_name']);
    if ($name != "") {
        $stmt = $conn->prepare("INSERT INTO colleges (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        $stmt->execute();
    }
    header("Location: colleges_courses.php");
    exit;
}

// --- Handle Edit College ---
if (isset($_POST['edit_college'])) {
    $id = intval($_POST['college_id']);
    $name = trim($_POST['college_name']);
    if ($name != "") {
        $stmt = $conn->prepare("UPDATE colleges SET name=? WHERE id=?");
        $stmt->bind_param("si", $name, $id);
        $stmt->execute();
    }
    header("Location: colleges_courses.php");
    exit;
}

// --- Handle Delete College ---
if (isset($_POST['delete_college'])) {
    $id = intval($_POST['college_id']);
    $stmt = $conn->prepare("DELETE FROM colleges WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: colleges_courses.php");
    exit;
}

// --- Handle Add Course ---
if (isset($_POST['add_course'])) {
    $college_id = intval($_POST['college_id']);
    $name = trim($_POST['course_name']);
    if ($name != "") {
        $stmt = $conn->prepare("INSERT INTO courses (college_id, name) VALUES (?, ?)");
        $stmt->bind_param("is", $college_id, $name);
        $stmt->execute();
    }
    header("Location: colleges_courses.php");
    exit;
}

// --- Handle Edit Course ---
if (isset($_POST['edit_course'])) {
    $id = intval($_POST['course_id']);
    $name = trim($_POST['course_name']);
    if ($name != "") {
        $stmt = $conn->prepare("UPDATE courses SET name=? WHERE id=?");
        $stmt->bind_param("si", $name, $id);
        $stmt->execute();
    }
    header("Location: colleges_courses.php");
    exit;
}

// --- Handle Delete Course ---
if (isset($_POST['delete_course'])) {
    $id = intval($_POST['course_id']);
    $stmt = $conn->prepare("DELETE FROM courses WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: colleges_courses.php");
    exit;
}

// --- Fetch Colleges and Courses ---
$colleges = [];
$result = $conn->query("SELECT * FROM colleges ORDER BY name ASC");
while ($row = $result->fetch_assoc()) {
    $colleges[$row['id']] = $row;
    $colleges[$row['id']]['courses'] = [];
}
$result = $conn->query("SELECT * FROM courses ORDER BY name ASC");
while ($row = $result->fetch_assoc()) {
    if (isset($colleges[$row['college_id']])) {
        $colleges[$row['college_id']]['courses'][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Colleges & Courses</title>
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
    padding: 14px 07px;
    position: relative;
    height: 60px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
}
.header-title {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    font-weight: 600;
    font-size: 21px;
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

/* MAIN CONTAINER */
.container {
    padding: 20px;
    max-width: 1000px;
    margin: 30px auto;
    background: white;
    border-radius: 10px;
    box-shadow: 0 7px 20px rgba(0,0,0,0.08);
}

/* TABLE */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}
th, td {
    padding: 12px 15px;
    text-align: left;
}
th { background: #f2f2f2; }
tr:nth-child(even) { background: #fafafa; }

/* BUTTONS */
.btn {
    padding: 8px 14px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s ease;
}
.btn-add { background: #800000; color: white; }
.btn-add:hover { background: #a00000; transform: scale(1.05); }
.btn-edit { background: #3498db; color: white; }
.btn-edit:hover { background: #217dbb; transform: scale(1.05); }
.btn-delete { background: #e74c3c; color: white; }
.btn-delete:hover { background: #c0392b; transform: scale(1.05); }

.toggle-btn {
    background: transparent;
    border: none;
    color: #800000;
    font-weight: bold;
    cursor: pointer;
}

/* MODAL */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0; top: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.6);
    justify-content: center;
    align-items: center;
}
.modal-content {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    width: 400px;
    max-width: 90%;
    text-align: center;
}
.modal input, .modal select {
    width: 90%;
    padding: 10px;
    margin: 10px 0;
    border: 1px solid #ccc;
    border-radius: 6px;
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

<div class="container">

    <button class="btn btn-add" onclick="openModal('addCollegeModal')">+ Add College</button>
    <button class="btn btn-add" onclick="openModal('addCourseModal')">+ Add Course</button>

    <table>
        <tr>
            <th>College</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($colleges as $college): ?>
        <tr>
            <td>
                <?= htmlspecialchars($college['name']) ?>
                <br>
                <button class="toggle-btn" onclick="toggleCourses(<?= $college['id'] ?>)">Show Courses ▼</button>
                <div id="courses-<?= $college['id'] ?>" style="display:none; margin-top:10px;">
                    <table style="width:90%; margin:10px auto; background:#fafafa;">
                        <tr>
                            <th>Course</th>
                            <th>Actions</th>
                        </tr>
                        <?php foreach ($college['courses'] as $course): ?>
                        <tr>
                            <td><?= htmlspecialchars($course['name']) ?></td>
                            <td>
                                <button class="btn btn-edit" onclick="openEditCourse(<?= $course['id'] ?>, '<?= htmlspecialchars($course['name'], ENT_QUOTES) ?>')">Edit</button>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                    <button type="submit" name="delete_course" class="btn btn-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </td>
            <td>
                <button class="btn btn-edit" onclick="openEditCollege(<?= $college['id'] ?>, '<?= htmlspecialchars($college['name'], ENT_QUOTES) ?>')">Edit</button>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="college_id" value="<?= $college['id'] ?>">
                    <button type="submit" name="delete_college" class="btn btn-delete">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- Add College Modal -->
<div id="addCollegeModal" class="modal">
  <div class="modal-content">
    <form method="post">
      <h3>Add College</h3>
      <input type="text" name="college_name" placeholder="College Name" required>
      <br>
      <button type="submit" name="add_college" class="btn btn-add">Save</button>
      <button type="button" class="btn btn-delete" onclick="closeModal('addCollegeModal')">Cancel</button>
    </form>
  </div>
</div>

<!-- Edit College Modal -->
<div id="editCollegeModal" class="modal">
  <div class="modal-content">
    <form method="post">
      <h3>Edit College</h3>
      <input type="hidden" name="college_id" id="editCollegeId">
      <input type="text" name="college_name" id="editCollegeName" required>
      <br>
      <button type="submit" name="edit_college" class="btn btn-edit">Update</button>
      <button type="button" class="btn btn-delete" onclick="closeModal('editCollegeModal')">Cancel</button>
    </form>
  </div>
</div>

<!-- Add Course Modal -->
<div id="addCourseModal" class="modal">
  <div class="modal-content">
    <form method="post">
      <h3>Add Course</h3>
      <select name="college_id" required>
        <option value="">-- Select College --</option>
        <?php foreach ($colleges as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="course_name" placeholder="Course Name" required>
      <br>
      <button type="submit" name="add_course" class="btn btn-add">Save</button>
      <button type="button" class="btn btn-delete" onclick="closeModal('addCourseModal')">Cancel</button>
    </form>
  </div>
</div>

<!-- Edit Course Modal -->
<div id="editCourseModal" class="modal">
  <div class="modal-content">
    <form method="post">
      <h3>Edit Course</h3>
      <input type="hidden" name="course_id" id="editCourseId">
      <input type="text" name="course_name" id="editCourseName" required>
      <br>
      <button type="submit" name="edit_course" class="btn btn-edit">Update</button>
      <button type="button" class="btn btn-delete" onclick="closeModal('editCourseModal')">Cancel</button>
    </form>
  </div>
</div>

<script>
function toggleCourses(id) {
    let section = document.getElementById("courses-" + id);
    section.style.display = (section.style.display === "none") ? "block" : "none";
}
function openModal(id) {
    document.getElementById(id).style.display = "flex";
}
function closeModal(id) {
    document.getElementById(id).style.display = "none";
}
function openEditCollege(id, name) {
    document.getElementById('editCollegeId').value = id;
    document.getElementById('editCollegeName').value = name;
    openModal('editCollegeModal');
}
function openEditCourse(id, name) {
    document.getElementById('editCourseId').value = id;
    document.getElementById('editCourseName').value = name;
    openModal('editCourseModal');
}
</script>

</body>
</html>
