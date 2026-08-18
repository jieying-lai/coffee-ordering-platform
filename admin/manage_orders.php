<?php
require_once '../includes/admin_auth_check.php';
require_once '../includes/db_connect.php';

$message = '';
$message_type = '';

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['status'] ?? 'Pending';
    
    $allowedStatuses = ['Pending', 'Preparing', 'Ready', 'Completed'];
    if ($orderId > 0 && in_array($newStatus, $allowedStatuses)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt->bind_param("si", $newStatus, $orderId);
        if ($stmt->execute()) {
            $message = "Order #{$orderId} status updated to '{$newStatus}'.";
            $message_type = "success";
        } else {
            $message = "Failed to update order status.";
            $message_type = "error";
        }
        $stmt->close();
    }
}

// Filter Tab
$statusFilter = $_GET['status'] ?? 'all';
$whereClause = "";
if (in_array($statusFilter, ['Pending', 'Preparing', 'Ready', 'Completed'])) {
    $whereClause = "WHERE o.status = '" . $conn->real_escape_string($statusFilter) . "'";
}

// Fetch Stats
$stats = [
    'total' => 0,
    'pending' => 0,
    'preparing' => 0,
    'ready' => 0,
    'completed' => 0,
    'revenue' => 0.00
];
$resStats = $conn->query("SELECT status, COUNT(*) as count, SUM(total_amount) as total FROM orders GROUP BY status");
if ($resStats) {
    while ($r = $resStats->fetch_assoc()) {
        $stats['total'] += $r['count'];
        $stats['revenue'] += (float)$r['total'];
        if ($r['status'] === 'Pending') $stats['pending'] = $r['count'];
        if ($r['status'] === 'Preparing') $stats['preparing'] = $r['count'];
        if ($r['status'] === 'Ready') $stats['ready'] = $r['count'];
        if ($r['status'] === 'Completed') $stats['completed'] = $r['count'];
    }
}

// Fetch Orders with User Info
$ordersQuery = "
    SELECT o.*, u.fullname, u.email 
    FROM orders o 
    LEFT JOIN users u ON u.id = o.user_id 
    $whereClause 
    ORDER BY o.order_date DESC
";
$ordersResult = $conn->query($ordersQuery);
$ordersList = [];

