<?php
session_start();
require_once '../includes/db_connect.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? (int)$_SESSION['user_id'] : 0;

// Fetch Cart Data first for promo calculation
$cartItems = [];
$subtotal = 0;

if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $itemIds = array_column($_SESSION['cart'], 'item_id');
    
    if (!empty($itemIds)) {
        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $types = str_repeat('i', count($itemIds));
        
        $stmt = $conn->prepare("SELECT * FROM menu_items WHERE item_id IN ($placeholders)");
        $stmt->bind_param($types, ...$itemIds);
        $stmt->execute();
        $result = $stmt->get_result();

        $dbItems = [];
        while ($row = $result->fetch_assoc()) {
            $dbItems[$row['item_id']] = $row;
        }
        $stmt->close();

        // Build item array matching session selections
        foreach ($_SESSION['cart'] as $key => $cartData) {
            $id = $cartData['item_id'];
            if (isset($dbItems[$id])) {
                $item = $dbItems[$id];
                $effectivePrice = (isset($cartData['custom_price']) && $cartData['custom_price'] > 0) ? (float)$cartData['custom_price'] : (float)$item['price'];
                $itemTotal = $effectivePrice * $cartData['quantity'];
                $subtotal += $itemTotal;

                $imagePath = (preg_match('/^https?:\/\//i', $item['image']))
                    ? $item['image']
                    : '../images/menu/' . $item['image'];

                $cartItems[] = [
                    'cart_key' => $key,
                    'item_id' => $id,
                    'category_id' => (int)$item['category_id'],
                    'name' => $item['name'],
                    'price' => $effectivePrice,
                    'image' => $imagePath,
                    'quantity' => (int)$cartData['quantity'],
                    'temperature' => $cartData['temperature'] ?? '',
                    'sweetness' => $cartData['sweetness'] ?? '',
                    'remarks' => $cartData['remarks'] ?? '',
                    'item_total' => $itemTotal
                ];
            }
        }
    }
}

