<?php
session_start();
require_once "../db.php";

// Protect page: Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ----------------------------------------------------
// 1. GET & VALIDATE ORDER ID
// ----------------------------------------------------
$order_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($order_id <= 0) {
    header("Location: index.php");
    exit();
}

// Fetch order, making sure it belongs to the logged-in user
$orderStmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
$orderStmt->bind_param("ii", $order_id, $user_id);
$orderStmt->execute();
$order = $orderStmt->get_result()->fetch_assoc();
$orderStmt->close();

// If order doesn't exist or doesn't belong to this user, bounce back
if (!$order) {
    header("Location: index.php");
    exit();
}

// Fetch order items with menu item details (name + image)
$itemsStmt = $conn->prepare("SELECT oi.*, m.name, m.image FROM order_items oi JOIN menu_items m ON m.item_id = oi.item_id WHERE oi.order_id = ?");
$itemsStmt->bind_param("i", $order_id);
$itemsStmt->execute();
$itemsRes = $itemsStmt->get_result();
$items = [];
$subtotal = 0;
while ($it = $itemsRes->fetch_assoc()) {
    $lineTotal = $it['price_at_order'] * $it['quantity'];
    $subtotal += $lineTotal;
    $items[] = $it;
}
$itemsStmt->close();

$totalItemCount = 0;
foreach ($items as $it) {
    $totalItemCount += (int) $it['quantity'];
}

// ----------------------------------------------------
// 2. STATUS -> PROGRESS TRACKER MAPPING
// ----------------------------------------------------
$status = $order['status'];
$statusLower = strtolower($status);

$steps = ['Ordered', 'Preparing', 'Ready', 'Completed'];
$stepIcons = ['📋', '👨‍🍳', '🛍️', '✅'];
$currentStepIndex = 0; // 0-based index into $steps

switch ($statusLower) {
    case 'pending':
        $currentStepIndex = 0;
        break;
    case 'preparing':
        $currentStepIndex = 1;
        break;
    case 'ready':
        $currentStepIndex = 2;
        break;
    case 'completed':
        $currentStepIndex = 3;
        break;
}

$statusHeadline = 'Order Received & Pending Barista Review';
$statusColor = '#92400e';
$statusBg = '#fef3c7';
if ($statusLower === 'preparing') {
    $statusHeadline = 'Barista is Handcrafting Your Order ☕';
    $statusColor = '#1e40af';
    $statusBg = '#dbeafe';
}
if ($statusLower === 'ready') {
    $statusHeadline = 'Order Ready for Pickup / Table Service! 🎉';
    $statusColor = '#3730a3';
    $statusBg = '#e0e7ff';
}
if ($statusLower === 'completed') {
    $statusHeadline = 'Order Completed — Thanks for visiting Cozy Coffee Co.!';
    $statusColor = '#065f46';
    $statusBg = '#d1fae5';
}

