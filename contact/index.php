<?php
require_once '../includes/db_connect.php';

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
	<title>Cozy Coffee Co. — Contact Us</title>
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
    <li><a href="index.php" class="active">Contact</a></li>
    <li><a href="../cart/index.php">Cart</a></li>
    <li><a href="../login/index.php">Login</a></li>
  </ul>
  <button class="hamburger" aria-label="Menu"><span></span><span></span><span></span></button>
</nav>

<!-- ============ ABOUT / INTRO ============ -->
<section class="about-section">
  <div class="eyebrow"><?php echo htmlspecialchars($about['eyebrow']); ?></div>
  <h2><?php echo htmlspecialchars($about['heading']); ?></h2>
  <p><?php echo nl2br(htmlspecialchars($about['body_text'])); ?></p>
</section>

<!-- ============ CONTACT US ============ -->
<section class="contact-section">

  <div class="contact-info">
    <h3>Get in Touch</h3>
    <ul>
      <li><strong>Address</strong> <?php echo htmlspecialchars($info['address']); ?></li>
      <li><strong>Phone</strong> <?php echo htmlspecialchars($info['phone']); ?></li>
      <li><strong>Email</strong> <?php echo htmlspecialchars($info['email']); ?></li>
      <li><strong>Hours</strong> <?php echo htmlspecialchars($info['hours']); ?></li>
    </ul>

    <div class="contact-map">
      <iframe
        src="<?php echo htmlspecialchars($info['map_embed_url']); ?>"
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade">
      </iframe>
    </div>

    <div class="social-links">
      <a href="<?php echo htmlspecialchars($info['instagram_url']); ?>">Instagram</a>
      <a href="<?php echo htmlspecialchars($info['facebook_url']); ?>">Facebook</a>
      <a href="<?php echo htmlspecialchars($info['tiktok_url']); ?>">TikTok</a>
    </div>
  </div>

  <div class="contact-form">
    <h3>Send Us a Message</h3>
    <!-- TODO: point this form to a PHP handler that inserts into contact_messages -->
    <form action="#" method="POST">
      <div class="form-group">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" required>
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>
      </div>
      <div class="form-group">
        <label for="message">Message</label>
        <textarea id="message" name="message" required></textarea>
      </div>
      <button type="submit" class="submit-btn">Send Message</button>
    </form>
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