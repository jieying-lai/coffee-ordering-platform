<?php
require_once '../includes/admin_auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/admin.css">
  <title>Cozy Coffee Co. — Admin Dashboard</title>
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
  <h1>Dashboard</h1>
  <p class="admin-subtitle">Manage everything customers see on the site from here.</p>

  <div class="admin-grid">

    <a href="manage_menu.php" class="admin-card">
      <span class="icon">☕</span>
      <h3>Manage Menu</h3>
      <p>Add, edit, or remove menu items and prices.</p>
    </a>

    <a href="manage_users.php" class="admin-card">
      <span class="icon">👤</span>
      <h3>Manage Users</h3>
      <p>View registered customers and remove accounts.</p>
    </a>

    <a href="manage_blog.php" class="admin-card">
      <span class="icon">📸</span>
      <h3>Manage Blog</h3>
      <p>Moderate, edit, or remove community coffee moments.</p>
    </a>

    <a href="manage_contact.php" class="admin-card">
      <span class="icon">📍</span>
      <h3>Update Contact Page</h3>
      <p>Edit address, phone, hours, map, and social links.</p>
    </a>

    <a href="manage_about.php" class="admin-card">
      <span class="icon">📝</span>
      <h3>Update About Us</h3>
      <p>Edit the story text shown on the Contact page.</p>
    </a>

  </div>
</div>

</body>
</html>