// A simple, presentable order reference built from the real order_id
$orderReference = 'CC' . str_pad($order_id, 6, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/profile.css">
  <title>Order #<?php echo $order_id; ?> — Cozy Coffee Co.</title>
</head>

<body class="profile-page" style="background: linear-gradient(135deg, #F9F4EC 0%, #EFE5D6 50%, #F5ECDF 100%); min-height: 100vh;">

<?php
  $activePage = 'profile';
  require_once '../includes/header_nav.php';
?>

<main class="container" style="max-width: 720px; margin: 20px auto;">

  <a href="index.php" style="display: inline-flex; align-items: center; gap: 6px; color: var(--color-primary); font-weight: 700; text-decoration: none; margin-bottom: 16px;">
    &larr; Back to My Profile
  </a>

  <!-- ORDER STATUS HERO -->
  <div class="profile-card" style="text-align: center; background: <?php echo $statusBg; ?>; border: none;">
    <div style="text-transform: uppercase; letter-spacing: 1px; font-weight: 800; color: <?php echo $statusColor; ?>; font-size: 1.4rem; margin-bottom: 6px;">
      Order <?php echo htmlspecialchars($status); ?>
    </div>
    <div style="color: #555; font-size: 0.95rem; margin-bottom: 18px;"><?php echo $statusHeadline; ?></div>

    <div style="font-size: 0.85rem; color: #777; text-transform: uppercase; letter-spacing: 1px;">Order Reference</div>
    <div style="font-size: 1.8rem; font-weight: 800; color: var(--color-accent-dark); letter-spacing: 2px; margin-bottom: 20px;">
      <?php echo $orderReference; ?>
    </div>

    <!-- PROGRESS TRACKER -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; max-width: 480px; margin: 0 auto; position: relative;">
      <div style="position: absolute; top: 22px; left: 12%; right: 12%; height: 3px; background: #e5dace; z-index: 0;">
        <div style="height: 100%; width: <?php echo ($currentStepIndex / (count($steps) - 1)) * 100; ?>%; background: var(--color-accent-dark); transition: width 0.4s ease;"></div>
      </div>
      <?php foreach ($steps as $i => $stepLabel): 
        $isDone = $i <= $currentStepIndex;
      ?>
        <div style="flex: 1; position: relative; z-index: 1; display: flex; flex-direction: column; align-items: center;">
          <div style="width: 44px; height: 44px; border-radius: 50%; background: <?php echo $isDone ? 'var(--color-accent-dark)' : '#fff'; ?>; border: 2px solid <?php echo $isDone ? 'var(--color-accent-dark)' : '#e5dace'; ?>; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
            <?php echo $stepIcons[$i]; ?>
          </div>
          <span style="font-size: 0.75rem; font-weight: 700; margin-top: 6px; color: <?php echo $isDone ? 'var(--color-primary)' : '#aaa'; ?>; text-transform: uppercase; letter-spacing: 0.5px;">
            <?php echo $stepLabel; ?>
          </span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ORDER ITEMS -->
  <div class="profile-card" style="margin-top: 24px;">
    <h2 style="margin-bottom: 6px;">🧾 Order Items</h2>
    <p style="color: #666; font-size: 0.9rem; margin-bottom: 18px;"><?php echo $totalItemCount; ?> item<?php echo $totalItemCount !== 1 ? 's' : ''; ?> in this order</p>

    <?php foreach ($items as $it): ?>
      <div style="display: flex; gap: 14px; padding: 14px 0; border-bottom: 1px solid #f0e8dd;">
        <div style="width: 64px; height: 64px; border-radius: 10px; overflow: hidden; flex-shrink: 0; background: #f0e8dd;">
          <?php if (!empty($it['image']) && file_exists("../images/menu/" . $it['image'])): ?>
            <img src="../images/menu/<?php echo htmlspecialchars($it['image']); ?>" alt="<?php echo htmlspecialchars($it['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
          <?php else: ?>
            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">☕</div>
          <?php endif; ?>
        </div>

        <div style="flex: 1;">
          <div style="display: flex; justify-content: space-between; gap: 10px;">
            <span style="font-weight: 700; color: var(--color-primary);"><?php echo htmlspecialchars($it['name']); ?> &times; <?php echo $it['quantity']; ?></span>
            <span style="font-weight: 700; white-space: nowrap;">RM <?php echo number_format($it['price_at_order'] * $it['quantity'], 2); ?></span>
          </div>
          <div style="font-size: 0.85rem; color: #888; margin-top: 2px;">RM <?php echo number_format($it['price_at_order'], 2); ?> each</div>

          <?php if (!empty($it['item_options'])): ?>
            <div style="margin-top: 6px; display: flex; gap: 4px; flex-wrap: wrap;">
              <?php
                $opts = array_map('trim', explode(',', $it['item_options']));
                foreach ($opts as $opt):
                  if (empty($opt)) continue;
              ?>
                <span class="tag-chip tag-chip-sweet"><?php echo htmlspecialchars($opt); ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <!-- TOTALS -->
    <div style="margin-top: 18px; padding-top: 14px;">
      <div style="display: flex; justify-content: space-between; padding: 4px 0; color: #555; font-size: 0.95rem;">
        <span>Subtotal</span>
        <span>RM <?php echo number_format($subtotal, 2); ?></span>
      </div>
      <?php if (abs($subtotal - $order['total_amount']) > 0.001): ?>
        <div style="display: flex; justify-content: space-between; padding: 4px 0; color: #555; font-size: 0.95rem;">
          <span>Adjustments</span>
          <span>RM <?php echo number_format($order['total_amount'] - $subtotal, 2); ?></span>
        </div>
      <?php endif; ?>
      <div style="display: flex; justify-content: space-between; align-items: center; background: var(--color-bg); padding: 12px 14px; border-radius: 8px; font-weight: 800; margin-top: 8px;">
        <span>Total Paid</span>
        <span style="color: var(--color-accent-dark); font-size: 1.15rem;">RM <?php echo number_format($order['total_amount'], 2); ?></span>
      </div>
    </div>
  </div>

  <!-- ORDER INFO -->
  <div class="profile-card" style="margin-top: 24px;">
    <h2 style="margin-bottom: 16px;">ℹ️ Order Information</h2>
    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0e8dd; font-size: 0.95rem;">
      <span style="color: #777;">Order Reference</span>
      <span style="font-weight: 700;"><?php echo $orderReference; ?></span>
    </div>
    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0e8dd; font-size: 0.95rem;">
      <span style="color: #777;">Order Date &amp; Time</span>
      <span style="font-weight: 700;"><?php echo date('d/m/Y · h:i:s A', strtotime($order['order_date'])); ?></span>
    </div>
    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0e8dd; font-size: 0.95rem;">
      <span style="color: #777;">Status</span>
      <span style="font-weight: 700; color: <?php echo $statusColor; ?>;"><?php echo htmlspecialchars($status); ?></span>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; font-size: 0.95rem;">
      <span style="color: #777;">Payment Method</span>
      <span style="font-weight: 700; display: flex; align-items: center; gap: 6px;">
        <?php
          $paymentMethod = !empty($order['payment_method']) ? $order['payment_method'] : 'Cash';
          $paymentIcon = '💵';
          $pmLower = strtolower($paymentMethod);
          if (strpos($pmLower, 'card') !== false) { $paymentIcon = '💳'; }
          if (strpos($pmLower, 'touch') !== false) { $paymentIcon = '📱'; }
          if (strpos($pmLower, 'bank') !== false || strpos($pmLower, 'online') !== false) { $paymentIcon = '🏦'; }
        ?>
        <span><?php echo $paymentIcon; ?></span>
        <span><?php echo htmlspecialchars($paymentMethod); ?></span>
      </span>
    </div>
  </div>

  <div style="text-align: center; margin: 28px 0;">
    <a href="../menu/index.php" class="btn btn-orange" style="display: inline-block; text-decoration: none;">Order Again &nbsp;☕</a>
  </div>

</main>

</body>
</html>