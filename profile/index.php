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
$user_stmt = $conn->prepare("SELECT fullname, username, email, birthday, gender, profile_pic FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// Profile Picture fallback
$avatar = (!empty($user['profile_pic']) && file_exists("../images/profiles/" . $user['profile_pic'])) 
          ? "../images/profiles/" . $user['profile_pic'] 
          : "../images/default-avatar.png";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/profile.css">
  <title>Cozy Coffee Co. — My Profile</title>
</head>

<body class="profile-page">

<!-- NAVIGATION -->
<nav>
  <div class="logo"><a href="../home/index.php">Cozy Coffee Co.</a></div>
  <ul class="nav-links">
    <li><a href="../home/index.php">Home</a></li>
    <li>
      <a href="../menu/index.php">Menu ▾</a>
      <div class="dropdown">
        <a href="../menu/index.php?cat=specialty#specialty">Specialty</a>
        <a href="../menu/index.php?cat=classic#classic">Classic Coffee</a>
        <a href="../menu/index.php?cat=noncoffein#noncoffein">Non-Coffein</a>
        <a href="../menu/index.php?cat=smoothies#smoothies">Smoothies &amp; Sodas</a>
        <a href="../menu/index.php?cat=mains#mains">Main Dishes</a>
        <a href="../menu/index.php?cat=desserts#desserts">Desserts</a>
      </div>
    </li>
    <li><a href="../contact/index.php">Contact</a></li>
    <li><a href="../cart/index.php">Cart</a></li>

    <!-- DYNAMIC NAVIGATION LINK -->
    <?php if (isset($_SESSION['user_id'])): ?>
      <li>
        <a href="index.php" class="active"><?php echo htmlspecialchars($_SESSION['fullname']); ?> ▾</a>
        <div class="dropdown">
          <a href="index.php">My Profile</a>
          <a href="../logout.php">Logout</a>
        </div>
      </li>
    <?php else: ?>
      <li><a href="../login/index.php">Login</a></li>
    <?php endif; ?>
  </ul>
  <button class="hamburger" aria-label="Menu"><span></span><span></span><span></span></button>
</nav>

<!-- MAIN CONTENT -->
<main class="container">
  
  <div class="profile-card">
    
    <h2>👤 My Profile</h2>

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