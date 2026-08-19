<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Handle AJAX Mark All as Read (Persisted to Database)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_read') {
    $_SESSION['notif_read_all'] = true;
    if ($userId > 0) {
        // 1. Update database table records
        $uStmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? OR user_id = 0");
        $uStmt->bind_param("i", $userId);
        $uStmt->execute();
        $uStmt->close();

        // 2. Persist last_notif_read_at timestamp to user's database row so read status survives logins/logouts
        $usrUpd = $conn->prepare("UPDATE users SET last_notif_read_at = NOW() WHERE id = ?");
        $usrUpd->bind_param("i", $userId);
        $usrUpd->execute();
        $usrUpd->close();
    }
    echo json_encode(['status' => 'success']);
    exit;
}

$isMarkedAllRead = !empty($_SESSION['notif_read_all']);
$lastNotifReadAt = null;
$notifications = [];

if ($userId > 0) {
    // 1. Fetch user data including persistent last_notif_read_at timestamp
    $userStmt = $conn->prepare("SELECT birthday, is_rewards_member, last_notif_read_at FROM users WHERE id = ?");
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $userData = $userStmt->get_result()->fetch_assoc();
    $userStmt->close();

    if ($userData && !empty($userData['last_notif_read_at'])) {
        $lastNotifReadAt = strtotime($userData['last_notif_read_at']);
    }

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

    // 2. Fetch notifications from DB table (Up to 10)
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? OR user_id = 0 ORDER BY created_at DESC LIMIT 10");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        // Normalize any order notification link target to ../profile/orders.php
        if (isset($row['link']) && (strpos($row['link'], 'profile/index.php') !== false || empty($row['link'])) && (isset($row['type']) && $row['type'] === 'order' || strpos(strtolower($row['title'] ?? ''), 'order') !== false)) {
            $row['link'] = '../profile/orders.php';
        }
        $notifications[] = $row;
    }
    $stmt->close();

    // 3. Check recent order statuses and link directly to My Orders & Live Status page
    $ordStmt = $conn->prepare("SELECT order_id, status, order_date FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 3");
    $ordStmt->bind_param("i", $userId);
    $ordStmt->execute();
    $ordRes = $ordStmt->get_result();
    while ($ord = $ordRes->fetch_assoc()) {
        $st = strtolower($ord['status']);
        $statusMsg = 'Order placed successfully. Click to view live barista status!';
        if ($st === 'preparing') $statusMsg = 'Barista is handcrafting your order right now ☕';
        if ($st === 'ready') $statusMsg = 'Your order is ready for pickup at our barista counter! 🎉';
        if ($st === 'completed') $statusMsg = 'Order completed. View full digital receipt & order summary.';

        $notifications[] = [
            'id' => 'ord_' . $ord['order_id'],
            'title' => '☕ Order #' . $ord['order_id'] . ' (' . ucfirst($ord['status']) . ')',
            'message' => $statusMsg,
            'link' => '../profile/orders.php',
            'created_at' => $ord['order_date']
        ];
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
    'message' => 'Check out our curated drink & food offers this month!',
    'link' => '../offers/index.php',
    'created_at' => date('Y-m-d H:i:s')
];

// Slice to maximum 10 notifications
$notifications = array_slice($notifications, 0, 10);

// Count unread and enforce is_read status persistently
$unreadCount = 0;
foreach ($notifications as &$n) {
    $itemTime = strtotime($n['created_at'] ?? 'now');
    
    // If user clicked mark_read in this session OR notification timestamp is before last_notif_read_at timestamp in database
    if ($isMarkedAllRead || ($lastNotifReadAt && $itemTime <= $lastNotifReadAt + 5)) {
        $n['is_read'] = 1;
    }
    
    if (empty($n['is_read']) || (int)$n['is_read'] === 0) {
        $unreadCount++;
    }
}

echo json_encode([
    'status' => 'success',
    'unread_count' => $unreadCount,
    'items' => $notifications
]);
exit;
