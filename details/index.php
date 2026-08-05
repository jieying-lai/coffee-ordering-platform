<?php
session_start();
require_once '../includes/db_connect.php';

$itemId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare('SELECT * FROM menu_items WHERE item_id = ?');
$stmt->bind_param('i', $itemId);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();
$stmt->close();

// If the item doesn't exist, bounce back to the menu instead of showing a blank page
if (!$item) {
    header('Location: ../menu/index.php');
    exit;
}

$isNew = false;
if (!empty($item['created_at'])) {
    $isNew = strtotime($item['created_at']) >= strtotime('-7 days');
}

$rawImage = $item['image'];
$imgSrc = (stripos($rawImage, 'http://') === 0 || stripos($rawImage, 'https://') === 0)
    ? $rawImage
    : '../images/menu/' . $rawImage;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/details.css">
  <title>Cozy Coffee Co. — <?php echo htmlspecialchars($item['name']); ?></title>
</head>

<body>
<nav>
  <div class="logo"><a href="../home/index.php">Cozy Coffee Co.</a></div>
  <ul class="nav-links">
    <li><a href="../home/index.php">Home</a></li>
    <li><a href="../menu/index.php" class="active">Menu ▾</a></li>
    <li><a href="../blog/index.php">Blog</a></li>
    <li><a href="../contact/index.php">Contact</a></li>
    <li><a href="../cart/index.php">Cart</a></li>
    <?php if (isset($_SESSION['user_id'])): ?>
      <li><a href="../profile/index.php"><?php echo htmlspecialchars($_SESSION['fullname']); ?></a></li>
    <?php else: ?>
      <li><a href="../login/index.php">Login</a></li>
    <?php endif; ?>
  </ul>
  <button class="hamburger" aria-label="Menu"><span></span><span></span><span></span></button>
</nav>

<div class="details-overlay">
  <div class="details-card">
    <a href="javascript:history.back()" class="details-close" aria-label="Close">&times;</a>

    <div class="details-image">
      <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars(strtoupper($item['name'])); ?>">
    </div>

    <div class="details-body">
      <h1>
        <?php echo htmlspecialchars($item['name']); ?>
        <?php if ($isNew): ?>
          <span class="badge-new">NEW</span>
        <?php endif; ?>
      </h1>

      <p class="details-description"><?php echo htmlspecialchars($item['description']); ?></p>

      <div class="details-price" id="detailsPrice" data-unit-price="<?php echo (float)$item['price']; ?>">
        RM <?php echo number_format($item['price'], 2); ?>
      </div>

      <div class="details-qty">
        <button type="button" class="qty-btn" id="qtyMinus">&minus;</button>
        <span id="qtyValue">1</span>
        <button type="button" class="qty-btn" id="qtyPlus">+</button>
      </div>

      <button class="btn btn-orange btn-full details-add-btn" id="addToCartBtn"
        data-id="<?php echo (int)$item['item_id']; ?>"
        data-name="<?php echo htmlspecialchars($item['name']); ?>"
        data-price="<?php echo (float)$item['price']; ?>"
        data-image="<?php echo htmlspecialchars($imgSrc); ?>">
        Add to Cart 🛒
      </button>

      <a href="../menu/index.php" class="details-back">← Back to Menu</a>
    </div>
  </div>
</div>

<div class="message" id="message"></div>

<script>
  document.querySelector('.hamburger').addEventListener('click', () => {
    const nav = document.querySelector('.nav-links');
    nav.style.display = nav.style.display === 'flex' ? 'none' : 'flex';
  });

  // ---- Quantity stepper (also updates the displayed price = unit price × qty) ----
  let qty = 1;
  const qtyValue = document.getElementById('qtyValue');
  const priceEl = document.getElementById('detailsPrice');
  const unitPrice = parseFloat(priceEl.dataset.unitPrice);

  function updatePriceDisplay() {
    const total = (unitPrice * qty).toFixed(2);
    priceEl.textContent = `RM ${total}`;
  }

  document.getElementById('qtyMinus').addEventListener('click', () => {
    qty = Math.max(1, qty - 1);
    qtyValue.textContent = qty;
    updatePriceDisplay();
  });
  document.getElementById('qtyPlus').addEventListener('click', () => {
    qty += 1;
    qtyValue.textContent = qty;
    updatePriceDisplay();
  });

  // ---- Add to cart ----
  // NOTE: This stores the cart in localStorage under the key "cart" as an
  // array of { id, name, price, image, qty }. If your cart page (cart/index.php)
  // already reads cart data from somewhere else (a different localStorage key,
  // a session, or a database table), tell me how it's stored and I'll update
  // this to match instead of introducing a second cart mechanism.
  document.getElementById('addToCartBtn').addEventListener('click', function () {
    const btn = this;
    const item = {
      id: btn.dataset.id,
      name: btn.dataset.name,
      price: parseFloat(btn.dataset.price),
      image: btn.dataset.image
    };

    let cart = JSON.parse(localStorage.getItem('cart') || '[]');
    const existing = cart.find(c => c.id === item.id);
    if (existing) {
      existing.qty += qty;
    } else {
      cart.push({ ...item, qty });
    }
    localStorage.setItem('cart', JSON.stringify(cart));

    const msg = document.getElementById('message');
    msg.textContent = `${item.name} added to cart ✓`;
    msg.classList.add('show');
    setTimeout(() => msg.classList.remove('show'), 2000);
  });
</script>

</body>
</html>
