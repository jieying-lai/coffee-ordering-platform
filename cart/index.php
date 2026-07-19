<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="style/mystyle.css">
	<title>Cozy Coffee Co. — Cart</title>
</head>
<body>
<h1>Cart</h1>
 <!-- cart items – design only, no interaction -->
  <div class="cart-list">
    <!-- item 1 -->
    <div class="cart-item">
      <div class="item-info">
        <span class="item-emoji">☕</span>
        <span class="item-name">Espresso</span>
        <span class="item-price">$3.50</span>
      </div>
      <div class="item-controls">
        <div class="qty-selector">
          <span class="qty-btn">−</span>
          <span class="qty-value">2</span>
          <span class="qty-btn">+</span>
        </div>
        <span class="item-total">$7.00</span>
        <span class="remove-btn">✕</span>
      </div>
    </div>

    <!-- item 2 -->
    <div class="cart-item">
      <div class="item-info">
        <span class="item-emoji">☕</span>
        <span class="item-name">Cappuccino</span>
        <span class="item-price">$4.20</span>
      </div>
      <div class="item-controls">
        <div class="qty-selector">
          <span class="qty-btn">−</span>
          <span class="qty-value">1</span>
          <span class="qty-btn">+</span>
        </div>
        <span class="item-total">$4.20</span>
        <span class="remove-btn">✕</span>
      </div>
    </div>

    <!-- item 3 -->
    <div class="cart-item">
      <div class="item-info">
        <span class="item-emoji">🍫</span>
        <span class="item-name">Mocha</span>
        <span class="item-price">$5.00</span>
      </div>
      <div class="item-controls">
        <div class="qty-selector">
          <span class="qty-btn">−</span>
          <span class="qty-value">1</span>
          <span class="qty-btn">+</span>
        </div>
        <span class="item-total">$5.00</span>
        <span class="remove-btn">✕</span>
      </div>
    </div>
  </div>

  <!-- cart summary -->
  <div class="cart-summary">
    <span class="total-label">Total</span>
    <span class="total-price">$16.20</span>
    <span class="checkout-btn">🛒 Checkout</span>
  </div>
  <div style="text-align: right; margin-top: 0.6rem; font-size: 0.8rem; color: #8b7a69; padding-right: 0.3rem;">
    <span>3 items</span>
  </div>
</div>
</body>
