<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Handle AJAX Mark All as Read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_read') {
    $_SESSION['notif_read_all'] = true;
    if ($userId > 0) {
        $uStmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? OR user_id = 0");
        $uStmt->bind_param("i", $userId);
        $uStmt->execute();
        $uStmt->close();
    }
    echo json_encode(['status' => 'success']);
    exit;
}

// Check if user has marked all as read
if (!empty($_SESSION['notif_read_all'])) {
    echo json_encode([
        'status' => 'success',
        'unread_count' => 0,
        'items' => []
    ]);
    exit;
}
if ($userId > 0) {
    // 1. Check if user is birthday month
    $userStmt = $conn->prepare("SELECT birthday, is_rewards_member FROM users WHERE id = ?");
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $userData = $userStmt->get_result()->fetch_assoc();
    $userStmt->close();

    if ($userData && !empty($userData['birthday'])) {
        $birthMonth = date('m', strtotime($userData['birthday']));
        $currentMonth = date('m');
        if ($birthMonth === $currentMonth) {
            $notifications[] = [
                'id' => 'bday',
                'title' => '🎂 Happy Birthday Month!',
                'message' => 'Claim your free birthday drink at Cozy Coffee Co. this month!',
                'link' => '../offers/index.php',
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
    }

    // 2. Fetch notifications from DB table
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? OR user_id = 0 ORDER BY created_at DESC LIMIT 5");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();

    // 3. Check recent order statuses
    $ordStmt = $conn->prepare("SELECT order_id, status, order_date FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 2");
    $ordStmt->bind_param("i", $userId);
    $ordStmt->execute();
    $ordRes = $ordStmt->get_result();
    while ($ord = $ordRes->fetch_assoc()) {
        if ($ord['status'] === 'Ready') {
            $notifications[] = [
                'id' => 'ord_' . $ord['order_id'],
                'title' => '☕ Order #' . $ord['order_id'] . ' is Ready!',
                'message' => 'Your takeaway order is ready for pickup at our counter.',
                'link' => '../profile/index.php',
                'created_at' => $ord['order_date']
            ];
        }
    }
    $ordStmt->close();
} else {
    $notifications[] = [
        'id' => 'guest_welcome',
        'title' => '🌟 Welcome to Cozy Coffee Co.',
        'message' => 'Log in or join Cozy Rewards to earn points & get member discounts!',
        'link' => '../login/index.php',
        'created_at' => date('Y-m-d H:i:s')
    ];
}

// System promotions fallback
$notifications[] = [
    'id' => 'promo_monthly',
    'title' => '🏷️ Member Monthly Offers',
    'message' => 'Check out our 3 curated drink & food offers this month!',
    'link' => '../offers/index.php',
    'created_at' => date('Y-m-d H:i:s')
];

echo json_encode([
    'status' => 'success',
    'unread_count' => count($notifications),
    'items' => array_slice($notifications, 0, 5)
]);
exit;
