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
    <li><a href="index.php" class="active">Benefits</a></li>
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
    <li><a href="../blog/index.php">Blog</a></li>
    <li><a href="../contact/index.php">Contact</a></li>
    <li><a href="../cart/index.php">Cart</a></li>

    <?php if ($isLoggedIn): ?>
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

<section class="program-hero">
  <div class="eyebrow">Cozy Rewards</div>
  <h1>As a member, you always get more</h1>
  <p class="lede">Every visit adds up. Join Cozy Rewards for free and enjoy points on every order, member-only prices, and treats made just for you.</p>
  <div class="hero-actions">
    <?php if ($isMember): ?>
      <a href="../rewards/index.php" class="cta-btn cta-btn-primary">View my Cozy Rewards card</a>
    <?php elseif ($isLoggedIn): ?>
      <a href="../rewards/join.php" class="cta-btn cta-btn-primary">Activate Cozy Rewards</a>
    <?php else: ?>
      <a href="../register/index.php" class="cta-btn cta-btn-primary">Join Cozy Rewards — it's free</a>
    <?php endif; ?>
  </div>
</section>

<section class="program-section">
  <div class="benefit-grid">

    <div class="benefit-card">
      <div class="benefit-photo"><img src="../images/menu/1785944785_4362788e.jpeg" alt="Earn Cozy Points"></div>
      <div class="benefit-info">
        <div class="benefit-icon">⭐</div>
        <h3>Earn Cozy Points</h3>
        <p>Earn 1 Cozy Point for every RM1 spent on drinks, food, or merchandise — in-store or online. Every 100 points can be redeemed for RM1 off your next order.</p>
        <a href="../rewards/index.php" class="benefit-link">Check my points &gt;</a>
      </div>
    </div>

    <div class="benefit-card">
      <div class="benefit-photo"><img src="../images/menu/1785943018_2e6489c6.jpg" alt="Member discounts"></div>
      <div class="benefit-info">
        <div class="benefit-icon">🏷️</div>
        <h3>Member-only discounts</h3>
        <p>Enjoy monthly refreshed member prices on selected drinks and dishes across the menu — look out for the Cozy Rewards tag next to eligible items.</p>
        <a href="../offers/index.php#drinks" class="benefit-link">See this month's offers &gt;</a>
      </div>
    </div>

    <div class="benefit-card">
      <div class="benefit-photo"><img src="../images/menu/1785947636_40c4e216.jpeg" alt="Birthday treat"></div>
      <div class="benefit-info">
        <div class="benefit-icon">🎂</div>
        <h3>Birthday treat</h3>
        <p>Members enjoy a free slice of dessert or 3x Cozy Points on their first order during their birthday month. Our way of saying happy birthday!</p>
      </div>
    </div>

    <div class="benefit-card">
      <div class="benefit-photo"><img src="../images/menu/1785944926_2818b37d.jpeg" alt="Welcome drink"></div>
      <div class="benefit-info">
        <div class="benefit-icon">☕</div>
        <h3>A warm welcome, every visit</h3>
        <p>We like to treat our members like family — show your digital Cozy Rewards card on your first visit each month for a complimentary hot coffee or tea.</p>
      </div>
    </div>

    <div class="benefit-card">
      <div class="benefit-photo"><img src="../images/menu/1785947072_8b5fb528.jpg" alt="Delicious savings"></div>
      <div class="benefit-info">
        <div class="benefit-icon">🍽️</div>
        <h3>Delicious savings</h3>
        <p>Get member prices on our all-day mains, sandwiches, and sharing plates — perfect for lingering a little longer over lunch.</p>
        <a href="../offers/index.php#food" class="benefit-link">Browse food offers &gt;</a>
      </div>
    </div>

    <div class="benefit-card">
      <div class="benefit-photo"><img src="../images/2.jpg" alt="Get inspired"></div>
      <div class="benefit-info">
        <div class="benefit-icon">📬</div>
        <h3>Sip first, always</h3>
        <p>Get exclusive emails with new menu drops, secret-menu recipes, and early access before anyone else. Keep your details up to date so you never miss out.</p>
        <a href="../profile/index.php" class="benefit-link">Update my details &gt;</a>
      </div>
    </div>

    <div class="benefit-card">
      <div class="benefit-photo"><img src="../images/1.jpg" alt="Workshops and events"></div>
      <div class="benefit-info">
        <div class="benefit-icon">🧑‍🍳</div>
        <h3>Workshops &amp; events</h3>
        <p>We share our brewing know-how with hands-on latte art classes and cupping sessions. Members get first priority when new workshop slots open.</p>
        <a href="../activities/index.php#workshops" class="benefit-link">See what's happening &gt;</a>
      </div>
    </div>

    <div class="benefit-card">
      <div class="benefit-photo"><img src="../images/menu/1785945576_c6ef0025.webp" alt="Partner perks"></div>
      <div class="benefit-info">
        <div class="benefit-icon">🤝</div>
        <h3>Extra perks from our friends</h3>
        <p>Better everyday value from the local roasters, bakeries, and bookstores we love — as simple as showing your Cozy Rewards card.</p>
        <a href="../offers/index.php#partners" class="benefit-link">Check them out &gt;</a>
      </div>
    </div>

    <div class="benefit-card">
      <div class="benefit-photo"><img src="../images/3.jpg" alt="Give back"></div>
      <div class="benefit-info">
        <div class="benefit-icon">🌱</div>
        <h3>Give back with us</h3>
        <p>Bring your own cup for a points bonus, and drop off used coffee grounds for our community compost programme — small habits, real impact.</p>
        <a href="../activities/index.php#giveback" class="benefit-link">Learn more &gt;</a>
      </div>
    </div>

  </div>
</section>

<script>
  document.querySelector('.hamburger').addEventListener('click', () => {
    const nav = document.querySelector('.nav-links');
    nav.style.display = nav.style.display === 'flex' ? 'none' : 'flex';
  });
</script>

</body>
</html>
