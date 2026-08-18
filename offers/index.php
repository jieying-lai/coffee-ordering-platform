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

      <form class="add-offer-form" style="margin-top: 14px;">
        <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
        <input type="hidden" name="custom_price" value="<?php echo $memberPrice; ?>">
        <button type="submit" class="btn btn-orange btn-full" style="padding: 8px 14px; font-size: 0.85rem; font-weight: 700;">Order Offer (RM <?php echo number_format($memberPrice, 2); ?>) 🛒</button>
      </form>
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

      <form class="add-offer-form" style="margin-top: 14px;">
        <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
        <input type="hidden" name="custom_price" value="<?php echo $memberPrice; ?>">
        <button type="submit" class="btn btn-orange btn-full" style="padding: 8px 14px; font-size: 0.85rem; font-weight: 700;">Order Offer (RM <?php echo number_format($memberPrice, 2); ?>) 🛒</button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ============ PARTNER PROMOTIONS ============ -->
<section class="program-section" id="partners">
  <h2>🤝 Partner Promotions</h2>
  <p class="subtitle">Better everyday value from the local businesses we love, just for showing your Cozy Rewards card.</p>

  <div class="partner-list">
    <div class="partner-item">
      <div class="partner-emoji">🫘</div>
      <div>
        <h4>Kajang Roastery Co-op</h4>
        <p>15% off single-origin bean bags for members, plus a free cupping session on your first visit.</p>
      </div>
    </div>
    <div class="partner-item">
      <div class="partner-emoji">📚</div>
      <div>
        <h4>Pages &amp; Pour Bookstore</h4>
        <p>Show your Cozy Rewards card for RM5 off any purchase over RM30 — perfect for a slow-reading afternoon.</p>
      </div>
    </div>
    <div class="partner-item">
      <div class="partner-emoji">🥐</div>
      <div>
        <h4>Sungai Long Sourdough Bakery</h4>
        <p>Buy 1 free 1 on day-old bakes every Sunday for Cozy Rewards members.</p>
      </div>
    </div>
    <div class="partner-item">
      <div class="partner-emoji">🧘</div>
      <div>
        <h4>Cozy Yoga Studio</h4>
        <p>10% off drop-in classes, and bring your reusable Cozy Coffee Co. cup for a free post-class drink.</p>
      </div>
    </div>
  </div>
</section>

<script>
  document.querySelector('.hamburger')?.addEventListener('click', () => {
    const nav = document.querySelector('.nav-links');
    nav.style.display = nav.style.display === 'flex' ? 'none' : 'flex';
  });

  document.querySelectorAll('.add-offer-form').forEach(form => {
    form.addEventListener('submit', function(e) {
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
            alert('🎉 Offer item added to cart at special member price!');
          }
        });
    });
  });
</script>

</body>
</html>
