<?php
session_start();
require_once '../includes/db_connect.php';

$orderId = (int)($_GET['order_id'] ?? $_SESSION['last_order_info']['order_id'] ?? 0);
$orderInfo = $_SESSION['last_order_info'] ?? null;
$dbOrder = null;
$orderItems = [];

if ($orderId > 0) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $dbOrder = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($dbOrder) {
        $stmt2 = $conn->prepare("SELECT oi.*, mi.name as item_name, mi.image as item_image FROM order_items oi JOIN menu_items mi ON oi.item_id = mi.item_id WHERE oi.order_id = ?");
        $stmt2->bind_param("i", $orderId);
        $stmt2->execute();
        $res = $stmt2->get_result();
        while ($r = $res->fetch_assoc()) {
            $orderItems[] = $r;
        }
        $stmt2->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/checkout.css">
  <title>Cozy Coffee Co. — Order Confirmation #<?php echo $orderId; ?></title>
  <style>
    .receipt-card {
      background: #FFFFFF;
      border-radius: 24px;
      border: 1.5px solid #E8DDD0;
      box-shadow: 0 16px 40px rgba(60, 42, 33, 0.08);
      max-width: 680px;
      margin: 40px auto 60px;
      padding: 36px 32px;
      box-sizing: border-box;
    }

    .success-badge-icon {
      width: 70px;
      height: 70px;
      border-radius: 50%;
      background: #ECFDF5;
      border: 2px solid #6EE7B7;
      color: #059669;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.2rem;
      margin: 0 auto 16px;
    }

    .receipt-header { text-align: center; margin-bottom: 26px; }
    .receipt-header h1 {
      font-family: var(--font-heading);
      font-size: 1.7rem;
      color: #2C1C14;
      margin: 0 0 6px 0;
      font-weight: 800;
    }

    .receipt-section {
      background: #FAF7F2;
      border-radius: 16px;
      border: 1px solid #E5D9CC;
      padding: 18px 20px;
      margin-bottom: 20px;
    }

    .receipt-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
      font-size: 0.92rem;
    }

    .receipt-row:last-child { margin-bottom: 0; }
    .receipt-row .lbl { color: #7A685A; font-weight: 600; }
    .receipt-row .val { color: #2C1C14; font-weight: 800; }
  </style>
</head>

<body>

<?php 
  $activePage = 'cart';
  require_once '../includes/header_nav.php'; 
?>

<div class="container">
  
  <div class="receipt-card">
    <div class="success-badge-icon">✓</div>
    
    <div class="receipt-header">
      <h1>Order Received &amp; Placed! 🎉</h1>
      <p style="color: #665447; font-size: 0.95rem; margin: 0;">
        Thank you for ordering with Cozy Coffee Co. Your order has been dispatched to our barista counter!
      </p>
    </div>

    <?php if ($dbOrder): ?>
      <!-- ORDER SUMMARY METADATA -->
      <div class="receipt-section">
        <div class="receipt-row">
          <span class="lbl">Order Number</span>
          <span class="val" style="color: #C85A3E; font-size: 1.1rem; font-family: var(--font-heading);">#<?php echo $dbOrder['order_id']; ?></span>
        </div>

        <div class="receipt-row">
          <span class="lbl">Fulfillment Mode</span>
          <span class="val">
            <?php echo htmlspecialchars($dbOrder['fulfillment_type'] ?? 'Dine-In'); ?>
            <?php if (!empty($dbOrder['table_number'])): ?>
              (<?php echo htmlspecialchars($dbOrder['table_number']); ?>)
            <?php endif; ?>
          </span>
        </div>

        <div class="receipt-row">
          <span class="lbl">Payment Method</span>
          <span class="val"><?php echo htmlspecialchars($dbOrder['payment_method'] ?? 'Pay at Counter'); ?></span>
        </div>

        <div class="receipt-row">
          <span class="lbl">Contact Phone</span>
          <span class="val"><?php echo htmlspecialchars($dbOrder['contact_number'] ?? '—'); ?></span>
        </div>

        <div class="receipt-row">
          <span class="lbl">Order Date &amp; Time</span>
          <span class="val"><?php echo date('d M Y, g:i A', strtotime($dbOrder['order_date'])); ?></span>
        </div>
      </div>

      <!-- ECO BYO NOTICE IF APPLICABLE -->
      <?php if ((int)($dbOrder['byo_tumbler'] ?? 0) === 1 || (int)($dbOrder['byo_container'] ?? 0) === 1): ?>
        <div style="background: #ECFDF5; border: 1.5px solid #6EE7B7; color: #065F46; padding: 14px 18px; border-radius: 14px; margin-bottom: 20px; font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; gap: 10px;">
          <span style="font-size: 1.4rem;">🌱</span>
          <div>
            <div>Eco BYO Initiative Active</div>
            <div style="font-weight: 600; font-size: 0.84rem; opacity: 0.9;">
              Please pass your tumbler / container to our counter barista upon arrival.
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- ORDERED ITEMS LIST -->
      <div style="margin-bottom: 24px;">
        <h3 style="font-family: var(--font-heading); font-size: 1.15rem; color: #2C1C14; font-weight: 800; margin-bottom: 12px;">Ordered Items</h3>
        <div style="display: flex; flex-direction: column; gap: 10px;">
          <?php foreach ($orderItems as $item): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; background: #FFF; padding: 12px 14px; border-radius: 12px; border: 1px solid #E8DDD0;">
              <div style="display: flex; gap: 12px; align-items: center;">
                <img src="../images/menu/<?php echo htmlspecialchars($item['item_image']); ?>" style="width: 48px; height: 48px; object-fit: cover; border-radius: 8px; border: 1px solid #E5D9CC;">
                <div>
                  <div style="font-weight: 800; color: #2C1C14; font-size: 0.95rem;">
                    <?php echo htmlspecialchars($item['item_name']); ?>
                    <span style="color: #C85A3E;">×<?php echo $item['quantity']; ?></span>
                  </div>
                  <?php if (!empty($item['item_options'])): ?>
                    <div style="font-size: 0.78rem; color: #7A685A; font-weight: 600;"><?php echo htmlspecialchars($item['item_options']); ?></div>
                  <?php endif; ?>
                </div>
              </div>
              <div style="font-weight: 800; color: #C85A3E; font-size: 1rem;">
                RM <?php echo number_format($item['price_at_order'] * $item['quantity'], 2); ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- TOTAL SUMMARY -->
      <div style="display: flex; justify-content: space-between; align-items: center; background: #FAF4EB; padding: 16px 20px; border-radius: 14px; border: 1.5px solid #E8DDD0; margin-bottom: 28px;">
        <span style="font-weight: 800; color: #2C1C14; font-size: 1.05rem;">Total Amount Paid / Payable</span>
        <span style="font-family: var(--font-heading); font-size: 1.4rem; font-weight: 800; color: #C85A3E;">
          RM <?php echo number_format($dbOrder['total_amount'], 2); ?>
        </span>
      </div>
    <?php endif; ?>

    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
      <a href="../orders/track.php?order_id=<?php echo $orderId; ?>&contact=<?php echo urlencode($dbOrder['contact_number'] ?? ''); ?>" class="btn btn-orange btn-full" style="flex: 1; min-width: 220px; height: 48px; border-radius: 12px; font-weight: 800; font-size: 0.95rem; justify-content: center; display: flex; align-items: center; text-decoration: none;">
        ⚡ Track Live Order Status #<?php echo $orderId; ?>
      </a>
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="../profile/orders.php" class="btn btn-outline btn-full" style="flex: 1; min-width: 180px; height: 48px; border-radius: 12px; font-weight: 800; font-size: 0.95rem; justify-content: center; display: flex; align-items: center; text-decoration: none; border: 1.5px solid #C85A3E; color: #C85A3E;">
          📋 My Account Orders
        </a>
      <?php endif; ?>
      <a href="../menu/index.php" class="btn btn-outline btn-full" style="flex: 1; min-width: 160px; height: 48px; border-radius: 12px; font-weight: 700; font-size: 0.95rem; justify-content: center; text-align: center; border: 1.5px solid #E5D9CC; color: #665447; text-decoration: none; display: flex; align-items: center;">
        ☕ Back to Menu
      </a>
    </div>

  </div>

</div>

</body>
</html>
