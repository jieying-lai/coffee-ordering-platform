<?php
// ============================================
// COZY REWARDS — HUB PAGE
// Logged in + member  -> show digital member card & points
// Logged in + guest   -> show "Join Cozy Rewards" CTA
// Not logged in       -> prompt to log in first
// ============================================
session_start();
require_once '../includes/db_connect.php';

$isLoggedIn = isset($_SESSION['user_id']);
$member = null;

if ($isLoggedIn) {
    $userId = (int) $_SESSION['user_id'];
    $stmt = $conn->prepare(
        "SELECT fullname, is_rewards_member, rewards_points, rewards_member_no, rewards_joined_at
         FROM users WHERE id = ?"
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $member = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/rewards.css">
  <title>Cozy Coffee Co. — Cozy Rewards</title>
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
    <li><a href="../blog/index.php">Blog</a></li>
    <li><a href="../contact/index.php">Contact</a></li>
    <li><a href="../cart/index.php">Cart</a></li>

    <?php if ($isLoggedIn): ?>
      <li>
        <a href="../profile/index.php"><?php echo htmlspecialchars($_SESSION['fullname']); ?> ▾</a>
        <div class="dropdown">
          <a href="../profile/index.php">My Profile</a>
          <a href="index.php" class="active">Cozy Rewards</a>
          <a href="../logout.php">Logout</a>
        </div>
      </li>
    <?php else: ?>
      <li><a href="../login/index.php">Login</a></li>
    <?php endif; ?>
  </ul>
  <button class="hamburger" aria-label="Menu"><span></span><span></span><span></span></button>
</nav>

<div class="rewards-hub">

  <?php if (!$isLoggedIn): ?>

    <div class="rewards-cta-box">
      <div class="eyebrow" style="margin-bottom: 10px;">Cozy Rewards</div>
      <h2>Log in to join Cozy Rewards</h2>
      <p>Create a free Cozy Coffee Co. account (or log in) to activate your Cozy Rewards membership and start earning points on every order.</p>
      <a href="../login/index.php" class="cta-btn cta-btn-primary">Login</a>
      <a href="../register/index.php" class="cta-btn cta-btn-outline" style="margin-left: 10px;">Create an account</a>
    </div>

  <?php elseif ($member && (int) $member['is_rewards_member'] === 1): ?>

    <div class="member-card">
      <div class="card-eyebrow">Cozy Rewards Member</div>
      <h2><?php echo htmlspecialchars($member['fullname']); ?></h2>
      <div class="member-card-row">
        <div>
          <div class="points-label">Member No.</div>
          <div class="member-no"><?php echo htmlspecialchars($member['rewards_member_no']); ?></div>
        </div>
        <div style="text-align: right;">
          <div class="points-value"><?php echo (int) $member['rewards_points']; ?></div>
          <div class="points-label">Cozy Points</div>
        </div>
      </div>
    </div>

    <div class="rewards-cta-box">
      <h2>You're all set, <?php echo htmlspecialchars(explode(' ', $member['fullname'])[0]); ?> ☕</h2>
      <p>
        Member since <?php echo $member['rewards_joined_at'] ? date('d M Y', strtotime($member['rewards_joined_at'])) : '—'; ?>.
        Every RM1 you spend earns 1 Cozy Point — with every 100 points you can redeem RM1 off your next order.
      </p>
      <div class="rewards-perks-mini">
        <div class="perk"><span class="perk-icon">🎂</span>Free drink in your birthday month</div>
        <div class="perk"><span class="perk-icon">🏷️</span>Member-only monthly discounts</div>
        <div class="perk"><span class="perk-icon">🧑‍🍳</span>Priority spots at workshops</div>
      </div>
      <a href="../benefits/index.php" class="cta-btn cta-btn-outline">See all Benefits</a>
      <a href="../menu/index.php" class="cta-btn cta-btn-primary" style="margin-left: 10px;">Order now</a>
    </div>

  <?php else: ?>

    <div class="rewards-cta-box">
      <div class="eyebrow" style="margin-bottom: 10px;">It's free to join</div>
      <h2>Activate your Cozy Rewards card</h2>
      <p>Hi <?php echo htmlspecialchars($member['fullname'] ?? ''); ?>! You're logged in but haven't activated Cozy Rewards yet — it only takes a minute with your mobile number.</p>
      <div class="rewards-perks-mini">
        <div class="perk"><span class="perk-icon">⭐</span>1 point for every RM1 spent</div>
        <div class="perk"><span class="perk-icon">🎁</span>50 bonus points when you join</div>
        <div class="perk"><span class="perk-icon">🎂</span>Free birthday-month drink</div>
      </div>
      <a href="join.php" class="cta-btn cta-btn-primary">Activate Cozy Rewards</a>
    </div>

  <?php endif; ?>

</div>

<script>
  document.querySelector('.hamburger').addEventListener('click', () => {
    const nav = document.querySelector('.nav-links');
    nav.style.display = nav.style.display === 'flex' ? 'none' : 'flex';
  });
</script>

</body>
</html>