if ($ordersResult) {
    while ($row = $ordersResult->fetch_assoc()) {
        $orderId = $row['order_id'];
        
        // Fetch order items with options
        $itemsStmt = $conn->prepare("
            SELECT oi.*, m.name, m.image 
            FROM order_items oi 
            JOIN menu_items m ON m.item_id = oi.item_id 
            WHERE oi.order_id = ?
        ");
        $itemsStmt->bind_param("i", $orderId);
        $itemsStmt->execute();
        $itemsRes = $itemsStmt->get_result();
        
        $items = [];
        while ($itemRow = $itemsRes->fetch_assoc()) {
            $items[] = $itemRow;
        }
        $itemsStmt->close();
        
        $row['items'] = $items;
        $ordersList[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/admin.css">
  <title>Cozy Coffee Co. — Manage Orders</title>
  <style>
    .order-card {
      background: #fff;
      border: 1px solid var(--color-border);
      border-radius: 14px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.03);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .order-card:hover {
      box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    }
    .order-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      flex-wrap: wrap;
      gap: 12px;
      padding-bottom: 14px;
      border-bottom: 1px solid #f0e8dd;
    }
    .order-id {
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--color-primary);
    }
    .order-time {
      font-size: 0.85rem;
      color: #777;
    }
    .status-badge {
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.82rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .badge-pending { background: #fef3c7; color: #92400e; }
    .badge-preparing { background: #dbeafe; color: #1e40af; }
    .badge-ready { background: #e0e7ff; color: #3730a3; }
    .badge-completed { background: #d1fae5; color: #065f46; }
    
    .order-items-table {
      width: 100%;
      margin: 16px 0;
      border-collapse: collapse;
    }
    .order-items-table th, .order-items-table td {
      padding: 10px 12px;
      text-align: left;
      border-bottom: 1px dashed #ede5da;
      font-size: 0.92rem;
    }
    .order-items-table th {
      color: #666;
      font-weight: 600;
      background: #faf7f2;
    }
    .item-options-tag {
      display: inline-block;
      background: #f3ece4;
      color: #5a4332;
      padding: 2px 8px;
      border-radius: 6px;
      font-size: 0.8rem;
      margin-top: 4px;
    }
    .order-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 12px;
      padding-top: 14px;
      background: #faf7f2;
      padding: 14px;
      border-radius: 10px;
    }
    .total-amount {
      font-size: 1.15rem;
      font-weight: 800;
      color: var(--color-accent-dark);
    }
    .status-form {
      display: flex;
      gap: 8px;
      align-items: center;
    }
    .status-select {
      padding: 8px 12px;
      border-radius: 8px;
      border: 1px solid var(--color-border);
      font-weight: 600;
      background: #fff;
    }
    .update-btn {
      padding: 8px 16px;
      background: var(--color-accent-dark);
      color: #fff;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }
    .update-btn:hover {
      background: var(--color-primary);
    }
    .stats-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }
    .stat-card {
      background: #fff;
      padding: 18px;
      border-radius: 12px;
      border: 1px solid var(--color-border);
      text-align: center;
    }
    .stat-val {
      font-size: 1.8rem;
      font-weight: 800;
      color: var(--color-primary);
    }
    .stat-label {
      font-size: 0.85rem;
      color: #666;
      margin-top: 4px;
    }
    .filter-tabs {
      display: flex;
      gap: 10px;
      margin-bottom: 26px;
      flex-wrap: wrap;
    }
    .filter-tab {
      padding: 10px 20px;
      border-radius: 20px;
      background: #ffffff;
      border: 2px solid #d4c5b5;
      color: #3c2a21;
      text-decoration: none;
      font-weight: 700;
      font-size: 0.92rem;
      transition: all 0.2s ease;
      box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    }
    .filter-tab:hover {
      background: #f4ede4;
      border-color: var(--color-accent-dark);
      color: var(--color-accent-dark);
      transform: translateY(-1px);
    }
    .filter-tab.active {
      background: var(--color-accent-dark) !important;
      color: #ffffff !important;
      border-color: var(--color-accent-dark) !important;
      font-weight: 800;
      box-shadow: 0 4px 14px rgba(140, 109, 88, 0.35);
    }
  </style>
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
      <a href="manage_orders.php" class="admin-nav-active">📦 Orders</a>
      <a href="manage_chat.php">💬 Customer Chat</a>
      <a href="manage_menu.php">☕ Menu</a>
      <a href="manage_users.php">👤 Users</a>
      <a href="manage_blog.php">📸 Blog</a>
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
  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-bottom: 20px;">
    <div>
      <h1>📋 Customer Orders Management</h1>
      <p class="admin-subtitle">Track incoming coffee orders, view drink customizations, and update fulfillment status.</p>
    </div>
    <a href="dashboard.php" class="btn btn-outline" style="padding: 8px 16px;">&larr; Back to Dashboard</a>
  </div>

  <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: 20px; padding: 12px 16px; border-radius: 8px; background: <?php echo $message_type === 'success' ? '#d1fae5' : '#fee2e2'; ?>; color: <?php echo $message_type === 'success' ? '#065f46' : '#991b1b'; ?>;">
      <?php echo htmlspecialchars($message); ?>
    </div>
  <?php endif; ?>

  <!-- Stats Overview -->
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-val"><?php echo $stats['total']; ?></div>
      <div class="stat-label">Total Orders</div>
    </div>
    <div class="stat-card">
      <div class="stat-val" style="color: #d97706;"><?php echo $stats['pending']; ?></div>
      <div class="stat-label">Pending Orders</div>
    </div>
    <div class="stat-card">
      <div class="stat-val" style="color: #2563eb;"><?php echo $stats['preparing']; ?></div>
      <div class="stat-label">Preparing Orders</div>
    </div>
    <div class="stat-card">
      <div class="stat-val" style="color: #059669;">RM <?php echo number_format($stats['revenue'], 2); ?></div>
      <div class="stat-label">Total Revenue</div>
    </div>
  </div>

  <!-- Filter Tabs -->
  <div class="filter-tabs">
    <a href="manage_orders.php?status=all" class="filter-tab <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">All Orders (<?php echo $stats['total']; ?>)</a>
    <a href="manage_orders.php?status=Pending" class="filter-tab <?php echo $statusFilter === 'Pending' ? 'active' : ''; ?>">Pending (<?php echo $stats['pending']; ?>)</a>
    <a href="manage_orders.php?status=Preparing" class="filter-tab <?php echo $statusFilter === 'Preparing' ? 'active' : ''; ?>">Preparing (<?php echo $stats['preparing']; ?>)</a>
    <a href="manage_orders.php?status=Ready" class="filter-tab <?php echo $statusFilter === 'Ready' ? 'active' : ''; ?>">Ready for Pickup (<?php echo $stats['ready']; ?>)</a>
    <a href="manage_orders.php?status=Completed" class="filter-tab <?php echo $statusFilter === 'Completed' ? 'active' : ''; ?>">Completed (<?php echo $stats['completed']; ?>)</a>
  </div>

  <!-- Orders List -->
  <?php if (empty($ordersList)): ?>
    <div style="background: #fff; padding: 40px; text-align: center; border-radius: 12px; border: 1px dashed #d4c5b3;">
      <span style="font-size: 3rem;">☕</span>
      <h3 style="margin-top: 10px;">No orders found</h3>
      <p style="color: #777;">There are currently no orders matching this status filter.</p>
    </div>
  <?php else: ?>
    <?php foreach ($ordersList as $ord): 
      $badgeClass = 'badge-' . strtolower($ord['status']);
    ?>
      <div class="order-card">
        <div class="order-header">
          <div>
            <div class="order-id">Order #<?php echo $ord['order_id']; ?></div>
            <div class="order-time">
              📅 <?php echo date('M d, Y · h:i A', strtotime($ord['order_date'])); ?>
              · Customer: <strong><?php echo htmlspecialchars($ord['fullname'] ?? 'Guest User'); ?></strong> 
              (<?php echo htmlspecialchars($ord['email'] ?? 'N/A'); ?>)
            </div>
          </div>
          <span class="status-badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($ord['status']); ?></span>
        </div>

        <table class="order-items-table">
          <thead>
            <tr>
              <th>Item</th>
              <th>Unit Price</th>
              <th>Qty</th>
              <th>Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($ord['items'] as $it): ?>
              <tr>
                <td>
                  <strong><?php echo htmlspecialchars($it['name']); ?></strong>
                  <?php if (!empty($it['item_options'])): ?>
                    <br><span class="item-options-tag">⚙️ <?php echo htmlspecialchars($it['item_options']); ?></span>
                  <?php endif; ?>
                </td>
                <td>RM <?php echo number_format($it['price_at_order'], 2); ?></td>
                <td>x<?php echo $it['quantity']; ?></td>
                <td><strong>RM <?php echo number_format($it['price_at_order'] * $it['quantity'], 2); ?></strong></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="order-footer">
          <div>
            <span style="color: #666; font-size: 0.9rem;">Total Amount: </span>
            <span class="total-amount">RM <?php echo number_format($ord['total_amount'], 2); ?></span>
          </div>

          <form action="" method="POST" class="status-form">
            <input type="hidden" name="order_id" value="<?php echo $ord['order_id']; ?>">
            <label style="font-size: 0.88rem; font-weight: 600; color: #555;">Update Status:</label>
            <select name="status" class="status-select">
              <option value="Pending" <?php if ($ord['status'] === 'Pending') echo 'selected'; ?>>Pending</option>
              <option value="Preparing" <?php if ($ord['status'] === 'Preparing') echo 'selected'; ?>>Preparing</option>
              <option value="Ready" <?php if ($ord['status'] === 'Ready') echo 'selected'; ?>>Ready</option>
              <option value="Completed" <?php if ($ord['status'] === 'Completed') echo 'selected'; ?>>Completed</option>
            </select>
            <button type="submit" name="update_status" class="update-btn">Save</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>

</body>
</html>
