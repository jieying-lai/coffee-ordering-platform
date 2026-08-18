<?php
require_once '../includes/admin_auth_check.php';
require_once '../includes/db_connect.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address   = trim($_POST['address']);
    $phone     = trim($_POST['phone']);
    $email     = trim($_POST['email']);
    $hours     = trim($_POST['hours']);
    $mapUrl    = trim($_POST['map_embed_url']);
    $instagram = trim($_POST['instagram_url']);
    $facebook  = trim($_POST['facebook_url']);
    $tiktok    = trim($_POST['tiktok_url']);

    $stmt = $conn->prepare('UPDATE contact_info SET address=?, phone=?, email=?, hours=?, map_embed_url=?, instagram_url=?, facebook_url=?, tiktok_url=? WHERE id=1');
    $stmt->bind_param('ssssssss', $address, $phone, $email, $hours, $mapUrl, $instagram, $facebook, $tiktok);
    $stmt->execute();
    $stmt->close();

    $message = 'Contact page updated.';
}

$info = $conn->query('SELECT * FROM contact_info WHERE id = 1')->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="../style/mystyle.css">
	<link rel="stylesheet" href="../style/admin.css">
	<title>Cozy Coffee Co. — Update Contact Page</title>
</head>
<body class="admin-page">

<nav class="admin-nav-bar" style="background: var(--color-primary, #3C2A21); color: #fff; padding: 14px 5%; position: sticky; top: 0; z-index: 9999; box-shadow: 0 4px 14px rgba(0,0,0,0.15);">
  <div style="max-width: 1400px; margin: 0 auto; width: 100%; display: flex; justify-content: space-between; align-items: center;">
    
    <div style="font-weight: 800; font-size: 1.15rem; color: #fff;">
      <a href="dashboard.php" style="color: #fff; text-decoration: none; display: flex; align-items: center; gap: 8px;">
        <span>☕</span> Cozy Barista Admin Portal
      </a>
    </div>

    <button class="admin-hamburger" id="adminNavToggle" aria-label="Toggle Admin Menu" style="display: none; flex-direction: column; justify-content: space-between; width: 28px; height: 20px; background: transparent; border: none; cursor: pointer; padding: 0;">
      <span style="display: block; height: 3px; width: 100%; background: #ffffff; border-radius: 3px;"></span>
      <span style="display: block; height: 3px; width: 100%; background: #ffffff; border-radius: 3px;"></span>
      <span style="display: block; height: 3px; width: 100%; background: #ffffff; border-radius: 3px;"></span>
    </button>

    <div class="admin-nav-links" id="adminNavMenu">
      <a href="dashboard.php">📋 Dashboard</a>
      <a href="manage_orders.php">📦 Orders</a>
      <a href="manage_chat.php">💬 Customer Chat</a>
      <a href="manage_menu.php">☕ Menu</a>
      <a href="manage_users.php">👤 Users</a>
      <a href="manage_blog.php">📸 Blog</a>
      <a href="manage_contact.php" class="admin-nav-active">📍 Contact/About</a>
      <a href="logout.php" style="color: #f87171 !important; text-decoration: none; padding: 6px 12px; border-radius: 6px; background: rgba(239, 68, 68, 0.15);">Logout</a>
    </div>

  </div>
</nav>

<script>
  document.getElementById('adminNavToggle')?.addEventListener('click', function() {
    document.getElementById('adminNavMenu')?.classList.toggle('admin-menu-active');
  });
</script>

<div class="admin-wrap">
  <a href="dashboard.php" class="admin-back">← Back to Dashboard</a>
  <h1>Update Contact Page</h1>
  <p class="admin-subtitle">Changes here appear immediately on the live Contact page.</p>

  <?php if ($message): ?>
    <div class="admin-alert admin-alert-success"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <form class="admin-form" method="POST">

    <div class="form-row">
      <label for="address">Address</label>
      <input type="text" name="address" id="address" value="<?php echo htmlspecialchars($info['address']); ?>">
    </div>

    <div class="form-row">
      <label for="phone">Phone</label>
      <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($info['phone']); ?>">
    </div>

    <div class="form-row">
      <label for="email">Email</label>
      <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($info['email']); ?>">
    </div>

    <div class="form-row">
      <label for="hours">Opening Hours</label>
      <input type="text" name="hours" id="hours" value="<?php echo htmlspecialchars($info['hours']); ?>">
    </div>

    <div class="form-row">
      <label for="map_embed_url">Google Maps Embed URL</label>
      <textarea name="map_embed_url" id="map_embed_url"><?php echo htmlspecialchars($info['map_embed_url']); ?></textarea>
    </div>

    <div class="form-row">
      <label for="instagram_url">Instagram Link</label>
      <input type="text" name="instagram_url" id="instagram_url" value="<?php echo htmlspecialchars($info['instagram_url']); ?>">
    </div>

    <div class="form-row">
      <label for="facebook_url">Facebook Link</label>
      <input type="text" name="facebook_url" id="facebook_url" value="<?php echo htmlspecialchars($info['facebook_url']); ?>">
    </div>

    <div class="form-row">
      <label for="tiktok_url">TikTok Link</label>
      <input type="text" name="tiktok_url" id="tiktok_url" value="<?php echo htmlspecialchars($info['tiktok_url']); ?>">
    </div>

    <div class="form-actions">
      <button type="submit" class="admin-btn admin-btn-primary">Save Changes</button>
    </div>
  </form>

</div>

</body>
</html>