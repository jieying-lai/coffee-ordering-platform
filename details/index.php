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

      <div class="details-options" style="margin: 18px 0; text-align: left;">
        <div style="margin-bottom: 12px;">
          <label style="font-weight: 600; font-size: 0.9em; display: block; margin-bottom: 4px;">Temperature Option:</label>
          <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <label class="chip-btn"><input type="radio" name="details_temp" value="Regular Ice" checked> <span>Regular Ice</span></label>
            <label class="chip-btn"><input type="radio" name="details_temp" value="Less Ice"> <span>Less Ice</span></label>
            <label class="chip-btn"><input type="radio" name="details_temp" value="No Ice"> <span>No Ice</span></label>
            <label class="chip-btn"><input type="radio" name="details_temp" value="Warm"> <span>Warm</span></label>
            <label class="chip-btn"><input type="radio" name="details_temp" value="Hot"> <span>Hot</span></label>
          </div>
        </div>

        <div style="margin-bottom: 12px;">
          <label style="font-weight: 600; font-size: 0.9em; display: block; margin-bottom: 4px;">Sweetness Level:</label>
          <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <label class="chip-btn"><input type="radio" name="details_sweet" value="Regular Sugar" checked> <span>Regular</span></label>
            <label class="chip-btn"><input type="radio" name="details_sweet" value="Less Sugar"> <span>Less Sugar</span></label>
            <label class="chip-btn"><input type="radio" name="details_sweet" value="No Sugar"> <span>No Sugar</span></label>
          </div>
        </div>

        <div>
          <label for="detailsRemarks" style="font-weight: 600; font-size: 0.9em; display: block; margin-bottom: 4px;">Special Remarks:</label>
          <input type="text" id="detailsRemarks" placeholder="e.g. Extra hot, oat milk..." style="width: 100%; padding: 8px 12px; border: 1px solid #d4c5b3; border-radius: 8px;">
        </div>
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
    nav.classList.toggle('nav-active');
  });

  // ---- Quantity stepper ----
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

  // ---- Add to cart via AJAX (PHP session) ----
  document.getElementById('addToCartBtn').addEventListener('click', function () {
    const btn = this;
    const itemId = btn.dataset.id;
    const itemName = btn.dataset.name;

    const tempEl = document.querySelector('input[name="details_temp"]:checked');
    const tempVal = tempEl ? tempEl.value : '';

    const sweetEl = document.querySelector('input[name="details_sweet"]:checked');
    const sweetVal = sweetEl ? sweetEl.value : '';

    const remarksVal = document.getElementById('detailsRemarks').value.trim();

    const formData = new FormData();
    formData.append('item_id', itemId);
    formData.append('quantity', qty);
    formData.append('temperature', tempVal);
    formData.append('sweetness', sweetVal);
    formData.append('remarks', remarksVal);
    formData.append('ajax', '1');

    btn.disabled = true;
    btn.textContent = 'Adding...';

    fetch('../cart/add_to_cart.php', {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
      btn.disabled = false;
      btn.textContent = 'Add to Cart 🛒';
      if (data.status === 'success') {
        const msg = document.getElementById('message');
        msg.textContent = `${itemName} added to cart ☕`;
        msg.classList.add('show');
        setTimeout(() => msg.classList.remove('show'), 3000);
      }
    })
    .catch(() => {
      btn.disabled = false;
      btn.textContent = 'Add to Cart 🛒';
    });
  });
</script>

</body>
</html>

