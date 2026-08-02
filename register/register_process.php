<?php
// register/register_process.php

session_start();
require_once "../db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // 1. Sanitize & Retrieve Inputs
    $fullname         = trim($_POST['fullname']);
    $email            = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $username         = trim($_POST['username']);
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 2. Server-side Validation
    if (empty($fullname) || empty($email) || empty($username) || empty($password) || empty($confirm_password)) {
        die("Error: All fields are required.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Error: Invalid email format.");
    }

    if ($password !== $confirm_password) {
        die("Error: Passwords do not match.");
    }

    if (strlen($password) < 8) {
        die("Error: Password must be at least 8 characters long.");
    }

    // 3. Check for Existing Username or Email
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check_stmt->bind_param("ss", $username, $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        die("Error: Username or Email is already registered.");
    }
    $check_stmt->close();

    // 4. Hash Password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 5. Insert User into Database
    $insert_stmt = $conn->prepare("INSERT INTO users (fullname, email, username, password) VALUES (?, ?, ?, ?)");
    $insert_stmt->bind_param("ssss", $fullname, $email, $username, $hashed_password);

    if ($insert_stmt->execute()) {
        $insert_stmt->close();
        $conn->close();

        // Display Pop-up Alert then redirect to Login Page
        echo "<script>
                alert('Registration Successful! Click OK to continue to the Login Page.');
                window.location.href = '../login/index.php';
              </script>";
        exit();
    } else {
        echo "Error: " . $conn->error;
    }

    $insert_stmt->close();
    $conn->close();

} else {
    header("Location: index.php");
    exit();
}
?>