<?php
session_start();
require_once '../includes/db_connect.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

$selectedUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$messageSent = '';

// Handle Admin Sending Reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_admin_reply'])) {
    $replyUserId = (int)$_POST['user_id'];
    $replyText = trim($_POST['reply_message'] ?? '');

    if ($replyUserId > 0 && !empty($replyText)) {
        $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, sender_type, message) VALUES (?, 'admin', ?)");
        $stmt->bind_param("is", $replyUserId, $replyText);
        $stmt->execute();
        $stmt->close();
        $messageSent = 'Reply sent successfully!';
        $selectedUserId = $replyUserId;
    }
}

// Fetch list of users with chat history
$chatUsers = [];
$uSql = "SELECT DISTINCT cm.user_id, u.fullname, u.email, 
                (SELECT message FROM chat_messages WHERE user_id = cm.user_id ORDER BY created_at DESC LIMIT 1) as last_msg,
                (SELECT created_at FROM chat_messages WHERE user_id = cm.user_id ORDER BY created_at DESC LIMIT 1) as last_time
         FROM chat_messages cm
         JOIN users u ON u.id = cm.user_id
         ORDER BY last_time DESC";
$uRes = $conn->query($uSql);
while ($row = $uRes->fetch_assoc()) {
    $chatUsers[] = $row;
}

if ($selectedUserId === 0 && !empty($chatUsers)) {
    $selectedUserId = (int)$chatUsers[0]['user_id'];
}

