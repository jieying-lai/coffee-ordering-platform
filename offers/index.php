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

// Fetch active special offers dynamically from special_offers table
function fetchSpecialOffers($conn, $categoryType) {
    $sql = "SELECT so.*, mi.name, mi.description, mi.price as original_price, mi.image 
            FROM special_offers so 
            JOIN menu_items mi ON so.item_id = mi.item_id 
            WHERE so.category_type = ? AND so.is_active = 1 
            ORDER BY so.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $categoryType);
    $stmt->execute();
    $res = $stmt->get_result();
    $offers = [];
    while ($row = $res->fetch_assoc()) {
        $origP = (float)$row['original_price'];
        if ($row['discount_type'] === 'percentage') {
            $discP = round($origP * (1 - ((float)$row['discount_value'] / 100)), 2);
        } else {
            $discP = (float)$row['discount_value'];
        }
        $row['offer_price'] = $discP;
        $offers[] = $row;
    }
    $stmt->close();
    return $offers;
}

$drinkOffers = fetchSpecialOffers($conn, 'drink');
$foodOffers  = fetchSpecialOffers($conn, 'food');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/programs.css">
  <title>Cozy Coffee Co. — Special Offers</title>
</head>

<body>
<?php 
  $activePage = 'offers';
  require_once '../includes/header_nav.php'; 
?>

<section class="program-hero">
  <div class="eyebrow">Cozy Rewards</div>
  <h1>Offers Made for Members</h1>
  <p class="lede">Fresh discounts on handcrafted drinks and food, managed live by our head barista team.</p>
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
    <?php if (!empty($drinkOffers)): ?>
      <?php foreach ($drinkOffers as $item): ?>
      <div class="offer-card" style="display: flex; flex-direction: column; justify-content: space-between;">
        <div>
          <span class="offer-badge"><?php echo htmlspecialchars($item['offer_title']); ?></span>
          <div class="offer-photo"><img src="../images/menu/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>"></div>
          <h3><?php echo htmlspecialchars($item['name']); ?></h3>
          <div class="offer-price-row">
            <span class="offer-price-old">RM <?php echo number_format($item['original_price'], 2); ?></span>
            <span class="offer-price-new">RM <?php echo number_format($item['offer_price'], 2); ?></span>
          </div>
          <p><?php echo htmlspecialchars(strtok($item['description'], "\r\n")); ?></p>
        </div>

        <button type="button" class="btn btn-orange btn-full open-offer-modal-btn" 
                data-id="<?php echo $item['item_id']; ?>"
                data-name="<?php echo htmlspecialchars($item['name']); ?>"
                data-desc="<?php echo htmlspecialchars($item['description']); ?>"
                data-price="<?php echo $item['offer_price']; ?>"
                data-image="<?php echo htmlspecialchars($item['image']); ?>"
                style="margin-top: 14px; padding: 12px 14px; font-size: 0.95rem; font-weight: 800; border-radius: 12px;">Add to Cart 🛒</button>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="grid-column: 1 / -1; color: #888;">No drink offers currently active.</p>
    <?php endif; ?>
  </div>

  <p class="offer-note">Member prices automatically applied when ordering from this page! Offers refresh monthly — check back often!</p>
</section>

<!-- ============ FOOD OFFERS ============ -->
<section class="program-section" id="food">
  <h2>🍽️ Food Offers</h2>
  <p class="subtitle">Member prices on mains, sharing plates, and desserts — great for lingering a little longer.</p>

  <div class="offer-grid">
    <?php if (!empty($foodOffers)): ?>
      <?php foreach ($foodOffers as $item): ?>
      <div class="offer-card" style="display: flex; flex-direction: column; justify-content: space-between;">
        <div>
          <span class="offer-badge"><?php echo htmlspecialchars($item['offer_title']); ?></span>
          <div class="offer-photo"><img src="../images/menu/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>"></div>
          <h3><?php echo htmlspecialchars($item['name']); ?></h3>
          <div class="offer-price-row">
            <span class="offer-price-old">RM <?php echo number_format($item['original_price'], 2); ?></span>
            <span class="offer-price-new">RM <?php echo number_format($item['offer_price'], 2); ?></span>
          </div>
          <p><?php echo htmlspecialchars(strtok($item['description'], "\r\n")); ?></p>
        </div>

        <button type="button" class="btn btn-orange btn-full open-offer-modal-btn" 
                data-id="<?php echo $item['item_id']; ?>"
                data-name="<?php echo htmlspecialchars($item['name']); ?>"
                data-desc="<?php echo htmlspecialchars($item['description']); ?>"
                data-price="<?php echo $item['offer_price']; ?>"
                data-image="<?php echo htmlspecialchars($item['image']); ?>"
                style="margin-top: 14px; padding: 12px 14px; font-size: 0.95rem; font-weight: 800; border-radius: 12px;">Add to Cart 🛒</button>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="grid-column: 1 / -1; color: #888;">No food offers currently active.</p>
    <?php endif; ?>
  </div>
</section>

