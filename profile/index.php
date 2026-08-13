<?php
session_start();
require_once "../db.php";

// Protect page: Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$message_type = "";

// ----------------------------------------------------
// 1. HANDLE PROFILE DETAILS & PICTURE UPDATE
// ----------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname']);
    $email    = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $birthday = !empty($_POST['birthday']) ? $_POST['birthday'] : NULL;
    $gender   = !empty($_POST['gender']) ? $_POST['gender'] : NULL;

    // Check if new profile photo is uploaded
    $profile_pic_name = NULL;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $file_tmp   = $_FILES['profile_pic']['tmp_name'];
        $file_name  = $_FILES['profile_pic']['name'];
        $file_ext   = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed    = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($file_ext, $allowed)) {
            // Give file a unique name: e.g. user_1_1690000000.jpg
            $new_filename = "user_" . $user_id . "_" . time() . "." . $file_ext;
            $upload_dir   = "../images/profiles/";

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                $profile_pic_name = $new_filename;
            }
        }
    }

    // Update Query
    if ($profile_pic_name) {
        $stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, birthday = ?, gender = ?, profile_pic = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $fullname, $email, $birthday, $gender, $profile_pic_name, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, birthday = ?, gender = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $fullname, $email, $birthday, $gender, $user_id);
    }

    if ($stmt->execute()) {
        $_SESSION['fullname'] = $fullname; // Update session
        $message = "Profile updated successfully!";
        $message_type = "success";
    } else {
        $message = "Error updating profile: " . $conn->error;
        $message_type = "error";
    }
    $stmt->close();
}

// ----------------------------------------------------
// 2. HANDLE CHANGE PASSWORD UPDATE
// ----------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass     = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass !== $confirm_pass) {
        $message = "New passwords do not match!";
        $message_type = "error";
    } elseif (strlen($new_pass) < 8) {
        $message = "New password must be at least 8 characters long.";
        $message_type = "error";
    } else {
        // Fetch current password from DB
        $pass_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $pass_stmt->bind_param("i", $user_id);
        $pass_stmt->execute();
        $res = $pass_stmt->get_result()->fetch_assoc();
        $pass_stmt->close();

        if (password_verify($current_pass, $res['password'])) {
            $hashed_new = password_hash($new_pass, PASSWORD_DEFAULT);
            $update_pass = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_pass->bind_param("si", $hashed_new, $user_id);
            if ($update_pass->execute()) {
                $message = "Password changed successfully!";
                $message_type = "success";
            }
            $update_pass->close();
        } else {
            $message = "Current password is incorrect.";
            $message_type = "error";
        }
    }
}

