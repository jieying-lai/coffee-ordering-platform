<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($userId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Please log in to use live customer care chat.']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

if ($action === 'fetch_messages') {
    $stmt = $conn->prepare("SELECT * FROM chat_messages WHERE user_id = ? ORDER BY created_at ASC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $messages = [];
    while ($row = $res->fetch_assoc()) {
        $messages[] = [
            'id' => $row['id'],
            'sender' => $row['sender_type'],
            'text' => htmlspecialchars($row['message']),
            'time' => date('h:i A', strtotime($row['created_at']))
        ];
    }
    $stmt->close();

    echo json_encode(['status' => 'success', 'messages' => $messages]);
    exit;
}

if ($action === 'send_message') {
    $msg = trim($_POST['message'] ?? '');
    if (empty($msg)) {
        echo json_encode(['status' => 'error', 'message' => 'Message cannot be empty.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, sender_type, message) VALUES (?, 'user', ?)");
    $stmt->bind_param("is", $userId, $msg);
    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
exit;
