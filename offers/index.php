<?php
session_start();
require_once '../includes/db_connect.php';
$isLoggedIn = isset($_SESSION['user_id']);
$isMember = false;
if ($isLoggedIn) {
    $stmt = $conn->prepare("SELECT is_rewards_member FROM users WHERE id = ?");
    $uid = (int) $_SESSION['user_id'];
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $isMember = $r && (int) $r['is_rewards_member'] === 1;
}

// Featured "member price" drinks & food — pulled live from the menu so
// prices always match what's actually on sale.
// Featured 3 "member price" drinks & 3 food offers — curated highlights
$drinkIds = [43, 35, 58];
$foodIds  = [84, 79, 33];

function fetchFeatured($conn, $ids) {
    if (empty($ids)) return [];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt = $conn->prepare("SELECT item_id, name, description, price, image FROM menu_items WHERE item_id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) { $rows[$row['item_id']] = $row; }
    $stmt->close();
    // Preserve the requested order
    $ordered = [];
    foreach ($ids as $id) { if (isset($rows[$id])) $ordered[] = $rows[$id]; }
    return $ordered;
}

$drinkOffers = fetchFeatured($conn, $drinkIds);
$foodOffers  = fetchFeatured($conn, $foodIds);
$memberDiscountRate = 0.15; // 15% member price, shown for browsing
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/programs.css">
  <title>Cozy Coffee Co. — Offers</title>
</head>

<body>
<?php 
  $activePage = 'offers';
  require_once '../includes/header_nav.php'; 
?>

<section class="program-hero">
  <div class="eyebrow">Cozy Rewards</div>
  <h1>Offers made for members</h1>
  <p class="lede">Fresh discounts on drinks and food, refreshed regularly, plus everyday value from the partners we love.</p>
  <?php if (!$isMember): ?>
    <div class="hero-actions">
      <a href="<?php echo $isLoggedIn ? '../rewards/join.php' : '../register/index.php'; ?>" class="cta-btn cta-btn-primary">Join Cozy Rewards to unlock these prices</a>
    </div>
  <?php endif; ?>
</section>

<!-- ============ DRINK OFFERS ============ -->
<section class="program-section" id="drinks">
  <h2>🌟 Drink Offers</h2>
  <p class="subtitle">Member prices on a rotating selection of specialty and classic coffees this month.</p>

  <div class="offer-grid">
    <?php foreach ($drinkOffers as $item):
      $memberPrice = round($item['price'] * (1 - $memberDiscountRate), 2);
    ?>
    <div class="offer-card" style="display: flex; flex-direction: column; justify-content: space-between;">
      <div>
        <span class="offer-badge">Member Special (15% OFF)</span>
        <div class="offer-photo"><img src="../images/menu/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>"></div>
        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
        <div class="offer-price-row">
          <span class="offer-price-old">RM <?php echo number_format($item['price'], 2); ?></span>
          <span class="offer-price-new">RM <?php echo number_format($memberPrice, 2); ?></span>
        </div>
        <p><?php echo htmlspecialchars(strtok($item['description'], "\r\n")); ?></p>
      </div>

      <button type="button" class="btn btn-orange btn-full open-offer-modal-btn" 
              data-id="<?php echo $item['item_id']; ?>"
              data-name="<?php echo htmlspecialchars($item['name']); ?>"
              data-desc="<?php echo htmlspecialchars($item['description']); ?>"
              data-price="<?php echo $memberPrice; ?>"
              data-image="<?php echo htmlspecialchars($item['image']); ?>"
              style="margin-top: 14px; padding: 10px 14px; font-size: 0.88rem; font-weight: 700;">Customize &amp; Order (RM <?php echo number_format($memberPrice, 2); ?>) 🛒</button>
    </div>
    <?php endforeach; ?>
  </div>

  <p class="offer-note">Member prices automatically applied when ordering from this page! Offers refresh monthly — check back often!</p>
</section>

<!-- ============ FOOD OFFERS ============ -->
<section class="program-section" id="food">
  <h2>🍽️ Food Offers</h2>
  <p class="subtitle">Member prices on mains, sharing plates, and desserts — great for lingering a little longer.</p>

  <div class="offer-grid">
    <?php foreach ($foodOffers as $item):
      $memberPrice = round($item['price'] * (1 - $memberDiscountRate), 2);
    ?>
    <div class="offer-card" style="display: flex; flex-direction: column; justify-content: space-between;">
      <div>
        <span class="offer-badge">Member Special (15% OFF)</span>
        <div class="offer-photo"><img src="../images/menu/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>"></div>
        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
        <div class="offer-price-row">
          <span class="offer-price-old">RM <?php echo number_format($item['price'], 2); ?></span>
          <span class="offer-price-new">RM <?php echo number_format($memberPrice, 2); ?></span>
        </div>
        <p><?php echo htmlspecialchars(strtok($item['description'], "\r\n")); ?></p>
      </div>

      <button type="button" class="btn btn-orange btn-full open-offer-modal-btn" 
              data-id="<?php echo $item['item_id']; ?>"
              data-name="<?php echo htmlspecialchars($item['name']); ?>"
              data-desc="<?php echo htmlspecialchars($item['description']); ?>"
              data-price="<?php echo $memberPrice; ?>"
              data-image="<?php echo htmlspecialchars($item['image']); ?>"
              style="margin-top: 14px; padding: 10px 14px; font-size: 0.88rem; font-weight: 700;">Customize &amp; Order (RM <?php echo number_format($memberPrice, 2); ?>) 🛒</button>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- GUEST / NON-MEMBER LOCK MODAL -->
<div id="memberLockModal" class="modal-overlay">
  <div class="modal-content" style="max-width: 460px; padding: 30px; text-align: center;">
    <button class="modal-close" onclick="document.getElementById('memberLockModal').style.display='none'">&times;</button>
    <div style="font-size: 3rem; margin-bottom: 10px;">🔒</div>
    <h3 style="color: var(--color-primary); font-family: var(--font-heading); margin-bottom: 8px;">Cozy Rewards Member Exclusive</h3>
    <p style="color: #666; font-size: 0.92rem; line-height: 1.6; margin-bottom: 22px;">
      Promotional member prices (15% OFF) are exclusively available to registered Cozy Rewards members. Join for free or log in to unlock member discounts!
    </p>
    <div style="display: flex; gap: 12px; justify-content: center;">
      <a href="../login/index.php" class="btn btn-orange" style="flex: 1; padding: 10px 16px;">Log In</a>
      <a href="../register/index.php" class="btn btn-outline" style="flex: 1; padding: 10px 16px;">Register Free</a>
    </div>
  </div>
</div>

<!-- OFFER CUSTOMIZATION MODAL -->
<div id="offerCustomModal" class="modal-overlay">
  <div class="modal-content" style="max-width: 500px; padding: 25px; max-height: 90vh; overflow-y: auto;">
    <button class="modal-close" onclick="document.getElementById('offerCustomModal').style.display='none'">&times;</button>
    
    <div style="display: flex; gap: 16px; margin-bottom: 16px; align-items: center;">
      <img id="offerModalImg" src="" style="width: 80px; height: 80px; object-fit: cover; border-radius: 12px; border: 1px solid #e0d5c4;">
      <div>
        <h3 id="offerModalTitle" style="color: var(--color-primary); margin-bottom: 4px; font-family: var(--font-heading);"></h3>
        <div style="font-weight: 800; color: var(--color-accent-dark); font-size: 1.2rem;">
          Special Member Price: <span id="offerModalPrice"></span>
        </div>
      </div>
    </div>

    <p id="offerModalDesc" style="color: #666; font-size: 0.88rem; line-height: 1.5; margin-bottom: 16px; background: #faf5ee; padding: 10px 12px; border-radius: 8px;"></p>

    <form id="offerModalForm">
      <input type="hidden" name="item_id" id="offerModalItemId">
      <input type="hidden" name="custom_price" id="offerModalCustomPrice">

      <!-- Temperature Option -->
      <div style="margin-bottom: 14px;">
        <label style="font-weight: 700; font-size: 0.88rem; color: #444; display: block; margin-bottom: 6px;">Temperature Preference:</label>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
          <label style="font-size: 0.85rem; background: #fff; border: 1px solid #d0c4b8; padding: 6px 12px; border-radius: 20px; cursor: pointer;">
            <input type="radio" name="temperature" value="Regular Ice" checked> 🧊 Regular Ice
          </label>
          <label style="font-size: 0.85rem; background: #fff; border: 1px solid #d0c4b8; padding: 6px 12px; border-radius: 20px; cursor: pointer;">
            <input type="radio" name="temperature" value="Less Ice"> ❄️ Less Ice
          </label>
          <label style="font-size: 0.85rem; background: #fff; border: 1px solid #d0c4b8; padding: 6px 12px; border-radius: 20px; cursor: pointer;">
            <input type="radio" name="temperature" value="Warm / Hot"> ☕ Hot
          </label>
        </div>
      </div>

      <!-- Sweetness Option -->
      <div style="margin-bottom: 14px;">
        <label style="font-weight: 700; font-size: 0.88rem; color: #444; display: block; margin-bottom: 6px;">Sweetness Level:</label>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
          <label style="font-size: 0.85rem; background: #fff; border: 1px solid #d0c4b8; padding: 6px 12px; border-radius: 20px; cursor: pointer;">
            <input type="radio" name="sweetness" value="100% Normal" checked> 🍯 100% Normal
          </label>
          <label style="font-size: 0.85rem; background: #fff; border: 1px solid #d0c4b8; padding: 6px 12px; border-radius: 20px; cursor: pointer;">
            <input type="radio" name="sweetness" value="50% Less Sweet"> 🍬 50% Less Sweet
          </label>
          <label style="font-size: 0.85rem; background: #fff; border: 1px solid #d0c4b8; padding: 6px 12px; border-radius: 20px; cursor: pointer;">
            <input type="radio" name="sweetness" value="0% Sugar Free"> 🚫 No Sugar
          </label>
        </div>
      </div>

      <!-- Special Remarks -->
      <div style="margin-bottom: 16px;">
        <label style="font-weight: 700; font-size: 0.88rem; color: #444; display: block; margin-bottom: 6px;">Special Instructions / Remarks:</label>
        <textarea name="remarks" rows="2" placeholder="e.g. Extra hot, oat milk substitute..." style="width: 100%; padding: 8px 12px; border-radius: 8px; border: 1px solid #d0c4b8; box-sizing: border-box; font-size: 0.9rem;"></textarea>
      </div>

      <!-- Quantity & Submit -->
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <div style="display: flex; align-items: center; gap: 8px;">
          <label style="font-weight: 700; font-size: 0.88rem; color: #444;">Qty:</label>
          <input type="number" name="quantity" value="1" min="1" max="20" style="width: 50px; text-align: center; padding: 6px; border-radius: 6px; border: 1px solid #d0c4b8;">
        </div>
        <button type="submit" class="btn btn-orange" style="padding: 10px 20px; font-weight: 700;">Add Offer to Cart 🛒</button>
      </div>
    </form>
  </div>
</div>

<script>
  const isMemberUser = <?php echo ($isLoggedIn && $isMember) ? 'true' : 'false'; ?>;

  document.querySelectorAll('.open-offer-modal-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      if (!isMemberUser) {
        document.getElementById('memberLockModal').style.display = 'flex';
        return;
      }

      const id = this.dataset.id;
      const name = this.dataset.name;
      const desc = this.dataset.desc;
      const price = parseFloat(this.dataset.price).toFixed(2);
      const img = this.dataset.image;

      document.getElementById('offerModalItemId').value = id;
      document.getElementById('offerModalCustomPrice').value = price;
      document.getElementById('offerModalTitle').textContent = name;
      document.getElementById('offerModalPrice').textContent = 'RM ' + price;
      document.getElementById('offerModalDesc').textContent = desc;
      document.getElementById('offerModalImg').src = '../images/menu/' + img;

      document.getElementById('offerCustomModal').style.display = 'flex';
    });
  });

  document.getElementById('offerModalForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('ajax', '1');

    fetch('../cart/add_to_cart.php', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          const badge = document.getElementById('globalCartCount') || document.querySelector('.nav-cart-badge');
          if (badge) {
            badge.textContent = data.total_quantity || data.cart_count;
            badge.style.display = 'inline-block';
          }
          document.getElementById('offerCustomModal').style.display = 'none';
          alert('🎉 Promotional offer item added to cart!');
        }
      });
  });
</script>

</body>
</html>
