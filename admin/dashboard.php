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

<nav class="admin-nav-bar" style="background: var(--color-primary, #3C2A21); color: #fff; padding: 14px 5%; sticky: top: 0; z-index: 9999; box-shadow: 0 4px 14px rgba(0,0,0,0.15);">
  <div style="max-width: 1400px; margin: 0 auto; width: 100%; display: flex; justify-content: space-between; align-items: center;">
    
    <div style="font-weight: 800; font-size: 1.15rem; color: #fff;">
      <a href="dashboard.php" style="color: #fff; text-decoration: none; display: flex; align-items: center; gap: 8px;">
        <span>☕</span> Cozy Barista Admin Portal
      </a>
    </div>

    <!-- Admin Hamburger Button for Mobile / Tablet / Split Screen -->
    <button class="admin-hamburger" id="adminNavToggle" aria-label="Toggle Admin Menu" style="display: none; flex-direction: column; justify-content: space-between; width: 28px; height: 20px; background: transparent; border: none; cursor: pointer; padding: 0;">
      <span style="display: block; height: 3px; width: 100%; background: #ffffff; border-radius: 3px;"></span>
      <span style="display: block; height: 3px; width: 100%; background: #ffffff; border-radius: 3px;"></span>
      <span style="display: block; height: 3px; width: 100%; background: #ffffff; border-radius: 3px;"></span>
    </button>

    <!-- Admin Links Container -->
    <div class="admin-nav-links" id="adminNavMenu">
      <a href="dashboard.php" class="admin-nav-active">📋 Dashboard</a>
      <a href="manage_orders.php">📦 Orders</a>
      <a href="manage_chat.php">💬 Customer Chat</a>
      <a href="manage_menu.php">☕ Menu</a>
      <a href="manage_activities.php">📢 Store Activities</a>
      <a href="manage_blog.php">📸 Blog</a>
      <a href="manage_users.php">👤 Users</a>
      <a href="manage_contact.php">📍 Contact/About</a>
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
  <h1>Dashboard</h1>
  <p class="admin-subtitle">Manage everything customers see on the site from here.</p>

  <div class="admin-grid">

    <a href="manage_orders.php" class="admin-card" style="border-left: 4px solid var(--color-accent-dark);">
      <span class="icon">📋</span>
      <h3>Manage Orders</h3>
      <p>Track customer orders, drink customizations &amp; update statuses.</p>
    </a>

    <a href="manage_activities.php" class="admin-card" style="border-left: 4px solid #C85A3E;">
      <span class="icon">📢</span>
      <h3>Manage Store Activities</h3>
      <p>Publish official store events, Merdeka promos, workshops &amp; announcements.</p>
    </a>

    <a href="manage_chat.php" class="admin-card" style="border-left: 4px solid #2563eb;">
      <span class="icon">💬</span>
      <h3>Customer Care Chat</h3>
      <p>View customer inquiry messages, complaints &amp; reply in real-time.</p>
    </a>

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