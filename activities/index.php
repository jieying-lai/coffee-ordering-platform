<?php
session_start();
require_once '../includes/db_connect.php';
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/programs.css">
  <title>Cozy Coffee Co. — Activities</title>
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
      <a href="index.php" class="active">Activities ▾</a>
      <div class="dropdown">
        <a href="index.php#workshops">Coffee Workshops</a>
        <a href="index.php#giveback">Cozy Give-Back</a>
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
  <h1>Learn, taste, and give back with us</h1>
  <p class="lede">From hands-on workshops to small sustainable habits — members get first priority on everything happening at Cozy Coffee Co.</p>
</section>

<!-- ============ COFFEE WORKSHOPS ============ -->
<section class="program-section" id="workshops">
  <h2>🧑‍🍳 Coffee Workshops</h2>
  <p class="subtitle">We share our brewing know-how so you can bring a little Cozy Coffee Co. home.</p>

  <div class="activity-card">
    <div class="activity-photo"><img src="../images/1.jpg" alt="Latte art workshop"></div>
    <div class="activity-copy">
      <div class="eyebrow">Monthly · In-store</div>
      <h3>Latte Art &amp; Home Brewing Basics</h3>
      <p>Get hands-on with our baristas to learn milk-steaming, pour techniques, and how to pull a balanced shot at home.</p>
      <ul>
        <li>Small groups, hosted right at the bar</li>
        <li>All equipment and beans provided</li>
        <li>Cozy Rewards members get priority booking &amp; a member-only price</li>
      </ul>
      <a href="../contact/index.php" class="cta-btn cta-btn-outline">Ask about upcoming dates</a>
    </div>
  </div>

  <div class="activity-card reverse">
    <div class="activity-photo"><img src="../images/menu/1785945826_03e1df6e.webp" alt="Cupping session"></div>
    <div class="activity-copy">
      <div class="eyebrow">Quarterly · In-store</div>
      <h3>Cupping &amp; Tasting Sessions</h3>
      <p>Sample new single-origin beans side by side and learn to pick out tasting notes like the pros, guided by our roastery partners.</p>
      <ul>
        <li>New beans featured every season</li>
        <li>Take home a bag of your favorite blend</li>
        <li>Free for Cozy Rewards members</li>
      </ul>
    </div>
  </div>
</section>

<!-- ============ COZY GIVE-BACK ============ -->
<section class="program-section" id="giveback">
  <h2>🌱 Cozy Give-Back</h2>
  <p class="subtitle">Small, everyday habits that add up — our take on giving things a second life.</p>

  <div class="activity-card">
    <div class="activity-photo"><img src="../images/3.jpg" alt="Bring your own cup"></div>
    <div class="activity-copy">
      <div class="eyebrow">Every visit</div>
      <h3>Bring Your Own Cup</h3>
      <p>Skip the disposable cup — bring your own tumbler or mug and earn 5 bonus Cozy Points on that order, every single time.</p>
    </div>
  </div>

  <div class="activity-card reverse">
    <div class="activity-photo"><img src="../images/about-bg.jpg" alt="Coffee grounds compost drop-off"></div>
    <div class="activity-copy">
      <div class="eyebrow">Weekly · Saturdays</div>
      <h3>Coffee Grounds Give-Back</h3>
      <p>Instead of going to waste, our used coffee grounds are bagged up free for members to take home for composting or as a natural garden fertiliser — just ask at the counter.</p>
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
