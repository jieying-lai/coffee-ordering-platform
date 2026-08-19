<?php
session_start();
require_once '../includes/db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/index.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch all orders for logged-in user
$ordersStmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$ordersStmt->bind_param("i", $userId);
$ordersStmt->execute();
$ordersRes = $ordersStmt->get_result();

$userOrders = [];
$activeOrder = null;

while ($row = $ordersRes->fetch_assoc()) {
    $orderId = $row['order_id'];
    
    // Fetch items for this order
    $itemsStmt = $conn->prepare("SELECT oi.*, m.name, m.image FROM order_items oi JOIN menu_items m ON oi.item_id = m.item_id WHERE oi.order_id = ?");
    $itemsStmt->bind_param("i", $orderId);
    $itemsStmt->execute();
    $itemsRes = $itemsStmt->get_result();
    
    $items = [];
    while ($itemRow = $itemsRes->fetch_assoc()) {
        $items[] = $itemRow;
    }
    $itemsStmt->close();

    $row['items'] = $items;
    $userOrders[] = $row;

    // Check for active live order (pending, preparing, or ready)
    $st = strtolower($row['status']);
    if (in_array($st, ['pending', 'preparing', 'ready']) && !$activeOrder) {
        $activeOrder = $row;
    }
}
$ordersStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/profile.css">
  <title>Cozy Coffee Co. — My Orders &amp; Live Status</title>
  <style>
    .order-card-refined {
      background: #FFFFFF;
      border: 1.5px solid #E8DDD0;
      border-radius: 20px;
      padding: 24px;
      box-shadow: 0 6px 20px rgba(60, 42, 33, 0.04);
      transition: all 0.25s ease;
      cursor: pointer;
    }
    .order-card-refined:hover {
      box-shadow: 0 12px 30px rgba(60, 42, 33, 0.08);
      border-color: #C85A3E;
    }
  </style>
</head>

<body style="background: #FAF7F2; min-height: 100vh;">

<?php 
  $activePage = 'profile';
  require_once '../includes/header_nav.php'; 
?>

<!-- SUB NAVIGATION TAB BAR (SEAMLESS WARM BACKGROUND) -->
<div style="background: rgba(249, 244, 236, 0.95); border-bottom: 1.5px solid #E8DDD0; padding: 12px 5%; box-shadow: 0 4px 12px rgba(60,42,33,0.03);">
  <div style="max-width: 960px; margin: 0 auto; display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
    <a href="index.php" style="padding: 9px 22px; border-radius: 20px; font-size: 0.88rem; font-weight: 700; background: #FFFFFF; color: #665447; border: 1.5px solid #E5D9CC; text-decoration: none;">👤 Edit My Profile</a>
    <a href="orders.php" style="padding: 9px 22px; border-radius: 20px; font-size: 0.88rem; font-weight: 800; background: var(--color-accent-dark); color: #ffffff; text-decoration: none; box-shadow: 0 4px 12px rgba(140,109,88,0.3);">📦 My Orders &amp; Live Status</a>
    <a href="../rewards/index.php" style="padding: 9px 22px; border-radius: 20px; font-size: 0.88rem; font-weight: 700; background: #FFFFFF; color: #665447; border: 1.5px solid #E5D9CC; text-decoration: none;">⭐ Cozy Rewards</a>
  </div>
</div>

