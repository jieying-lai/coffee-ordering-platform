<?php
// ============================================
// COZY COFFEE CO. — ADMIN LOGIN PROCESSING
// Validates the submitted username/password against
// the `admins` table and starts a session on success.
// ============================================

session_start();
require_once '../includes/db_connect.php';

$username = trim($_POST['admin_username'] ?? '');
$password = $_POST['admin_password'] ?? '';

if ($username === '' || $password === '') {
    header('Location: index.php?error=empty');
    exit;
}

// Prepared statement — protects against SQL injection
$stmt = $conn->prepare('SELECT admin_id, username, password_hash FROM admins WHERE username = ?');
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();

    // password_verify() checks the plain password against the bcrypt hash
    // stored in the database — the plain password is never stored.
    if (password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_username'] = $admin['username'];

        $stmt->close();
        $conn->close();

        header('Location: dashboard.php');
        exit;
    }
}

$stmt->close();
$conn->close();

header('Location: index.php?error=invalid');
exit;