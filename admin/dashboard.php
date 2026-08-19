<?php
require_once '../includes/admin_auth_check.php';
$adminActivePage = 'dashboard';
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

<?php require_once '../includes/admin_header_nav.php'; ?>

<div class="admin-wrap">
  <div class="admin-page-header">
    <h1 class="admin-header-title">Barista Control Dashboard</h1>
    <p class="admin-header-subtitle">Manage store operations, active customer orders, special offers, and content from one centralized portal.</p>
  </div>

  <!-- SECTION 1: STORE & ORDER OPERATIONS -->
  <div style="margin-bottom: 32px;">
    <div style="font-size: 0.95rem; font-weight: 800; color: #8C6D58; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
      <span></span> Store &amp; Order Operations
    </div>
    <div class="admin-grid">

      <a href="manage_orders.php" class="admin-card">
        <div>
          <span class="icon">📋</span>
          <h3>Manage Orders</h3>
          <p>Track incoming orders, view drink customization options, and update live preparation statuses.</p>
        </div>
        <div class="admin-card-action">Go to Management &rarr;</div>
      </a>

      <a href="manage_menu.php" class="admin-card">
        <div>
          <span class="icon">☕</span>
          <h3>Manage Menu Items</h3>
          <p>Add new specialty drinks, update prices, edit food descriptions, or delete discontinued items.</p>
        </div>
        <div class="admin-card-action">Go to Management &rarr;</div>
      </a>

      <a href="manage_chat.php" class="admin-card">
        <div>
          <span class="icon">💬</span>
          <h3>Customer Care Chat</h3>
          <p>Read customer inquiry messages, feedback, and assist store visitors in real-time.</p>
        </div>
        <div class="admin-card-action">Go to Management &rarr;</div>
      </a>

    </div>
  </div>

  <!-- SECTION 2: SALES & PROMOTIONS -->
  <div style="margin-bottom: 32px;">
    <div style="font-size: 0.95rem; font-weight: 800; color: #8C6D58; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
      <span></span> Sales &amp; Promotions
    </div>
    <div class="admin-grid">

      <a href="manage_promos.php" class="admin-card">
        <div>
          <span class="icon">🎟️</span>
          <h3>Manage Promo Codes</h3>
          <p>Create campaign vouchers (e.g. Merdeka67), set fixed/percentage discount codes and validity limits.</p>
        </div>
        <div class="admin-card-action">Go to Management &rarr;</div>
      </a>

      <a href="manage_offers.php" class="admin-card">
        <div>
          <span class="icon">🏷️</span>
          <h3>Manage Special Offers</h3>
          <p>Set special discounted prices or percentage deals for curated drink and food categories.</p>
        </div>
        <div class="admin-card-action">Go to Management &rarr;</div>
      </a>

    </div>
  </div>

  <!-- SECTION 3: CONTENT & STORE INFO -->
  <div>
    <div style="font-size: 0.95rem; font-weight: 800; color: #8C6D58; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
      <span></span> Content &amp; Store Information
    </div>
    <div class="admin-grid">

      <a href="manage_activities.php" class="admin-card">
        <div>
          <span class="icon">📢</span>
          <h3>Store Activities</h3>
          <p>Publish store events, coffee brewing workshops, and community give-back campaigns.</p>
        </div>
        <div class="admin-card-action">Go to Management &rarr;</div>
      </a>

      <a href="manage_blog.php" class="admin-card">
        <div>
          <span class="icon">📸</span>
          <h3>Manage Blog</h3>
          <p>Publish coffee stories, behind-the-scenes barista updates, and community posts.</p>
        </div>
        <div class="admin-card-action">Go to Management &rarr;</div>
      </a>

      <a href="manage_users.php" class="admin-card">
        <div>
          <span class="icon">👤</span>
          <h3>Manage Users</h3>
          <p>View registered customer accounts, reward point balances, and user memberships.</p>
        </div>
        <div class="admin-card-action">Go to Management &rarr;</div>
      </a>

      <a href="manage_contact.php" class="admin-card">
        <div>
          <span class="icon">📍</span>
          <h3>Contact &amp; Store Info</h3>
          <p>Update store address, operating hours, customer service contact number, and map location.</p>
        </div>
        <div class="admin-card-action">Go to Management &rarr;</div>
      </a>

    </div>
  </div>

</div>

<?php require_once '../includes/admin_footer.php'; ?>

</body>
</html>