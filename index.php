<?php
session_start();
require 'db.php';

$response = [
  'status' => '',
  'message' => ''
];

// Handle Login
if (isset($_POST['login'])) {
  $email = $_POST['email'];
  $password = $_POST['password'];

  $stmt = $conn->prepare("SELECT id, password FROM students WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows === 1) {
    $stmt->bind_result($id, $hashedPassword);
    $stmt->fetch();

    if (password_verify($password, $hashedPassword)) {
      $_SESSION['student_id'] = $id;
      header("Location: dashboard.php");
      exit();
    } else {
      $response = ['status' => 'error', 'message' => 'Invalid password'];
    }
  } else {
    $response = ['status' => 'error', 'message' => 'Email not found'];
  }
  $stmt->close();
}

// Handle Signup
if (isset($_POST['signup'])) {
  $student_number = $_POST['student_number'];
  $full_name = $_POST['full_name'];
  $college_id = $_POST['college_id'];
  $course_id = $_POST['course_id'] ?? null;
  $year_level = $_POST['year_level'];
  $email = $_POST['email'];
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

  if (!$course_id) {
    $response = ['status' => 'error', 'message' => 'Please select a course'];
  } else {
    $check = $conn->prepare("SELECT id FROM students WHERE student_number = ? OR email = ?");
    $check->bind_param("ss", $student_number, $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
      $response = ['status' => 'error', 'message' => 'Student number or email already exists'];
    } else {
      $stmt = $conn->prepare("INSERT INTO students (student_number, full_name, college_id, course_id, year_level, email, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("ssiisss", $student_number, $full_name, $college_id, $course_id, $year_level, $email, $password);

      if ($stmt->execute()) {
        $last_id = $stmt->insert_id;
        $_SESSION['student_id'] = $last_id;
        header("Location: dashboard.php");
        exit();
      } else {
        $response = ['status' => 'error', 'message' => 'Error during signup'];
      }

      $stmt->close();
    }
    $check->close();
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Login & Signup</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: linear-gradient(rgba(255,255,255,0.8), rgba(255,255,255,0.6)), url('images/landscape.jpg') no-repeat center center fixed;
      background-size: cover;
    }
    header {
      background-color: maroon;
      color: white;
      padding: 15px;
      display: flex;
      align-items: center;
    }
    header img { height: 50px; margin-right: 15px; }
    header h1 { font-size: 18px; margin: 0; }

    .container {
      max-width: 400px;
      background: white;
      margin: 50px auto;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 2px 15px rgba(0,0,0,0.1);
    }

    h2 { text-align: center; margin-bottom: 20px; }

    .form-group { margin-bottom: 15px; }

    input, select {
      width: 100%;
      padding: 10px;
      border-radius: 5px;
      border: 1px solid #ccc;
    }

    button {
      width: 100%;
      background: maroon;
      color: white;
      padding: 10px;
      border: none;
      border-radius: 5px;
      font-weight: bold;
      cursor: pointer;
    }

    .toggle, .guest {
      text-align: center;
      margin-top: 10px;
    }

    .toggle a, .guest a {
      color: maroon;
      text-decoration: underline;
      cursor: pointer;
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 10;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.5);
    }

    .modal-content {
      background-color: #fff;
      margin: 15% auto;
      padding: 20px;
      border-radius: 10px;
      width: 90%;
      max-width: 400px;
      text-align: center;
    }

    .modal-success { border-left: 5px solid green; }
    .modal-error { border-left: 5px solid red; }
  </style>
</head>
<body>

<header>
  <img src="logo/guidance_office_logo.png" alt="Logo" />
  <h1>Digital Records & Reporting System - ZPPSU</h1>
</header>

<!-- Login Form -->
<div class="container" id="loginForm">
  <h2>Login</h2>
  <form method="POST">
    <div class="form-group">
      <input type="email" name="email" placeholder="Email" required />
    </div>
    <div class="form-group">
      <input type="password" name="password" placeholder="Password" required />
    </div>
    <button type="submit" name="login">Login</button>
  </form>
  <div class="toggle">Don't have an account? <a onclick="toggleForm()">Sign up</a></div>
 
</div>

<!-- Signup Form -->
<div class="container" id="signupForm" style="display:none;">
  <h2>Sign Up</h2>
  <form method="POST">
    <div class="form-group">
      <input type="text" name="student_number" placeholder="Student Number" required />
    </div>
    <div class="form-group">
      <input type="text" name="full_name" placeholder="Full Name" required />
    </div>
    <div class="form-group">
      <select name="college_id" id="collegeSelect" required>
        <option value="">Select College</option>
      </select>
    </div>
    <div class="form-group">
      <!-- Fixed here: no disabled -->
     <select name="course_id" id="courseSelect" required>
  <option value="">Select Course</option>
</select>

    </div>
    <div class="form-group">
      <input type="text" name="year_level" placeholder="Year Level (ex. 1st, 2nd, 3rd, or 4th)" required />
    </div>
    <div class="form-group">
      <input type="email" name="email" placeholder="Email" required />
    </div>
    <div class="form-group">
      <input type="password" name="password" placeholder="Password" required />
    </div>
    <button type="submit" name="signup">Sign Up</button>
  </form>
  <div class="toggle">Already have an account? <a onclick="toggleForm()">Login</a></div>
  
</div>

<!-- Modal -->
<div id="modal" class="modal">
  <div id="modal-content" class="modal-content">
    <p id="modal-message"></p>
  </div>
</div>

<script>
  function toggleForm() {
    const login = document.getElementById("loginForm");
    const signup = document.getElementById("signupForm");
    login.style.display = login.style.display === "none" ? "block" : "none";
    signup.style.display = signup.style.display === "none" ? "block" : "none";
  }

  function showModal(type, message) {
    const modal = document.getElementById("modal");
    const modalContent = document.getElementById("modal-content");
    const modalMessage = document.getElementById("modal-message");
    modalContent.className = "modal-content modal-" + type;
    modalMessage.innerText = message;
    modal.style.display = "block";
    setTimeout(() => { modal.style.display = "none"; }, 3000);
  }

  <?php if ($response['status']): ?>
    showModal("<?= $response['status'] ?>", "<?= $response['message'] ?>");
  <?php endif; ?>

  // Load college and courses dynamically
  document.addEventListener("DOMContentLoaded", () => {
    fetch("get_colleges.php")
      .then(res => res.json())
      .then(data => {
        const collegeSelect = document.getElementById("collegeSelect");
        data.forEach(college => {
          const option = document.createElement("option");
          option.value = college.id;
          option.text = college.name;
          collegeSelect.appendChild(option);
        });
      });

    document.getElementById("collegeSelect").addEventListener("change", function () {
      const collegeId = this.value;
      const courseSelect = document.getElementById("courseSelect");
      courseSelect.innerHTML = '<option value="">Loading...</option>';

      fetch("get_courses.php?college_id=" + collegeId)
        .then(res => res.json())
        .then(data => {
          courseSelect.innerHTML = '';
          if (data.length > 0) {
            data.forEach(course => {
              const option = document.createElement("option");
              option.value = course.id;
              option.text = course.name;
              courseSelect.appendChild(option);
            });
            courseSelect.selectedIndex = 0;
          } else {
            courseSelect.innerHTML = '<option value="">No courses found</option>';
          }
        });
    });
  });
</script>

</body>
</html>