// Handle AJAX requests for Cart Updates, Deletions, and Promo Applications
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $cartKey = $_POST['cart_key'] ?? '';

    if ($_POST['action'] === 'apply_promo') {
        $code = strtoupper(trim($_POST['promo_code'] ?? ''));
        $fulfillment = trim($_POST['fulfillment'] ?? 'Dine-In');

        if (empty($code)) {
            $_SESSION['applied_promo'] = [
                'code' => '',
                'discount' => 0,
                'free_item_id' => null,
                'fulfillment' => $fulfillment
            ];
            echo json_encode(['status' => 'success', 'discount' => 0, 'message' => 'Promo cleared.']);
            exit;
        }

        // Query promo_codes table
        $stmt = $conn->prepare("SELECT * FROM promo_codes WHERE code = ? AND is_active = 1");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $promo = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$promo) {
            // Fallback for standard vouchers if not in DB table
            if ($code === 'COZY3OFF') {
                $promo = ['code' => 'COZY3OFF', 'title' => 'RM3 Off Any Order', 'discount_type' => 'fixed', 'discount_value' => 3.00, 'category_target' => 'all', 'min_spend' => 10.00];
            } elseif ($code === 'COZYPASTRY') {
                $promo = ['code' => 'COZYPASTRY', 'title' => 'Free Bakery Pastry (RM8 Off)', 'discount_type' => 'fixed', 'discount_value' => 8.00, 'category_target' => 'all', 'min_spend' => 15.00];
            } elseif ($code === 'COZY50OFF') {
                $promo = ['code' => 'COZY50OFF', 'title' => '50% Off Specialty Coffee', 'discount_type' => 'percentage', 'discount_value' => 50.00, 'category_target' => 'all', 'min_spend' => 20.00];
            }
        }

        if (!$promo) {
            echo json_encode(['status' => 'error', 'message' => "Invalid or expired promo code '{$code}'."]);
            exit;
        }

        // Check min_spend
        if ($subtotal < (float)$promo['min_spend']) {
            echo json_encode([
                'status' => 'error',
                'message' => "Minimum spend of RM " . number_format($promo['min_spend'], 2) . " required for code {$code}."
            ]);
            exit;
        }

        $discountAmount = 0.00;
        $freeItemId = null;

        // Check category restrictions / Free Item logic (e.g. Free Dessert BDAYCAKEFREE)
        if ($promo['discount_type'] === 'free_item' || $promo['category_target'] === 'dessert') {
            $hasDessert = false;
            $highestDessertPrice = 0.00;

            foreach ($cartItems as $item) {
                $catId = (int)($item['category_id'] ?? 0);
                $itemNameLower = strtolower($item['name']);
                $isDessert = ($catId === 6) || strpos($itemNameLower, 'tart') !== false || strpos($itemNameLower, 'cake') !== false || strpos($itemNameLower, 'pastry') !== false || strpos($itemNameLower, 'misu') !== false || strpos($itemNameLower, 'brownie') !== false;

                if ($isDessert) {
                    $hasDessert = true;
                    if ($item['price'] > $highestDessertPrice) {
                        $highestDessertPrice = $item['price'];
                        $freeItemId = $item['item_id'];
                    }
                }
            }

            if (!$hasDessert) {
                echo json_encode([
                    'status' => 'error',
                    'message' => "🎂 Code '{$code}' requires at least 1 Dessert item in your cart! Please add a dessert from the menu."
                ]);
                exit;
            }

            // 1 Dessert becomes FREE (RM 0.00)
            $discountAmount = $highestDessertPrice;
        } elseif ($promo['discount_type'] === 'percentage') {
            $discountAmount = $subtotal * ((float)$promo['discount_value'] / 100.0);
        } else { // fixed
            $discountAmount = (float)$promo['discount_value'];
        }

        $discountAmount = min($subtotal, $discountAmount);

        $_SESSION['applied_promo'] = [
            'code' => $code,
            'discount' => $discountAmount,
            'free_item_id' => $freeItemId,
            'fulfillment' => $fulfillment
        ];

        echo json_encode([
            'status' => 'success',
            'code' => $code,
            'discount' => $discountAmount,
            'free_item_id' => $freeItemId,
            'message' => "Voucher '{$code}' applied! (-RM " . number_format($discountAmount, 2) . ")"
        ]);
        exit;
    }

    if ($_POST['action'] === 'update_qty') {
        $newQty = (int)($_POST['quantity'] ?? 1);
        if (isset($_SESSION['cart'][$cartKey])) {
            if ($newQty > 0) {
                $_SESSION['cart'][$cartKey]['quantity'] = $newQty;
            } else {
                unset($_SESSION['cart'][$cartKey]);
            }
        }
        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($_POST['action'] === 'remove_item') {
        if (isset($_SESSION['cart'][$cartKey])) {
            unset($_SESSION['cart'][$cartKey]);
        }
        echo json_encode(['status' => 'success']);
        exit;
    }
}

// Fetch Logged-In User's Active Claimed Vouchers for "Select My Voucher" Popup
$userClaimedVouchers = [];
if ($isLoggedIn) {
    $vStmt = $conn->prepare("SELECT * FROM user_vouchers WHERE user_id = ? AND status = 'ACTIVE' ORDER BY created_at DESC");
    $vStmt->bind_param("i", $userId);
    $vStmt->execute();
    $resV = $vStmt->get_result();
    while ($r = $resV->fetch_assoc()) {
        $userClaimedVouchers[] = $r;
    }
    $vStmt->close();
}

// Fetch Active Public Admin Campaign Promos (e.g. MERDEKA67)
$publicCampaignPromos = [];
$pStmt = $conn->query("SELECT * FROM promo_codes WHERE is_active = 1 AND code NOT LIKE 'BDAY%' ORDER BY created_at DESC");
if ($pStmt) {
    while ($r = $pStmt->fetch_assoc()) {
        $publicCampaignPromos[] = $r;
    }
}

