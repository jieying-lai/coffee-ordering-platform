<?php
require_once '../includes/admin_auth_check.php';
require_once '../includes/db_connect.php';

$message = '';
$message_type = '';

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['status'] ?? 'Pending';
    $isAjax = isset($_POST['is_ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    
    $allowedStatuses = ['Pending', 'Preparing', 'Ready', 'Completed'];
    $success = false;
    $message = '';
    $message_type = 'error';

    if ($orderId > 0 && in_array($newStatus, $allowedStatuses)) {
        // Enforce backend check: Completed orders cannot be changed anymore
        $chk = $conn->prepare("SELECT status FROM orders WHERE order_id = ?");
        $chk->bind_param("i", $orderId);
        $chk->execute();
        $curStatus = $chk->get_result()->fetch_assoc()['status'] ?? '';
        $chk->close();

        if ($curStatus === 'Completed') {
            $message = "Order #{$orderId} has already been Completed and finalized. Status cannot be modified.";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
            $stmt->bind_param("si", $newStatus, $orderId);
            if ($stmt->execute()) {
                $message = "Order #{$orderId} status successfully updated to '{$newStatus}'.";
                $message_type = "success";
                $success = true;
            } else {
                $message = "Failed to update order status.";
                $message_type = "error";
            }
            $stmt->close();
        }
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'message_type' => $message_type,
            'order_id' => $orderId,
            'new_status' => $newStatus
        ]);
        exit;
    }
}

// Filters: Status & Payment Method
$statusFilter = $_GET['status'] ?? 'all';
$paymentFilter = $_GET['payment'] ?? 'all';

$whereConditions = [];
if (in_array($statusFilter, ['Pending', 'Preparing', 'Ready', 'Completed'])) {
    $whereConditions[] = "o.status = '" . $conn->real_escape_string($statusFilter) . "'";
}

if ($paymentFilter !== 'all' && !empty($paymentFilter)) {
    if ($paymentFilter === 'counter') {
        $whereConditions[] = "o.payment_method LIKE '%Counter%'";
    } elseif ($paymentFilter === 'banking') {
        $whereConditions[] = "o.payment_method LIKE '%Banking%' OR o.payment_method LIKE '%FPX%'";
    } elseif ($paymentFilter === 'card') {
        $whereConditions[] = "o.payment_method LIKE '%Card%'";
    } elseif ($paymentFilter === 'tng') {
        $whereConditions[] = "o.payment_method LIKE '%Touch%' OR o.payment_method LIKE '%E-Wallet%'";
    }
}

$whereClause = "";
if (!empty($whereConditions)) {
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
}

// Fetch Overview Stats
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

