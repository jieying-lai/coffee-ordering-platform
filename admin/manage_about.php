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

<div class="admin-topbar">
  <div class="admin-logo">Cozy Coffee Co. — Admin</div>
  <div>
    <span class="admin-user">Logged in as <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
    <a href="logout.php" class="logout-link">Logout</a>
  </div>
</div>

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