$appliedCode = $_SESSION['applied_promo']['code'] ?? '';
$appliedDiscount = $_SESSION['applied_promo']['discount'] ?? 0.00;
$appliedFreeItemId = $_SESSION['applied_promo']['free_item_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/cart.css">
  <title>Cozy Coffee Co. — Cart</title>
</head>

<body class="cart-page">

<?php 
  $activePage = 'cart';
  require_once '../includes/header_nav.php'; 
?>

<div class="container">

    <!-- Header -->
    <div class="header">
        <h1>🛒 Your Cart</h1>
        <span class="badge" id="cartCount"><?php echo count($cartItems); ?> Items</span>
    </div>

    <!-- Cart Items Container -->
    <div class="cart-box" id="cartBox">
        <?php if (empty($cartItems)): ?>
            <div class="empty-cart" id="emptyState">
                <span class="icon">🛒</span>
                <h2>Cart is Empty</h2>
                <p>You haven't added any coffee yet.<br>Browse our menu to get started!</p>
                <a href="../menu/index.php" class="btn btn-orange btn-full">
                    ☕ Browse Menu &amp; Order
                </a>
            </div>
        <?php else: ?>
            <div class="cart-items-list">
                <?php foreach ($cartItems as $item): 
                  $isFreeItem = ($appliedFreeItemId && (int)$item['item_id'] === (int)$appliedFreeItemId);
                ?>
                    <div class="cart-item" data-key="<?php echo $item['cart_key']; ?>" data-price="<?php echo $item['price']; ?>" data-item-id="<?php echo $item['item_id']; ?>">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-img">
                        
                        <div class="cart-item-details">
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                              <h3 style="margin: 0;"><?php echo htmlspecialchars($item['name']); ?></h3>
                              <?php if ($isFreeItem): ?>
                                <span style="background: #ECFDF5; color: #065F46; border: 1.5px solid #6EE7B7; padding: 2px 8px; border-radius: 6px; font-weight: 800; font-size: 0.75rem;">
                                  🎂 PROMO FREE ITEM (RM 0.00)
                                </span>
                              <?php endif; ?>
                            </div>
                            
                            <!-- Display Custom Choices as Tag Chips -->
                            <div class="item-options" style="margin-top:4px;">
                                <?php if (!empty($item['temperature'])): ?>
                                    <span class="tag-chip tag-chip-temp"><?php echo htmlspecialchars($item['temperature']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($item['sweetness'])): ?>
                                    <span class="tag-chip tag-chip-sweet"><?php echo htmlspecialchars($item['sweetness']); ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($item['remarks'])): ?>
                                <p class="item-remarks" style="margin-top:4px;"><em>Note: "<?php echo htmlspecialchars($item['remarks']); ?>"</em></p>
                            <?php endif; ?>

                            <div class="cart-item-bottom">
                                <div class="qty-picker">
                                    <button type="button" class="qty-btn" onclick="updateQty('<?php echo $item['cart_key']; ?>', -1)">-</button>
                                    <span class="qty-val"><?php echo $item['quantity']; ?></span>
                                    <button type="button" class="qty-btn" onclick="updateQty('<?php echo $item['cart_key']; ?>', 1)">+</button>
                                </div>
                                <div style="text-align: right;" class="price-breakdown-box">
                                  <?php if ($isFreeItem): ?>
                                    <span style="text-decoration: line-through; color: #9CA3AF; font-size: 0.88rem; margin-right: 6px;">RM <?php echo number_format($item['price'], 2); ?></span>
                                    <span class="item-price" style="font-weight: 800; font-size: 1.1rem; color: #059669;">RM 0.00 (FREE)</span>
                                  <?php elseif ($item['quantity'] > 1): ?>
                                    <span style="font-size: 0.82rem; color: #7A685A; font-weight: 700; margin-right: 4px;">RM <?php echo number_format($item['price'], 2); ?> &times; <?php echo $item['quantity']; ?> =</span>
                                    <span class="item-price" style="font-weight: 800; font-size: 1.1rem; color: var(--color-accent-dark);">RM <?php echo number_format($item['item_total'], 2); ?></span>
                                  <?php else: ?>
                                    <span class="item-price" style="font-weight: 800; font-size: 1.1rem; color: var(--color-accent-dark);">RM <?php echo number_format($item['item_total'], 2); ?></span>
                                  <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <button class="remove-btn" onclick="removeItem('<?php echo $item['cart_key']; ?>')" title="Remove Item">&times;</button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Order Summary Box -->
    <div class="summary-box" id="summaryBox" style="<?php echo empty($cartItems) ? 'display: none;' : ''; ?>">
        <h2>Order Summary</h2>

        <div style="background: #faf5ee; padding: 12px; border-radius: 10px; font-size: 0.88rem; margin-bottom: 14px; border: 1px solid #e0d5c4;">
          <label style="font-weight: 700; color: var(--color-primary); display: block; margin-bottom: 6px;">🍽️ Order Fulfillment Option:</label>
          <select id="fulfillmentSelect" style="width: 100%; padding: 8px 12px; border-radius: 8px; border: 1px solid #d0c4b8; font-weight: 600;">
            <option value="Dine-In" selected>🍽️ Dine-In (Table Service)</option>
            <option value="Takeaway Pickup">🛍️ Takeaway Pickup (Self Collect)</option>
          </select>
        </div>

        <!-- SELECT MY VOUCHER BUTTON -->
        <button type="button" onclick="openVoucherModal()" style="width: 100%; margin-bottom: 12px; font-weight: 800; border-radius: 10px; padding: 10px 14px; border: 1.5px solid #E5D9CC; background: #FFFBF5; color: #2C1C14; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
          <span>🎟️</span> Select My Voucher / Claimed Coupons
        </button>

        <div style="font-size: 0.78rem; color: #8A7769; font-weight: 700; margin-bottom: 8px; display: flex; align-items: center; gap: 5px; background: #FAF5EE; padding: 6px 10px; border-radius: 8px; border: 1px solid #E8DDD0;">
          <span>ℹ️</span> Note: Only 1 voucher code can be applied per transaction.
        </div>

        <div class="promo-row">
            <input type="text" placeholder="Enter coupon code (e.g. MERDEKA67)" id="promoInput" value="<?php echo htmlspecialchars($appliedCode); ?>">
            <button class="btn btn-outline btn-small" onclick="applyPromo()" style="font-weight: 800;">Apply</button>
        </div>

        <div class="price-row">
            <span>Subtotal</span>
            <span id="subtotal">RM <?php echo number_format($subtotal, 2); ?></span>
        </div>
        <div class="price-row">
            <span>Fulfillment Type</span>
            <span id="fulfillmentLabel">Dine-In (Table Service)</span>
        </div>
        <div class="price-row green" id="discountRow" style="<?php echo $appliedDiscount > 0 ? 'display: flex;' : 'display: none;'; ?>">
            <span>Coupon Discount (<?php echo htmlspecialchars($appliedCode); ?>)</span>
            <span id="discount">-RM <?php echo number_format($appliedDiscount, 2); ?></span>
        </div>
        <div class="price-row total">
            <span>Total</span>
            <span class="amount" id="total">
                RM <?php echo number_format(max(0, $subtotal - $appliedDiscount), 2); ?>
            </span>
        </div>

        <button class="btn btn-orange btn-full" onclick="checkout()" style="margin-top: 15px; font-weight: 800;">
            Proceed to Checkout 💳
        </button>
        <a href="../menu/index.php" class="menu-link">Continue Ordering (Back to Menu)</a>
    </div>

