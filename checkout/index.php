<?php
session_start();
require_once '../includes/db_connect.php';

$userId = $_SESSION['user_id'] ?? 0;
$isLoggedIn = $userId > 0;
$user = ['fullname' => '', 'email' => '', 'phone' => ''];

if ($isLoggedIn) {
    $stmt = $conn->prepare("SELECT id, fullname, email, phone FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc() ?: $user;
    $stmt->close();
}

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    header('Location: ../cart/index.php');
    exit;
}

// Fetch cart items with details
$cartItems = [];
$subtotal = 0;

if (!empty($_SESSION['cart'])) {
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

        foreach ($_SESSION['cart'] as $key => $cartData) {
            $id = $cartData['item_id'];
            if (isset($dbItems[$id])) {
                $item = $dbItems[$id];
                $effectivePrice = (isset($cartData['custom_price']) && $cartData['custom_price'] > 0) ? (float)$cartData['custom_price'] : (float)$item['price'];
                $itemTotal = $effectivePrice * $cartData['quantity'];
                $subtotal += $itemTotal;

                $cartItems[] = [
                    'cart_key' => $key,
                    'item_id' => $id,
                    'name' => $item['name'],
                    'price' => $effectivePrice,
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

// Applied Promo Code & Fulfillment Type from Cart
$appliedDiscount = $_SESSION['applied_promo']['discount'] ?? 0.00;
$appliedCode = $_SESSION['applied_promo']['code'] ?? '';
$fulfillmentType = $_SESSION['applied_promo']['fulfillment'] ?? 'Dine-In';

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'Pay at Counter';
    $tableNumber   = ($fulfillmentType === 'Dine-In') ? trim($_POST['table_number'] ?? 'Table 1') : null;
    $byoTumbler    = isset($_POST['byo_tumbler']) ? 1 : 0;
    $byoContainer  = isset($_POST['byo_container']) ? 1 : 0;
    
    // Calculate total (subtotal - discount)
    $totalAmount = max(0.00, $subtotal - $appliedDiscount);
    
    $errors = [];
    if (empty($contactNumber)) $errors[] = "Contact number is required for order verification.";
    if ($fulfillmentType === 'Dine-In' && empty($tableNumber)) $errors[] = "Please select your table number.";
    if (empty($cartItems)) $errors[] = "Your cart is empty.";
    
    if (empty($errors)) {
        $status = 'Pending';
        
        // Prepare user_id (NULL for guest users, integer for logged-in users)
        if ($isLoggedIn) {
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, payment_method, fulfillment_type, table_number, contact_number, byo_tumbler, byo_container, order_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("idsssssii", $userId, $totalAmount, $status, $paymentMethod, $fulfillmentType, $tableNumber, $contactNumber, $byoTumbler, $byoContainer);
        } else {
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, payment_method, fulfillment_type, table_number, contact_number, byo_tumbler, byo_container, order_date) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("dsssssii", $totalAmount, $status, $paymentMethod, $fulfillmentType, $tableNumber, $contactNumber, $byoTumbler, $byoContainer);
        }
        
        if ($stmt === false) {
            $error = "Database error preparing order: " . $conn->error;
        } else {
            if ($stmt->execute()) {
                $orderId = $conn->insert_id;
                $stmt->close();
                
                // Insert order items into order_items table
                $allItemsInserted = true;
                $stmtItems = $conn->prepare("INSERT INTO order_items (order_id, item_id, quantity, price_at_order, item_options) VALUES (?, ?, ?, ?, ?)");
                
                if ($stmtItems === false) {
                    $error = "Database error inserting items: " . $conn->error;
                    $conn->query("DELETE FROM orders WHERE order_id = $orderId");
                } else {
                    foreach ($cartItems as $item) {
                        $optsArr = [];
                        $isFoodCat = in_array((int)($item['category_id'] ?? 0), [5, 6], true);
                        if (!$isFoodCat && !empty($item['temperature'])) $optsArr[] = $item['temperature'];
                        if (!$isFoodCat && !empty($item['sweetness'])) $optsArr[] = $item['sweetness'];
                        if (!empty($item['remarks'])) $optsArr[] = 'Note: ' . $item['remarks'];
                        $optsStr = implode(', ', $optsArr);

                        $stmtItems->bind_param("iiids", 
                            $orderId, 
                            $item['item_id'], 
                            $item['quantity'], 
                            $item['price'],
                            $optsStr
                        );
                        
                        if (!$stmtItems->execute()) {
                            $allItemsInserted = false;
                            $error = "Failed to save order item: " . $stmtItems->error;
                            break;
                        }
                    }
                    $stmtItems->close();
                    
                    if ($allItemsInserted) {
                        // Award Cozy Points to user if logged in (1 point per RM1 spent + 10 bonus if BYO Tumbler)
                        if ($isLoggedIn) {
                            $earnedPoints = (int)floor($totalAmount);
                            $ecoBonus = 0;
                            if ($byoTumbler === 1) {
                                $earnedPoints += 10;
                                $ecoBonus += 10;
                            }
                            if ($byoContainer === 1) {
                                $earnedPoints += 10;
                                $ecoBonus += 10;
                            }

                            if ($earnedPoints > 0) {
                                // Update user points balance in users table
                                $pUpd = $conn->prepare("UPDATE users SET points = points + ?, rewards_points = rewards_points + ? WHERE id = ?");
                                $pUpd->bind_param("iii", $earnedPoints, $earnedPoints, $userId);
                                $pUpd->execute();
                                $pUpd->close();

                                // Record transaction history
                                $pLog = $conn->prepare("INSERT INTO points_history (user_id, points, description) VALUES (?, ?, ?)");
                                $desc = "Earned from Order #{$orderId}" . ($ecoBonus > 0 ? " (includes +{$ecoBonus} BYO Eco Bonus)" : "");
                                $pLog->bind_param("iis", $userId, $earnedPoints, $desc);
                                $pLog->execute();
                                $pLog->close();

                                // Add notification record
                                $nLog = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, 'order', '../profile/orders.php')");
                                $nTitle = "☕ Order #{$orderId} Placed!";
                                $nMsg = "Your order was placed successfully. You earned +{$earnedPoints} Cozy Points!";
                                $nLog->bind_param("iss", $userId, $nTitle, $nMsg);
                                $nLog->execute();
                                $nLog->close();
                            }
                        }

                        // Store order confirmation info in session
                        $_SESSION['last_order_info'] = [
                            'order_id' => $orderId,
                            'fulfillment_type' => $fulfillmentType,
                            'table_number' => $tableNumber,
                            'contact_number' => $contactNumber,
                            'payment_method' => $paymentMethod,
                            'byo_tumbler' => $byoTumbler,
                            'byo_container' => $byoContainer,
                            'subtotal' => $subtotal,
                            'discount' => $appliedDiscount,
                            'total_amount' => $totalAmount,
                            'cart_items' => $cartItems,
                            'is_logged_in' => $isLoggedIn
                        ];
                        
                        // Clear cart session
                        unset($_SESSION['cart']);
                        unset($_SESSION['applied_promo']);
                        
                        header("Location: order_confirmation.php?order_id={$orderId}");
                        exit;
                    } else {
                        $conn->query("DELETE FROM orders WHERE order_id = $orderId");
                    }
                }
            } else {
                $error = "Failed to create order: " . $stmt->error;
                $stmt->close();
            }
        }
    } else {
        $error = implode("<br>", $errors);
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
  <title>Cozy Coffee Co. — Checkout</title>
</head>

<body class="checkout-page">

<?php 
  $activePage = 'cart';
  require_once '../includes/header_nav.php'; 
?>

<div class="container" style="max-width: 1040px; margin: 30px auto; padding: 0 4%;">

    <!-- Header -->
    <div class="header" style="margin-bottom: 24px;">
        <h1 style="font-family: var(--font-heading); font-size: 1.8rem; color: #2C1C14; font-weight: 800;">Order Checkout</h1>
        <span class="badge" style="background: rgba(200, 90, 62, 0.12); color: #C85A3E; font-weight: 800; padding: 6px 14px; border-radius: 20px; border: 1px solid rgba(200, 90, 62, 0.3);">
          <?php echo htmlspecialchars($fulfillmentType); ?> Mode (<?php echo count($cartItems); ?> Items)
        </span>
    </div>

    <?php if (!$isLoggedIn): ?>
      <div style="background: #FFFBF5; border: 1.5px solid #E5D9CC; padding: 16px 20px; border-radius: 16px; margin-bottom: 24px; color: #665447; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; box-shadow: 0 4px 14px rgba(0,0,0,0.04);">
        <span>💡 <strong>Tip:</strong> Log in or register for free to earn <strong>1 Cozy Point per RM1 spent</strong> on this order!</span>
        <div style="display: flex; gap: 8px;">
          <a href="../login/index.php" class="btn btn-orange btn-small" style="font-weight: 800; padding: 8px 18px; border-radius: 20px;">Log In</a>
          <a href="../register/index.php" class="btn btn-outline btn-small" style="font-weight: 700; padding: 8px 18px; border-radius: 20px;">Register Free</a>
        </div>
      </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error" style="background: #FEE2E2; border: 1px solid #FCA5A5; color: #991B1B; padding: 14px 18px; border-radius: 12px; margin-bottom: 24px; font-weight: 700;">
          <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" class="checkout-form">
        
        <!-- Fulfillment & Contact Details Section -->
        <div class="section-box" style="background: #FFFFFF; border-radius: 20px; border: 1.5px solid #E8DDD0; padding: 26px; margin-bottom: 24px; box-shadow: 0 6px 20px rgba(60,42,33,0.04);">
            <h2 style="font-family: var(--font-heading); font-size: 1.35rem; color: #2C1C14; margin-bottom: 18px; font-weight: 800;">
              Order Fulfillment &amp; Contact
            </h2>
            
            <!-- DINE-IN MODE: TABLE NUMBER DROPDOWN (ONLY TABLES 1 TO 14) -->
            <?php if ($fulfillmentType === 'Dine-In'): ?>
              <div class="form-group" style="margin-bottom: 18px;">
                <label for="table_number" style="font-weight: 800; color: #2C1C14; display: block; margin-bottom: 6px;">
                  🍽️ Select Table Number (Tables 1 – 14) *
                </label>
                <select id="table_number" name="table_number" required style="width: 100%; height: 46px; border-radius: 12px; border: 1.5px solid #E5D9CC; background: #FAF7F2; padding: 10px 14px; font-size: 0.95rem; font-weight: 700; color: #2C1C14;">
                  <?php for ($i = 1; $i <= 14; $i++): ?>
                    <option value="Table <?php echo $i; ?>" <?php echo (isset($_POST['table_number']) && $_POST['table_number'] === "Table $i") ? 'selected' : ''; ?>>
                      🍽️ Table <?php echo $i; ?>
                    </option>
                  <?php endfor; ?>
                </select>
              </div>
            <?php endif; ?>

            <!-- CONTACT NUMBER FIELD -->
            <div class="form-group" style="margin-bottom: 18px;">
                <label for="contact_number" style="font-weight: 800; color: #2C1C14; display: block; margin-bottom: 6px;">
                  📱 Contact Phone Number *
                </label>
                <input type="tel" id="contact_number" name="contact_number" required 
                       value="<?php echo htmlspecialchars($_POST['contact_number'] ?? $user['phone'] ?? ''); ?>"
                       pattern="[0-9+\-\s()]+"
                       placeholder="e.g. 012-3456789"
                       style="width: 100%; height: 46px; border-radius: 12px; border: 1.5px solid #E5D9CC; background: #FAF7F2; padding: 10px 14px; font-size: 0.95rem; font-weight: 700; color: #2C1C14; box-sizing: border-box;">
            </div>

            <!-- TAKEAWAY PICKUP MODE: ECO BYO REQUIREMENTS CHECKBOXES (NO TABLE DROPDOWN) -->
            <?php if ($fulfillmentType === 'Takeaway Pickup'): ?>
              <div style="background: #FAF4EB; border: 1.5px solid #E8DDD0; border-radius: 14px; padding: 18px; margin-top: 10px;">
                <h4 style="font-family: var(--font-heading); color: #2C1C14; font-size: 1rem; margin: 0 0 10px 0; font-weight: 800;">
                  🌿 Eco BYO Requirements (Optional)
                </h4>
                
                <div style="display: flex; flex-direction: column; gap: 10px;">
                  <label style="display: flex; align-items: center; gap: 10px; font-size: 0.9rem; font-weight: 700; color: #4A3B32; cursor: pointer;">
                    <input type="checkbox" name="byo_tumbler" id="byo_tumbler" value="1" onchange="toggleByoNotice()" style="width: 18px; height: 18px; accent-color: #C85A3E;">
                    <span>🥤 I bring my own tumbler (Eco BYO +10 Cozy Points)</span>
                  </label>

                  <label style="display: flex; align-items: center; gap: 10px; font-size: 0.9rem; font-weight: 700; color: #4A3B32; cursor: pointer;">
                    <input type="checkbox" name="byo_container" id="byo_container" value="1" onchange="toggleByoNotice()" style="width: 18px; height: 18px; accent-color: #C85A3E;">
                    <span>🍱 I bring my own lunchbox / container (Eco BYO)</span>
                  </label>
                </div>

                <!-- DYNAMIC ECO NOTICE MESSAGE -->
                <div id="byoNoticeBox" style="display: none; background: #ECFDF5; border: 1px solid #6EE7B7; color: #065F46; padding: 12px 16px; border-radius: 10px; font-weight: 700; font-size: 0.88rem; margin-top: 12px;">
                  🌿 Please pass your tumbler / container to our counter barista upon arrival.
                </div>
              </div>
            <?php endif; ?>

        </div>

        <!-- Payment Method Section -->
        <div class="section-box" style="background: #FFFFFF; border-radius: 20px; border: 1.5px solid #E8DDD0; padding: 26px; margin-bottom: 24px; box-shadow: 0 6px 20px rgba(60,42,33,0.04);">
            <h2 style="font-family: var(--font-heading); font-size: 1.35rem; color: #2C1C14; margin-bottom: 18px; font-weight: 800;">
              Payment Method
            </h2>
            <div class="payment-methods">
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="Pay at Counter" checked>
                    <span class="payment-label">
                        <span class="payment-icon">💵</span>
                        Pay at Counter (Cash)
                    </span>
                </label>

                <label class="payment-option">
                    <input type="radio" name="payment_method" value="Online Banking (FPX)">
                    <span class="payment-label">
                        <span class="payment-icon">🏦</span>
                        Online Banking (FPX)
                    </span>
                </label>
                
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="Credit/Debit Card">
                    <span class="payment-label">
                        <span class="payment-icon">💳</span>
                        Credit / Debit Card
                    </span>
                </label>
                
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="Touch 'n Go E-Wallet">
                    <span class="payment-label">
                        <span class="payment-icon">📱</span>
                        Touch 'n Go E-Wallet
                    </span>
                </label>
            </div>
        </div>

        <!-- Order Summary & Checkout Actions -->
        <div class="summary-box" style="background: #FFFFFF; border-radius: 20px; border: 1.5px solid #E8DDD0; padding: 26px; box-shadow: 0 6px 20px rgba(60,42,33,0.04);">
            <h2 style="font-family: var(--font-heading); font-size: 1.35rem; color: #2C1C14; margin-bottom: 18px; font-weight: 800;">Order Summary</h2>
            
            <div class="order-items" style="margin-bottom: 20px;">
                <?php foreach ($cartItems as $item): ?>
                    <div class="order-item" style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #FAF4EB;">
                        <div class="item-info">
                            <span class="item-name" style="font-weight: 800; color: #2C1C14;">
                                <?php echo htmlspecialchars($item['name']); ?>
                                <span class="item-qty" style="color: #C85A3E;">×<?php echo $item['quantity']; ?></span>
                            </span>
                            <div class="item-options" style="margin-top: 4px; display:flex; gap:6px; flex-wrap:wrap;">
                                <?php if (!empty($item['temperature'])): ?>
                                    <span class="tag-chip tag-chip-temp"><?php echo htmlspecialchars($item['temperature']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($item['sweetness'])): ?>
                                    <span class="tag-chip tag-chip-sweet"><?php echo htmlspecialchars($item['sweetness']); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($item['remarks'])): ?>
                                <p class="item-remarks" style="margin-top: 4px; font-size: 0.82rem; color: #8A7769;"><em>Note: "<?php echo htmlspecialchars($item['remarks']); ?>"</em></p>
                            <?php endif; ?>
                        </div>
                        <span class="item-price" style="font-weight: 800; color: #C85A3E;">RM <?php echo number_format($item['item_total'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="price-breakdown" style="border-top: 1.5px solid #E8DDD0; padding-top: 16px; margin-bottom: 24px;">
                <div class="price-row" style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: #665447; font-weight: 600;">Subtotal</span>
                    <span style="font-weight: 700; color: #2C1C14;">RM <?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="price-row" style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: #665447; font-weight: 600;">Fulfillment Mode</span>
                    <span style="font-weight: 700; color: #2C1C14;"><?php echo htmlspecialchars($fulfillmentType); ?></span>
                </div>
                <?php if ($appliedDiscount > 0): ?>
                    <div class="price-row green" style="display: flex; justify-content: space-between; margin-bottom: 8px; color: #059669; font-weight: 700;">
                        <span>Voucher Discount (<?php echo htmlspecialchars($appliedCode); ?>)</span>
                        <span>-RM <?php echo number_format($appliedDiscount, 2); ?></span>
                    </div>
                <?php endif; ?>
                <div class="price-row total" style="display: flex; justify-content: space-between; margin-top: 12px; padding-top: 12px; border-top: 1px solid #FAF4EB; font-size: 1.25rem;">
                    <span style="font-weight: 800; color: #2C1C14;">Total Payable</span>
                    <span class="amount" style="font-weight: 800; color: #C85A3E; font-family: var(--font-heading);">RM <?php echo number_format(max(0, $subtotal - $appliedDiscount), 2); ?></span>
                </div>
            </div>

            <div class="checkout-actions" style="display: flex; flex-direction: column; gap: 10px;">
                <button type="submit" name="place_order" class="btn btn-orange btn-full" style="height: 50px; border-radius: 12px; font-weight: 800; font-size: 1.05rem; justify-content: center; box-shadow: 0 6px 20px rgba(200, 90, 62, 0.35);">
                    Continue Checkout 💳
                </button>
                <a href="../cart/index.php" class="btn btn-outline btn-full" style="height: 44px; border-radius: 12px; font-weight: 700; font-size: 0.9rem; justify-content: center; text-align: center; border: 1.5px solid #E5D9CC; color: #665447; text-decoration: none; display: flex; align-items: center;">
                    ← Back to Cart
                </a>
            </div>
        </div>
    </form>
</div>

<script>
function toggleByoNotice() {
    const tumblerCb = document.getElementById('byo_tumbler');
    const containerCb = document.getElementById('byo_container');
    const noticeBox = document.getElementById('byoNoticeBox');
    
    if (noticeBox) {
        if ((tumblerCb && tumblerCb.checked) || (containerCb && containerCb.checked)) {
            noticeBox.style.display = 'block';
        } else {
            noticeBox.style.display = 'none';
        }
    }
}
</script>
</body>
</html>