<?php
// ============================================
// COZY COFFEE CO. — DATABASE CONNECTION
// Included by any page that needs to read/write
// the database, e.g.:
//   require_once '../includes/db_connect.php';
// ============================================

$db_host = "127.0.0.1";
$db_user = "root";
$db_pass = "";
$db_name = "cozy_coffee_db";
$db_port = 3308; 

// Pass all 5 variables cleanly into mysqli
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');