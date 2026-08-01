<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="style/mystyle.css">
	<title>Cozy Coffee Co. — Cart</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style/mystyle.css">
    <style>
        :root {
            --orange: #C85A3E;
            --dark: #3d2019;
            --gray: #8b7355;
            --light: #fdf8f4;
            --white: #ffffff;
            --border: #ead5c7;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: var(--light);
            padding: 15px;
            color: var(--dark);
        }

        .container {
            max-width: 750px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            background: var(--white);
            padding: 18px 22px;
            border-radius: 14px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
        }

        .badge {
            background: var(--orange);
            color: var(--white);
            padding: 7px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 14px;
        }

        /* Buttons */
        .btn {
            font-family: 'Nunito', sans-serif;
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-orange {
            background: var(--orange);
            color: var(--white);
        }

        .btn-orange:hover {
            background: #b04a30;
        }

        .btn-outline {
            background: var(--white);
            color: var(--orange);
            border: 2px solid var(--orange);
        }

        .btn-outline:hover {
            background: var(--orange);
            color: var(--white);
        }

        .btn-red {
            background: none;
            color: #c0392b;
            border: 2px solid #c0392b;
            padding: 5px 10px;
            font-size: 13px;
            border-radius: 8px;
        }

        .btn-red:hover {
            background: #c0392b;
            color: white;
        }

        .btn-full {
            width: 100%;
            padding: 14px;
            font-size: 16px;
        }

        .btn-small {
            padding: 7px 14px;
            font-size: 13px;
        }

        /* Cart Box */
        .cart-box {
            background: var(--white);
            border-radius: 14px;
            margin-bottom: 15px;
        }

        /* Empty Cart */
        .empty-cart {
            text-align: center;
            padding: 45px 20px;
        }

        .empty-cart .icon {
            font-size: 65px;
            display: block;
            margin-bottom: 15px;
        }

        .empty-cart h2 {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            margin-bottom: 8px;
        }

        .empty-cart p {
            color: var(--gray);
            font-size: 15px;
            margin-bottom: 20px;
        }

        /* Cart Item */
        .cart-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            border-bottom: 1px solid var(--border);
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-img {
            width: 55px;
            height: 55px;
            border-radius: 10px;
            background: #f5e6d8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            flex-shrink: 0;
        }

        .item-info {
            flex: 1;
            min-width: 100px;
        }

        .item-info .name {
            font-family: 'Playfair Display', serif;
            font-size: 16px;
            font-weight: 700;
        }

        .item-info .detail {
            font-size: 12px;
            color: var(--gray);
            font-weight: 600;
        }

        .item-info .price {
            font-family: 'Playfair Display', serif;
            font-size: 15px;
            font-weight: 700;
            color: var(--orange);
        }

        /* Quantity */
        .qty-group {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .qty-btn {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid var(--border);
            background: white;
            cursor: pointer;
            font-size: 16px;
            font-weight: 700;
            color: var(--orange);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qty-btn:hover {
            background: var(--orange);
            color: white;
            border-color: var(--orange);
        }

        .qty-num {
            font-weight: 700;
            font-size: 15px;
            min-width: 20px;
            text-align: center;
        }

        .item-total {
            font-family: 'Playfair Display', serif;
            font-size: 16px;
            font-weight: 700;
            min-width: 55px;
            text-align: right;
        }

        /* Summary */
        .summary-box {
            background: var(--white);
            border-radius: 14px;
            padding: 20px 22px;
        }

        .summary-box h2 {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            margin-bottom: 15px;
        }

        .promo-row {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
        }

        .promo-row input {
            flex: 1;
            padding: 10px 14px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-family: 'Nunito', sans-serif;
            font-size: 14px;
            outline: none;
        }

        .promo-row input:focus {
            border-color: var(--orange);
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            padding: 7px 0;
            font-size: 14px;
            font-weight: 600;
        }

        .price-row.total {
            border-top: 2px solid var(--border);
            margin-top: 7px;
            padding-top: 13px;
            font-size: 18px;
            font-weight: 700;
        }

        .price-row.total .amount {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            color: var(--orange);
        }

        .green {
            color: #27ae60;
        }

        .menu-link {
            display: block;
            text-align: center;
            color: var(--orange);
            text-decoration: none;
            font-weight: 700;
            padding: 12px;
            font-size: 14px;
            margin-top: 8px;
        }

        .menu-link:hover {
            text-decoration: underline;
        }

        /* Message */
        .message {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #27ae60;
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            display: none;
            z-index: 999;
        }

        .message.show {
            display: block;
        }

        .message.error {
            background: #c0392b;
        }

        /* Responsive */
        @media (max-width: 600px) {
            .header h1 {
                font-size: 22px;
            }

            .cart-item {
                flex-wrap: wrap;
                gap: 8px;
                padding: 12px 14px;
            }

            .item-img {
                width: 45px;
                height: 45px;
                font-size: 22px;
            }

            .item-info .name {
                font-size: 15px;
            }

            .promo-row {
                flex-direction: column;
            }

            .summary-box {
                padding: 16px;
            }

            .price-row.total .amount {
                font-size: 22px;
            }
        }

        @media (max-width: 400px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .cart-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .item-total {
                align-self: flex-end;
            }

            .btn-red {
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
    <nav>
  <div class="logo"><a href="index.php">Cozy Coffee Co.</a></div>
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
    <li><a href="../login/index.php">Login</a></li>
  </ul>
  <button class="hamburger" aria-label="Menu"><span></span><span></span><span></span></button>
</nav>

    <div class="container">

        <!-- Header -->
        <div class="header">
            <h1>🛒 Your Cart</h1>
            <span class="badge" id="cartCount">0 Items</span>
        </div>

        <!-- Cart Items -->
        <div class="cart-box" id="cartBox">
            <div class="empty-cart" id="emptyState">
                <span class="icon">🛒</span>
                <h2>Cart is Empty</h2>
                <p>You haven't added any coffee yet.<br>Browse our menu to get started!</p>
                <a href="menu.html" class="btn btn-orange btn-full">
                    ☕ Browse Menu & Order
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
                <span id="subtotal">$0.00</span>
            </div>
            <div class="price-row">
                <span>Delivery Fee</span>
                <span id="delivery">$0.00</span>
            </div>
            <div class="price-row green" id="discountRow" style="display: none;">
                <span>Discount</span>
                <span id="discount">-$0.00</span>
            </div>
            <div class="price-row total">
                <span>Total</span>
                <span class="amount" id="total">$0.00</span>
            </div>

            <button class="btn btn-orange btn-full" onclick="checkout()" style="margin-top: 15px;">
                Proceed to Checkout 💳
            </button>
            <a href="menu.html" class="menu-link">← Continue Ordering — Back to Menu</a>
        </div>

    </div>

    <!-- Message -->
    <div class="message" id="message"></div>

</body>
</html>
