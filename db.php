<?php
// db.php
$host = "localhost";
$user = "root";
$password = ""; // Default XAMPP password is empty
$dbname = "cozy_coffee_db"; // Make sure everyone uses this exact database name

$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>