</div>

<!-- SELECT MY VOUCHER POPUP MODAL -->
<div id="voucherModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
  <div style="background: #FFFFFF; max-width: 540px; width: 90%; border-radius: 22px; padding: 26px; border: 1.5px solid #E8DDD0; position: relative;">
    <button type="button" onclick="closeVoucherModal()" style="position: absolute; right: 16px; top: 16px; background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>

    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
      <span style="font-size: 1.8rem;">🎟️</span>
      <div>
        <h3 style="font-family: var(--font-heading); color: #2C1C14; margin: 0; font-size: 1.25rem;">Select Your Available Voucher</h3>
        <p style="color: #665447; font-size: 0.82rem; margin: 0;">Choose from your claimed rewards or active campaign promos.</p>
      </div>
    </div>

    <!-- CLAIMED VOUCHERS LIST -->
    <div style="max-height: 340px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; padding-right: 4px;">
      
      <!-- MY CLAIMED VOUCHERS -->
      <?php if (!empty($userClaimedVouchers)): ?>
        <div style="font-weight: 800; font-size: 0.8rem; color: #8A7769; text-transform: uppercase; letter-spacing: 0.5px;">My Claimed Coupons:</div>
        <?php foreach ($userClaimedVouchers as $uv): ?>
          <div style="display: flex; justify-content: space-between; align-items: center; background: #FAF7F2; border: 1.5px solid #E8DDD0; padding: 12px 14px; border-radius: 12px;">
            <div>
              <span style="background: #C85A3E; color: #FFF; font-weight: 800; font-size: 0.75rem; padding: 2px 8px; border-radius: 4px; font-family: monospace;"><?php echo htmlspecialchars($uv['voucher_code']); ?></span>
              <div style="font-weight: 800; color: #2C1C14; font-size: 0.9rem; margin-top: 4px;">
                <?php 
                  if ((float)$uv['discount_amount'] > 0) echo "RM " . number_format($uv['discount_amount'], 2) . " OFF";
                  else echo "Special Promo Voucher";
                ?>
              </div>
              <div style="font-size: 0.76rem; color: #7A685A; margin-top: 2px;">
                <?php echo $uv['min_spend'] > 0 ? 'Min Spend RM' . number_format($uv['min_spend'], 2) : 'No Min Spend'; ?>
              </div>
            </div>

            <button type="button" onclick="selectVoucher('<?php echo htmlspecialchars($uv['voucher_code']); ?>')" class="btn btn-orange btn-small" style="font-weight: 800; padding: 6px 14px; border-radius: 8px;">
              Use Voucher
            </button>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <!-- PUBLIC CAMPAIGN PROMOS (E.G. MERDEKA67) -->
      <?php if (!empty($publicCampaignPromos)): ?>
        <div style="font-weight: 800; font-size: 0.8rem; color: #8A7769; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 8px;">Public Store Promos:</div>
        <?php foreach ($publicCampaignPromos as $cp): ?>
          <div style="display: flex; justify-content: space-between; align-items: center; background: #FFFBF5; border: 1.5px solid #E5D9CC; padding: 12px 14px; border-radius: 12px;">
            <div>
              <span style="background: #8C6D58; color: #FFF; font-weight: 800; font-size: 0.75rem; padding: 2px 8px; border-radius: 4px; font-family: monospace;"><?php echo htmlspecialchars($cp['code']); ?></span>
              <div style="font-weight: 800; color: #2C1C14; font-size: 0.9rem; margin-top: 4px;"><?php echo htmlspecialchars($cp['title']); ?></div>
              <div style="font-size: 0.76rem; color: #7A685A; margin-top: 2px;">
                <?php echo $cp['min_spend'] > 0 ? 'Min Spend RM' . number_format($cp['min_spend'], 2) : 'No Min Spend'; ?>
              </div>
            </div>

            <button type="button" onclick="selectVoucher('<?php echo htmlspecialchars($cp['code']); ?>')" class="btn btn-outline btn-small" style="font-weight: 800; padding: 6px 14px; border-radius: 8px; border: 1.5px solid #C85A3E; color: #C85A3E;">
              Apply Code
            </button>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

    </div>
  </div>
