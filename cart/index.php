<?php
// Start the session to access logged-in user data
session_start();
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
    <li><a href="../contact/index.php">Contact</a></li>
    <li><a href="../cart/index.php" class="active">Cart</a></li>

    <!-- DYNAMIC NAVIGATION LINK -->
    <?php if (isset($_SESSION['user_id'])): ?>
      <!-- Logged In State: Show Username & Profile Dropdown -->
      <li>
        <a href="../profile/index.php"><?php echo htmlspecialchars($_SESSION['username']); ?> ▾</a>
        <div class="dropdown">
          <a href="../profile/index.php">My Profile</a>
          <a href="../logout.php">Logout</a>
        </div>
      </li>
    <?php else: ?>
      <!-- Guest State: Show Login Link -->
      <li><a href="../login/index.php">Login</a></li>
    <?php endif; ?>

  </ul>
  <button class="hamburger" aria-label="Menu"><span></span><span></span><span></span></button>
</nav>

<div class="container">

    <!-- Header -->
    <div class="header">
        <h1>🛒 Your Cart</h1>
        <span class="badge" id="cartCount">0 Items</span>
    </div>

    <!-- Cart Items Box -->
    <div class="cart-box" id="cartBox">
        <div class="empty-cart" id="emptyState">
            <span class="icon">🛒</span>
            <h2>Cart is Empty</h2>
            <p>You haven't added any coffee yet.<br>Browse our menu to get started!</p>
            <a href="../menu/index.php" class="btn btn-orange btn-full">
                ☕ Browse Menu &amp; Order
            </a>
        </div>
    </div>

    <!-- Order Summary -->
    <div class="summary-box" id="summaryBox" style="display: none;">
        <h2>Order Summary</h2>

        <div class="promo-row">
            <input type="text" placeholder="Enter promo code" id="promoInput">
            <button class="btn btn-outline btn-small" onclick="applyPromo()">Apply</button>
        </div>

        <div class="price-row">
            <span>Subtotal</span>
            <span id="subtotal">RM 0.00</span>
        </div>
        <div class="price-row">
            <span>Delivery Fee</span>
            <span id="delivery">RM 0.00</span>
        </div>
        <div class="price-row green" id="discountRow" style="display: none;">
            <span>Discount</span>
            <span id="discount">-RM 0.00</span>
        </div>
        <div class="price-row total">
            <span>Total</span>
            <span class="amount" id="total">RM 0.00</span>
        </div>

        <button class="btn btn-orange btn-full" onclick="checkout()" style="margin-top: 15px;">
            Proceed to Checkout 💳
        </button>
        <a href="../menu/index.php" class="menu-link">← Continue Ordering — Back to Menu</a>
    </div>

</div>

<!-- Message (toast for add/remove/promo feedback) -->
<div class="message" id="message"></div>

<script>
    // Mobile nav toggle
    document.querySelector('.hamburger').addEventListener('click', () => {
        const nav = document.querySelector('.nav-links');
        nav.style.display = nav.style.display === 'flex' ? 'none' : 'flex';
    });
</script>

</body>
</html>