<main class="container" style="max-width: 960px; margin: 30px auto 60px; padding: 0 20px;">

  <!-- SECTION 1: ACTIVE LIVE ORDER STATUS (PREMIUM DEEP & LIGHT COFFEE PALETTE) -->
  <?php if ($activeOrder): 
    $st = strtolower($activeOrder['status']);
    $stepPercent = 33;
    $statusMsg = 'Order Received & Pending Review';
    if ($st === 'preparing') { $stepPercent = 66; $statusMsg = 'Barista is Handcrafting Your Order ☕'; }
    if ($st === 'ready') { $stepPercent = 100; $statusMsg = 'Order Ready for Pickup / Table Service! 🎉'; }
  ?>
    <div style="background: linear-gradient(145deg, #FFFFFF 0%, #FFFBF5 100%); border: 2px solid #C85A3E; border-radius: 24px; padding: 28px; margin-bottom: 32px; box-shadow: 0 10px 30px rgba(200,90,62,0.08);">
      
      <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 18px; flex-wrap: wrap;">
        <div>
          <span style="background: #C85A3E; color: #FFF; padding: 4px 14px; border-radius: 20px; font-size: 0.76rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px;">
            ⚡ LIVE BARISTA STATUS
          </span>
          <h2 style="font-family: var(--font-heading); font-size: 1.65rem; color: #2C1C14; margin: 10px 0 3px 0; font-weight: 800;">
            Order #<?php echo $activeOrder['order_id']; ?>
          </h2>
          <p style="color: #665447; font-size: 0.92rem; margin: 0; font-weight: 700;"><?php echo $statusMsg; ?></p>
        </div>

        <span style="font-weight: 800; font-size: 0.88rem; padding: 6px 16px; background: #FEF3C7; color: #92400E; border: 1.5px solid #FCD34D; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px;">
          <?php echo htmlspecialchars($activeOrder['status']); ?>
        </span>
      </div>

      <!-- Live Progress Bar with Custom Coffee Cup / Coffee Bean Indicator -->
      <div style="position: relative; margin: 26px 0 20px;">
        <div style="height: 12px; background: #E8DDD0; border-radius: 20px; position: relative; overflow: visible;">
          <div style="height: 100%; width: <?php echo $stepPercent; ?>%; background: linear-gradient(90deg, #C85A3E 0%, #A8472F 100%); border-radius: 20px; position: relative; transition: width 0.5s ease;">
            <!-- Animated Coffee Cup / Bean Indicator sitting on top tip of progress bar -->
            <span style="position: absolute; right: -12px; top: -10px; font-size: 1.35rem; filter: drop-shadow(0 2px 4px rgba(60,42,33,0.3)); cursor: pointer;" title="Brewing Coffee...">☕</span>
          </div>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 800; color: #7A685A; margin-top: 14px;">
          <span style="<?php echo $stepPercent >= 33 ? 'color: #C85A3E;' : ''; ?>">Pending</span>
          <span style="<?php echo $stepPercent >= 66 ? 'color: #C85A3E;' : ''; ?>">Handcrafting ☕</span>
          <span style="<?php echo $stepPercent >= 100 ? 'color: #C85A3E;' : ''; ?>">Ready 🎉</span>
        </div>
      </div>

      <!-- Items Summary Box -->
      <div style="background: #FAF4EB; padding: 18px; border-radius: 16px; margin-top: 18px; border: 1.5px solid #E8DDD0;">
        <div style="font-weight: 800; font-size: 0.88rem; color: #2C1C14; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Order Items:</div>
        <div style="display: flex; flex-direction: column; gap: 10px;">
          <?php foreach ($activeOrder['items'] as $it): 
            $itemPrice = (float)($it['price_at_order'] ?? $it['price'] ?? 0);
          ?>
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem; padding-bottom: 8px; border-bottom: 1px dashed #E5D9CC;">
              <div style="display: flex; gap: 10px; align-items: center;">
                <img src="../images/menu/<?php echo htmlspecialchars($it['image']); ?>" style="width: 42px; height: 42px; object-fit: cover; border-radius: 8px; border: 1px solid #E5D9CC;">
                <div>
                  <strong style="color: #2C1C14;"><?php echo htmlspecialchars($it['name']); ?></strong>
                  <span style="color: #C85A3E; font-weight: 800;">×<?php echo $it['quantity']; ?></span>
                  <?php if (!empty($it['item_options'])): ?>
                    <div style="font-size: 0.78rem; color: #8A7769; margin-top: 2px; font-weight: 600;"><?php echo htmlspecialchars($it['item_options']); ?></div>
                  <?php endif; ?>
                </div>
              </div>
              <span style="font-weight: 800; color: #C85A3E;">RM <?php echo number_format($itemPrice * $it['quantity'], 2); ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>
  <?php endif; ?>

  <!-- SECTION 2: ORDER HISTORY -->
  <div style="background: #ffffff; border: 1.5px solid #E8DDD0; border-radius: 24px; padding: 28px; box-shadow: 0 6px 20px rgba(60,42,33,0.03);">
    <h2 style="font-size: 1.55rem; color: #2C1C14; margin-bottom: 6px; font-family: var(--font-heading); font-weight: 800;">📦 My Order History</h2>
    <p style="color: #665447; font-size: 0.9rem; margin-bottom: 22px;">View all your past handcrafted coffee and food orders. Click any order to view receipt details.</p>

    <?php if (empty($userOrders)): ?>
      <div style="text-align: center; padding: 40px; color: #777; background: #FAF7F2; border-radius: 16px; border: 1.5px solid #E8DDD0;">
        <div style="font-size: 2.5rem; margin-bottom: 10px;">☕</div>
        <h3 style="margin-bottom: 6px; color: #2C1C14; font-weight: 800;">No orders yet</h3>
        <p style="font-size: 0.9rem; margin-bottom: 16px; color: #665447;">Order your first takeaway coffee or main dish today!</p>
        <a href="../menu/index.php" class="btn btn-orange" style="font-weight: 800; border-radius: 12px; padding: 10px 24px;">Browse Menu &amp; Order</a>
      </div>
    <?php else: ?>
      <div style="display: flex; flex-direction: column; gap: 18px;">
        <?php foreach ($userOrders as $ord): 
          $st = strtolower($ord['status']);
          $badgeBg = '#E5E7EB'; $badgeCol = '#374151';
          if ($st === 'completed') { $badgeBg = '#ECFDF5'; $badgeCol = '#065F46'; }
          if ($st === 'cancelled') { $badgeBg = '#FEE2E2'; $badgeCol = '#991B1B'; }
          if (in_array($st, ['pending', 'preparing', 'ready'])) { $badgeBg = '#FEF3C7'; $badgeCol = '#92400E'; }
        ?>
          <div class="order-card-refined" onclick="openOrderModal(<?php echo $ord['order_id']; ?>)">
            
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 12px; flex-wrap: wrap;">
              <div>
                <span style="font-weight: 800; font-size: 1.15rem; color: #2C1C14;">Order #<?php echo $ord['order_id']; ?></span>
                <span style="font-size: 0.82rem; color: #7A685A; margin-left: 8px; font-weight: 600;"><?php echo date('M d, Y · h:i A', strtotime($ord['order_date'])); ?></span>
              </div>
              <div style="text-align: right;">
                <span style="background: <?php echo $badgeBg; ?>; color: <?php echo $badgeCol; ?>; font-weight: 800; padding: 4px 12px; font-size: 0.78rem; border-radius: 20px; text-transform: uppercase;">
                  <?php echo htmlspecialchars($ord['status']); ?>
                </span>
              </div>
            </div>

            <!-- Items breakdown -->
            <div style="display: flex; flex-direction: column; gap: 8px; border-top: 1px dashed #E5D9CC; padding-top: 12px; margin-top: 10px;">
              <?php foreach ($ord['items'] as $it): 
                $itemPrice = (float)($it['price_at_order'] ?? $it['price'] ?? 0);
              ?>
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.88rem;">
                  <div>
                    <strong style="color: #2C1C14;"><?php echo htmlspecialchars($it['name']); ?> ×<?php echo $it['quantity']; ?></strong>
                    <?php if (!empty($it['item_options'])): ?>
                      <div style="font-size: 0.78rem; color: #8A7769; margin-top: 2px; font-weight: 600;">
                        <?php echo htmlspecialchars($it['item_options']); ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <span style="font-weight: 800; color: #C85A3E;">RM <?php echo number_format($itemPrice * $it['quantity'], 2); ?></span>
                </div>
              <?php endforeach; ?>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1.5px solid #E8DDD0; padding-top: 12px; margin-top: 12px;">
              <span style="font-size: 0.85rem; color: #665447; font-weight: 700;">Total Amount Paid</span>
              <span style="font-size: 1.25rem; font-weight: 800; color: #C85A3E;">RM <?php echo number_format($ord['total_amount'], 2); ?></span>
            </div>

            <button type="button" onclick="event.stopPropagation(); openOrderModal(<?php echo $ord['order_id']; ?>);" style="width: 100%; margin-top: 14px; padding: 10px; border-radius: 10px; border: 1.5px solid #C85A3E; background: #FFFBF5; color: #C85A3E; font-weight: 800; font-size: 0.88rem; cursor: pointer; transition: all 0.2s ease;">
              View Details
            </button>

          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</main>