// Fetch active thread messages
$activeMessages = [];
$activeUser = null;
if ($selectedUserId > 0) {
    $uStmt = $conn->prepare("SELECT id, fullname, email FROM users WHERE id = ?");
    $uStmt->bind_param("i", $selectedUserId);
    $uStmt->execute();
    $activeUser = $uStmt->get_result()->fetch_assoc();
    $uStmt->close();

    $mStmt = $conn->prepare("SELECT * FROM chat_messages WHERE user_id = ? ORDER BY created_at ASC");
    $mStmt->bind_param("i", $selectedUserId);
    $mStmt->execute();
    $mRes = $mStmt->get_result();
    while ($r = $mRes->fetch_assoc()) {
        $activeMessages[] = $r;
    }
    $mStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <title>Cozy Admin — Customer Care Live Chat</title>
</head>
<body style="background: #f4efe9; min-height: 100vh;">

  <!-- ADMIN TOPBAR NAV -->
  <nav style="background: var(--color-primary); color: #fff; padding: 14px 5%; display: flex; justify-content: space-between; align-items: center;">
    <div style="font-weight: 700; font-size: 1.2rem; color: #fff;">☕ Barista Admin Portal</div>
    <div style="display: flex; gap: 16px; font-weight: 600; font-size: 0.9rem;">
      <a href="manage_orders.php" style="color: #fff; text-decoration: none;">📦 Manage Orders</a>
      <a href="manage_chat.php" style="color: #fcd34d; font-weight: 800; text-decoration: none;">💬 Customer Care Chat</a>
      <a href="admin_logout.php" style="color: #f87171; text-decoration: none;">Logout</a>
    </div>
  </nav>

  <div class="container" style="max-width: 1200px; margin: 30px auto; padding: 0 20px;">
    
    <h2 style="font-size: 1.6rem; color: var(--color-primary); margin-bottom: 20px;">💬 Customer Care &amp; Inquiry Messages</h2>

    <?php if (empty($chatUsers)): ?>
      <div style="background: #fff; padding: 40px; border-radius: 16px; text-align: center; color: #666;">
        ☕ No customer chat messages received yet.
      </div>
    <?php else: ?>

      <div style="display: grid; grid-template-columns: 320px 1fr; gap: 24px; background: #fff; border-radius: 16px; border: 1px solid #e0d5c4; overflow: hidden; min-height: 520px; box-shadow: 0 8px 24px rgba(0,0,0,0.06);">
        
        <!-- LEFT COLUMN: USER CONVERSATION LIST -->
        <div style="border-right: 1px solid #eee; background: #faf6f0; padding: 16px; display: flex; flex-direction: column; gap: 8px; overflow-y: auto;">
          <div style="font-weight: 700; font-size: 0.85rem; color: #777; text-transform: uppercase; margin-bottom: 8px;">Customer Conversations</div>
          
          <?php foreach ($chatUsers as $cu): 
            $isSelected = ((int)$cu['user_id'] === $selectedUserId);
          ?>
            <a href="manage_chat.php?user_id=<?php echo $cu['user_id']; ?>" style="text-decoration: none; display: block; padding: 12px 14px; border-radius: 12px; background: <?php echo $isSelected ? 'var(--color-accent-dark)' : '#ffffff'; ?>; color: <?php echo $isSelected ? '#ffffff' : '#333333'; ?>; border: 1px solid <?php echo $isSelected ? 'var(--color-accent-dark)' : '#e5dace'; ?>; transition: all 0.2s ease;">
              <div style="font-weight: 700; font-size: 0.95rem;"><?php echo htmlspecialchars($cu['fullname']); ?></div>
              <div style="font-size: 0.78rem; opacity: 0.85; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($cu['last_msg']); ?></div>
              <div style="font-size: 0.72rem; opacity: 0.7; margin-top: 4px;"><?php echo date('M d · h:i A', strtotime($cu['last_time'])); ?></div>
            </a>
          <?php endforeach; ?>
        </div>

        <!-- RIGHT COLUMN: CHAT THREAD & REPLY FORM -->
        <div style="display: flex; flex-direction: column; height: 100%;">
          
          <!-- THREAD HEADER -->
          <div style="padding: 16px 20px; border-bottom: 1px solid #eee; background: #fdfaf6; display: flex; justify-content: space-between; align-items: center;">
            <div>
              <h3 style="margin: 0; font-size: 1.1rem; color: var(--color-primary);"><?php echo htmlspecialchars($activeUser['fullname'] ?? 'Customer'); ?></h3>
              <div style="font-size: 0.8rem; color: #777;"><?php echo htmlspecialchars($activeUser['email'] ?? ''); ?></div>
            </div>
            <span class="tag-chip" style="background: #e0e7ff; color: #3730a3; padding: 4px 10px; border-radius: 999px; font-weight: 700; font-size: 0.75rem;">LIVE CHAT</span>
          </div>

          <!-- MESSAGES BOX -->
          <div style="flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; background: #ffffff;">
            <?php foreach ($activeMessages as $m): 
              $isAdmin = ($m['sender_type'] === 'admin');
            ?>
              <div style="align-self: <?php echo $isAdmin ? 'flex-end' : 'flex-start'; ?>; max-width: 75%; background: <?php echo $isAdmin ? 'var(--color-accent-dark)' : '#f4ede4'; ?>; color: <?php echo $isAdmin ? '#ffffff' : '#4a3b32'; ?>; padding: 12px 16px; border-radius: 16px; font-size: 0.92rem; box-shadow: 0 2px 6px rgba(0,0,0,0.05);">
                <div style="font-weight: 700; font-size: 0.75rem; opacity: 0.8; margin-bottom: 4px;"><?php echo $isAdmin ? '☕ Cozy Barista Team' : htmlspecialchars($activeUser['fullname']); ?></div>
                <div><?php echo nl2br(htmlspecialchars($m['message'])); ?></div>
                <div style="font-size: 0.7rem; opacity: 0.7; margin-top: 6px; text-align: right;"><?php echo date('h:i A', strtotime($m['created_at'])); ?></div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- ADMIN REPLY FORM -->
          <form action="manage_chat.php?user_id=<?php echo $selectedUserId; ?>" method="POST" style="padding: 16px; border-top: 1px solid #eee; background: #faf6f0; display: flex; gap: 10px;">
            <input type="hidden" name="user_id" value="<?php echo $selectedUserId; ?>">
            <input type="text" name="reply_message" placeholder="Type your barista reply to customer..." style="flex: 1; padding: 10px 16px; border-radius: 20px; border: 1px solid #d0c4b8; font-size: 0.9rem; outline: none;" required>
            <button type="submit" name="send_admin_reply" style="background: var(--color-accent-dark); color: #fff; border: none; border-radius: 20px; padding: 10px 22px; font-weight: 700; cursor: pointer;">Send Reply ☕</button>
          </form>

        </div>

      </div>

    <?php endif; ?>

  </div>

</body>
</html>