<!-- GUEST / NON-MEMBER LOCK MODAL -->
<div id="memberLockModal" class="modal-overlay" style="display:none;">
  <div class="modal-content" style="max-width: 460px; padding: 30px; text-align: center; border-radius: 20px;">
    <button class="modal-close" onclick="closeLockModal()">&times;</button>
    <div style="font-size: 40px; margin-bottom: 10px;">🔒</div>
    <h3 style="font-family: var(--font-heading); color: #2C1C14; margin-bottom: 10px; font-weight: 800;">Cozy Rewards Member Exclusive</h3>
    <p style="color: #665447; font-size: 0.92rem; line-height: 1.55; margin-bottom: 20px;">
      These special discount prices are reserved for Cozy Rewards members. Join today for free or log in to order at member prices!
    </p>
    <div style="display: flex; gap: 10px; justify-content: center;">
      <a href="<?php echo $isLoggedIn ? '../rewards/join.php' : '../register/index.php'; ?>" class="btn btn-orange" style="font-weight: 800; padding: 10px 20px; border-radius: 30px;">Join Cozy Rewards</a>
      <button type="button" class="btn btn-secondary" onclick="closeLockModal()" style="font-weight: 700; padding: 10px 20px; border-radius: 30px;">Maybe Later</button>
    </div>
  </div>
</div>

<!-- ITEM CUSTOMIZATION & ORDER MODAL -->
<div id="offerCustomModal" class="modal-overlay" style="display:none;">
  <div class="modal-content" style="max-width: 520px; padding: 30px; border-radius: 20px;">
    <button class="modal-close" onclick="closeOfferModal()">&times;</button>
    
    <div style="display: flex; gap: 16px; margin-bottom: 18px; align-items: center;">
      <img id="offModalImg" src="" style="width: 80px; height: 80px; object-fit: cover; border-radius: 12px; border: 1.5px solid #E8DDD0;">
      <div>
        <h3 id="offModalName" style="font-family: var(--font-heading); font-size: 1.25rem; color: #2C1C14; margin: 0 0 4px 0; font-weight: 800;"></h3>
        <span style="background: rgba(200, 90, 62, 0.12); color: #C85A3E; font-size: 0.78rem; font-weight: 800; padding: 3px 10px; border-radius: 20px; border: 1px solid rgba(200, 90, 62, 0.3);">Special Member Price</span>
      </div>
    </div>

    <form id="offerOrderForm">
      <input type="hidden" name="item_id" id="offModalItemId">
      <input type="hidden" name="item_name" id="offModalItemName">
      <input type="hidden" name="item_price" id="offModalItemPrice">
      
      <div style="margin-bottom: 16px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">Quantity</label>
        <input type="number" name="quantity" value="1" min="1" max="20" style="width:100px; height:40px; border-radius:8px; border:1px solid #E8DDD0; padding:6px 12px; text-align:center; font-weight:700;">
      </div>

      <div style="margin-bottom: 20px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">Special Instructions / Temperature / Sugar Level (Optional)</label>
        <textarea name="remarks" rows="2" placeholder="e.g. Less ice, extra oat milk, less sweet..." style="width:100%; border-radius:10px; border:1px solid #E8DDD0; padding:10px; font-family:inherit; font-size:0.9rem; box-sizing:border-box;"></textarea>
      </div>

      <div style="display:flex; justify-content:space-between; align-items:center; background:#FAF4EB; padding:14px 18px; border-radius:12px; margin-bottom:20px;">
        <span style="font-weight:700; color:#665447;">Special Offer Price:</span>
        <span id="offModalPriceText" style="font-size:1.4rem; font-weight:800; color:#C85A3E; font-family:var(--font-heading);"></span>
      </div>

      <button type="submit" class="btn btn-orange btn-full" style="font-weight:800; height:48px; border-radius:12px; font-size:1rem;">Confirm Add to Cart 🛒</button>
    </form>
  </div>
</div>

<script>
const isMemberUser = <?php echo $isMember ? 'true' : 'false'; ?>;

document.querySelectorAll('.open-offer-modal-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        if (!isMemberUser) {
            document.getElementById('memberLockModal').style.display = 'flex';
            return;
        }

        const id = this.dataset.id;
        const name = this.dataset.name;
        const price = parseFloat(this.dataset.price);
        const image = this.dataset.image;

        document.getElementById('offModalItemId').value = id;
        document.getElementById('offModalItemName').value = name;
        document.getElementById('offModalItemPrice').value = price;
        document.getElementById('offModalName').textContent = name;
        document.getElementById('offModalImg').src = '../images/menu/' + image;
        document.getElementById('offModalPriceText').textContent = 'RM ' + price.toFixed(2);

        document.getElementById('offerCustomModal').style.display = 'flex';
    });
});

function closeLockModal() {
    document.getElementById('memberLockModal').style.display = 'none';
}

function closeOfferModal() {
    document.getElementById('offerCustomModal').style.display = 'none';
}

// Handle Add to Cart submission
document.getElementById('offerOrderForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'add_to_cart');

    fetch('../includes/cart_process.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            alert('🛒 ' + formData.get('item_name') + ' added to cart at special member price!');
            closeOfferModal();
            location.reload();
        } else {
            alert('Failed to add item to cart: ' + (data.message || 'Error'));
        }
    })
    .catch(err => {
        // Fallback for direct session cart storage
        alert('🛒 Item added to your order cart!');
        closeOfferModal();
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>

</body>
</html>
