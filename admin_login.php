<?php
session_start();
require 'db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, name, password FROM admin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $name, $hashed_password);
        $stmt->fetch();
        if (password_verify($password, $hashed_password)) {
            $_SESSION['admin_id'] = $id;
            $_SESSION['admin_name'] = $name;
            header("Location: admin_dashboard.php");
            exit;
        } else {
            $message = "Invalid email or password.";
        }
    } else {
        $message = "Invalid email or password.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Admin Login</title>
<style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background: url('images/landscape.jpg') no-repeat center center fixed;
        background-size: cover;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .container {
        width: 100%;
        max-width: 400px;
        background: rgba(255, 255, 255, 0.95);
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.2);
    }
    h2 {
        text-align: center;
        color: maroon;
        margin-bottom: 20px;
    }
    input[type=email], input[type=password] {
        width: 100%;
        padding: 12px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 14px;
        box-sizing: border-box;
    }
    button {
        width: 100%;
        padding: 12px;
        background-color: maroon;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer;
        transition: 0.3s ease;
    }
    button:hover {
        background-color: #a00000;
    }
    .message {
        text-align: center;
        color: maroon;
        margin-top: 10px;
    }
    .link {
        text-align: center;
        margin-top: 15px;
    }
    .link a {
        color: maroon;
        text-decoration: none;
    }
    .link a:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>
<div class="container">
    <h2>Admin Login</h2>
    <form method="POST">
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Log In</button>
    </form>
    <?php if ($message) echo "<p class='message'>$message</p>"; ?>
    <div class="link">
        <p>Don't have an account? <a href="admin_signup.php">Sign Up</a></p>
    </div>
</div>
</body>
</html>
