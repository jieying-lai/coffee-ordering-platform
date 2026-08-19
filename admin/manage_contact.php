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

<?php $adminActivePage = 'contact'; require_once '../includes/admin_header_nav.php'; ?>

<div class="admin-wrap">
  <div class="admin-page-header">
    <a href="dashboard.php" class="btn-back-dashboard">&larr; Back to Dashboard</a>
    <h1 class="admin-header-title">Manage Contact Page</h1>
    <p class="admin-header-subtitle">Changes here appear immediately on the live Contact page.</p>
  </div>

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

<?php require_once '../includes/admin_footer.php'; ?>

</body>
</html>