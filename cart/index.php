<?php
session_start();
require_once '../includes/db_connect.php';

// Handle AJAX requests for Cart Updates, Deletions, and Promo Applications
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $cartKey = $_POST['cart_key'] ?? '';

    if ($_POST['action'] === 'apply_promo') {
        $code = trim($_POST['promo_code'] ?? '');
        $discount = (float)($_POST['discount'] ?? 0);
        $fulfillment = trim($_POST['fulfillment'] ?? 'Dine-In');
        $_SESSION['applied_promo'] = [
            'code' => $code,
            'discount' => $discount,
            'fulfillment' => $fulfillment
        ];
        echo json_encode(['status' => 'success']);
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

// Fetch Cart Data from DB
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
                <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item" data-key="<?php echo $item['cart_key']; ?>" data-price="<?php echo $item['price']; ?>">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-img">
                        
                        <div class="cart-item-details">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            
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
                                <div style="text-align: right;">
                                  <span class="item-price" style="font-weight: 800; font-size: 1.1rem; color: var(--color-accent-dark);">RM <?php echo number_format($item['item_total'], 2); ?></span>
                                  <?php if ($item['quantity'] > 1): ?>
                                    <div style="font-size: 0.78rem; color: #777;">(RM <?php echo number_format($item['price'], 2); ?> &times; <?php echo $item['quantity']; ?>)</div>
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

        <div class="promo-row">
            <input type="text" placeholder="Enter coupon code (e.g. COZY3OFF)" id="promoInput">
            <button class="btn btn-outline btn-small" onclick="applyPromo()">Apply</button>
        </div>

        <div class="price-row">
            <span>Subtotal</span>
            <span id="subtotal">RM <?php echo number_format($subtotal, 2); ?></span>
        </div>
        <div class="price-row">
            <span>Fulfillment Type</span>
            <span id="fulfillmentLabel">Dine-In (Table Service)</span>
        </div>
        <div class="price-row green" id="discountRow" style="display: none;">
            <span>Coupon Discount</span>
            <span id="discount">-RM 0.00</span>
        </div>
        <div class="price-row total">
            <span>Total</span>
            <span class="amount" id="total">
                RM <?php echo number_format($subtotal, 2); ?>
            </span>
        </div>

        <button class="btn btn-orange btn-full" onclick="checkout()" style="margin-top: 15px;">
            Proceed to Checkout 💳
        </button>
        <a href="../menu/index.php" class="menu-link">Continue Ordering (Back to Menu)</a>
    </div>

</div>

<!-- Message (toast for add/remove/promo feedback) -->
<div class="message" id="message"></div>

<script>
    // Navigation toggle
    document.querySelector('.hamburger').addEventListener('click', () => {
        const nav = document.querySelector('.nav-links');
        nav.style.display = nav.style.display === 'flex' ? 'none' : 'flex';
    });

    let currentDiscount = 0;

    function showMessage(text) {
        const msgEl = document.getElementById('message');
        msgEl.textContent = text;
        msgEl.classList.add('show');
        setTimeout(() => msgEl.classList.remove('show'), 3000);
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
            removeItem(cartKey);
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
                itemEl.querySelector('.item-price').textContent = 'RM ' + (unitPrice * newQty).toFixed(2);
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

    let currentPromoCode = '';

    document.getElementById('fulfillmentSelect')?.addEventListener('change', function() {
        document.getElementById('fulfillmentLabel').textContent = this.options[this.selectedIndex].text;
    });

    function saveCartState(callback) {
        const code = document.getElementById('promoInput').value.trim().toUpperCase() || currentPromoCode;
        const fulfillment = document.getElementById('fulfillmentSelect')?.value || 'Dine-In';
        
        const formData = new FormData();
        formData.append('action', 'apply_promo');
        formData.append('promo_code', code);
        formData.append('discount', currentDiscount);
        formData.append('fulfillment', fulfillment);

        fetch('index.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(() => { if (callback) callback(); });
    }

    function applyPromo() {
        const code = document.getElementById('promoInput').value.trim().toUpperCase();
        let subtotalVal = 0;
        document.querySelectorAll('.cart-item').forEach(item => {
            subtotalVal += parseFloat(item.dataset.price) * parseInt(item.querySelector('.qty-val').textContent);
        });

        if (code === 'COZY3OFF' || code === 'COZY10') {
            currentDiscount = 3.00;
            currentPromoCode = code;
            document.getElementById('discountRow').style.display = 'flex';
            document.getElementById('discount').textContent = '-RM ' + currentDiscount.toFixed(2);
            showMessage('Voucher COZY3OFF applied (-RM 3.00)!');
        } else if (code === 'COZYPASTRY') {
            currentDiscount = 8.00;
            currentPromoCode = code;
            document.getElementById('discountRow').style.display = 'flex';
            document.getElementById('discount').textContent = '-RM ' + currentDiscount.toFixed(2);
            showMessage('Voucher COZYPASTRY applied (-RM 8.00 Free Pastry)!');
        } else if (code === 'COZY50OFF') {
            currentDiscount = subtotalVal * 0.50;
            currentPromoCode = code;
            document.getElementById('discountRow').style.display = 'flex';
            document.getElementById('discount').textContent = '-RM ' + currentDiscount.toFixed(2);
            showMessage('Voucher COZY50OFF applied (50% OFF Coffee)!');
        } else {
            showMessage('Invalid or expired voucher code.');
        }
        calculateTotals();
        saveCartState();
    }

    function checkout() {
        saveCartState(() => {
            window.location.href = '../checkout/index.php';
        });
    }
</script>

</body>
</html>
