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
$deliveryFee = 0.00; // Takeaway Pickup mode (No Delivery Fee)

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

// Applied Promo Code & Discount from Cart
$appliedDiscount = $_SESSION['applied_promo']['discount'] ?? 0.00;
$appliedCode = $_SESSION['applied_promo']['code'] ?? '';
$fulfillmentType = $_SESSION['applied_promo']['fulfillment'] ?? 'Dine-In';

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $deliveryAddress = trim($_POST['delivery_address'] ?? 'Dine-In Table Service');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $specialInstructions = trim($_POST['special_instructions'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'Pay at Counter';
    
    // Calculate total (subtotal - discount)
    $totalAmount = max(0.00, $subtotal - $appliedDiscount);
    
    $errors = [];
    if (empty($deliveryAddress)) $errors[] = "Delivery address is required.";
    if (empty($contactNumber)) $errors[] = "Contact number is required.";
    if (empty($cartItems)) $errors[] = "Your cart is empty.";
    
    if (empty($errors)) {
        // Combine all extra info into special instructions (since we can't add new columns)
        $combinedInstructions = "Payment: " . ucfirst($paymentMethod) . "\n";
        $combinedInstructions .= "Contact: " . $contactNumber . "\n";
        $combinedInstructions .= "Address: " . $deliveryAddress . "\n";
        if ($specialInstructions) {
            $combinedInstructions .= "Notes: " . $specialInstructions;
        }
        
        // Insert order - now including payment_method
        $status = 'Pending';
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, payment_method) VALUES (?, ?, ?, ?)");
        
        if ($stmt === false) {
            $error = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("idss", $userId, $totalAmount, $status, $paymentMethod);
            
            if ($stmt->execute()) {
                $orderId = $conn->insert_id;
                $stmt->close();
                
                // Insert order items with customizations saved in item_options column
                $allItemsInserted = true;
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, item_id, quantity, price_at_order, item_options) VALUES (?, ?, ?, ?, ?)");
                
                if ($stmt === false) {
                    $error = "Database error: " . $conn->error;
                    // Clean up - delete the order
                    $conn->query("DELETE FROM orders WHERE order_id = $orderId");
                } else {
                    foreach ($cartItems as $item) {
                        $optsArr = [];
                        if (!empty($item['temperature'])) $optsArr[] = $item['temperature'];
                        if (!empty($item['sweetness'])) $optsArr[] = $item['sweetness'];
                        if (!empty($item['remarks'])) $optsArr[] = 'Note: ' . $item['remarks'];
                        $optsStr = implode(', ', $optsArr);

                        $stmt->bind_param("iiids", 
                            $orderId, 
                            $item['item_id'], 
                            $item['quantity'], 
                            $item['price'],
                            $optsStr
                        );
                        
                        if (!$stmt->execute()) {
                            $allItemsInserted = false;
                            $error = "Failed to save order items: " . $stmt->error;
                            break;
                        }
                    }
                    $stmt->close();
                    
                    if ($allItemsInserted) {
                        // Award Cozy Points to user if logged in (1 point per RM 1 spent)
                        if ($userId > 0) {
                            $earnedPoints = (int)floor($totalAmount);
                            if ($earnedPoints > 0) {
                                // Update user balance
                                $pUpd = $conn->prepare("UPDATE users SET rewards_points = rewards_points + ? WHERE id = ?");
                                $pUpd->bind_param("ii", $earnedPoints, $userId);
                                $pUpd->execute();
                                $pUpd->close();

                                // Record transaction history
                                $pLog = $conn->prepare("INSERT INTO points_history (user_id, points, description) VALUES (?, ?, ?)");
                                $desc = "Earned from Takeaway Order #{$orderId}";
                                $pLog->bind_param("iis", $userId, $earnedPoints, $desc);
                                $pLog->execute();
                                $pLog->close();

                                // Add notification record
                                $nLog = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, 'order', '../profile/index.php')");
                                $nTitle = "☕ Order #{$orderId} Placed!";
                                $nMsg = "Your takeaway order was placed successfully. You earned +{$earnedPoints} Cozy Points!";
                                $nLog->bind_param("iss", $userId, $nTitle, $nMsg);
                                $nLog->execute();
                                $nLog->close();
                            }
                        }

                        // Store the extra order info in session for the confirmation page
                        $_SESSION['order_delivery_info'] = [
                            'address' => $deliveryAddress,
                            'contact' => $contactNumber,
                            'instructions' => $specialInstructions,
                            'payment_method' => $paymentMethod,
                            'subtotal' => $subtotal,
                            'delivery_fee' => $deliveryFee,
                            'cart_items' => $cartItems
                        ];
                        
                        // Success! Clear cart and redirect
                        unset($_SESSION['cart']);
                        $_SESSION['last_order_id'] = $orderId;
                        header('Location: order_confirmation.php');
                        exit;
                    } else {
                        // Clean up - delete the order
                        $conn->query("DELETE FROM orders WHERE order_id = $orderId");
                        if (empty($error)) {
                            $error = "Failed to place order. Please try again.";
                        }
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

<div class="container">

    <!-- Header -->
    <div class="header">
        <h1>Takeaway Pickup Checkout</h1>
        <span class="badge"><?php echo count($cartItems); ?> Items</span>
    </div>

    <?php if (!$isLoggedIn): ?>
      <div style="background: #fff8eb; border: 1px solid #fcd34d; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; color: #92400e; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <span>💡 <strong>Tip:</strong> Logging in or creating an account earns you <strong>1 Cozy Point per RM1 spent</strong> + unlocks instant member discounts!</span>
        <div>
          <a href="../login/index.php" class="btn btn-orange btn-small" style="font-weight: 700;">Login</a>
          <a href="../register/index.php" class="btn btn-outline btn-small" style="margin-left: 6px;">Register</a>
        </div>
      </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="" class="checkout-form">
        <!-- Fulfillment & Contact Details Section -->
        <div class="section-box">
            <h2>Order Fulfillment Contact &amp; Table / Pickup Notes</h2>
            
            <div class="form-group">
                <label for="delivery_address">Table No. / Pickup Contact Name *</label>
                <input type="text" id="delivery_address" name="delivery_address" required 
                       value="<?php echo htmlspecialchars($deliveryAddress ?? 'Table / Self-Collect'); ?>"
                       placeholder="e.g. Table #5 or Pickup Name">
            </div>

            <div class="form-group">
                <label for="contact_number">Contact Number *</label>
                <input type="tel" id="contact_number" name="contact_number" required 
                       value="<?php echo htmlspecialchars($contactNumber ?? $user['phone'] ?? ''); ?>"
                       pattern="[0-9+\-\s()]+"
                       placeholder="012-3456789">
            </div>

            <div class="form-group">
                <label for="special_instructions">Special Instructions (Optional)</label>
                <textarea id="special_instructions" name="special_instructions" 
                          placeholder="Any special delivery instructions..."><?php echo htmlspecialchars($specialInstructions ?? ''); ?></textarea>
            </div>
        </div>

        <div class="section-box">
            <h2>Payment Method</h2>
            <div class="payment-methods">
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="Pay at Counter" checked>
                    <span class="payment-label">
                        <span class="payment-icon">💵</span>
                        Pay at Counter (Cash / Counter QR)
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

        <!-- Order Summary -->
        <div class="summary-box">
            <h2>Order Summary</h2>
            
            <div class="order-items">
                <?php foreach ($cartItems as $item): ?>
                    <div class="order-item">
                        <div class="item-info">
                            <span class="item-name">
                                <?php echo htmlspecialchars($item['name']); ?>
                                <span class="item-qty">×<?php echo $item['quantity']; ?></span>
                            </span>
                            <div class="item-options" style="margin-top: 4px; display:flex; gap:4px; flex-wrap:wrap;">
                                <?php if (!empty($item['temperature'])): ?>
                                    <span class="tag-chip tag-chip-temp"><?php echo htmlspecialchars($item['temperature']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($item['sweetness'])): ?>
                                    <span class="tag-chip tag-chip-sweet"><?php echo htmlspecialchars($item['sweetness']); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($item['remarks'])): ?>
                                <p class="item-remarks" style="margin-top: 4px;"><em>Note: "<?php echo htmlspecialchars($item['remarks']); ?>"</em></p>
                            <?php endif; ?>
                        </div>
                        <span class="item-price">RM <?php echo number_format($item['item_total'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="price-breakdown">
                <div class="price-row">
                    <span>Subtotal</span>
                    <span>RM <?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="price-row">
                    <span>Fulfillment Mode</span>
                    <span><?php echo htmlspecialchars($fulfillmentType); ?></span>
                </div>
                <?php if ($appliedDiscount > 0): ?>
                    <div class="price-row green" style="color: #059669; font-weight: 700;">
                        <span>Voucher Discount (<?php echo htmlspecialchars($appliedCode); ?>)</span>
                        <span>-RM <?php echo number_format($appliedDiscount, 2); ?></span>
                    </div>
                <?php endif; ?>
                <div class="price-row total">
                    <span>Total Payable</span>
                    <span class="amount">RM <?php echo number_format(max(0, $subtotal - $appliedDiscount), 2); ?></span>
                </div>
            </div>

            <div class="checkout-actions">
                <button type="submit" name="place_order" class="btn btn-orange btn-full">
                    Place Order 🛒
                </button>
                <a href="../cart/index.php" class="btn btn-outline btn-full">
                    ← Back to Cart
                </a>
            </div>
        </div>
    </form>
</div>

<script>
    document.querySelector('.hamburger').addEventListener('click', () => {
        const nav = document.querySelector('.nav-links');
        nav.style.display = nav.style.display === 'flex' ? 'none' : 'flex';
    });
</script>
</body>
</html>