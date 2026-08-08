<?php
session_start();
require_once '../includes/db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = '../checkout/index.php';
    header('Location: ../login/index.php');
    exit;
}

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    header('Location: ../cart/index.php');
    exit;
}

// Fetch user details
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, fullname, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch cart items with details
$cartItems = [];
$subtotal = 0;
$deliveryFee = 5.00;

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
                $itemTotal = $item['price'] * $cartData['quantity'];
                $subtotal += $itemTotal;

                $cartItems[] = [
                    'cart_key' => $key,
                    'item_id' => $id,
                    'name' => $item['name'],
                    'price' => (float)$item['price'],
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

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $deliveryAddress = trim($_POST['delivery_address'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $specialInstructions = trim($_POST['special_instructions'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    
    // Calculate total (subtotal + delivery fee)
    $totalAmount = $subtotal + $deliveryFee;
    
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
        
        // Insert order - only using existing columns
        $status = 'Pending';
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, ?)");
        
        if ($stmt === false) {
            $error = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("ids", $userId, $totalAmount, $status);
            
            if ($stmt->execute()) {
                $orderId = $conn->insert_id;
                $stmt->close();
                
                // Insert order items with customizations in a note
                $allItemsInserted = true;
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, item_id, quantity, price_at_order) VALUES (?, ?, ?, ?)");
                
                if ($stmt === false) {
                    $error = "Database error: " . $conn->error;
                    // Clean up - delete the order
                    $conn->query("DELETE FROM orders WHERE order_id = $orderId");
                } else {
                    foreach ($cartItems as $item) {
                        $stmt->bind_param("iiid", 
                            $orderId, 
                            $item['item_id'], 
                            $item['quantity'], 
                            $item['price']
                        );
                        
                        if (!$stmt->execute()) {
                            $allItemsInserted = false;
                            $error = "Failed to save order items: " . $stmt->error;
                            break;
                        }
                    }
                    $stmt->close();
                    
                    if ($allItemsInserted) {
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

<nav>
  <div class="logo"><a href="../home/index.php">Cozy Coffee Co.</a></div>
  <ul class="nav-links">
    <li><a href="../home/index.php">Home</a></li>
    <li>
      <a href="../menu/index.php">Menu ▾</a>
      <div class="dropdown">
        <a href="../menu/index.php?cat=specialty#specialty">Specialty</a>
        <a href="../menu/index.php?cat=classic#classic">Classic Coffee</a>
        <a href="../menu/index.php?cat=noncoffein#noncoffein">Non-Coffein</a>
        <a href="../menu/index.php?cat=smoothies#smoothies">Smoothies &amp; Sodas</a>
        <a href="../menu/index.php?cat=mains#mains">Main Dishes</a>
        <a href="../menu/index.php?cat=desserts#desserts">Desserts</a>
      </div>
    </li>
    <li><a href="../blog/index.php">Blog</a></li>
        <li><a href="../benefits/index.php">Benefits</a></li>
    <li>
      <a href="../offers/index.php">Offers ▾</a>
      <div class="dropdown">
        <a href="../offers/index.php#drinks">Drink Offers</a>
        <a href="../offers/index.php#food">Food Offers</a>
        <a href="../offers/index.php#partners">Partner Promotions</a>
      </div>
    </li>
    <li>
      <a href="../activities/index.php">Activities ▾</a>
      <div class="dropdown">
        <a href="../activities/index.php#workshops">Coffee Workshops</a>
        <a href="../activities/index.php#giveback">Cozy Give-Back</a>
      </div>
    </li>
    <li><a href="../contact/index.php">Contact</a></li>
    <li><a href="../cart/index.php">Cart</a></li>

    <?php if (isset($_SESSION['user_id'])): ?>
      <li>
        <a href="../profile/index.php"><?php echo htmlspecialchars($_SESSION['fullname']); ?> ▾</a>
        <div class="dropdown">
          <a href="../profile/index.php">My Profile</a>
          <a href="../rewards/index.php">Cozy Rewards</a>
          <a href="../logout.php">Logout</a>
        </div>
      </li>
    <?php else: ?>
      <li><a href="../login/index.php">Login</a></li>
    <?php endif; ?>

  </ul>
  <button class="hamburger" aria-label="Menu"><span></span><span></span><span></span></button>
</nav>

<div class="container">

    <!-- Header -->
    <div class="header">
        <h1>Checkout</h1>
        <span class="badge"><?php echo count($cartItems); ?> Items</span>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="" class="checkout-form">
        <!-- Delivery & Payment Section -->
        <div class="section-box">
            <h2>Delivery Details</h2>
            
            <div class="form-group">
                <label for="delivery_address">Delivery Address *</label>
                <textarea id="delivery_address" name="delivery_address" required 
                          placeholder="Enter your full delivery address"><?php echo htmlspecialchars($deliveryAddress ?? ''); ?></textarea>
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
                    <input type="radio" name="payment_method" value="cash" checked>
                    <span class="payment-label">
                        <span class="payment-icon">💵</span>
                        Cash on Delivery
                    </span>
                </label>
                
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="card">
                    <span class="payment-label">
                        <span class="payment-icon">💳</span>
                        Credit/Debit Card
                    </span>
                </label>
                
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="ewallet">
                    <span class="payment-label">
                        <span class="payment-icon">📱</span>
                        E-Wallet
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
                            <?php if ($item['temperature'] || $item['sweetness']): ?>
                                <div class="item-options">
                                    <?php if ($item['temperature']): ?>
                                        <span class="option-tag"><?php echo htmlspecialchars($item['temperature']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($item['sweetness']): ?>
                                        <span class="option-tag"><?php echo htmlspecialchars($item['sweetness']); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($item['remarks']): ?>
                                <p class="item-remarks"><em>Note: "<?php echo htmlspecialchars($item['remarks']); ?>"</em></p>
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
                    <span>Delivery Fee</span>
                    <span>RM <?php echo number_format($deliveryFee, 2); ?></span>
                </div>
                <div class="price-row total">
                    <span>Total</span>
                    <span class="amount">RM <?php echo number_format($subtotal + $deliveryFee, 2); ?></span>
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

