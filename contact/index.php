<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php'; // sets $isLoggedIn, $currentUserId, $currentUsername

$about = $conn->query('SELECT * FROM about_us WHERE id = 1')->fetch_assoc();
$info  = $conn->query('SELECT * FROM contact_info WHERE id = 1')->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style/mystyle.css">
    <link rel="stylesheet" href="../style/contact.css">
    <title>Cozy Coffee Co. — About & Contact</title>
</head>

<body>
<nav>
  <div class="logo"><a href="../home/index.php">Cozy Coffee Co.</a></div>
  <ul class="nav-links">
    <li><a href="../home/index.php">Home</a></li>
    <li>
      <a href="../menu/index.php">Menu ▾</a>
      <div class="dropdown">
        <a href="../menu/index.php#specialty">Specialty</a>
        <a href="../menu/index.php#classic">Classic Coffee</a>
        <a href="../menu/index.php#noncoffein">Non-Coffein</a>
        <a href="../menu/index.php#smoothies">Smoothies &amp; Sodas</a>
        <a href="../menu/index.php#mains">Main Dishes</a>
        <a href="../menu/index.php#desserts">Desserts</a>
      </div>
    </li>
    <li><a href="../blog/index.php">Blog</a></li>
    <li><a href="index.php" class="active">Contact</a></li>
    <li><a href="../cart/index.php">Cart</a></li>
    <?php if ($isLoggedIn): ?>
      <li><a href="../logout.php">Logout (<?php echo htmlspecialchars($currentUsername); ?>)</a></li>
    <?php else: ?>
      <li><a href="../login/index.php">Login</a></li>
    <?php endif; ?>
  </ul>
  <button class="hamburger" aria-label="Menu"><span></span><span></span><span></span></button>
</nav>

<!-- ============ ABOUT / INTRO ============ -->
<section class="about-section">
  <div class="eyebrow"><?php echo htmlspecialchars($about['eyebrow'] ?? 'Our Story'); ?></div>
  <h2><?php echo htmlspecialchars($about['heading'] ?? 'Crafting Cozy Moments Daily'); ?></h2>
  <p><?php echo nl2br(htmlspecialchars($about['body_text'] ?? 'Welcome to Cozy Coffee Co.')); ?></p>
</section>

<!-- ============ CONTACT INFO & LOCATION ============ -->
<section class="contact-section" style="display: block; max-width: 900px; margin: 0 auto;">
  <div class="contact-info" style="width: 100%;">
    <h3>Get in Touch & Visit Us</h3>
    <ul>
      <li><strong>Address:</strong> <?php echo htmlspecialchars($info['address'] ?? ''); ?></li>
      <li><strong>Phone:</strong> <?php echo htmlspecialchars($info['phone'] ?? ''); ?></li>
      <li><strong>Email:</strong> <?php echo htmlspecialchars($info['email'] ?? ''); ?></li>
      <li><strong>Hours:</strong> <?php echo htmlspecialchars($info['hours'] ?? ''); ?></li>
    </ul>

    <?php if (!empty($info['map_embed_url'])): ?>
      <div class="contact-map" style="margin-top: 20px;">
        <iframe
          src="<?php echo htmlspecialchars($info['map_embed_url']); ?>"
          loading="lazy"
          style="width: 100%; height: 350px; border: 0; border-radius: 8px;"
          referrerpolicy="no-referrer-when-downgrade">
        </iframe>
      </div>
    <?php endif; ?>

    <div class="social-links" style="margin-top: 20px;">
      <?php if (!empty($info['instagram_url'])): ?><a href="<?php echo htmlspecialchars($info['instagram_url']); ?>">Instagram</a><?php endif; ?>
      <?php if (!empty($info['facebook_url'])): ?><a href="<?php echo htmlspecialchars($info['facebook_url']); ?>">Facebook</a><?php endif; ?>
      <?php if (!empty($info['tiktok_url'])): ?><a href="<?php echo htmlspecialchars($info['tiktok_url']); ?>">TikTok</a><?php endif; ?>
    </div>
  </div>
</section>

<!-- ============ REVIEWS ============ -->
<section class="reviews-section">
  <h2>What Our Customers Say</h2>
  <div class="reviews-grid">
    <div class="review-card">
      <div class="review-stars">★★★★★</div>
      <p class="review-text">"The honey oat latte is my go-to every morning. Consistent, warm, and the staff always remember my order."</p>
      <div class="review-author">— Mei Ling</div>
    </div>
    <div class="review-card">
      <div class="review-stars">★★★★★</div>
      <p class="review-text">"Love that I can order ahead and just walk in to grab it. Saves me so much time before work."</p>
      <div class="review-author">— Arif Hakim</div>
    </div>
    <div class="review-card">
      <div class="review-stars">★★★★☆</div>
      <p class="review-text">"Great coffee and cozy vibe. Would love to see more pastry options in the future!"</p>
      <div class="review-author">— Priya Sharma</div>
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