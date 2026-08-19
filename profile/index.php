<?php
session_start();
require_once '../includes/db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/index.php');
    exit;
}

$userId = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Fetch user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $birthday = trim($_POST['birthday'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    
    // Avatar upload handling
    $avatarPath = $user['profile_pic'] ?? '';
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_pic']['tmp_name'];
        $fileName = $_FILES['profile_pic']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = 'user_' . $userId . '_' . time() . '.' . $fileExtension;
            $uploadFileDir = '../images/profiles/';

            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            $dest_path = $uploadFileDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $avatarPath = $newFileName;
            }
        }
    }

    if (!empty($fullname) && !empty($email)) {
        $updateStmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, phone = ?, birthday = ?, gender = ?, profile_pic = ? WHERE id = ?");
        $updateStmt->bind_param("ssssssi", $fullname, $email, $phone, $birthday, $gender, $avatarPath, $userId);
        
        if ($updateStmt->execute()) {
            $_SESSION['fullname'] = $fullname;
            $_SESSION['email'] = $email;
            $message = "Profile updated successfully!";
            $message_type = "success";
            
            // Refresh user data
            $user['fullname'] = $fullname;
            $user['email'] = $email;
            $user['phone'] = $phone;
            $user['birthday'] = $birthday;
            $user['gender'] = $gender;
            $user['profile_pic'] = $avatarPath;
        } else {
            $message = "Error updating profile. Please try again.";
            $message_type = "error";
        }
        $updateStmt->close();
    } else {
        $message = "Full Name and Email are required fields.";
        $message_type = "error";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "All password fields are required.";
        $message_type = "error";
    } elseif ($new_password !== $confirm_password) {
        $message = "New password and confirm password do not match.";
        $message_type = "error";
    } elseif (strlen($new_password) < 6) {
        $message = "New password must be at least 6 characters long.";
        $message_type = "error";
    } else {
        if (password_verify($current_password, $user['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $passStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $passStmt->bind_param("si", $hashed_password, $userId);
            if ($passStmt->execute()) {
                $message = "Password updated successfully!";
                $message_type = "success";
            } else {
                $message = "Failed to update password.";
                $message_type = "error";
            }
            $passStmt->close();
        } else {
            $message = "Current password is incorrect.";
            $message_type = "error";
        }
    }
}

$pic = $user['profile_pic'] ?? '';
$avatar = '../images/avatar-placeholder.png';
if (!empty($pic)) {
    if (preg_match('/^https?:\/\//i', $pic)) {
        $avatar = $pic;
    } elseif (file_exists('../images/profiles/' . $pic)) {
        $avatar = '../images/profiles/' . $pic;
    } elseif (file_exists('../uploads/avatars/' . $pic)) {
        $avatar = '../uploads/avatars/' . $pic;
    } elseif (file_exists('../' . $pic)) {
        $avatar = '../' . $pic;
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

<body class="profile-page" style="background: #FAF7F2; min-height: 100vh;">

<?php 
  $activePage = 'profile';
  require_once '../includes/header_nav.php'; 
?>

<!-- SUB NAVIGATION TAB BAR (SEAMLESS WARM BACKGROUND) -->
<div style="background: rgba(249, 244, 236, 0.95); border-bottom: 1.5px solid #E8DDD0; padding: 12px 5%; box-shadow: 0 4px 12px rgba(60,42,33,0.03);">
  <div style="max-width: 960px; margin: 0 auto; display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
    <a href="index.php" style="padding: 9px 22px; border-radius: 20px; font-size: 0.88rem; font-weight: 800; background: var(--color-accent-dark); color: #ffffff; text-decoration: none; box-shadow: 0 4px 12px rgba(140,109,88,0.3);">👤 Edit My Profile</a>
    <a href="orders.php" style="padding: 9px 22px; border-radius: 20px; font-size: 0.88rem; font-weight: 700; background: #FFFFFF; color: #665447; border: 1.5px solid #E5D9CC; text-decoration: none;">📦 My Orders &amp; Live Status</a>
    <a href="../rewards/index.php" style="padding: 9px 22px; border-radius: 20px; font-size: 0.88rem; font-weight: 700; background: #FFFFFF; color: #665447; border: 1.5px solid #E5D9CC; text-decoration: none;">⭐ Cozy Rewards</a>
  </div>
</div>

<!-- MAIN CONTENT WRAPPER -->
<main class="container" style="max-width: 860px; margin: 30px auto 50px; padding: 0 20px;">
  
  <div class="profile-card" style="background: #FFFFFF; border-radius: 22px; border: 1.5px solid #E8DDD0; padding: 32px; box-shadow: 0 8px 24px rgba(60,42,33,0.04);">
    
    <h2 style="font-family: var(--font-heading); color: #2C1C14; font-size: 1.6rem; font-weight: 800; margin-top: 0; margin-bottom: 18px;">👤 My Profile</h2>

    <div class="alert" style="background: #FAF4EB; border: 1.5px solid #E8DDD0; border-radius: 14px; padding: 14px 18px; margin-bottom: 22px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
      <?php if ((int) ($user['is_rewards_member'] ?? 0) === 1): ?>
        <span style="color: #2C1C14; font-size: 0.92rem;">☕ <strong>Cozy Rewards</strong> — Member <?php echo htmlspecialchars($user['rewards_member_no']); ?> · <strong><?php echo (int) ($user['rewards_points'] ?? $user['points'] ?? 0); ?> points</strong></span>
        <a href="../rewards/index.php" style="font-weight: 800; color: #C85A3E; text-decoration: none;">View card &gt;</a>
      <?php else: ?>
        <span style="color: #2C1C14; font-size: 0.92rem;">☕ You haven't activated <strong>Cozy Rewards</strong> yet</span>
        <a href="../rewards/join.php" style="font-weight: 800; color: #C85A3E; text-decoration: none;">Activate now &gt;</a>
      <?php endif; ?>
    </div>

    <?php if (!empty($message)): ?>
      <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: 20px; padding: 14px 18px; border-radius: 12px; background: <?php echo $message_type === 'success' ? '#d1fae5' : '#fee2e2'; ?>; color: <?php echo $message_type === 'success' ? '#065f46' : '#991b1b'; ?>; font-weight: 700;">
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

      <hr class="divider" style="border: none; border-top: 1.5px solid #FAF4EB; margin: 24px 0;">

      <!-- USER DETAILS FORM -->
      <div class="form-grid">
        
        <div class="input-group">
          <label>Username (Read-Only)</label>
          <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled class="disabled-input">
        </div>

        <div class="input-group">
          <label for="fullname">Full Name *</label>
          <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
        </div>

        <div class="input-group">
          <label for="email">Email Address *</label>
          <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>

        <div class="input-group">
          <label for="birthday">Birthday</label>
          <input type="date" id="birthday" name="birthday" value="<?php echo htmlspecialchars($user['birthday'] ?? ''); ?>">
        </div>

        <div class="input-group">
          <label for="phone">Phone Number</label>
          <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="e.g. 012-3456789">
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

      <div class="actions-row" style="margin-top: 24px;">
        <button type="submit" name="update_profile" class="btn btn-orange" style="font-weight: 800; padding: 12px 24px; border-radius: 12px;">Save Changes</button>
        <button type="button" class="btn btn-outline" onclick="openPassModal()" style="font-weight: 700; padding: 12px 24px; border-radius: 12px;">🔒 Change Password</button>
      </div>

    </form>
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

<?php require_once '../includes/footer.php'; ?>

</body>
</html>