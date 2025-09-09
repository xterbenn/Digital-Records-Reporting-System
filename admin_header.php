<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Admin Header</title>
<style>
  body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f8f8f8;
  }
  .admin-header {
    background-color: maroon;
    color: white;
    display: flex;
    align-items: center;
    padding: 14px 25px;
    position: relative;
    height: 64px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
  }
  /* Center text container */
  .header-title {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    font-weight: 800;
    font-size: 28px;
    user-select: none;
    letter-spacing: 1.2px;
  }
  /* Nav container on right */
  .nav-links {
    margin-left: auto;
    display: flex;
    align-items: center;
    font-weight: 700;
    font-size: 18px;
  }
  .nav-links a {
    color: white;
    text-decoration: none;
    padding: 10px 16px;
    position: relative;
    transition: 
      color 0.25s ease,
      transform 0.2s ease;
    user-select: none;
  }
  /* vertical separator line between links */
  .nav-links a + a::before {
    content: "";
    position: absolute;
    left: 0;
    top: 25%;
    height: 50%;
    width: 2px;
    background: rgba(255, 255, 255, 0.6);
    border-radius: 1px;
  }
  .nav-links a:hover {
    color: #ffb3b3; /* lighter pinkish */
    transform: scale(1.1);
    text-shadow: 0 0 8px #ffb3b3;
  }
  /* On smaller screens, adjust layout */
  @media (max-width: 600px) {
    .nav-links {
      font-size: 14px;
    }
    .admin-header {
      height: 54px;
      padding: 10px 15px;
    }
    .header-title {
      font-size: 22px;
    }
  }
</style>
</head>
<body>
  <header class="admin-header">
    <div class="header-title">Digital Reporting System</div>
    <nav class="nav-links" aria-label="Admin navigation">
      <a href="students.php">Students</a>
      <a href="guest.php">Guests</a>
      <a href="admin_schedule.php">Schedule</a>
      <a href="profile.php">Profile</a>
      <a href="admin_logout.php">Logout</a>
    </nav>
  </header>
</body>
</html>