<!-- ORDER DETAILS POPUP MODAL -->
<div id="profileOrderModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 9999; align-items: center; justify-content: center;">
  <div style="background: #FFFFFF; max-width: 580px; width: 92%; border-radius: 24px; padding: 28px; border: 1.5px solid #E8DDD0; position: relative; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 50px rgba(0,0,0,0.25);">
    <button type="button" onclick="closeOrderModal()" style="position: absolute; right: 18px; top: 18px; background: #FAF7F2; border: 1px solid #E5D9CC; border-radius: 50%; width: 36px; height: 36px; font-size: 1.3rem; cursor: pointer; display: flex; align-items: center; justify-content: center;">&times;</button>
    <div id="modalReceiptContent">
      <!-- Loaded dynamically via JS -->
    </div>
  </div>
</div>

<script>
const userOrdersJson = <?php echo json_encode($userOrders); ?>;

function openOrderModal(orderId) {
    const ord = userOrdersJson.find(o => parseInt(o.order_id) === parseInt(orderId));
    if (!ord) return;

    let itemsHtml = '';
    ord.items.forEach(it => {
        const price = parseFloat(it.price_at_order || it.price || 0);
        const sub = price * parseInt(it.quantity);
        itemsHtml += `
          <div style="display: flex; justify-content: space-between; align-items: center; background: #FFFBF5; border: 1.5px solid #E5D9CC; padding: 12px; border-radius: 12px; margin-bottom: 8px;">
            <div style="display: flex; gap: 12px; align-items: center;">
              <img src="../images/menu/${it.image}" style="width: 46px; height: 46px; object-fit: cover; border-radius: 10px; border: 1px solid #E5D9CC;">
              <div>
                <strong style="color: #2C1C14; font-size: 0.92rem;">${it.name}</strong>
                <span style="color: #C85A3E; font-weight: 800; margin-left: 4px;">×${it.quantity}</span>
                ${it.item_options ? `<div style="font-size: 0.78rem; color: #7A685A; margin-top: 2px; font-weight: 600;">${it.item_options}</div>` : ''}
              </div>
            </div>
            <div style="text-align: right;">
              <div style="font-size: 0.75rem; color: #7A685A;">RM ${price.toFixed(2)} each</div>
              <strong style="color: #C85A3E; font-size: 0.95rem;">RM ${sub.toFixed(2)}</strong>
            </div>
          </div>
        `;
    });

    const st = (ord.status || '').toLowerCase();
    let stepPercent = 33;
    if (st === 'preparing') stepPercent = 66;
    if (st === 'ready' || st === 'completed') stepPercent = 100;

    const html = `
      <div style="text-align: center; margin-bottom: 20px; border-bottom: 1.5px dashed #E8DDD0; padding-bottom: 16px;">
        <span style="font-size: 2.2rem;">🧾</span>
        <h3 style="font-family: var(--font-heading); color: #2C1C14; margin: 6px 0 2px; font-size: 1.4rem; font-weight: 800;">
          Cozy Coffee Co. Digital Receipt
        </h3>
        <div style="font-size: 0.85rem; color: #7A685A; font-weight: 700;">Order #${ord.order_id} · ${ord.order_date}</div>
        <div style="margin-top: 10px;">
          <span style="font-weight: 800; font-size: 0.85rem; padding: 5px 14px; background: #FEF3C7; color: #92400E; border: 1.5px solid #FCD34D; border-radius: 20px; text-transform: uppercase;">
            STATUS: ${ord.status}
          </span>
        </div>
      </div>

      <!-- PROGRESS BAR WITH COFFEE CUP TIP INDICATOR -->
      <div style="margin: 16px 0 20px; background: #FAF7F2; padding: 16px; border-radius: 16px; border: 1.5px solid #E8DDD0;">
        <div style="font-size: 0.78rem; font-weight: 800; color: #7A685A; margin-bottom: 10px; text-transform: uppercase;">Live Barista Progress:</div>
        <div style="height: 12px; background: #E8DDD0; border-radius: 20px; position: relative; overflow: visible;">
          <div style="height: 100%; width: ${stepPercent}%; background: linear-gradient(90deg, #C85A3E 0%, #A8472F 100%); border-radius: 20px; position: relative;">
            <span style="position: absolute; right: -12px; top: -10px; font-size: 1.35rem; filter: drop-shadow(0 2px 4px rgba(60,42,33,0.3));">☕</span>
          </div>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 0.78rem; font-weight: 800; color: #7A685A; margin-top: 14px;">
          <span style="${stepPercent >= 33 ? 'color: #C85A3E;' : ''}">Pending</span>
          <span style="${stepPercent >= 66 ? 'color: #C85A3E;' : ''}">Handcrafting ☕</span>
          <span style="${stepPercent >= 100 ? 'color: #C85A3E;' : ''}">Ready 🎉</span>
        </div>
      </div>

      <div style="background: #FAF7F2; border: 1.5px solid #E8DDD0; border-radius: 16px; padding: 16px; margin-bottom: 20px; font-size: 0.88rem; display: flex; flex-direction: column; gap: 8px;">
        <div style="display: flex; justify-content: space-between;">
          <span style="color: #7A685A; font-weight: 600;">Fulfillment Mode:</span>
          <strong style="color: #2C1C14;">${ord.fulfillment_type || 'Dine-In'} ${ord.table_number ? '(' + ord.table_number + ')' : ''}</strong>
        </div>
        <div style="display: flex; justify-content: space-between;">
          <span style="color: #7A685A; font-weight: 600;">Payment Method:</span>
          <strong style="color: #2C1C14;">💳 ${ord.payment_method || 'Pay at Counter'}</strong>
        </div>
        <div style="display: flex; justify-content: space-between;">
          <span style="color: #7A685A; font-weight: 600;">Contact Phone:</span>
          <strong style="color: #2C1C14;">${ord.contact_number || '—'}</strong>
        </div>
      </div>

      <div style="margin-bottom: 20px;">
        <div style="font-weight: 800; font-size: 0.9rem; color: #2C1C14; margin-bottom: 10px;">Itemized Receipt Breakdown:</div>
        ${itemsHtml}
      </div>

      <div style="background: #2C1C14; color: #FAF7F2; padding: 16px 20px; border-radius: 16px; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-weight: 800; font-size: 1rem;">Total Amount Paid</span>
        <span style="font-family: var(--font-heading); font-size: 1.4rem; font-weight: 800; color: #F2C94C;">
          RM ${parseFloat(ord.total_amount).toFixed(2)}
        </span>
      </div>
    `;

    document.getElementById('modalReceiptContent').innerHTML = html;
    document.getElementById('profileOrderModal').style.display = 'flex';
}

function closeOrderModal() {
    document.getElementById('profileOrderModal').style.display = 'none';
}
</script>

<?php require_once '../includes/footer.php'; ?>

</body>
</html>