// ----------------------------------------------------
// 3. FETCH CURRENT USER DATA FROM DB
// ----------------------------------------------------
$user_stmt = $conn->prepare("SELECT fullname, username, email, birthday, gender, profile_pic, is_rewards_member, rewards_points, rewards_member_no FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// Profile Picture fallback
$avatar = (!empty($user['profile_pic']) && file_exists("../images/profiles/" . $user['profile_pic'])) 
          ? "../images/profiles/" . $user['profile_pic'] 
          : "../images/default-avatar.png";

// ----------------------------------------------------
// 4. FETCH USER ORDER HISTORY & LIVE STATUS
// ----------------------------------------------------
$userOrdersStmt = $conn->prepare("SELECT o.* FROM orders o WHERE o.user_id = ? ORDER BY o.order_date DESC LIMIT 10");
$userOrdersStmt->bind_param("i", $user_id);
$userOrdersStmt->execute();
$userOrdersRes = $userOrdersStmt->get_result();
$userOrders = [];
while ($ord = $userOrdersRes->fetch_assoc()) {
    $oStmt = $conn->prepare("SELECT oi.*, m.name FROM order_items oi JOIN menu_items m ON m.item_id = oi.item_id WHERE oi.order_id = ?");
    $oStmt->bind_param("i", $ord['order_id']);
    $oStmt->execute();
    $oItemsRes = $oStmt->get_result();
    $ord['items'] = [];
    while ($it = $oItemsRes->fetch_assoc()) {
        $ord['items'][] = $it;
    }
    $oStmt->close();
    $userOrders[] = $ord;
}
$userOrdersStmt->close();
// Fetch active live order (Pending / Preparing / Ready)
$activeOrder = null;
foreach ($userOrders as $ord) {
    $st = strtolower($ord['status']);
    if (in_array($st, ['pending', 'preparing', 'ready'])) {
        $activeOrder = $ord;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/profile.css">
  <title>Cozy Coffee Co. — My Account</title>
</head>

<body class="profile-page" style="background: linear-gradient(135deg, #F9F4EC 0%, #EFE5D6 50%, #F5ECDF 100%); min-height: 100vh;">

<?php 
  $activePage = 'profile';
  require_once '../includes/header_nav.php'; 
?>

<!-- STICKY ACTIVE LIVE ORDER WIDGET (IF ANY ORDER ACTIVE) -->
<?php if ($activeOrder): 
  $st = strtolower($activeOrder['status']);
  $stepPercent = 33;
  $statusMsg = 'Order Received & Pending Barista Review';
  if ($st === 'preparing') { $stepPercent = 66; $statusMsg = 'Barista is Handcrafting Your Order ☕'; }
  if ($st === 'ready') { $stepPercent = 100; $statusMsg = 'Order Ready for Pickup / Table Service! 🎉'; }
?>
  <a href="order_detail.php?id=<?php echo $activeOrder['order_id']; ?>" id="stickyLiveOrderBanner" style="text-decoration: none; color: inherit; position: sticky; top: 70px; z-index: 990; background: #ffffff; border: 2px solid var(--color-accent); border-radius: 14px; margin: 16px auto; max-width: 960px; padding: 16px 20px; box-shadow: 0 8px 24px rgba(168,71,47,0.15); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; cursor: pointer;">
    <div>
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 1.3rem;">⚡</span>
        <span style="font-weight: 800; font-size: 1.1rem; color: var(--color-primary);">Active Live Order #<?php echo $activeOrder['order_id']; ?></span>
        <span class="tag-chip tag-chip-sweet" style="text-transform: uppercase; font-weight: 800; padding: 4px 10px; background: #fef3c7; color: #92400e; border: 1px solid #fcd34d;"><?php echo htmlspecialchars($activeOrder['status']); ?></span>
      </div>
      <div style="font-size: 0.88rem; color: #555; margin-top: 4px;"><?php echo $statusMsg; ?></div>
    </div>

    <div style="min-width: 220px; flex: 1; max-width: 320px;">
      <div style="height: 8px; background: #e5dace; border-radius: 999px; overflow: hidden;">
        <div style="height: 100%; width: <?php echo $stepPercent; ?>%; background: var(--color-accent-dark); transition: width 0.4s ease;"></div>
      </div>
    </div>
  </a>
<?php endif; ?>

<!-- MAIN CONTENT WRAPPER -->
<main class="container" style="max-width: 960px; margin: 20px auto;">
  
  <div class="profile-card">
    
    <h2>👤 My Profile</h2>

    <div class="alert" style="background: var(--color-bg); border: 1px solid var(--color-border); border-radius: 10px; padding: 14px 16px; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
      <?php if ((int) ($user['is_rewards_member'] ?? 0) === 1): ?>
        <span>☕ <strong>Cozy Rewards</strong> — Member <?php echo htmlspecialchars($user['rewards_member_no']); ?> · <?php echo (int) $user['rewards_points']; ?> points</span>
        <a href="../rewards/index.php" style="font-weight: 700; color: var(--color-accent-dark);">View card &gt;</a>
      <?php else: ?>
        <span>☕ You haven't activated <strong>Cozy Rewards</strong> yet</span>
        <a href="../rewards/join.php" style="font-weight: 700; color: var(--color-accent-dark);">Activate now &gt;</a>
      <?php endif; ?>
    </div>

    <?php if (!empty($message)): ?>
      <div class="alert alert-<?php echo $message_type; ?>">
        <?php echo $message; ?>
      </div>
    <?php endif; ?>

    <form action="index.php" method="POST" enctype="multipart/form-data">

      <!-- PROFILE PICTURE UPLOAD -->
      <div class="avatar-section">
        <div class="avatar-preview">
          <img src="<?php echo $avatar; ?>" id="avatarImg" alt="Profile Picture">
        </div>
        <div class="avatar-upload">
          <label for="profile_pic" class="btn-upload">📷 Change Photo</label>
          <input type="file" id="profile_pic" name="profile_pic" accept="image/*" onchange="previewImage(this)">
        </div>
      </div>

      <hr class="divider">

      <!-- USER DETAILS FORM -->
      <div class="form-grid">
        
        <div class="input-group">
          <label>Username (Read-Only)</label>
          <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled class="disabled-input">
        </div>

        <div class="input-group">
          <label for="fullname">Full Name</label>
          <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
        </div>

        <div class="input-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>

        <div class="input-group">
          <label for="birthday">Birthday</label>
          <input type="date" id="birthday" name="birthday" value="<?php echo htmlspecialchars($user['birthday'] ?? ''); ?>">
        </div>

        <div class="input-group">
          <label for="gender">Gender</label>
          <select id="gender" name="gender">
            <option value="">Select Gender</option>
            <option value="Male" <?php if(($user['gender'] ?? '') === 'Male') echo 'selected'; ?>>Male</option>
            <option value="Female" <?php if(($user['gender'] ?? '') === 'Female') echo 'selected'; ?>>Female</option>
            <option value="Other" <?php if(($user['gender'] ?? '') === 'Other') echo 'selected'; ?>>Other</option>
          </select>
        </div>

      </div>

      <div class="actions-row">
        <button type="submit" name="update_profile" class="btn btn-orange">Save Changes</button>
        <button type="button" class="btn btn-outline" onclick="openPassModal()">🔒 Change Password</button>
      </div>

    </form>
  </div>

  <!-- ORDER HISTORY & LIVE STATUS TRACKER -->
  <div class="profile-card" style="margin-top: 30px;">
    <h2>📦 My Orders &amp; Live Status</h2>
    <p style="color: #666; font-size: 0.95rem; margin-bottom: 20px;">Track your active coffee orders and review past receipts.</p>

    <?php if (empty($userOrders)): ?>
      <div style="text-align: center; padding: 30px; background: var(--color-bg); border-radius: 12px; border: 1px dashed var(--color-border);">
        <span style="font-size: 2.5rem;">☕</span>
        <p style="margin-top: 10px; color: #666;">You haven't placed any coffee orders yet.</p>
        <a href="../menu/index.php" class="btn btn-orange" style="margin-top: 12px; display: inline-block; font-size: 0.9rem;">Browse Menu &amp; Order</a>
      </div>
    <?php else: ?>
      <div class="user-orders-list">
        <?php foreach ($userOrders as $ord): 
          $statusLower = strtolower($ord['status']);
          $statusColor = '#92400e';
          $statusBg = '#fef3c7';
          if ($statusLower === 'preparing') { $statusColor = '#1e40af'; $statusBg = '#dbeafe'; }
          if ($statusLower === 'ready') { $statusColor = '#3730a3'; $statusBg = '#e0e7ff'; }
          if ($statusLower === 'completed') { $statusColor = '#065f46'; $statusBg = '#d1fae5'; }
        ?>
          <a href="order_detail.php?id=<?php echo $ord['order_id']; ?>"
             style="text-decoration: none; color: inherit; display: block; border: 1px solid var(--color-border); border-radius: 12px; padding: 18px; margin-bottom: 16px; background: #fff; transition: box-shadow 0.2s ease, transform 0.2s ease; cursor: pointer;"
             onmouseover="this.style.boxShadow='0 6px 18px rgba(168,71,47,0.15)'; this.style.transform='translateY(-2px)';"
             onmouseout="this.style.boxShadow='none'; this.style.transform='none';">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; border-bottom: 1px solid #f0e8dd; padding-bottom: 12px; margin-bottom: 12px;">
              <div>
                <span style="font-weight: 700; color: var(--color-primary); font-size: 1.1rem;">Order #<?php echo $ord['order_id']; ?></span>
                <span style="font-size: 0.85rem; color: #777; margin-left: 10px;">📅 <?php echo date('M d, Y · h:i A', strtotime($ord['order_date'])); ?></span>
              </div>
              <span style="padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 0.82rem; text-transform: uppercase; background: <?php echo $statusBg; ?>; color: <?php echo $statusColor; ?>;">
                <?php echo htmlspecialchars($ord['status']); ?>
              </span>
            </div>

            <!-- Order Items -->
            <div style="margin-bottom: 12px;">
              <?php foreach ($ord['items'] as $it): ?>
                <div style="display: flex; justify-content: space-between; font-size: 0.92rem; padding: 4px 0;">
                  <div>
                    <span><strong><?php echo htmlspecialchars($it['name']); ?></strong> &times; <?php echo $it['quantity']; ?></span>
                    <?php if (!empty($it['item_options'])): ?>
                      <div style="margin-top: 3px; display: flex; gap: 4px; flex-wrap: wrap;">
                        <?php 
                          $opts = array_map('trim', explode(',', $it['item_options']));
                          foreach ($opts as $opt):
                            if (empty($opt)) continue;
                        ?>
                          <span class="tag-chip tag-chip-sweet"><?php echo htmlspecialchars($opt); ?></span>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <span style="font-weight: 600;">RM <?php echo number_format($it['price_at_order'] * $it['quantity'], 2); ?></span>
                </div>
              <?php endforeach; ?>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; background: var(--color-bg); padding: 10px 14px; border-radius: 8px; font-weight: 700;">
              <span>Total Paid:</span>
              <span style="color: var(--color-accent-dark); font-size: 1.1rem;">RM <?php echo number_format($ord['total_amount'], 2); ?></span>
            </div>

            <div style="text-align: right; margin-top: 10px; font-size: 0.85rem; font-weight: 700; color: var(--color-accent-dark);">
              View Order Details &rarr;
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</main>

<!-- CHANGE PASSWORD MODAL -->
<div class="modal-backdrop" id="passModal">
  <div class="modal-box">
    <h3>🔒 Change Password</h3>
    <form action="index.php" method="POST">
      <div class="input-group">
        <label for="current_password">Current Password</label>
        <input type="password" id="current_password" name="current_password" required>
      </div>
      <div class="input-group">
        <label for="new_password">New Password</label>
        <input type="password" id="new_password" name="new_password" required>
      </div>
      <div class="input-group">
        <label for="confirm_password">Confirm New Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
      </div>
      <div class="modal-actions">
        <button type="submit" name="change_password" class="btn btn-orange">Update Password</button>
        <button type="button" class="btn btn-red" onclick="closePassModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
  // Mobile Nav Toggle
  document.querySelector('.hamburger').addEventListener('click', () => {
    const nav = document.querySelector('.nav-links');
    nav.style.display = nav.style.display === 'flex' ? 'none' : 'flex';
  });

  // Image Live Preview
  function previewImage(input) {
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('avatarImg').src = e.target.result;
      }
      reader.readAsDataURL(input.files[0]);
    }
  }

  // Password Modal Controls
  function openPassModal() {
    document.getElementById('passModal').style.display = 'flex';
  }
  function closePassModal() {
    document.getElementById('passModal').style.display = 'none';
  }
</script>

</body>
</html>