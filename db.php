<?php
// db.php — Try adding 3306 or 3307 as the 5th parameter

$host = "127.0.0.1"; // Using IP instead of localhost often fixes Wamp socket issues
$db_user = "root";
$db_pass = "";
$db_name = "cozy_coffee_db";
$port = 3308; // Try 3306 first. If it fails, change to 3307

$conn = new mysqli($host, $db_user, $db_pass, $db_name, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>