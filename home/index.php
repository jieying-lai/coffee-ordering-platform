<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="../style/mystyle.css">
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
        <a href="../menu/index.php?cat=hot">Hot Coffee</a>
        <a href="../menu/index.php?cat=cold">Cold Coffee</a>
        <a href="../menu/index.php?cat=pastries">Pastries</a>
      </div>
    </li>
    <li><a href="../contact/index.php">Contact</a></li>
    <li><a href="../cart/index.php">Cart</a></li>
    <li><a href="../login/index.php">Login</a></li>
  </ul>
  <button class="hamburger" aria-label="Menu"><span></span><span></span><span></span></button>
</nav>

<section class="hero">
  <div class="eyebrow">Small Batch · Slow Roasted</div>
  <h1>Warm cups, cozy corners.</h1>
  <p>Freshly roasted coffee and house-made pastries, ready for pickup or delivery.</p>

  <div class="hero-actions">
    <a href="../menu/index.php" class="cta-btn cta-btn-primary">Order Now</a>
    <a href="#gallery" class="cta-btn cta-btn-outline">Explore More</a>
  </div>
</section>

<section class="gallery-section" id="gallery">
  <h2>A Peek Inside</h2>
  <p class="subtitle">Take a look around before you order.</p>

  <div class="gallery-grid">
    <div class="gallery-photo">Our Roastery Corner</div>
    <div class="gallery-photo">Fresh Brews Daily</div>
    <div class="gallery-photo">Cozy Seating Area</div>
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