</div>

<!-- Message (toast for add/remove/promo feedback) -->
<div class="message" id="message"></div>

<script>
    // Navigation toggle
    document.querySelector('.hamburger')?.addEventListener('click', () => {
        const nav = document.querySelector('.nav-links');
        nav.style.display = nav.style.display === 'flex' ? 'none' : 'flex';
    });

    let currentDiscount = <?php echo (float)$appliedDiscount; ?>;
    let currentPromoCode = '<?php echo htmlspecialchars($appliedCode); ?>';

    function showMessage(text) {
        const msgEl = document.getElementById('message');
        msgEl.textContent = text;
        msgEl.classList.add('show');
        setTimeout(() => msgEl.classList.remove('show'), 3500);
    }

    function openVoucherModal() {
        document.getElementById('voucherModal').style.display = 'flex';
    }
    function closeVoucherModal() {
        document.getElementById('voucherModal').style.display = 'none';
    }

    function selectVoucher(code) {
        document.getElementById('promoInput').value = code;
        closeVoucherModal();
        applyPromo();
    }

    function calculateTotals() {
        let subtotal = 0;
        const items = document.querySelectorAll('.cart-item');
        
        items.forEach(item => {
            const price = parseFloat(item.dataset.price);
            const qty = parseInt(item.querySelector('.qty-val').textContent);
            subtotal += price * qty;
        });

        const total = Math.max(0, subtotal - currentDiscount);

        document.getElementById('subtotal').textContent = 'RM ' + subtotal.toFixed(2);
        document.getElementById('total').textContent = 'RM ' + total.toFixed(2);
        document.getElementById('cartCount').textContent = items.length + ' Items';

        if (items.length === 0) {
            document.getElementById('summaryBox').style.display = 'none';
            document.getElementById('cartBox').innerHTML = `
                <div class="empty-cart" id="emptyState">
                    <span class="icon">🛒</span>
                    <h2>Cart is Empty</h2>
                    <p>You haven't added any coffee yet.<br>Browse our menu to get started!</p>
                    <a href="../menu/index.php" class="btn btn-orange btn-full">
                        ☕ Browse Menu &amp; Order
                    </a>
                </div>`;
        }
    }

    function updateQty(cartKey, delta) {
        const itemEl = document.querySelector(`.cart-item[data-key="${cartKey}"]`);
        if (!itemEl) return;

        const qtyValEl = itemEl.querySelector('.qty-val');
        let currentQty = parseInt(qtyValEl.textContent);
        let newQty = currentQty + delta;

        if (newQty <= 0) {
            const itemName = itemEl.querySelector('h3')?.textContent || 'this item';
            if (confirm(`Do you want to remove "${itemName}" from your cart?`)) {
                removeItem(cartKey);
            }
            return;
        }

        const formData = new FormData();
        formData.append('action', 'update_qty');
        formData.append('cart_key', cartKey);
        formData.append('quantity', newQty);

        fetch('index.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                qtyValEl.textContent = newQty;
                const unitPrice = parseFloat(itemEl.dataset.price);
                const subtotalPrice = unitPrice * newQty;
                const priceBox = itemEl.querySelector('.price-breakdown-box');

                if (priceBox) {
                    if (newQty > 1) {
                        priceBox.innerHTML = `<span style="font-size: 0.82rem; color: #7A685A; font-weight: 700; margin-right: 4px;">RM ${unitPrice.toFixed(2)} &times; ${newQty} =</span><span class="item-price" style="font-weight: 800; font-size: 1.1rem; color: var(--color-accent-dark);">RM ${subtotalPrice.toFixed(2)}</span>`;
                    } else {
                        priceBox.innerHTML = `<span class="item-price" style="font-weight: 800; font-size: 1.1rem; color: var(--color-accent-dark);">RM ${unitPrice.toFixed(2)}</span>`;
                    }
                }
                calculateTotals();
            }
        });
    }

    function removeItem(cartKey) {
        const itemEl = document.querySelector(`.cart-item[data-key="${cartKey}"]`);
        if (!itemEl) return;

        const formData = new FormData();
        formData.append('action', 'remove_item');
        formData.append('cart_key', cartKey);

        fetch('index.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                itemEl.remove();
                calculateTotals();
                showMessage('Item removed from cart.');
            }
        });
    }

    document.getElementById('fulfillmentSelect')?.addEventListener('change', function() {
        document.getElementById('fulfillmentLabel').textContent = this.options[this.selectedIndex].text;
    });

    function saveCartState(callback) {
        const code = document.getElementById('promoInput').value.trim().toUpperCase() || currentPromoCode;
        const fulfillment = document.getElementById('fulfillmentSelect')?.value || 'Dine-In';
        
        const formData = new FormData();
        formData.append('action', 'apply_promo');
        formData.append('promo_code', code);
        formData.append('fulfillment', fulfillment);

        fetch('index.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(() => { if (callback) callback(); });
    }

    function applyPromo() {
        const code = document.getElementById('promoInput').value.trim().toUpperCase();
        const fulfillment = document.getElementById('fulfillmentSelect')?.value || 'Dine-In';

        const formData = new FormData();
        formData.append('action', 'apply_promo');
        formData.append('promo_code', code);
        formData.append('fulfillment', fulfillment);

        fetch('index.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                currentDiscount = parseFloat(data.discount);
                currentPromoCode = data.code;

                if (currentDiscount > 0) {
                    document.getElementById('discountRow').style.display = 'flex';
                    document.getElementById('discount').textContent = '-RM ' + currentDiscount.toFixed(2);
                } else {
                    document.getElementById('discountRow').style.display = 'none';
                }
                showMessage(data.message || 'Promo code applied!');
                calculateTotals();
                // Reload to reflect free item badge
                if (data.free_item_id) {
                    setTimeout(() => { location.reload(); }, 1200);
                }
            } else {
                showMessage(data.message || 'Invalid or expired promo code.');
            }
        })
        .catch(err => {
            showMessage('Error applying promo code.');
        });
    }

    function checkout() {
        saveCartState(() => {
            window.location.href = '../checkout/index.php';
        });
    }
</script>

<?php require_once '../includes/footer.php'; ?>

</body>
</html>
