<?php
require_once __DIR__ . '/../includes/db_connect.php';

// Update all existing order notifications in the database to link directly to ../profile/orders.php
$query = "UPDATE notifications SET link = '../profile/orders.php' WHERE link LIKE '%profile/index.php%' AND (type = 'order' OR title LIKE '%Order%')";
if ($conn->query($query)) {
    echo "Successfully updated " . $conn->affected_rows . " notification link records in database to ../profile/orders.php!\n";
} else {
    echo "Error updating notification records: " . $conn->error . "\n";
}
