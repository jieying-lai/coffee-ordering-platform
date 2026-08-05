<?php
// ============================================
// COZY COFFEE CO. — DATABASE CONNECTION
// Included by any page that needs to read/write
// the database, e.g.:
//   require_once '../includes/db_connect.php';
// ============================================

$db_host = 'localhost';
$db_name = 'cozy_coffee_co';
$db_user = 'root';   // default WAMP username
$db_pass = '';       // default WAMP password is blank

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');