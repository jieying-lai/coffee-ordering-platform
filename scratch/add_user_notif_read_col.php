<?php
require_once __DIR__ . '/../includes/db_connect.php';

// Add last_notif_read_at DATETIME column to users table if it doesn't exist
$checkCol = $conn->query("SHOW COLUMNS FROM users LIKE 'last_notif_read_at'");
if ($checkCol->num_rows === 0) {
    if ($conn->query("ALTER TABLE users ADD COLUMN last_notif_read_at DATETIME NULL DEFAULT NULL")) {
        echo "Successfully added last_notif_read_at column to users table!\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
} else {
    echo "Column last_notif_read_at already exists in users table.\n";
}