// Fetch Orders
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
  <title>Cozy Coffee Co. — Customer Orders Management</title>
  <style>
    /* SPLIT 2-COLUMN LAYOUT */
    .orders-split-layout {
      display: grid;
      grid-template-columns: 270px 1fr;
      gap: 24px;
      align-items: flex-start;
    }

    @media (max-width: 992px) {
      .orders-split-layout {
        grid-template-columns: 1fr;
      }
      .orders-stats-sidebar {
        position: static !important;
        top: auto !important;
      }
    }

    /* LEFT SIDEBAR "田" STATS PANEL */
    .orders-stats-sidebar {
      background: #FFFFFF;
      border: 1.5px solid #E8DDD0;
      border-radius: 18px;
      padding: 18px;
      box-shadow: 0 4px 16px rgba(60, 42, 33, 0.04);
      position: sticky;
      top: 90px;
    }

    .stats-panel-header {
      font-family: var(--font-heading, serif);
      font-size: 1.05rem;
      font-weight: 800;
      color: #2C1C14;
      margin-bottom: 14px;
      padding-bottom: 8px;
      border-bottom: 1.5px solid #F4EDE4;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    /* "田" 2x2 Grid */
    .stats-grid-2x2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-bottom: 10px;
    }

    .stat-card-compact {
      background: #FAF7F2;
      border: 1.5px solid #E8DDD0;
      border-radius: 12px;
      padding: 12px 8px;
      text-align: center;
      transition: all 0.2s ease;
    }

    .stat-card-compact:hover {
      background: #FFFFFF;
      border-color: #C85A3E;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(60, 42, 33, 0.06);
    }

    .revenue-card {
      background: linear-gradient(135deg, #FAF4EB 0%, #F4EDE4 100%);
      border-color: #E5D9CC;
      padding: 14px 12px;
    }

    .stat-val {
      font-family: var(--font-heading, serif);
      font-size: 1.5rem;
      font-weight: 800;
      color: #2C1C14;
      line-height: 1.1;
    }

    .stat-label {
      font-size: 0.74rem;
      color: #7A685A;
      margin-top: 4px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* RIGHT MAIN ORDERS CONTENT */
    .filter-tabs {
      display: flex;
      gap: 8px;
      margin-bottom: 18px;
      flex-wrap: wrap;
    }
    .filter-tab {
      padding: 7px 14px;
      border-radius: 20px;
      background: #FFFFFF;
      border: 1.5px solid #E8DDD0;
      color: #4A3B32;
      text-decoration: none;
      font-weight: 700;
      font-size: 0.82rem;
      transition: all 0.2s ease;
      box-shadow: 0 2px 6px rgba(60, 42, 33, 0.03);
    }
    .filter-tab:hover {
      background: #FAF4EB;
      border-color: #C85A3E;
      color: #C85A3E;
      transform: translateY(-1px);
    }
    .filter-tab.active {
      background: linear-gradient(135deg, #C85A3E 0%, #A8472F 100%) !important;
      color: #FFFFFF !important;
      border-color: #C85A3E !important;
      font-weight: 800;
      box-shadow: 0 4px 12px rgba(200, 90, 62, 0.35);
    }

    .order-card {
      background: #FFFFFF;
      border: 1.5px solid #E8DDD0;
      border-radius: 16px;
      padding: 22px;
      margin-bottom: 20px;
      box-shadow: 0 4px 16px rgba(60, 42, 33, 0.04);
      transition: all 0.2s ease;
    }
    .order-card:hover {
      box-shadow: 0 10px 28px rgba(60, 42, 33, 0.08);
      border-color: #D4C5B3;
    }
    .order-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      flex-wrap: wrap;
      gap: 12px;
      padding-bottom: 14px;
      border-bottom: 1px solid #F0E8DD;
    }
    .order-id {
      font-family: var(--font-heading, serif);
      font-size: 1.25rem;
      font-weight: 800;
      color: #2C1C14;
    }
    .order-time {
      font-size: 0.85rem;
      color: #7A685A;
      margin-top: 2px;
    }
    .status-badge {
      padding: 5px 14px;
      border-radius: 20px;
      font-size: 0.78rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .badge-pending { background: #FEF3C7; color: #92400E; border: 1px solid #FCD34D; }
    .badge-preparing { background: #DBEAFE; color: #1E40AF; border: 1px solid #93C5FD; }
    .badge-ready { background: #D1FAE5; color: #065F46; border: 1px solid #6EE7B7; }
    .badge-completed { background: #F3F4F6; color: #374151; border: 1px solid #D1D5DB; }
    
    .order-items-table {
      width: 100%;
      margin: 16px 0;
      border-collapse: collapse;
    }
    .order-items-table th, .order-items-table td {
      padding: 10px 12px;
      text-align: left;
      border-bottom: 1px dashed #EDE5DA;
      font-size: 0.9rem;
    }
    .order-items-table th {
      color: #7A685A;
      font-weight: 700;
      background: #FAF7F2;
    }
    .item-options-tag {
      display: inline-block;
      background: #F3ECE4;
      color: #5A4332;
      padding: 2px 8px;
      border-radius: 6px;
      font-size: 0.78rem;
      margin-top: 4px;
    }
    .order-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 14px;
      padding: 14px 18px;
      background: #FAF7F2;
      border: 1px solid #E8DDD0;
      border-radius: 12px;
    }
    .total-amount {
      font-size: 1.15rem;
      font-weight: 800;
      color: #C85A3E;
    }

    /* Colored Pill Option Buttons for Status Update */
    .status-pill-btn {
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 0.82rem;
      font-weight: 700;
      cursor: pointer;
      background: #FFFFFF;
      transition: all 0.2s ease;
      border: 1.5px solid #DDD;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }

    .btn-pending { border-color: #D97706; color: #D97706; }
    .btn-pending:hover, .active-pending { background: #D97706 !important; color: #FFFFFF !important; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.3); }

    .btn-preparing { border-color: #2563EB; color: #2563EB; }
    .btn-preparing:hover, .active-preparing { background: #2563EB !important; color: #FFFFFF !important; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); }

    .btn-ready { border-color: #059669; color: #059669; }
    .btn-ready:hover, .active-ready { background: #059669 !important; color: #FFFFFF !important; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3); }

    .btn-completed { border-color: #4B5563; color: #4B5563; }
    .btn-completed:hover, .active-completed { background: #4B5563 !important; color: #FFFFFF !important; box-shadow: 0 4px 12px rgba(75, 85, 99, 0.3); }

    .save-status-btn {
      padding: 7px 18px;
      background: linear-gradient(135deg, #C85A3E 0%, #A8472F 100%);
      color: #FFFFFF;
      border: none;
      border-radius: 20px;
      font-weight: 800;
      font-size: 0.84rem;
      cursor: pointer;
      box-shadow: 0 4px 12px rgba(200, 90, 62, 0.3);
      transition: all 0.2s ease;
      margin-left: 4px;
    }
    /* Floating Bottom-Right Toast Notification */
    .toast-notification {
      position: fixed;
      bottom: 24px;
      right: 24px;
      z-index: 99999;
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 20px;
      border-radius: 14px;
      font-weight: 700;
      font-size: 0.88rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);
      animation: toastSlideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
      transition: all 0.3s ease;
    }

    @keyframes toastSlideUp {
      from {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .toast-success {
      background: #ECFDF5;
      color: #065F46;
      border: 1.5px solid #6EE7B7;
    }

    .toast-error {
      background: #FEF2F2;
      color: #991B1B;
      border: 1.5px solid #FCA5A5;
    }

    .toast-close {
      background: transparent;
      border: none;
      font-size: 1.2rem;
      cursor: pointer;
      color: inherit;
      opacity: 0.7;
      margin-left: 8px;
      padding: 0 4px;
      line-height: 1;
    }

    .toast-close:hover {
      opacity: 1;
    }
  </style>
</head>
<body class="admin-page">

<?php $adminActivePage = 'orders'; require_once '../includes/admin_header_nav.php'; ?>

<!-- FLOATING BOTTOM-RIGHT TOAST NOTIFICATION -->
<?php if (!empty($message)): ?>
  <div id="toastNotification" class="toast-notification toast-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
    <span class="toast-icon"><?php echo $message_type === 'success' ? '✅' : '⚠️'; ?></span>
    <span class="toast-text"><?php echo htmlspecialchars($message); ?></span>
    <button type="button" onclick="closeToast()" class="toast-close">&times;</button>
  </div>
  <script>
    setTimeout(() => {
      const t = document.getElementById('toastNotification');
      if (t) {
        t.style.opacity = '0';
        t.style.transform = 'translateY(20px)';
        setTimeout(() => t.remove(), 300);
      }
    }, 4500);
    function closeToast() {
      const t = document.getElementById('toastNotification');
      if (t) t.remove();
    }
  </script>
<?php endif; ?>

<div class="admin-wrap">
  <div class="admin-page-header">
    <a href="dashboard.php" class="btn-back-dashboard">&larr; Back to Dashboard</a>
    <h1 class="admin-header-title">Manage Customer Orders</h1>
    <p class="admin-header-subtitle">Track incoming coffee orders, view drink customizations, sort by payment method, and update fulfillment status.</p>
  </div>

  <!-- SPLIT 2-COLUMN LAYOUT: LEFT "田" STATS PANEL + RIGHT FILTERS & ORDERS LIST -->
  <div class="orders-split-layout">
    
    <!-- LEFT SIDEBAR: "田" STATS PANEL -->
    <div class="orders-stats-sidebar">
      <div class="stats-panel-header">
        📊 Order Summary
      </div>

      <!-- 2x2 GRID ("田"字形) -->
      <div class="stats-grid-2x2">
        <div class="stat-card-compact">
          <div class="stat-val"><?php echo $stats['total']; ?></div>
          <div class="stat-label">Total</div>
        </div>
        <div class="stat-card-compact">
          <div class="stat-val" style="color: #D97706;"><?php echo $stats['pending']; ?></div>
          <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card-compact">
          <div class="stat-val" style="color: #2563EB;"><?php echo $stats['preparing']; ?></div>
          <div class="stat-label">Preparing</div>
        </div>
        <div class="stat-card-compact">
          <div class="stat-val" style="color: #059669;"><?php echo $stats['ready']; ?></div>
          <div class="stat-label">Ready</div>
        </div>
      </div>

      <!-- TOTAL REVENUE FULL WIDTH CARD -->
      <div class="stat-card-compact revenue-card">
        <div class="stat-val" style="color: #C85A3E;">RM <?php echo number_format($stats['revenue'], 2); ?></div>
        <div class="stat-label" style="color: #665447;">Total Revenue</div>
      </div>
    </div>

    <!-- RIGHT MAIN: FILTERS & ORDERS LIST -->
    <div class="orders-main-content">
      <!-- UNIFIED FILTER CONTROL CARD -->
      <div style="background: #FFFFFF; border: 1.5px solid #E8DDD0; border-radius: 16px; padding: 18px 20px; margin-bottom: 24px; box-shadow: 0 4px 14px rgba(60, 42, 33, 0.03);">
        
        <!-- Status Filter Tabs -->
        <div style="margin-bottom: 6px; font-size: 0.78rem; font-weight: 800; color: #8C6D58; text-transform: uppercase; letter-spacing: 0.5px;">
          📌 Filter by Order Status:
        </div>
        <div class="filter-tabs" style="margin-bottom: 14px;">
          <a href="manage_orders.php?status=all&payment=<?php echo urlencode($paymentFilter); ?>" class="filter-tab <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">All (<?php echo $stats['total']; ?>)</a>
          <a href="manage_orders.php?status=Pending&payment=<?php echo urlencode($paymentFilter); ?>" class="filter-tab <?php echo $statusFilter === 'Pending' ? 'active' : ''; ?>">Pending (<?php echo $stats['pending']; ?>)</a>
          <a href="manage_orders.php?status=Preparing&payment=<?php echo urlencode($paymentFilter); ?>" class="filter-tab <?php echo $statusFilter === 'Preparing' ? 'active' : ''; ?>">Preparing (<?php echo $stats['preparing']; ?>)</a>
          <a href="manage_orders.php?status=Ready&payment=<?php echo urlencode($paymentFilter); ?>" class="filter-tab <?php echo $statusFilter === 'Ready' ? 'active' : ''; ?>">Ready (<?php echo $stats['ready']; ?>)</a>
          <a href="manage_orders.php?status=Completed&payment=<?php echo urlencode($paymentFilter); ?>" class="filter-tab <?php echo $statusFilter === 'Completed' ? 'active' : ''; ?>">Completed (<?php echo $stats['completed']; ?>)</a>
        </div>

        <!-- Payment Method Filter Tabs -->
        <div style="margin-bottom: 6px; font-size: 0.78rem; font-weight: 800; color: #8C6D58; text-transform: uppercase; letter-spacing: 0.5px;">
          💳 Filter / Sort by Payment Method:
        </div>
        <div class="filter-tabs" style="margin-bottom: 0;">
          <a href="manage_orders.php?status=<?php echo urlencode($statusFilter); ?>&payment=all" class="filter-tab <?php echo $paymentFilter === 'all' ? 'active' : ''; ?>">All Methods</a>
          <a href="manage_orders.php?status=<?php echo urlencode($statusFilter); ?>&payment=counter" class="filter-tab <?php echo $paymentFilter === 'counter' ? 'active' : ''; ?>">💵 Counter</a>
          <a href="manage_orders.php?status=<?php echo urlencode($statusFilter); ?>&payment=banking" class="filter-tab <?php echo $paymentFilter === 'banking' ? 'active' : ''; ?>">🏦 Banking</a>
          <a href="manage_orders.php?status=<?php echo urlencode($statusFilter); ?>&payment=card" class="filter-tab <?php echo $paymentFilter === 'card' ? 'active' : ''; ?>">💳 Card</a>
          <a href="manage_orders.php?status=<?php echo urlencode($statusFilter); ?>&payment=tng" class="filter-tab <?php echo $paymentFilter === 'tng' ? 'active' : ''; ?>">📱 Touch 'n Go</a>
        </div>

      </div>

      <!-- Orders List Cards -->
      <?php if (empty($ordersList)): ?>
        <div style="background: #FFFFFF; padding: 44px; text-align: center; border-radius: 16px; border: 1.5px dashed #E8DDD0; color: #7A685A;">
          <span style="font-size: 3rem;">☕</span>
          <h3 style="margin-top: 10px; color: #2C1C14; font-family: var(--font-heading);">No matching orders found</h3>
          <p style="color: #7A685A; font-size: 0.9rem;">There are currently no customer orders matching your selected status or payment filters.</p>
        </div>
      <?php else: ?>
        <?php foreach ($ordersList as $ord): 
          $badgeClass = 'badge-' . strtolower($ord['status']);
          $isCompleted = ($ord['status'] === 'Completed');
        ?>
          <div class="order-card" id="order_card_<?php echo $ord['order_id']; ?>">
            <div class="order-header">
              <div>
                <div class="order-id">Order #<?php echo $ord['order_id']; ?></div>
                <div class="order-time">
                  📅 <?php echo date('M d, Y · h:i A', strtotime($ord['order_date'])); ?>
                  · Customer: <strong><?php echo htmlspecialchars($ord['fullname'] ?? 'Guest User'); ?></strong> 
                  (<?php echo htmlspecialchars($ord['email'] ?? 'N/A'); ?>)
                </div>
                <div style="font-size: 0.82rem; font-weight: 700; color: #C85A3E; margin-top: 4px;">
                  💳 Payment: <strong><?php echo htmlspecialchars($ord['payment_method'] ?? 'Pay at Counter'); ?></strong>
                  <?php if (!empty($ord['fulfillment_type'])): ?>
                    · 📍 Fulfillment: <strong><?php echo htmlspecialchars($ord['fulfillment_type']); ?></strong>
                  <?php endif; ?>
                </div>
              </div>
              <span class="status-badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($ord['status']); ?></span>
            </div>

            <table class="order-items-table">
              <thead>
                <tr>
                  <th>Item Name</th>
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
                <span style="color: #665447; font-size: 0.88rem; font-weight: 600;">Total Amount: </span>
                <span class="total-amount">RM <?php echo number_format($ord['total_amount'], 2); ?></span>
              </div>

              <?php if ($isCompleted): ?>
                <!-- FINALIZED LOCKED BADGE FOR COMPLETED ORDERS -->
                <div style="display: flex; align-items: center; gap: 6px; padding: 7px 16px; background: #ECFDF5; border: 1.5px solid #A7F3D0; border-radius: 20px; font-weight: 800; font-size: 0.84rem; color: #065F46;">
                  <span>🔒</span> Completed
                </div>
              <?php else: ?>
                <!-- COLORED BUTTON OPTION SELECT FOR NON-COMPLETED ORDERS -->
                <form action="" method="POST" class="status-form" onsubmit="return handleOrderSubmit(event, this, <?php echo $ord['order_id']; ?>);">
                  <input type="hidden" name="order_id" value="<?php echo $ord['order_id']; ?>">
                  <input type="hidden" name="status" id="status_input_<?php echo $ord['order_id']; ?>" value="<?php echo $ord['status']; ?>">
                  <input type="hidden" name="update_status" value="1">

                  <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                    <span style="font-size: 0.84rem; font-weight: 700; color: #665447; margin-right: 4px;">Update Status:</span>

                    <button type="button" 
                            onclick="selectStatus(<?php echo $ord['order_id']; ?>, 'Pending')" 
                            id="btn_Pending_<?php echo $ord['order_id']; ?>"
                            class="status-pill-btn btn-pending <?php echo $ord['status'] === 'Pending' ? 'active-pending' : ''; ?>">
                      🟡 Pending
                    </button>

                    <button type="button" 
                            onclick="selectStatus(<?php echo $ord['order_id']; ?>, 'Preparing')" 
                            id="btn_Preparing_<?php echo $ord['order_id']; ?>"
                            class="status-pill-btn btn-preparing <?php echo $ord['status'] === 'Preparing' ? 'active-preparing' : ''; ?>">
                      🔵 Preparing
                    </button>

                    <button type="button" 
                            onclick="selectStatus(<?php echo $ord['order_id']; ?>, 'Ready')" 
                            id="btn_Ready_<?php echo $ord['order_id']; ?>"
                            class="status-pill-btn btn-ready <?php echo $ord['status'] === 'Ready' ? 'active-ready' : ''; ?>">
                      🟢 Ready
                    </button>

                    <button type="button" 
                            onclick="selectStatus(<?php echo $ord['order_id']; ?>, 'Completed')" 
                            id="btn_Completed_<?php echo $ord['order_id']; ?>"
                            class="status-pill-btn btn-completed <?php echo $ord['status'] === 'Completed' ? 'active-completed' : ''; ?>">
                      ✔️ Completed
                    </button>

                    <button type="submit" class="save-status-btn">
                      💾 Save
                    </button>
                  </div>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>
</div>

<script>
  function selectStatus(orderId, statusVal) {
    const input = document.getElementById('status_input_' + orderId);
    if (input) input.value = statusVal;

    const statuses = ['Pending', 'Preparing', 'Ready', 'Completed'];
    statuses.forEach(s => {
      const btn = document.getElementById(`btn_${s}_${orderId}`);
      if (btn) {
        btn.classList.remove('active-pending', 'active-preparing', 'active-ready', 'active-completed');
        if (s === statusVal) {
          btn.classList.add(`active-${s.toLowerCase()}`);
        }
      }
    });
  }

  function handleOrderSubmit(e, formEl, orderId) {
    if (e) e.preventDefault();

    const hiddenInput = formEl.querySelector('input[name="status"]');
    const targetStatus = hiddenInput ? hiddenInput.value : 'Pending';

    if (targetStatus === 'Completed') {
      const confirmed = confirm(
        "⚠️ ARE YOU SURE YOU WANT TO MARK ORDER #" + orderId + " AS COMPLETED?\n\n" +
        "Once marked as Completed, this order will be finalized and CANNOT BE CHANGED ANYMORE."
      );
      if (!confirmed) {
        return false;
      }
    }

    const formData = new FormData(formEl);
    formData.append('is_ajax', '1');

    const saveBtn = formEl.querySelector('.save-status-btn');
    if (saveBtn) {
      saveBtn.disabled = true;
      saveBtn.innerText = '💾 Saving...';
    }

    fetch('manage_orders.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (saveBtn) {
        saveBtn.disabled = false;
        saveBtn.innerText = '💾 Save';
      }

      showToast(data.message, data.message_type);

      if (data.success) {
        const card = document.getElementById('order_card_' + orderId);
        if (card) {
          const badge = card.querySelector('.status-badge');
          if (badge) {
            badge.className = 'status-badge badge-' + data.new_status.toLowerCase();
            badge.textContent = data.new_status;
          }

          if (data.new_status === 'Completed') {
            const footer = card.querySelector('.order-footer');
            if (footer) {
              const rightPart = footer.children[1] || footer.querySelector('.status-form');
              if (rightPart) {
                rightPart.outerHTML = `
                  <div style="display: flex; align-items: center; gap: 6px; padding: 7px 16px; background: #ECFDF5; border: 1.5px solid #A7F3D0; border-radius: 20px; font-weight: 800; font-size: 0.84rem; color: #065F46;">
                    <span>🔒</span> Completed
                  </div>
                `;
              }
            }
          }
        }
      }
    })
    .catch(err => {
      if (saveBtn) {
        saveBtn.disabled = false;
        saveBtn.innerText = '💾 Save';
      }
      showToast('Error saving order status.', 'error');
    });

    return false;
  }

  function showToast(msg, type) {
    let t = document.getElementById('toastNotification');
    if (!t) {
      t = document.createElement('div');
      t.id = 'toastNotification';
      document.body.appendChild(t);
    }

    t.className = 'toast-notification toast-' + (type === 'success' ? 'success' : 'error');
    t.innerHTML = `
      <span class="toast-icon">${type === 'success' ? '✅' : '⚠️'}</span>
      <span class="toast-text">${msg}</span>
      <button type="button" onclick="closeToast()" class="toast-close">&times;</button>
    `;
    t.style.opacity = '1';
    t.style.transform = 'translateY(0)';

    if (window.toastTimer) clearTimeout(window.toastTimer);
    window.toastTimer = setTimeout(() => {
      if (t) {
        t.style.opacity = '0';
        t.style.transform = 'translateY(20px)';
        setTimeout(() => t.remove(), 300);
      }
    }, 4500);
  }

  function closeToast() {
    const t = document.getElementById('toastNotification');
    if (t) t.remove();
  }
</script>

<?php require_once '../includes/admin_footer.php'; ?>

</body>
</html>
