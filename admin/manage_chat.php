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

// Helper: Smart Date Formatting ("Today", "Yesterday", or Full Date)
function formatSmartChatDate($datetimeStr) {
    $timestamp = strtotime($datetimeStr);
    $msgDate = date('Y-m-d', $timestamp);
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $timeStr = date('h:i A', $timestamp);

    if ($msgDate === $today) {
        return "Today · " . $timeStr;
    } elseif ($msgDate === $yesterday) {
        return "Yesterday · " . $timeStr;
    } else {
        return date('M d, Y · ', $timestamp) . $timeStr;
    }
}

function formatSmartDateHeader($datetimeStr) {
    $timestamp = strtotime($datetimeStr);
    $msgDate = date('Y-m-d', $timestamp);
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    if ($msgDate === $today) {
        return "Today";
    } elseif ($msgDate === $yesterday) {
        return "Yesterday";
    } else {
        return date('F d, Y', $timestamp);
    }
}

function getUserAvatarPath($profilePic) {
    if (empty($profilePic)) return '';
    if (preg_match('/^https?:\/\//i', $profilePic)) return $profilePic;
    
    $paths = [
        '../images/profiles/' . $profilePic,
        '../uploads/avatars/' . $profilePic,
        '../uploads/profiles/' . $profilePic,
        '../images/users/' . $profilePic,
        '../images/' . $profilePic,
        '../uploads/' . $profilePic
    ];
    foreach ($paths as $p) {
        if (file_exists($p)) return $p;
    }
    return '';
}

// Handle Delete Admin Message via AJAX POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_message') {
    $deleteMsgId = (int)($_POST['msg_id'] ?? 0);
    if ($deleteMsgId > 0) {
        $stmt = $conn->prepare("DELETE FROM chat_messages WHERE id = ? AND sender_type = 'admin'");
        $stmt->bind_param("i", $deleteMsgId);
        $stmt->execute();
        $stmt->close();

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'msg_id' => $deleteMsgId]);
        exit();
    }
}

// Handle Admin Sending Reply (Supports AJAX Async & Normal Post)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_admin_reply'])) {
    $replyUserId = (int)$_POST['user_id'];
    $replyText = trim($_POST['reply_message'] ?? '');

    if ($replyUserId > 0 && !empty($replyText)) {
        $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, sender_type, message) VALUES (?, 'admin', ?)");
        $stmt->bind_param("is", $replyUserId, $replyText);
        $stmt->execute();
        $newMsgId = $stmt->insert_id;
        $stmt->close();
        
        $now = date('Y-m-d H:i:s');
        $formattedDate = formatSmartChatDate($now);

        // If AJAX async request, return JSON response without page reload!
        if ((!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['is_ajax'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'msg_id' => $newMsgId,
                'message' => $replyText,
                'user_id' => $replyUserId,
                'formatted_date' => $formattedDate,
                'timestamp' => $now
            ]);
            exit();
        }

        $messageSent = 'Reply sent successfully!';
        $selectedUserId = $replyUserId;
    }
}

// Fetch list of users with chat history and last sender type
$chatUsers = [];
$uSql = "SELECT DISTINCT cm.user_id, 
                COALESCE(u.fullname, u.username, CONCAT('Customer #', cm.user_id)) as fullname, 
                COALESCE(u.email, 'Registered User') as email, 
                u.username, u.profile_pic, u.birthday, u.gender, u.phone, u.created_at, u.points,
                (SELECT message FROM chat_messages WHERE user_id = cm.user_id ORDER BY created_at DESC, id DESC LIMIT 1) as last_msg,
                (SELECT sender_type FROM chat_messages WHERE user_id = cm.user_id ORDER BY created_at DESC, id DESC LIMIT 1) as last_sender,
                (SELECT created_at FROM chat_messages WHERE user_id = cm.user_id ORDER BY created_at DESC LIMIT 1) as last_time
         FROM chat_messages cm
         LEFT JOIN users u ON u.id = cm.user_id
         ORDER BY last_time DESC";
