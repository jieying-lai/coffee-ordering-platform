<?php
// Start session to access logged-in user details
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/home.css">
  <title>Cozy Coffee Co. — Home</title>
</head>

<body>
<nav>
  <div class="logo"><a href="index.php">Cozy Coffee Co.</a></div>
  <ul class="nav-links">
    <li><a href="index.php" class="active">Home</a></li>
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
    <li><a href="../blog/index.php">Blog</a></li>
    <li><a href="../contact/index.php">Contact</a></li>
    <li><a href="../cart/index.php">Cart</a></li>

    <!-- DYNAMIC NAVIGATION LINK -->
    <?php if (isset($_SESSION['user_id'])): ?>
      <!-- Logged In State: Show Username & Profile Dropdown -->
      <li>
        <a href="../profile/index.php"><?php echo htmlspecialchars($_SESSION['fullname']); ?> ▾</a>
        <div class="dropdown">
          <a href="../profile/index.php">My Profile</a>
          <a href="../logout.php">Logout</a>
        </div>
      </li>
    <?php else: ?>
      <!-- Guest State: Show Login Link -->
      <li><a href="../login/index.php">Login</a></li>
    <?php endif; ?>

  </ul>
  <button class="hamburger" aria-label="Menu"><span></span><span></span><span></span></button>
</nav>

<section class="hero">
  <div class="eyebrow">Small Batch · Slow Roasted</div>
  <h1>Warm cups, cozy corners.</h1>
  <p>all you need is love, or maybe coffee</p>

  <div class="hero-actions">
    <a href="../menu/index.php" class="cta-btn cta-btn-primary">Order Now</a>
    <a href="#gallery" class="cta-btn cta-btn-outline">Explore More</a>
  </div>
</section>

<section class="gallery-section" id="gallery">
  <h2>A Peek Inside</h2>
  <p class="subtitle">Take a look around before you order.</p>

  <div class="gallery-grid">
    <div class="gallery-card">
      <div class="gallery-photo">
        <img src="../images/1.jpg" alt="Our Roastery Corner">
      </div>
      <div class="photo-info">
        <h3>Our Roastery Corner</h3>
        <p>Where the beans are freshly roasted every morning.</p>
      </div>
    </div>

    <div class="gallery-card">
      <div class="gallery-photo">
        <img src="../images/2.jpg" alt="Fresh Brews Daily">
      </div>
      <div class="photo-info">
        <h3>Fresh Brews Daily</h3>
        <p>Handcrafted drinks made with love by our baristas.</p>
      </div>
    </div>

    <div class="gallery-card">
      <div class="gallery-photo">
        <img src="../images/3.jpg" alt="Cozy Seating Area">
      </div>
      <div class="photo-info">
        <h3>Cozy Seating Area</h3>
        <p>A warm space for your quiet afternoon or catch-ups.</p>
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