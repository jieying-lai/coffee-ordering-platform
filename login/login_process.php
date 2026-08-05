<?php
// login/login_process.php

session_start();
require_once "../db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username_or_email = trim($_POST['username']);
    $password          = $_POST['password'];

    if (empty($username_or_email) || empty($password)) {
        die("Error: Please enter both username/email and password.");
    }

    // Search for user by either username OR email
    $stmt = $conn->prepare("SELECT id, fullname, username, password FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username_or_email, $username_or_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify the hashed password
        if (password_verify($password, $user['password'])) {
            
            // Password correct! Store session variables
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['fullname']  = $user['fullname'];
            $_SESSION['username']  = $user['username'];

            $stmt->close();
            $conn->close();

            // Display success alert then redirect to Home page
            echo "<script>
                    alert('Welcome back, " . addslashes($user['fullname']) . "! Directing to home page...');
                    window.location.href = '../home/index.php';
                  </script>";
            exit();

        } else {
            // Invalid password
            echo "<script>
                    alert('Invalid password. Please try again.');
                    window.location.href = 'index.php';
                  </script>";
            exit();
        }

    } else {
        // User not found
        echo "<script>
                alert('No account found with that username or email.');
                window.location.href = 'index.php';
              </script>";
        exit();
    }

    $stmt->close();
    $conn->close();

} else {
    header("Location: index.php");
    exit();
}
?>
?>
