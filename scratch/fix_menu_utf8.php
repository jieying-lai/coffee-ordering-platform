<?php
require_once __DIR__ . '/../includes/db_connect.php';
$conn->set_charset("utf8mb4");

$updates = [
    "UPDATE menu_items SET name = 'Café au Lait' WHERE item_id = 51 OR name LIKE '%Caf%au Lait%'",
    "UPDATE menu_items SET name = 'Café Latte' WHERE item_id = 48 OR name LIKE '%Caf%Latte%'",
    "UPDATE menu_items SET name = 'Seasalt Dark Choco Spänner' WHERE item_id = 17 OR name LIKE '%Choco Sp%nner%'",
    "UPDATE menu_items SET name = 'Pineapple Coconut Smoothie Piña' WHERE item_id = 73 OR name LIKE '%Pi%a%'"
];

foreach ($updates as $sql) {
    $conn->query($sql);
}

$res = $conn->query("SELECT item_id, name FROM menu_items WHERE item_id IN (17, 48, 51, 73)");
echo "Updated Items:\n";
while ($row = $res->fetch_assoc()) {
    echo "ID: " . $row['item_id'] . " | Name: " . $row['name'] . "\n";
}
