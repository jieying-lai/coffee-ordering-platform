<?php
session_start();
require_once '../includes/db_connect.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? (int)$_SESSION['user_id'] : 0;

// Fetch all uncompleted live orders for Guest Mode display (status in 'pending', 'preparing', 'ready')
$uncompletedOrders = [];
$ordersQuery = "SELECT * FROM orders WHERE status IN ('pending', 'preparing', 'ready') ORDER BY order_date DESC";
$ordersRes = $conn->query($ordersQuery);

if ($ordersRes) {
    while ($row = $ordersRes->fetch_assoc()) {
        $orderId = $row['order_id'];
        
        // Fetch item details for each order
        $itemsStmt = $conn->prepare("SELECT oi.*, m.name as item_name, m.image as item_image FROM order_items oi JOIN menu_items m ON m.item_id = oi.item_id WHERE oi.order_id = ?");
        $itemsStmt->bind_param("i", $orderId);
        $itemsStmt->execute();
        $itemsRes = $itemsStmt->get_result();
        
        $items = [];
        while ($it = $itemsRes->fetch_assoc()) {
            $items[] = $it;
        }
        $itemsStmt->close();

        $row['items'] = $items;
        $uncompletedOrders[] = $row;
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
  <title>Cozy Coffee Co. — Live Order Status Monitor</title>
  <style>
    .track-hero-card {
      background: linear-gradient(135deg, #2C1C14 0%, #1F130E 60%, #160D09 100%);
      color: #FAF7F2;
      border-radius: 24px;
      padding: 32px;
      margin-bottom: 28px;
      box-shadow: 0 12px 32px rgba(44, 28, 20, 0.18);
      border: 1.5px solid rgba(242, 201, 76, 0.25);
    }
    
    .status-legend-bar {
      display: flex;
      gap: 12px;
      margin-top: 18px;
      flex-wrap: wrap;
    }
    .legend-chip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 14px;
      border-radius: 20px;
      font-size: 0.78rem;
      font-weight: 800;
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.15);
      color: #FAF7F2;
    }

    .guest-order-card {
      background: #FFFFFF;
      border: 1.5px solid #E8DDD0;
      border-radius: 20px;
      padding: 24px;
      box-shadow: 0 6px 20px rgba(60, 42, 33, 0.04);
      transition: all 0.25s ease;
      cursor: pointer;
    }
    .guest-order-card:hover {
      box-shadow: 0 12px 32px rgba(60, 42, 33, 0.08);
      border-color: #C85A3E;
    }
  </style>
</head>

<body style="background: #FAF7F2; min-height: 100vh;">

<?php 
  $activePage = 'track';
  require_once '../includes/header_nav.php'; 
?>

<div class="container" style="max-width: 960px; margin: 30px auto 60px; padding: 0 20px;">

  <!-- HERO MONITOR HEADER -->
  <div class="track-hero-card">
    <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
      <span style="font-size: 2.6rem;">☕</span>
      <div>
        <span style="background: rgba(242,201,76,0.18); color: #F2C94C; border: 1px solid rgba(242,201,76,0.35); padding: 3px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px;">
          GUEST LIVE KITCHEN MONITOR
        </span>
        <h1 style="font-family: var(--font-heading); color: #FFFFFF; font-size: 1.8rem; margin: 6px 0 2px 0; font-weight: 800;">
          Live Order Status Board
        </h1>
        <p style="color: #D6C7B8; font-size: 0.92rem; margin: 0;">
          Real-time barista preparation progress for in-store and guest orders. Click View Details on any order.
        </p>
      </div>
    </div>

    <div class="status-legend-bar">
      <span class="legend-chip" style="border-color: #FCD34D; color: #FCD34D;">🟡 Pending</span>
      <span class="legend-chip" style="border-color: #93C5FD; color: #93C5FD;">🔵 Handcrafting ☕</span>
      <span class="legend-chip" style="border-color: #6EE7B7; color: #6EE7B7;">🟢 Ready 🎉</span>
    </div>
  </div>

  <!-- IF USER IS LOGGED IN NOTICE -->
  <?php if ($isLoggedIn): ?>
    <div style="background: #FFFBF5; border: 2px solid #C85A3E; border-radius: 20px; padding: 20px 24px; margin-bottom: 28px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
      <div>
        <strong style="color: #2C1C14; font-size: 0.98rem; display: block;">👤 Signed in as <?php echo htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username']); ?></strong>
        <span style="color: #665447; font-size: 0.86rem;">Access your personal account orders &amp; live barista progress directly on your Account Orders page.</span>
      </div>
      <a href="../profile/orders.php" class="btn btn-orange" style="font-weight: 800; border-radius: 12px; padding: 10px 22px; white-space: nowrap;">
        📦 My Account Orders
      </a>
    </div>
  <?php endif; ?>

  <!-- UNCOMPLETED ACTIVE ORDERS LIST FOR GUEST MODE -->
  <div style="background: #FFFFFF; border: 1.5px solid #E8DDD0; border-radius: 24px; padding: 28px; box-shadow: 0 6px 24px rgba(60,42,33,0.04);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; flex-wrap: wrap; gap: 10px; border-bottom: 1.5px solid #E8DDD0; padding-bottom: 16px;">
      <div>
        <h2 style="font-family: var(--font-heading); font-size: 1.45rem; color: #2C1C14; margin: 0 0 2px 0; font-weight: 800;">
          ⚡ Active Kitchen Orders (<?php echo count($uncompletedOrders); ?>)
        </h2>
        <span style="color: #7A685A; font-size: 0.84rem;">Orders currently being handcrafted by our barista team.</span>
      </div>
      <span style="font-size: 0.82rem; color: #065F46; font-weight: 800; background: #ECFDF5; border: 1px solid #6EE7B7; padding: 4px 14px; border-radius: 20px;">
        ● Live Status Active
      </span>
    </div>

    <?php if (empty($uncompletedOrders)): ?>
      <div style="text-align: center; padding: 50px 20px; color: #7A685A; background: #FAF7F2; border-radius: 18px; border: 1.5px dashed #E8DDD0;">
        <div style="font-size: 3rem; margin-bottom: 10px;">☕</div>
        <h3 style="margin-bottom: 6px; color: #2C1C14; font-weight: 800;">All Kitchen Orders Completed</h3>
        <p style="font-size: 0.9rem; margin: 0; color: #665447;">No active uncompleted orders in queue right now. Order fresh coffee &amp; food from our menu!</p>
        <a href="../menu/index.php" class="btn btn-orange" style="font-weight: 800; border-radius: 12px; padding: 11px 26px; margin-top: 18px; display: inline-block;">Browse Menu &amp; Order</a>
      </div>
    <?php else: ?>
      <div style="display: flex; flex-direction: column; gap: 20px;">
        <?php foreach ($uncompletedOrders as $ord): 
          $st = strtolower($ord['status']);
          $badgeBg = '#FEF3C7'; $badgeCol = '#92400E'; $badgeBorder = '#FCD34D';
          $statusLabel = 'Pending';
          if ($st === 'preparing') { $badgeBg = '#DBEAFE'; $badgeCol = '#1E40AF'; $badgeBorder = '#93C5FD'; $statusLabel = 'Handcrafting ☕'; }
          if ($st === 'ready') { $badgeBg = '#D1FAE5'; $badgeCol = '#065F46'; $badgeBorder = '#6EE7B7'; $statusLabel = 'Ready 🎉'; }
        ?>
          <div class="guest-order-card" onclick="openGuestModal(<?php echo $ord['order_id']; ?>)">
            
            <!-- CARD HEADER -->
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 14px; flex-wrap: wrap;">
              <div>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                  <span style="font-family: var(--font-heading); font-weight: 800; font-size: 1.35rem; color: #2C1C14;">
                    Order #<?php echo $ord['order_id']; ?>
                  </span>
                  <span style="background: #FFFBF5; border: 1.5px solid #E5D9CC; color: #665447; font-weight: 800; font-size: 0.8rem; padding: 3px 12px; border-radius: 14px;">
                    <?php echo htmlspecialchars($ord['fulfillment_type'] ?? 'Dine-In'); ?>
                    <?php if (!empty($ord['table_number'])): ?> (<?php echo htmlspecialchars($ord['table_number']); ?>)<?php endif; ?>
                  </span>
                </div>
                <div style="font-size: 0.82rem; color: #7A685A; margin-top: 4px; font-weight: 700;">
                  📅 Order Time: <?php echo date('d M Y · g:i A', strtotime($ord['order_date'])); ?>
                </div>
              </div>

              <!-- LIVE STATUS BADGE -->
              <span style="background: <?php echo $badgeBg; ?>; color: <?php echo $badgeCol; ?>; border: 1.5px solid <?php echo $badgeBorder; ?>; font-weight: 800; padding: 6px 14px; font-size: 0.82rem; border-radius: 20px; text-transform: uppercase;">
                <?php echo $statusLabel; ?>
              </span>
            </div>

            <!-- ORDER ITEMS PREVIEW -->
            <div style="background: #FAF7F2; border: 1.5px solid #E8DDD0; border-radius: 16px; padding: 16px; margin-bottom: 16px;">
              <div style="font-size: 0.8rem; font-weight: 800; color: #7A685A; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Items Ordered:</div>
              <div style="display: flex; flex-direction: column; gap: 10px;">
                <?php foreach ($ord['items'] as $it): 
                  $itemPrice = (float)($it['price_at_order'] ?? $it['price'] ?? 0);
                ?>
                  <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem; padding-bottom: 8px; border-bottom: 1px dashed #E5D9CC;">
                    <div style="display: flex; gap: 10px; align-items: center;">
                      <img src="../images/menu/<?php echo htmlspecialchars($it['item_image']); ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px; border: 1px solid #E5D9CC;">
                      <div>
                        <strong style="color: #2C1C14;"><?php echo htmlspecialchars($it['item_name']); ?></strong>
                        <span style="color: #C85A3E; font-weight: 800; margin-left: 4px;">×<?php echo $it['quantity']; ?></span>
                        <?php if (!empty($it['item_options'])): ?>
                          <span style="font-size: 0.78rem; color: #7A685A; font-weight: 600; margin-left: 6px;">(<?php echo htmlspecialchars($it['item_options']); ?>)</span>
                        <?php endif; ?>
                      </div>
                    </div>
                    <span style="font-weight: 800; color: #C85A3E;">RM <?php echo number_format($itemPrice * $it['quantity'], 2); ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- CARD FOOTER: COST & ACTION BUTTON -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
              <div>
                <span style="font-size: 0.85rem; color: #665447; font-weight: 700;">Total Food Cost:</span>
                <strong style="font-family: var(--font-heading); font-size: 1.3rem; color: #C85A3E; margin-left: 6px; font-weight: 800;">
                  RM <?php echo number_format($ord['total_amount'], 2); ?>
                </strong>
              </div>

              <button type="button" onclick="event.stopPropagation(); openGuestModal(<?php echo $ord['order_id']; ?>);" style="padding: 9px 20px; border-radius: 12px; border: 1.5px solid #C85A3E; background: #FFFBF5; color: #C85A3E; font-weight: 800; font-size: 0.88rem; cursor: pointer; transition: all 0.2s ease;">
                View Details
              </button>
            </div>

          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</div>

<!-- GUEST ORDER DETAILS POPUP MODAL -->
<div id="guestOrderModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 9999; align-items: center; justify-content: center;">
  <div style="background: #FFFFFF; max-width: 580px; width: 92%; border-radius: 24px; padding: 28px; border: 1.5px solid #E8DDD0; position: relative; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 50px rgba(0,0,0,0.25);">
    <button type="button" onclick="closeGuestModal()" style="position: absolute; right: 18px; top: 18px; background: #FAF7F2; border: 1px solid #E5D9CC; border-radius: 50%; width: 36px; height: 36px; font-size: 1.3rem; cursor: pointer; display: flex; align-items: center; justify-content: center;">&times;</button>
    
    <div id="guestModalContent">
      <!-- Loaded dynamically via JS -->
    </div>
  </div>
</div>

<script>
const guestOrdersJson = <?php echo json_encode($uncompletedOrders); ?>;

function openGuestModal(orderId) {
    const ord = guestOrdersJson.find(o => parseInt(o.order_id) === parseInt(orderId));
    if (!ord) return;

    let itemsHtml = '';
    ord.items.forEach(it => {
        const price = parseFloat(it.price_at_order || it.price || 0);
        const sub = price * parseInt(it.quantity);
        itemsHtml += `
          <div style="display: flex; justify-content: space-between; align-items: center; background: #FFFBF5; border: 1.5px solid #E5D9CC; padding: 12px; border-radius: 12px; margin-bottom: 8px;">
            <div style="display: flex; gap: 12px; align-items: center;">
              <img src="../images/menu/${it.item_image}" style="width: 46px; height: 46px; object-fit: cover; border-radius: 10px; border: 1px solid #E5D9CC;">
              <div>
                <strong style="color: #2C1C14; font-size: 0.92rem;">${it.item_name}</strong>
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

    // PRIVACY: Phone number is explicitly OMITTED from guest view
    const html = `
      <div style="text-align: center; margin-bottom: 20px; border-bottom: 1.5px dashed #E8DDD0; padding-bottom: 16px;">
        <span style="font-size: 2.2rem;">🧾</span>
        <h3 style="font-family: var(--font-heading); color: #2C1C14; margin: 6px 0 2px; font-size: 1.4rem; font-weight: 800;">
          Guest Live Order Receipt
        </h3>
        <div style="font-size: 0.85rem; color: #7A685A; font-weight: 700;">Order #${ord.order_id} · ${ord.order_date}</div>
        <div style="margin-top: 10px;">
          <span style="font-weight: 800; font-size: 0.85rem; padding: 5px 14px; background: #FEF3C7; color: #92400E; border: 1.5px solid #FCD34D; border-radius: 20px; text-transform: uppercase;">
            STATUS: ${ord.status}
          </span>
        </div>
      </div>

      <!-- PROGRESS BAR INSIDE MODAL (SHOWN ONLY WHEN VIEWED) -->
      <div style="margin: 16px 0 20px; background: #FAF7F2; padding: 14px; border-radius: 14px; border: 1px solid #E8DDD0;">
        <div style="font-size: 0.78rem; font-weight: 800; color: #7A685A; margin-bottom: 8px; text-transform: uppercase;">Live Barista Progress:</div>
        <div style="height: 10px; background: #E8DDD0; border-radius: 20px; overflow: hidden; margin-bottom: 10px;">
          <div style="height: 100%; width: ${stepPercent}%; background: linear-gradient(90deg, #C85A3E 0%, #A8472F 100%); transition: width 0.5s ease;"></div>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 0.78rem; font-weight: 800; color: #7A685A;">
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
          <span style="color: #7A685A; font-weight: 600;">Payment Method (Pay By):</span>
          <strong style="color: #2C1C14;">💳 ${ord.payment_method || 'Pay at Counter'}</strong>
        </div>
        <div style="display: flex; justify-content: space-between; opacity: 0.7;">
          <span style="color: #7A685A; font-weight: 600;">Customer Verification:</span>
          <strong style="color: #2C1C14;">🔒 Hidden for Privacy</strong>
        </div>
      </div>

      <div style="margin-bottom: 20px;">
        <div style="font-weight: 800; font-size: 0.9rem; color: #2C1C14; margin-bottom: 10px;">Itemized Receipt Breakdown:</div>
        ${itemsHtml}
      </div>

      <div style="background: #2C1C14; color: #FAF7F2; padding: 16px 20px; border-radius: 16px; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-weight: 800; font-size: 1rem;">Total Food Cost</span>
        <span style="font-family: var(--font-heading); font-size: 1.4rem; font-weight: 800; color: #F2C94C;">
          RM ${parseFloat(ord.total_amount).toFixed(2)}
        </span>
      </div>
    `;

    document.getElementById('guestModalContent').innerHTML = html;
    document.getElementById('guestOrderModal').style.display = 'flex';
}

function closeGuestModal() {
    document.getElementById('guestOrderModal').style.display = 'none';
}
</script>

<?php require_once '../includes/footer.php'; ?>

</body>
</html>
