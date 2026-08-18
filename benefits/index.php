<?php
session_start();
require_once '../includes/db_connect.php';
$isLoggedIn = isset($_SESSION['user_id']);
$isMember = false;
$userBirthday = null;
$hasBirthdaySet = false;
$birthdayMonthName = '';
$birthdayFormatted = '';
$daysUntilBirthday = 0;

if ($isLoggedIn) {
    $stmt = $conn->prepare("SELECT is_rewards_member, birthday FROM users WHERE id = ?");
    $uid = (int) $_SESSION['user_id'];
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $isMember = $r && (int) $r['is_rewards_member'] === 1;
    if ($r && !empty($r['birthday']) && $r['birthday'] !== '0000-00-00') {
        $userBirthday = $r['birthday'];
        $hasBirthdaySet = true;
        $btime = strtotime($userBirthday);
        $birthdayMonthName = date('F', $btime);
        $birthdayFormatted = date('F jS', $btime);
        
        $today = new DateTime();
        $nextBday = new DateTime(date('Y') . '-' . date('m-d', $btime));
        if ($nextBday < $today) {
            $nextBday->modify('+1 year');
        }
        $diff = $today->diff($nextBday);
        $daysUntilBirthday = $diff->days;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/programs.css">
  <title>Cozy Coffee Co. — Cozy Rewards Benefits</title>
</head>

<body>
<?php 
  $activePage = 'benefits';
  require_once '../includes/header_nav.php'; 
?>

<!-- PROGRAM HERO SHOWCASE -->
<section class="program-hero">
  <div class="eyebrow">✦ Cozy Rewards Member Benefits ✦</div>
  <h1>As a member, <span class="highlight-text">you always get more</span></h1>
  <p class="lede">Every visit adds up. Join Cozy Rewards for free to earn points on every order, unlock member-only prices, and enjoy treats made just for you.</p>
  <div class="hero-actions">
    <?php if ($isMember): ?>
      <a href="../rewards/index.php" class="cta-btn cta-btn-primary">💳 View My Cozy Rewards Card</a>
    <?php elseif ($isLoggedIn): ?>
      <a href="../rewards/join.php" class="cta-btn cta-btn-primary">✦ Activate Cozy Rewards</a>
    <?php else: ?>
      <a href="../register/index.php" class="cta-btn cta-btn-primary">✨ Join Cozy Rewards — It's Free</a>
    <?php endif; ?>
  </div>
</section>

<!-- CONSOLIDATED HIGH-VALUE BENEFIT CARDS -->
<section class="program-section">
  <div class="benefit-grid">

    <!-- 1. Earn Cozy Points -->
    <div class="benefit-card">
      <div class="benefit-photo"><img src="../images/points.jpg" alt="Earn Cozy Points"></div>
      <div class="benefit-info">
        <div class="benefit-icon">⭐</div>
        <h3>Earn Cozy Points</h3>
        <p>Earn 1 Cozy Point for every RM1 spent on drinks, food, or merchandise in-store or online. Every 100 points redeems RM1 off your next order.</p>
        <a href="../rewards/index.php" class="benefit-link">Check points &amp; rewards &gt;</a>
      </div>
    </div>

    <!-- 2. Birthday Treat (Interactive Popup Modal) -->
    <div class="benefit-card">
      <div class="benefit-photo"><img src="../images/menu/1785947636_40c4e216.jpeg" alt="Birthday treat"></div>
      <div class="benefit-info">
        <div class="benefit-icon">🎂</div>
        <h3>Birthday Treat &amp; Month Bonus</h3>
        <p>Enjoy a complimentary dessert slice or 3x Cozy Points on your first purchase during your birthday month! Our special gift to you.</p>
        <button type="button" class="benefit-popup-trigger" onclick="openModal('birthdayModal')">View my birthday treat &gt;</button>
      </div>
    </div>

    <!-- 3. Member Welcome Coffee -->
    <div class="benefit-card">
      <div class="benefit-photo"><img src="../images/day.jpg" alt="Welcome drink"></div>
      <div class="benefit-info">
        <div class="benefit-icon">☕</div>
        <h3>Monthly Welcome Perk</h3>
        <p>Show your digital Cozy Rewards card on your first visit each month for a complimentary hot coffee or tea on us.</p>
        <a href="../rewards/index.php" class="benefit-link">View digital card &gt;</a>
      </div>
    </div>

    <!-- 4. Workshops & Events (Interactive Walk-in Popup Modal) -->
    <div class="benefit-card">
      <div class="benefit-photo"><img src="../images/workshop.jpg" alt="Workshops and events"></div>
      <div class="benefit-info">
        <div class="benefit-icon">🧑‍🍳</div>
        <h3>Coffee Workshops &amp; Events</h3>
        <p>Join our hands-on latte art classes, pour-over demos, and cupping sessions. Walk-in friendly for all Cozy Rewards members!</p>
        <button type="button" class="benefit-popup-trigger" onclick="openModal('eventsModal')">See this month's events &gt;</button>
      </div>
    </div>

    <!-- 5. Partner Perks (Interactive Partner Popup Modal) -->
    <div class="benefit-card">
      <div class="benefit-photo"><img src="../images/fri.jpg" alt="Partner perks"></div>
      <div class="benefit-info">
        <div class="benefit-icon">🤝</div>
        <h3>Extra Perks from Friends</h3>
        <p>Enjoy exclusive member discounts with our partner local bakeries, bookstores, and roasteries. Simply show your Cozy Rewards card.</p>
        <button type="button" class="benefit-popup-trigger" onclick="openModal('partnerModal')">View partner discounts &gt;</button>
      </div>
    </div>

    <!-- 6. Give Back (Interactive Eco Popup Modal) -->
    <div class="benefit-card">
      <div class="benefit-photo"><img src="../images/giveback.jpg" alt="Give back"></div>
      <div class="benefit-info">
        <div class="benefit-icon">🌱</div>
        <h3>Give Back &amp; Eco Bonus</h3>
        <p>Bring your own cup for +10 bonus Cozy Points and take home free nutrient-rich coffee grounds for your home garden.</p>
        <button type="button" class="benefit-popup-trigger" onclick="openModal('giveBackModal')">Learn eco benefits &gt;</button>
      </div>
    </div>

  </div>
</section>

<!-- ============================================ -->
<!-- 1. BIRTHDAY TREAT MODAL -->
<!-- ============================================ -->
<div id="birthdayModal" class="program-modal-overlay" onclick="closeModalOnOutsideClick(event, 'birthdayModal')">
  <div class="program-modal-card">
    <button type="button" class="program-modal-close" onclick="closeModal('birthdayModal')">&times;</button>
    <div class="modal-header-badge">🎂 BIRTHDAY REWARDS</div>
    
    <?php if (!$isLoggedIn): ?>
      <h3>Celebrate Your Birthday with Us!</h3>
      <p>Log in or create a free Cozy Rewards account to receive a complimentary dessert slice or 3x Cozy Points during your birthday month!</p>
      <div class="modal-action-row">
        <a href="../login/index.php" class="cta-btn cta-btn-primary" style="font-size: 0.9rem; padding: 10px 24px;">Log In Now</a>
        <a href="../register/index.php" class="cta-btn" style="font-size: 0.9rem; padding: 10px 24px; border: 1px solid #E5D9CC; color: #665447;">Register Free</a>
      </div>
    <?php elseif ($hasBirthdaySet): ?>
      <h3>Your Birthday Celebration 🎈</h3>
      <p>We are excited to celebrate your special month with you!</p>
      <div class="modal-detail-box">
        <h4>🎉 Your Birthday: <?php echo htmlspecialchars($birthdayFormatted); ?></h4>
        <p style="margin: 4px 0 0 0; color: #C85A3E; font-weight: 700;">
          <?php if ($daysUntilBirthday == 0): ?>
            🌟 Happy Birthday today! Visit us to claim your free treat!
          <?php else: ?>
            ⏳ <?php echo $daysUntilBirthday; ?> days until your birthday! (Birthday month: <?php echo htmlspecialchars($birthdayMonthName); ?>)
          <?php endif; ?>
        </p>
      </div>
      <p style="font-size: 0.88rem; color: #7A685A;">
        🎁 <strong>Birthday Month Perks:</strong> Show your digital Cozy Rewards card at checkout anytime during <strong><?php echo htmlspecialchars($birthdayMonthName); ?></strong> to enjoy a free dessert slice or 3x Cozy Points on your first purchase!
      </p>
      <div class="modal-action-row">
        <a href="../rewards/index.php" class="cta-btn cta-btn-primary" style="font-size: 0.9rem; padding: 10px 24px;">💳 View Digital Card</a>
        <button type="button" class="cta-btn" onclick="closeModal('birthdayModal')" style="font-size: 0.9rem; padding: 10px 24px; border: 1px solid #E5D9CC; color: #665447;">Close</button>
      </div>
    <?php else: ?>
      <h3>You Haven't Set Your Birthday Yet! 🎂</h3>
      <p>Don't miss out on your free birthday dessert or 3x Cozy Points bonus! Update your profile birthdate so we know when to send your birthday reward.</p>
      <div class="modal-detail-box" style="border-color: #E0A96D; background: #FFFBF5;">
        <h4 style="color: #A8472F;">⚠️ Birthday Not Set</h4>
        <p style="margin: 0; color: #665447;">Set your birthdate once in your profile. It only takes 10 seconds!</p>
      </div>
      <div class="modal-action-row">
        <a href="../profile/index.php" class="cta-btn cta-btn-primary" style="font-size: 0.9rem; padding: 10px 24px;">✏️ Set Birthday Now</a>
        <button type="button" class="cta-btn" onclick="closeModal('birthdayModal')" style="font-size: 0.9rem; padding: 10px 24px; border: 1px solid #E5D9CC; color: #665447;">Maybe Later</button>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- ============================================ -->
<!-- 2. WORKSHOPS & EVENTS MODAL (Walk-in Friendly) -->
<!-- ============================================ -->
<div id="eventsModal" class="program-modal-overlay" onclick="closeModalOnOutsideClick(event, 'eventsModal')">
  <div class="program-modal-card">
    <button type="button" class="program-modal-close" onclick="closeModal('eventsModal')">&times;</button>
    <div class="modal-header-badge">🧑‍🍳 MONTHLY COMMUNITY EVENTS</div>
    <h3>Coffee Workshops &amp; Gatherings</h3>
    <p>We love sharing our coffee passion with fellow enthusiasts! All events are walk-in friendly for Cozy Rewards members.</p>
    
    <div class="modal-detail-box">
      <h4>☕ Latte Art Masterclass</h4>
      <p style="margin: 0 0 6px 0; color: #8C6D58; font-weight: 700; font-size: 0.85rem;">📅 Every Saturday · 3:00 PM – 4:30 PM</p>
      <p style="margin: 0;">Learn milk steaming texture techniques and rosette pouring with our head baristas.</p>
    </div>

    <div class="modal-detail-box">
      <h4>🫘 Single-Origin Cupping Session</h4>
      <p style="margin: 0 0 6px 0; color: #8C6D58; font-weight: 700; font-size: 0.85rem;">📅 First Sunday of the Month · 11:00 AM</p>
      <p style="margin: 0;">Taste seasonal micro-lots from Ethiopia, Colombia, and Guatemala side-by-side.</p>
    </div>

    <div class="modal-detail-box">
      <h4>💧 Home Pour-Over Drip Workshop</h4>
      <p style="margin: 0 0 6px 0; color: #8C6D58; font-weight: 700; font-size: 0.85rem;">📅 Third Wednesday · 6:00 PM</p>
      <p style="margin: 0;">Master grind sizes, water ratio, and V60 extraction for brewing coffee at home.</p>
    </div>

    <div style="background: rgba(200, 90, 62, 0.08); border-radius: 14px; padding: 14px 16px; border: 1px solid rgba(200, 90, 62, 0.2); margin-bottom: 20px;">
      <p style="margin: 0; color: #C85A3E; font-weight: 700; font-size: 0.88rem;">
        📍 <strong>Walk-in Friendly:</strong> No advance booking needed! Simply walk in and show your Cozy Rewards digital card to participate.
      </p>
    </div>

    <div class="modal-action-row">
      <button type="button" class="cta-btn cta-btn-primary" onclick="closeModal('eventsModal')" style="font-size: 0.9rem; padding: 10px 28px; width: 100%;">Got It ☕</button>
    </div>
  </div>
</div>

<!-- ============================================ -->
<!-- 3. PARTNER PERKS MODAL -->
<!-- ============================================ -->
<div id="partnerModal" class="program-modal-overlay" onclick="closeModalOnOutsideClick(event, 'partnerModal')">
  <div class="program-modal-card">
    <button type="button" class="program-modal-close" onclick="closeModal('partnerModal')">&times;</button>
    <div class="modal-header-badge">🤝 NEIGHBOURHOOD PARTNER PERKS</div>
    <h3>Discounts from Our Friends</h3>
    <p>Show your digital Cozy Rewards card at our local partner outlets to unlock exclusive member discounts!</p>
    
    <div class="modal-detail-box">
      <h4>🥐 Artisan Sourdough Bakery</h4>
      <p style="margin: 0 0 6px 0; color: #C85A3E; font-weight: 700; font-size: 0.85rem;">🏷️ 15% OFF All Fresh Pastries &amp; Breads</p>
      <p style="margin: 0;">Enjoy daily baked butter croissants and sourdough loaves next door.</p>
    </div>

    <div class="modal-detail-box">
      <h4>🫘 Beans &amp; Co. Micro-Roastery</h4>
      <p style="margin: 0 0 6px 0; color: #C85A3E; font-weight: 700; font-size: 0.85rem;">🏷️ Free 50g Sample Beans with RM50 Purchase</p>
      <p style="margin: 0;">Specialty roasted coffee bean bags &amp; drip bags for home brewing.</p>
    </div>

    <div class="modal-detail-box">
      <h4>📚 The Corner Bookstore &amp; Press</h4>
      <p style="margin: 0 0 6px 0; color: #C85A3E; font-weight: 700; font-size: 0.85rem;">🏷️ 10% OFF All Books &amp; Magazines</p>
      <p style="margin: 0;">Pair your coffee with curated independent literature and art books.</p>
    </div>

    <div class="modal-action-row">
      <button type="button" class="cta-btn cta-btn-primary" onclick="closeModal('partnerModal')" style="font-size: 0.9rem; padding: 10px 28px; width: 100%;">Awesome ✨</button>
    </div>
  </div>
</div>

<!-- ============================================ -->
<!-- 4. ECO GIVE BACK MODAL -->
<!-- ============================================ -->
<div id="giveBackModal" class="program-modal-overlay" onclick="closeModalOnOutsideClick(event, 'giveBackModal')">
  <div class="program-modal-card">
    <button type="button" class="program-modal-close" onclick="closeModal('giveBackModal')">&times;</button>
    <div class="modal-header-badge">🌱 ECO GIVE BACK PROGRAM</div>
    <h3>Sip Sustainably with Us</h3>
    <p>Small daily habits create a greener community. Join our eco initiatives every time you visit!</p>
    
    <div class="modal-detail-box">
      <h4>🥤 Bring Your Own Cup Bonus</h4>
      <p style="margin: 0 0 6px 0; color: #059669; font-weight: 700; font-size: 0.85rem;">⭐ +10 Extra Cozy Points Per Order</p>
      <p style="margin: 0;">Bring any reusable tumbler or cup for your takeaway coffee to earn bonus points.</p>
    </div>

    <div class="modal-detail-box">
      <h4>☕ Free Coffee Grounds for Garden Compost</h4>
      <p style="margin: 0 0 6px 0; color: #059669; font-weight: 700; font-size: 0.85rem;">🌱 100% Free Organic Soil Fertilizer</p>
      <p style="margin: 0;">Grab complimentary bags of used espresso coffee grounds near our exit for your plants!</p>
    </div>

    <div class="modal-detail-box">
      <h4>📦 Biodegradable Packaging</h4>
      <p style="margin: 0;">All takeaway cups, lids, and straws are 100% plant-based and commercially compostable.</p>
    </div>

    <div class="modal-action-row">
      <button type="button" class="cta-btn cta-btn-primary" onclick="closeModal('giveBackModal')" style="font-size: 0.9rem; padding: 10px 28px; width: 100%;">Support Sustainability 🌱</button>
    </div>
  </div>
</div>

<script>
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function closeModalOnOutsideClick(event, modalId) {
    if (event.target.id === modalId) {
        closeModal(modalId);
    }
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        ['birthdayModal', 'eventsModal', 'partnerModal', 'giveBackModal'].forEach(mId => {
            closeModal(mId);
        });
    }
});
</script>

</body>
</html>
