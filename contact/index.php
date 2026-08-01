<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="../style/mystyle.css">
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
        <a href="../menu/index.php?cat=specialty#specialty">Specialty</a>
        <a href="../menu/index.php?cat=classic#classic">Classic Coffee</a>
        <a href="../menu/index.php?cat=noncoffein#noncoffein">Non-Coffein</a>
        <a href="../menu/index.php?cat=smoothies#smoothies">Smoothies &amp; Sodas</a>
        <a href="../menu/index.php?cat=mains#mains">Main Dishes</a>
        <a href="../menu/index.php?cat=desserts#desserts">Desserts</a>
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
  <div class="about-content">
    <div class="eyebrow">Our Story</div>
    <h2>About Cozy Coffee Co.</h2>
    <p>
      Cozy Coffee Co. started as a small neighbourhood roastery with one simple goal:
      serve honest, carefully brewed coffee in a space that feels like home. Every bean
      is roasted in small batches, every pastry is baked fresh each morning, and every
      cup is made to order. Whether you're stopping by for a quiet moment or ordering
      ahead for pickup, we're glad you're here.
    </p>
  </div>
</section>

<!-- ============ CONTACT US ============ -->
<section class="contact-section">

  <div class="contact-info">
    <h3>Get in Touch</h3>
    <ul>
      <li><strong>Address</strong>7, Bandar Sungai Long, 43000 Kajang, Selangor</li>
      <li><strong>Phone</strong> +60 12-345 6789</li>
      <li><strong>Email</strong> cozycoffee@gmail.com</li>
      <li><strong>Hours</strong> Mon–Sun, 11:00 AM – 5:00 PM</li>
    </ul>

    <div class="contact-map">
      <!-- TODO: replace with your actual Google Maps embed link -->
      <iframe
        src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d1346.744641350054!2d101.79352459067356!3d3.0400653684064136!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2smy!4v1783592577677!5m2!1sen!2smy"
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade">
      </iframe>
    </div>

    <div class="social-links">
      <a href="#">Instagram</a>
      <a href="#">Facebook</a>
      <a href="#">TikTok</a>
    </div>
  </div>

  <div class="contact-form">
    <h3>Send Us a Message</h3>
    <!-- TODO: point this form to your PHP handler, e.g. action="contact_submit.php" -->
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