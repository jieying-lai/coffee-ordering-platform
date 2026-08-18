<?php
session_start();
require_once "../includes/db_connect.php";

// Protect page: Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$userStmt = $conn->prepare("SELECT fullname, email, is_rewards_member, rewards_points, rewards_member_no FROM users WHERE id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

// Fetch orders with order items
$userOrdersStmt = $conn->prepare("SELECT o.* FROM orders o WHERE o.user_id = ? ORDER BY o.order_date DESC");
$userOrdersStmt->bind_param("i", $user_id);
$userOrdersStmt->execute();
$userOrdersRes = $userOrdersStmt->get_result();
$userOrders = [];
while ($ord = $userOrdersRes->fetch_assoc()) {
    $oStmt = $conn->prepare("SELECT oi.*, m.name FROM order_items oi JOIN menu_items m ON m.item_id = oi.item_id WHERE oi.order_id = ?");
    $oStmt->bind_param("i", $ord['order_id']);
    $oStmt->execute();
    $oItemsRes = $oStmt->get_result();
    $ord['items'] = [];
    while ($it = $oItemsRes->fetch_assoc()) {
        $ord['items'][] = $it;
    }
    $oStmt->close();
    $userOrders[] = $ord;
}
$userOrdersStmt->close();

// Active Live Order
$activeOrder = null;
foreach ($userOrders as $ord) {
    $st = strtolower($ord['status']);
    if (in_array($st, ['pending', 'preparing', 'ready'])) {
        $activeOrder = $ord;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/profile.css">
  <title>Cozy Coffee Co. — My Orders &amp; Live Status</title>
</head>

<body style="background: linear-gradient(135deg, #F9F4EC 0%, #EFE5D6 50%, #F5ECDF 100%); min-height: 100vh;">

<?php 
  $activePage = 'profile';
  require_once '../includes/header_nav.php'; 
?>

<!-- SUB NAVIGATION TAB BAR -->
<div style="background: #ffffff; border-bottom: 1px solid var(--color-border); padding: 12px 5%; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
  <div style="max-width: 960px; margin: 0 auto; display: flex; gap: 16px;">
    <a href="index.php" style="padding: 8px 18px; border-radius: 20px; font-size: 0.9rem; font-weight: 600; color: #666; text-decoration: none;">👤 Edit My Profile</a>
    <a href="orders.php" style="padding: 8px 18px; border-radius: 20px; font-size: 0.9rem; font-weight: 700; background: var(--color-accent-dark); color: #ffffff; text-decoration: none; box-shadow: 0 4px 10px rgba(140,109,88,0.25);">📦 My Orders &amp; Live Status</a>
    <a href="../rewards/index.php" style="padding: 8px 18px; border-radius: 20px; font-size: 0.9rem; font-weight: 600; color: #666; text-decoration: none;">⭐ Cozy Rewards</a>
  </div>
</div>

<main class="container" style="max-width: 960px; margin: 30px auto; padding: 0 20px;">

  <!-- SECTION 1: ACTIVE LIVE ORDER STATUS (IF ANY) -->
  <?php if ($activeOrder): 
    $st = strtolower($activeOrder['status']);
    $stepPercent = 33;
    $statusMsg = 'Order Received & Pending Barista Review';
    if ($st === 'preparing') { $stepPercent = 66; $statusMsg = 'Barista is Handcrafting Your Order ☕'; }
    if ($st === 'ready') { $stepPercent = 100; $statusMsg = 'Order Ready for Pickup / Table Service! 🎉'; }
  ?>
    <div style="background: #ffffff; border: 2px solid var(--color-accent); border-radius: 20px; padding: 24px; margin-bottom: 28px; box-shadow: 0 10px 30px rgba(168,71,47,0.15);">
      <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;">
        <div>
          <span style="background: #8c6d58; color: #fff; padding: 2px 10px; border-radius: 999px; font-weight: 700; font-size: 0.75rem; text-transform: uppercase;">LIVE STATUS</span>
          <h2 style="font-size: 1.5rem; color: var(--color-primary); margin: 6px 0 2px;">⚡ Active Order #<?php echo $activeOrder['order_id']; ?></h2>
          <div style="color: #666; font-size: 0.9rem;"><?php echo $statusMsg; ?></div>
        </div>
        <span class="tag-chip" style="font-weight: 800; font-size: 0.9rem; padding: 6px 14px; background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; text-transform: uppercase;">
          <?php echo htmlspecialchars($activeOrder['status']); ?>
        </span>
      </div>

      <!-- Progress Bar -->
      <div style="margin: 20px 0 10px;">
        <div style="height: 10px; background: #e5dace; border-radius: 999px; overflow: hidden; margin-bottom: 12px;">
          <div style="height: 100%; width: <?php echo $stepPercent; ?>%; background: var(--color-accent-dark); transition: width 0.5s ease;"></div>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 700; color: #666;">
          <span style="<?php echo $stepPercent >= 33 ? 'color: var(--color-accent-dark);' : ''; ?>">1. Pending</span>
          <span style="<?php echo $stepPercent >= 66 ? 'color: var(--color-accent-dark);' : ''; ?>">2. Handcrafting ☕</span>
          <span style="<?php echo $stepPercent >= 100 ? 'color: var(--color-accent-dark);' : ''; ?>">3. Ready 🎉</span>
        </div>
      </div>

      <!-- Items List -->
      <div style="background: #faf6f0; padding: 14px; border-radius: 12px; margin-top: 16px; border: 1px solid #e8ded2;">
        <div style="font-weight: 700; font-size: 0.85rem; color: #777; margin-bottom: 8px;">Order Items:</div>
        <?php foreach ($activeOrder['items'] as $it): ?>
          <div style="display: flex; justify-content: space-between; font-size: 0.88rem; margin-bottom: 4px;">
            <span><?php echo htmlspecialchars($it['name']); ?> ×<?php echo $it['quantity']; ?></span>
            <span style="font-weight: 700;">RM <?php echo number_format($it['price'] * $it['quantity'], 2); ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- SECTION 2: ORDER HISTORY -->
  <div style="background: #ffffff; border: 1px solid var(--color-border); border-radius: 20px; padding: 28px; box-shadow: 0 4px 16px rgba(0,0,0,0.03);">
    <h2 style="font-size: 1.5rem; color: var(--color-primary); margin-bottom: 6px;">📦 My Order History</h2>
    <p style="color: #666; font-size: 0.9rem; margin-bottom: 22px;">View all your past handcrafted coffee and food orders.</p>

    <?php if (empty($userOrders)): ?>
      <div style="text-align: center; padding: 40px; color: #777; background: #faf6f0; border-radius: 14px;">
        <div style="font-size: 2.5rem; margin-bottom: 10px;">☕</div>
        <h3 style="margin-bottom: 6px; color: #333;">No orders yet</h3>
        <p style="font-size: 0.9rem; margin-bottom: 16px;">Order your first takeaway coffee or main dish today!</p>
        <a href="../menu/index.php" class="btn btn-orange">Browse Menu &amp; Order</a>
      </div>
    <?php else: ?>
      <div style="display: flex; flex-direction: column; gap: 18px;">
        <?php foreach ($userOrders as $ord): 
          $st = strtolower($ord['status']);
          $badgeBg = '#e5e7eb'; $badgeCol = '#374151';
          if ($st === 'completed') { $badgeBg = '#d1fae5'; $badgeCol = '#065f46'; }
          if ($st === 'cancelled') { $badgeBg = '#fee2e2'; $badgeCol = '#991b1b'; }
          if (in_array($st, ['pending', 'preparing', 'ready'])) { $badgeBg = '#fef3c7'; $badgeCol = '#92400e'; }
        ?>
          <div style="background: #faf6f0; border: 1px solid #e8ded2; border-radius: 14px; padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 12px; flex-wrap: wrap;">
              <div>
                <span style="font-weight: 800; font-size: 1.1rem; color: var(--color-primary);">Order #<?php echo $ord['order_id']; ?></span>
                <span style="font-size: 0.8rem; color: #777; margin-left: 8px;"><?php echo date('M d, Y · h:i A', strtotime($ord['order_date'])); ?></span>
              </div>
              <div style="text-align: right;">
                <span class="tag-chip" style="background: <?php echo $badgeBg; ?>; color: <?php echo $badgeCol; ?>; font-weight: 800; padding: 4px 12px; font-size: 0.8rem; border-radius: 999px; text-transform: uppercase;">
                  <?php echo htmlspecialchars($ord['status']); ?>
                </span>
              </div>
            </div>

            <!-- Items breakdown -->
            <div style="display: flex; flex-direction: column; gap: 8px; border-top: 1px dashed #e0d5c4; padding-top: 10px; margin-top: 10px;">
              <?php foreach ($ord['items'] as $it): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.88rem;">
                  <div>
                    <span style="font-weight: 600; color: #333;"><?php echo htmlspecialchars($it['name']); ?> ×<?php echo $it['quantity']; ?></span>
                    <?php if (!empty($it['temperature']) || !empty($it['sweetness'])): ?>
                      <div style="display: flex; gap: 4px; margin-top: 2px;">
                        <?php if (!empty($it['temperature'])): ?>
                          <span class="tag-chip tag-chip-temp" style="font-size: 0.72rem; padding: 2px 6px;"><?php echo htmlspecialchars($it['temperature']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($it['sweetness'])): ?>
                          <span class="tag-chip tag-chip-sweet" style="font-size: 0.72rem; padding: 2px 6px;"><?php echo htmlspecialchars($it['sweetness']); ?></span>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <span style="font-weight: 700; color: #555;">RM <?php echo number_format($it['price'] * $it['quantity'], 2); ?></span>
                </div>
              <?php endforeach; ?>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e0d5c4; padding-top: 12px; margin-top: 12px;">
              <span style="font-size: 0.85rem; color: #666;">Total Paid</span>
              <span style="font-size: 1.15rem; font-weight: 800; color: var(--color-accent-dark);">RM <?php echo number_format($ord['total_amount'], 2); ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</main>

</body>
</html>
