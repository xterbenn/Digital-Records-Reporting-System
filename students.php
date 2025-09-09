<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Handle update student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $student_number = $_POST['student_number'] ?? '';
        $full_name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $college_id = intval($_POST['college_id'] ?? 0);
        $course_id = intval($_POST['course_id'] ?? 0);
        $year_level = $_POST['year_level'] ?? '';

        $stmt = $conn->prepare("UPDATE students SET student_number = ?, full_name = ?, email = ?, college_id = ?, course_id = ?, year_level = ? WHERE id = ?");
        $stmt->bind_param("sssiisi", $student_number, $full_name, $email, $college_id, $course_id, $year_level, $id);
        $stmt->execute();
        $stmt->close();

        header("Location: students.php");
        exit;
    }

    // Handle delete student
    if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        header("Location: students.php");
        exit;
    }
}

// Fetch colleges for dropdown
$colleges = [];
$collegeResult = $conn->query("SELECT id, name FROM colleges ORDER BY name");
while ($row = $collegeResult->fetch_assoc()) {
    $colleges[] = $row;
}

// Fetch courses for dropdown
$courses = [];
$courseResult = $conn->query("SELECT id, college_id, name FROM courses ORDER BY name");
while ($row = $courseResult->fetch_assoc()) {
    $courses[] = $row;
}

// Fetch students
$sql = "SELECT * FROM students ORDER BY created_at DESC";
$result = $conn->query($sql);

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

// Year levels options
$yearLevels = ['1st', '2nd', '3rd', '4th'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Students List</title>
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
    max-width: 1300px;
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
    max-width: 460px;
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
  .modal-box input[type=text], .modal-box select {
    width: 100%;
    padding: 10px 14px;
    margin-top: 8px;
    font-size: 16px;
    border-radius: 8px;
    border: 1.8px solid #ccc;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    box-sizing: border-box;
  }
  .modal-box input[type=text]:focus, .modal-box select:focus {
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
  <div class="header-title">Digital Records & Reporting System</div>
  <nav class="nav-links" aria-label="Admin navigation">
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="admin_schedule.php">Schedule</a>
    <a href="admin_profile.php">Profile</a>
     <a href="more.php">More</a>
    <a href="admin_logout.php" onclick="return confirmLogout()">Logout</a>
  </nav>
</header>

<div class="dashboard-container">
  <div class="table-header">
    <h2>Students List</h2>
  </div>

  <table>
    <thead>
      <tr>
        <th>Student Number</th>
        <th>Full Name</th>
        <th>Email</th>
        <th>College</th>
        <th>Course</th>
        <th>Year Level</th>
        <th>Created At</th>
        <th style="min-width: 140px;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if(count($students) === 0): ?>
        <tr><td colspan="8" style="text-align:center; padding:20px;">No students found.</td></tr>
      <?php else: ?>
        <?php foreach($students as $student): ?>
          <tr>
            <td><?php echo htmlspecialchars($student['student_number']); ?></td>
            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
            <td><?php echo htmlspecialchars($student['email']); ?></td>
            <td>
              <?php
                $colName = "";
                foreach($colleges as $col) {
                  if ($col['id'] == $student['college_id']) {
                    $colName = $col['name'];
                    break;
                  }
                }
                echo htmlspecialchars($colName);
              ?>
            </td>
            <td>
              <?php
                $courseName = "";
                foreach($courses as $course) {
                  if ($course['id'] == $student['course_id']) {
                    $courseName = $course['name'];
                    break;
                  }
                }
                echo htmlspecialchars($courseName);
              ?>
            </td>
            <td><?php echo htmlspecialchars($student['year_level']); ?></td>
            <td><?php echo htmlspecialchars($student['created_at']); ?></td>
            <td>
              <button class="action-btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($student)); ?>)">Edit</button>

              <form method="post" style="display:inline;" onsubmit="return confirm('Confirm delete this student?');">
                <input type="hidden" name="id" value="<?php echo $student['id']; ?>" />
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
    <h3 id="modalTitle">Edit Student</h3>
    <form method="post" id="editForm">
      <input type="hidden" name="id" id="edit-id" />
      
      <label for="edit-student_number">Student Number</label>
      <input type="text" name="student_number" id="edit-student_number" required autocomplete="off" />

      <label for="edit-full_name">Full Name</label>
      <input type="text" name="full_name" id="edit-full_name" required autocomplete="off" />

      <label for="edit-email">Email</label>
      <input type="text" name="email" id="edit-email" required autocomplete="off" />

      <label for="edit-college_id">College</label>
      <select name="college_id" id="edit-college_id" onchange="updateModalCourses()" required>
        <option value="">-- Select College --</option>
        <?php foreach($colleges as $college): ?>
          <option value="<?php echo $college['id']; ?>"><?php echo htmlspecialchars($college['name']); ?></option>
        <?php endforeach; ?>
      </select>

      <label for="edit-course_id">Course</label>
      <select name="course_id" id="edit-course_id" required>
        <option value="">-- Select Course --</option>
      </select>

      <label for="edit-year_level">Year Level</label>
      <select name="year_level" id="edit-year_level" required>
        <option value="">-- Select Year Level --</option>
        <?php foreach ($yearLevels as $year): ?>
          <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
        <?php endforeach; ?>
      </select>

      <div class="modal-actions">
        <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
        <button type="submit" name="action" value="update" class="btn-save">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
  // Data for courses from PHP
  const allCourses = <?php echo json_encode($courses); ?>;

  function openEditModal(studentJson) {
    const student = typeof studentJson === 'string' ? JSON.parse(studentJson) : studentJson;

    document.getElementById('edit-id').value = student.id;
    document.getElementById('edit-student_number').value = student.student_number;
    document.getElementById('edit-full_name').value = student.full_name;
    document.getElementById('edit-email').value = student.email;
    document.getElementById('edit-college_id').value = student.college_id;
    updateModalCourses(student.course_id);
    document.getElementById('edit-year_level').value = student.year_level || "";

    document.getElementById('editModal').classList.add('active');
    document.getElementById('edit-student_number').focus();
  }

  function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
  }

  function updateModalCourses(selectedCourseId = null) {
    const collegeId = document.getElementById('edit-college_id').value;
    const courseSelect = document.getElementById('edit-course_id');
    courseSelect.innerHTML = '<option value="">-- Select Course --</option>';
    if (!collegeId) return;

    allCourses.forEach(course => {
      if (course.college_id == collegeId) {
        const opt = document.createElement('option');
        opt.value = course.id;
        opt.textContent = course.name;
        if (selectedCourseId && selectedCourseId == course.id) opt.selected = true;
        courseSelect.appendChild(opt);
      }
    });
  }

  // Close modal on clicking outside the modal box
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
