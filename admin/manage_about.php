<?php
require_once '../includes/admin_auth_check.php';
require_once '../includes/db_connect.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eyebrow  = trim($_POST['eyebrow']);
    $heading  = trim($_POST['heading']);
    $bodyText = trim($_POST['body_text']);

    $stmt = $conn->prepare('UPDATE about_us SET eyebrow=?, heading=?, body_text=? WHERE id=1');
    $stmt->bind_param('sss', $eyebrow, $heading, $bodyText);
    $stmt->execute();
    $stmt->close();

    $message = 'About Us section updated.';
}

$about = $conn->query('SELECT * FROM about_us WHERE id = 1')->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="../style/mystyle.css">
	<link rel="stylesheet" href="../style/admin.css">
	<title>Cozy Coffee Co. — Update About Us</title>
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
  <h1>Update About Us</h1>
  <p class="admin-subtitle">This text appears at the top of the live Contact page.</p>

  <?php if ($message): ?>
    <div class="admin-alert admin-alert-success"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <form class="admin-form" method="POST">

    <div class="form-row">
      <label for="eyebrow">Small Label (above heading)</label>
      <input type="text" name="eyebrow" id="eyebrow" value="<?php echo htmlspecialchars($about['eyebrow']); ?>">
    </div>

    <div class="form-row">
      <label for="heading">Heading</label>
      <input type="text" name="heading" id="heading" value="<?php echo htmlspecialchars($about['heading']); ?>">
    </div>

    <div class="form-row">
      <label for="body_text">Story Text</label>
      <textarea name="body_text" id="body_text" style="min-height:160px;"><?php echo htmlspecialchars($about['body_text']); ?></textarea>
    </div>

    <div class="form-actions">
      <button type="submit" class="admin-btn admin-btn-primary">Save Changes</button>
    </div>
  </form>

</div>

</body>
</html>