$uRes = $conn->query($uSql);
if ($uRes) {
    while ($row = $uRes->fetch_assoc()) {
        $chatUsers[] = $row;
    }
}

if ($selectedUserId === 0 && !empty($chatUsers)) {
    $selectedUserId = (int)$chatUsers[0]['user_id'];
}

// Fetch active thread messages and detailed user info
$activeMessages = [];
$activeUser = null;
if ($selectedUserId > 0) {
    $uStmt = $conn->prepare("SELECT id, 
                                    COALESCE(fullname, username, CONCAT('Customer #', id)) as fullname, 
                                    COALESCE(email, 'Registered User') as email,
                                    username, profile_pic, birthday, gender, phone, created_at, points, rewards_points, is_rewards_member
                             FROM users WHERE id = ?");
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

$activeLastMsg = end($activeMessages);
$activeIsUnreplied = ($activeLastMsg && $activeLastMsg['sender_type'] === 'user');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/admin.css">
  <title>Cozy Admin — Manage Customer Care Chat</title>
  <style>
    .chat-user-item {
      transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .chat-user-item:hover:not(.active) {
      background: #FFFFFF !important;
      border-color: #C85A3E !important;
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(60, 42, 33, 0.08) !important;
    }
    .chat-bubble-admin {
      background: linear-gradient(135deg, #C85A3E 0%, #A8472F 100%) !important;
      color: #FFFFFF !important;
      border-radius: 14px 14px 2px 14px !important;
      box-shadow: 0 2px 8px rgba(200, 90, 62, 0.2) !important;
      width: fit-content !important;
      max-width: 50% !important;
      padding: 8px 14px !important;
    }
    .chat-bubble-user {
      background: #FAF4EB !important;
      color: #2C1C14 !important;
      border: 1.5px solid #E8DDD0 !important;
      border-radius: 14px 14px 14px 2px !important;
      box-shadow: 0 2px 6px rgba(60, 42, 33, 0.04) !important;
      width: fit-content !important;
      max-width: 50% !important;
      padding: 8px 14px !important;
    }
    .clickable-user-header {
      cursor: pointer;
      transition: opacity 0.2s ease, transform 0.2s ease;
    }
    .clickable-user-header:hover {
      opacity: 0.88;
      transform: translateY(-1px);
    }
    .btn-delete-msg {
      background: rgba(255, 255, 255, 0.25);
      border: none;
      color: #FFFFFF;
      font-size: 0.85rem;
      border-radius: 50%;
      width: 22px;
      height: 22px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.2s ease;
      line-height: 1;
    }
    .btn-delete-msg:hover {
      background: #DC2626;
      color: #FFFFFF;
      transform: scale(1.15);
    }
    
    /* BURGER CONVERSATIONS TOGGLE BUTTON & RESPONSIVE DRAWER */
    .btn-conv-toggle {
      display: none;
      align-items: center;
      gap: 6px;
      background: #FFFFFF;
      border: 1.5px solid #E8DDD0;
      color: #2C1C14;
      font-weight: 800;
      font-size: 0.82rem;
      padding: 7px 14px;
      border-radius: 14px;
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(60,42,33,0.04);
      transition: all 0.2s ease;
    }
    .btn-conv-toggle:hover {
      border-color: #C85A3E;
      color: #C85A3E;
    }
    .mobile-close-drawer-btn {
      display: none;
      background: transparent;
      border: none;
      font-size: 1.4rem;
      color: #7A685A;
      cursor: pointer;
    }

    @media (max-width: 900px) {
      .chat-main-grid {
        grid-template-columns: 1fr !important;
      }
      .btn-conv-toggle {
        display: inline-flex !important;
      }
      .mobile-close-drawer-btn {
        display: inline-block !important;
      }
      .conversations-col {
        position: fixed !important;
        top: 0;
        left: -330px;
        width: 310px !important;
        height: 100vh !important;
        z-index: 99999 !important;
        box-shadow: 10px 0 35px rgba(0, 0, 0, 0.25) !important;
        transition: left 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
        background: #FAF7F2 !important;
      }
      .conversations-col.show-drawer {
        left: 0 !important;
      }
      .conv-drawer-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(24, 15, 10, 0.6);
        z-index: 99990;
        backdrop-filter: blur(4px);
      }
      .conv-drawer-overlay.show-overlay {
        display: block !important;
      }
    }
  </style>
</head>
<body class="admin-page" style="background: #FAF7F2; min-height: 100vh;">

<?php $adminActivePage = 'chat'; require_once '../includes/admin_header_nav.php'; ?>

<!-- BACKDROP OVERLAY FOR MOBILE CONVERSATIONS DRAWER -->
<div id="convDrawerOverlay" class="conv-drawer-overlay" onclick="closeConvDrawer()"></div>

<div class="admin-wrap">
  <div class="admin-page-header">
    <a href="dashboard.php" class="btn-back-dashboard">&larr; Back to Dashboard</a>
    <h1 class="admin-header-title">Manage Customer Care Chat</h1>
    <p class="admin-header-subtitle">Read customer inquiry messages, feedback, and assist store visitors in real-time.</p>
  </div>

  <?php if (empty($chatUsers)): ?>
    <div style="background: #FFFFFF; padding: 50px 30px; border-radius: 20px; border: 1.5px solid #E8DDD0; text-align: center; color: #7A685A; box-shadow: 0 8px 24px rgba(60,42,33,0.04);">
      <div style="font-size: 2.5rem; margin-bottom: 10px;">💬</div>
      <div style="font-size: 1.1rem; font-weight: 700; color: #2C1C14;">No Customer Messages Received Yet</div>
      <p style="margin-top: 6px; font-size: 0.9rem;">Customer chat inquiries will appear here automatically.</p>
    </div>
  <?php else: ?>

    <div class="chat-main-grid" style="display: grid; grid-template-columns: 340px 1fr; gap: 0; background: #FFFFFF; border-radius: 22px; border: 1.5px solid #E8DDD0; overflow: hidden; min-height: 580px; box-shadow: 0 14px 38px rgba(60, 42, 33, 0.08), 0 0 0 1px rgba(200, 90, 62, 0.08);">
      
      <!-- LEFT COLUMN: CUSTOMER CONVERSATION LIST (SLIDE-OUT DRAWER ON SMALL SCREEN) -->
      <div id="conversationsCol" class="conversations-col" style="border-right: 1.5px solid #E8DDD0; background: #FAF7F2; padding: 20px 16px; display: flex; flex-direction: column; gap: 10px; height: 100%; box-sizing: border-box; overflow-y: auto;">
        <div style="font-weight: 800; font-size: 0.78rem; color: #8C6D58; text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 8px; padding-left: 4px; display: flex; justify-content: space-between; align-items: center;">
          <span>Customer Conversations</span>
          <button type="button" onclick="closeConvDrawer()" class="mobile-close-drawer-btn" title="Close Drawer">&times;</button>
        </div>
        
        <?php foreach ($chatUsers as $cu): 
          $lastSender = $cu['last_sender'] ?? '';
          $isUnreplied = ($lastSender === 'user');
          $isSelected = ((int)$cu['user_id'] === $selectedUserId);
          $showUnreadDot = ($isUnreplied && !$isSelected);
          $initial = strtoupper(substr($cu['fullname'], 0, 1));
          $avatarPath = getUserAvatarPath($cu['profile_pic'] ?? '');
        ?>
          <a href="manage_chat.php?user_id=<?php echo $cu['user_id']; ?>" 
             class="chat-user-item <?php echo $isSelected ? 'active' : ''; ?> <?php echo $isUnreplied ? 'unreplied-item' : ''; ?>"
             style="text-decoration: none; display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 14px; background: <?php echo $isSelected ? 'linear-gradient(135deg, #C85A3E 0%, #A8472F 100%)' : '#FFFFFF'; ?>; color: <?php echo $isSelected ? '#FFFFFF' : '#2C1C14'; ?>; border: 1.5px solid <?php echo $isSelected ? '#C85A3E' : ($isUnreplied ? '#FCD34D' : '#E8DDD0'); ?>; box-shadow: <?php echo $isSelected ? '0 6px 18px rgba(200, 90, 62, 0.3)' : '0 2px 8px rgba(60, 42, 33, 0.03)'; ?>;">
            
            <!-- READ & SHOW REAL PROFILE PIC OR INITIAL FALLBACK -->
            <div style="width: 44px; height: 44px; border-radius: 50%; background: <?php echo $isSelected ? 'rgba(255,255,255,0.22)' : '#FAF4EB'; ?>; color: <?php echo $isSelected ? '#FFFFFF' : '#C85A3E'; ?>; border: 1.5px solid <?php echo $isSelected ? 'rgba(255,255,255,0.35)' : '#E8DDD0'; ?>; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.95rem; flex-shrink: 0; overflow: hidden; position: relative;">
              <?php if (!empty($avatarPath)): ?>
                <img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
              <?php else: ?>
                <?php echo $initial; ?>
              <?php endif; ?>
            </div>

            <div style="flex: 1; min-width: 0;">
              <!-- FULL NAME: BOLD IF UNREPLIED OR SELECTED -->
              <div style="font-weight: <?php echo ($isUnreplied || $isSelected) ? '900' : '700'; ?>; font-size: 0.95rem; font-family: var(--font-heading, serif); margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: <?php echo $isSelected ? '#FFFFFF' : '#2C1C14'; ?>; display: flex; align-items: center; justify-content: space-between; gap: 6px;">
                <span class="user-fullname"><?php echo htmlspecialchars($cu['fullname']); ?></span>
                <?php if ($showUnreadDot): ?>
                  <!-- SMALL NOTIFICATION DOT REMINDER IF UNOPENED & UNREPLIED -->
                  <span class="unread-dot" style="width: 9px; height: 9px; border-radius: 50%; background: #C85A3E; box-shadow: 0 0 0 3px rgba(200, 90, 62, 0.25); display: inline-block; flex-shrink: 0;" title="Needs Reply"></span>
                <?php endif; ?>
              </div>

              <!-- LAST MSG SNIPPET: BOLD IF UNREPLIED -->
              <div class="user-last-msg" style="font-size: 0.8rem; font-weight: <?php echo $isUnreplied ? '800' : '500'; ?>; opacity: <?php echo $isSelected ? '0.95' : ($isUnreplied ? '1' : '0.75'); ?>; color: <?php echo $isSelected ? '#FAF7F2' : ($isUnreplied ? '#2C1C14' : '#665447'); ?>; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <?php echo htmlspecialchars($cu['last_msg']); ?>
              </div>

              <div class="user-last-time" style="font-size: 0.72rem; opacity: <?php echo $isSelected ? '0.88' : '0.65'; ?>; color: <?php echo $isSelected ? '#FFE8E0' : '#8C7A6D'; ?>; margin-top: 4px; font-weight: 600;">
                <?php echo formatSmartChatDate($cu['last_time']); ?>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- RIGHT COLUMN: CHAT THREAD & REPLY FORM -->
      <div style="display: flex; flex-direction: column; height: 100%; background: #FFFFFF;">
        
        <?php 
          $activeAvatar = getUserAvatarPath($activeUser['profile_pic'] ?? '');
          $activeInitial = strtoupper(substr($activeUser['fullname'] ?? 'C', 0, 1));
        ?>

        <!-- THREAD HEADER (CLICKABLE -> OPENS USER DETAILS POPUP) -->
        <div style="padding: 18px 24px; border-bottom: 1.5px solid #E8DDD0; background: linear-gradient(135deg, #FAF4EB 0%, #F4EDE4 100%); display: flex; justify-content: space-between; align-items: center; gap: 12px;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <!-- BURGER TOGGLE BUTTON TO OPEN CONVERSATIONS DRAWER ON SMALL SCREEN -->
            <button type="button" onclick="openConvDrawer()" class="btn-conv-toggle" title="Open Customer Conversations">
              ☰ <span>Conversations</span>
            </button>

            <div onclick="openUserDetailModal()" class="clickable-user-header" style="display: flex; align-items: center; gap: 14px;" title="Click to view Customer Details">
              <!-- AVATAR IMAGE OR INITIAL BADGE -->
              <div style="width: 46px; height: 46px; border-radius: 50%; background: #C85A3E; color: #FFFFFF; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem; border: 1.5px solid #A8472F; box-shadow: 0 4px 10px rgba(200,90,62,0.25); overflow: hidden; flex-shrink: 0;">
                <?php if (!empty($activeAvatar)): ?>
                  <img src="<?php echo htmlspecialchars($activeAvatar); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                  <?php echo $activeInitial; ?>
                <?php endif; ?>
              </div>
              <div>
                <!-- SHOW ONLY FULL NAME IN DIALOG THREAD HEADER -->
                <h3 style="margin: 0; font-size: 1.15rem; color: #2C1C14; font-family: var(--font-heading, serif); font-weight: 800; display: flex; align-items: center; gap: 8px;">
                  <span><?php echo htmlspecialchars($activeUser['fullname'] ?? 'Customer'); ?></span>
                  <span style="font-size: 0.76rem; font-weight: 700; color: #C85A3E; background: #FFFFFF; border: 1.5px solid #E8DDD0; padding: 2px 10px; border-radius: 12px; font-family: var(--font-body);">View Profile</span>
                </h3>
              </div>
            </div>
          </div>

          <!-- SMART STATUS BADGE: "Needs Reply" OR "Replied" -->
          <span id="chatHeaderBadge" style="background: <?php echo $activeIsUnreplied ? '#FEF3C7' : '#ECFDF5'; ?>; color: <?php echo $activeIsUnreplied ? '#D97706' : '#059669'; ?>; border: 1.5px solid <?php echo $activeIsUnreplied ? '#FCD34D' : '#A7F3D0'; ?>; padding: 5px 14px; border-radius: 20px; font-weight: 800; font-size: 0.75rem; letter-spacing: 0.5px; white-space: nowrap;">
            <?php echo $activeIsUnreplied ? 'Needs Reply' : 'Replied'; ?>
          </span>
        </div>

        <!-- FIXED-HEIGHT CHAT THREAD BOX (~4 MESSAGES VISIBLE, SCROLLABLE, AUTO-SCROLL TO BOTTOM) -->
        <div id="chatThreadBox" style="height: 380px; max-height: 380px; padding: 24px; overflow-y: auto; display: flex; flex-direction: column; gap: 14px; background: #FFFFFF;">
          <?php 
          $lastDateKey = '';
          foreach ($activeMessages as $m): 
            $isAdmin = ($m['sender_type'] === 'admin');
            $msgDateKey = date('Y-m-d', strtotime($m['created_at']));

            // Render Date Separator when day changes (Today, Yesterday, or Date)
            if ($msgDateKey !== $lastDateKey):
              $lastDateKey = $msgDateKey;
          ?>
              <div style="text-align: center; margin: 10px 0;">
                <span style="background: #FAF4EB; border: 1px solid #E8DDD0; color: #8C7A6D; font-size: 0.75rem; font-weight: 800; padding: 4px 14px; border-radius: 20px;">
                  <?php echo formatSmartDateHeader($m['created_at']); ?>
                </span>
              </div>
          <?php endif; ?>

            <div id="msg-bubble-<?php echo $m['id']; ?>" class="<?php echo $isAdmin ? 'chat-bubble-admin' : 'chat-bubble-user'; ?>" style="align-self: <?php echo $isAdmin ? 'flex-end' : 'flex-start'; ?>;">
              <div style="font-weight: 800; font-size: 0.72rem; opacity: 0.85; margin-bottom: 2px; display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                <span><?php echo $isAdmin ? 'Cozy Barista Team' : htmlspecialchars($activeUser['fullname']); ?></span>
                <?php if ($isAdmin): ?>
                  <!-- DELETE MESSAGE BUTTON FOR ADMIN MESSAGES -->
                  <button type="button" onclick="deleteAdminMessage(<?php echo $m['id']; ?>)" class="btn-delete-msg" title="Delete this message">&times;</button>
                <?php endif; ?>
              </div>
              <div style="font-size: 0.88rem; line-height: 1.35; white-space: pre-line; word-break: break-word;"><?php echo htmlspecialchars($m['message']); ?></div>
              <div style="font-size: 0.65rem; opacity: 0.75; margin-top: 3px; text-align: right; font-weight: 600;">
                <?php echo formatSmartChatDate($m['created_at']); ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- ADMIN ASYNC REPLY FORM -->
        <form id="adminReplyForm" action="manage_chat.php?user_id=<?php echo $selectedUserId; ?>" method="POST" style="padding: 18px 24px; border-top: 1.5px solid #E8DDD0; background: #FAF7F2; display: flex; gap: 12px; align-items: center;">
          <input type="hidden" name="user_id" id="reply_user_id" value="<?php echo $selectedUserId; ?>">
          <input type="hidden" name="send_admin_reply" value="1">
          <input type="hidden" name="is_ajax" value="1">
          <input type="text" name="reply_message" id="reply_message" placeholder="Type your barista reply to customer..." style="flex: 1; padding: 12px 18px; border-radius: 25px; border: 1.5px solid #E8DDD0; font-size: 0.92rem; outline: none; background: #FFFFFF; color: #2C1C14; box-shadow: 0 2px 8px rgba(60,42,33,0.03);" required>
          <button type="submit" style="background: linear-gradient(135deg, #C85A3E 0%, #A8472F 100%); color: #FFFFFF; border: none; border-radius: 25px; padding: 12px 24px; font-weight: 800; font-size: 0.9rem; cursor: pointer; box-shadow: 0 4px 14px rgba(200,90,62,0.3); transition: all 0.2s ease;">Send</button>
        </form>

      </div>

    </div>

  <?php endif; ?>

</div>

<!-- USER DETAILS POPUP MODAL (NO EMOJIS) -->
<div id="userDetailModal" style="display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100vw; height: 100vh; background: rgba(24, 15, 10, 0.75); backdrop-filter: blur(8px); justify-content: center; align-items: center; padding: 20px; box-sizing: border-box;">
  <div style="background: #FFFFFF; border-radius: 24px; border: 1.5px solid #E5D9CC; box-shadow: 0 25px 70px rgba(44, 28, 20, 0.35); max-width: 480px; width: 100%; padding: 28px 30px; position: relative; text-align: center; max-height: 90vh; overflow-y: auto;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1.5px solid #F4EDE4;">
      <h2 style="font-family: var(--font-heading); color: #2C1C14; margin: 0; font-size: 1.25rem; font-weight: 800;">
        Customer Profile
      </h2>
      <button type="button" onclick="closeUserDetailModal()" style="background: transparent; border: none; font-size: 1.6rem; color: #7A685A; cursor: pointer; line-height: 1;">&times;</button>
    </div>

    <!-- AVATAR PHOTO OR INITIAL -->
    <div style="margin: 0 auto 16px; width: 90px; height: 90px; border-radius: 50%; border: 3px solid #E8DDD0; overflow: hidden; box-shadow: 0 6px 20px rgba(60,42,33,0.12); display: flex; align-items: center; justify-content: center; background: #FAF4EB;">
      <?php if (!empty($activeAvatar)): ?>
        <img src="<?php echo htmlspecialchars($activeAvatar); ?>" alt="Customer Avatar" style="width: 100%; height: 100%; object-fit: cover;">
      <?php else: ?>
        <div style="font-size: 2.2rem; font-weight: 800; color: #C85A3E;"><?php echo $activeInitial; ?></div>
      <?php endif; ?>
    </div>

    <h3 style="font-family: var(--font-heading); font-size: 1.35rem; color: #2C1C14; margin: 0 0 18px 0; font-weight: 800;">
      <?php echo htmlspecialchars($activeUser['fullname'] ?? 'Customer'); ?>
    </h3>

    <!-- PROFILE INFORMATION DETAILS GRID (CLEAN TEXT WITHOUT EMOJIS) -->
    <div style="background: #FAF7F2; border: 1.5px solid #E8DDD0; border-radius: 16px; padding: 18px; text-align: left; display: flex; flex-direction: column; gap: 12px; font-size: 0.9rem;">
      <div style="display: flex; justify-content: space-between; align-items: center;">
        <span style="color: #7A685A; font-weight: 600;">Username</span>
        <strong style="color: #2C1C14; font-size: 0.88rem;"><?php echo htmlspecialchars(!empty($activeUser['username']) ? $activeUser['username'] : 'N/A'); ?></strong>
      </div>
      <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed #E8DDD0; padding-top: 10px;">
        <span style="color: #7A685A; font-weight: 600;">Email Address</span>
        <strong style="color: #2C1C14; font-size: 0.88rem;"><?php echo htmlspecialchars($activeUser['email'] ?? 'N/A'); ?></strong>
      </div>
      <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed #E8DDD0; padding-top: 10px;">
        <span style="color: #7A685A; font-weight: 600;">Phone Number</span>
        <strong style="color: #2C1C14; font-size: 0.88rem;"><?php echo htmlspecialchars(!empty($activeUser['phone']) ? $activeUser['phone'] : 'Not provided'); ?></strong>
      </div>
      <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed #E8DDD0; padding-top: 10px;">
        <span style="color: #7A685A; font-weight: 600;">Birthday</span>
        <strong style="color: #2C1C14; font-size: 0.88rem;"><?php echo htmlspecialchars(!empty($activeUser['birthday']) && $activeUser['birthday'] !== '0000-00-00' ? date('F d, Y', strtotime($activeUser['birthday'])) : 'Not specified'); ?></strong>
      </div>
      <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed #E8DDD0; padding-top: 10px;">
        <span style="color: #7A685A; font-weight: 600;">Gender</span>
        <strong style="color: #2C1C14; font-size: 0.88rem;"><?php echo htmlspecialchars(!empty($activeUser['gender']) ? ucfirst($activeUser['gender']) : 'Unspecified'); ?></strong>
      </div>
      <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed #E8DDD0; padding-top: 10px;">
        <span style="color: #7A685A; font-weight: 600;">Joined Date</span>
        <strong style="color: #2C1C14; font-size: 0.88rem;"><?php echo htmlspecialchars(!empty($activeUser['created_at']) ? date('M d, Y', strtotime($activeUser['created_at'])) : 'N/A'); ?></strong>
      </div>
      <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed #E8DDD0; padding-top: 10px;">
        <span style="color: #7A685A; font-weight: 600;">Rewards Points</span>
        <span style="background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; font-weight: 800; font-size: 0.82rem; padding: 3px 10px; border-radius: 12px;">
          <?php echo number_format((int)($activeUser['rewards_points'] ?? $activeUser['points'] ?? 0)); ?> pts
        </span>
      </div>
    </div>

    <div style="margin-top: 22px;">
      <button type="button" onclick="closeUserDetailModal()" style="width: 100%; padding: 12px; border-radius: 25px; font-size: 0.92rem; font-weight: 800; background: linear-gradient(135deg, #C85A3E 0%, #A8472F 100%); border: none; color: #FFFFFF; cursor: pointer; box-shadow: 0 4px 14px rgba(200,90,62,0.25);">
        Close Details
      </button>
    </div>
  </div>
</div>

<script>
function openUserDetailModal() {
  const m = document.getElementById('userDetailModal');
  if (m) {
    m.style.display = 'flex';
  }
}

function closeUserDetailModal() {
  const m = document.getElementById('userDetailModal');
  if (m) {
    m.style.display = 'none';
  }
}

function openConvDrawer() {
  const drawer = document.getElementById('conversationsCol');
  const overlay = document.getElementById('convDrawerOverlay');
  if (drawer) drawer.classList.add('show-drawer');
  if (overlay) overlay.classList.add('show-overlay');
}

function closeConvDrawer() {
  const drawer = document.getElementById('conversationsCol');
  const overlay = document.getElementById('convDrawerOverlay');
  if (drawer) drawer.classList.remove('show-drawer');
  if (overlay) overlay.classList.remove('show-overlay');
}

// Auto scroll chat thread to bottom on page load (display latest messages)
const chatThreadBox = document.getElementById('chatThreadBox');
if (chatThreadBox) {
  chatThreadBox.scrollTop = chatThreadBox.scrollHeight;
}

// DELETE ADMIN SENT MESSAGE VIA ASYNC AJAX
function deleteAdminMessage(msgId) {
  if (!confirm('⚠️ Are you sure you want to delete this message?')) return;

  const formData = new FormData();
  formData.append('action', 'delete_message');
  formData.append('msg_id', msgId);

  fetch('manage_chat.php', {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if (data.status === 'success') {
      const bubble = document.getElementById('msg-bubble-' + msgId);
      if (bubble) {
        bubble.style.transition = 'all 0.3s ease';
        bubble.style.opacity = '0';
        bubble.style.transform = 'scale(0.9)';
        setTimeout(() => bubble.remove(), 300);
      }
    }
  })
  .catch(err => console.error('Error deleting message:', err));
}

// Intercept Admin Reply Form submit for Async AJAX POST
const replyForm = document.getElementById('adminReplyForm');
if (replyForm) {
  replyForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const msgInput = document.getElementById('reply_message');
    const msgVal = msgInput.value.trim();
    if (!msgVal) return;

    const formData = new FormData(replyForm);

    fetch('manage_chat.php?user_id=' + document.getElementById('reply_user_id').value, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: formData
    })
    .then(r => r.json())
    .then(data => {
      if (data.status === 'success') {
        // Clear input field
        msgInput.value = '';

        // Append new message bubble with delete button smoothly
        const newBubble = document.createElement('div');
        newBubble.id = 'msg-bubble-' + data.msg_id;
        newBubble.className = 'chat-bubble-admin';
        newBubble.style.cssText = 'align-self: flex-end;';
        newBubble.innerHTML = `
          <div style="font-weight: 800; font-size: 0.72rem; opacity: 0.85; margin-bottom: 2px; display: flex; justify-content: space-between; align-items: center; gap: 8px;">
            <span>Cozy Barista Team</span>
            <button type="button" onclick="deleteAdminMessage(${data.msg_id})" class="btn-delete-msg" title="Delete this message">&times;</button>
          </div>
          <div style="font-size: 0.88rem; line-height: 1.35; white-space: pre-line; word-break: break-word;">${escapeHtml(data.message)}</div>
          <div style="font-size: 0.65rem; opacity: 0.75; margin-top: 3px; text-align: right; font-weight: 600;">
            ${data.formatted_date}
          </div>
        `;
        
        if (chatThreadBox) {
          chatThreadBox.appendChild(newBubble);
          chatThreadBox.scrollTop = chatThreadBox.scrollHeight;
        }

        // Update header status badge dynamically to "Replied"
        const badge = document.getElementById('chatHeaderBadge');
        if (badge) {
          badge.textContent = 'Replied';
          badge.style.background = '#ECFDF5';
          badge.style.color = '#059669';
          badge.style.borderColor = '#A7F3D0';
        }

        // Update active user item in left list: unbold & remove unread dot
        const activeItem = document.querySelector('.chat-user-item.active');
        if (activeItem) {
          const lastMsgDiv = activeItem.querySelector('.user-last-msg');
          const lastTimeDiv = activeItem.querySelector('.user-last-time');
          const unreadDot = activeItem.querySelector('.unread-dot');
          
          if (lastMsgDiv) {
            lastMsgDiv.textContent = data.message;
            lastMsgDiv.style.fontWeight = '500';
            lastMsgDiv.style.opacity = '0.95';
          }
          if (lastTimeDiv) lastTimeDiv.textContent = data.formatted_date;
          if (unreadDot) unreadDot.remove();
        }
      }
    })
    .catch(err => console.error('Error sending reply:', err));
  });
}

function escapeHtml(str) {
  return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>

</body>
</html>
