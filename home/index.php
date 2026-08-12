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
<?php 
  $activePage = 'home';
  require_once '../includes/header_nav.php'; 
?>

<section class="hero">
  <div class="eyebrow">Small Batch · Slow Roasted</div>
  <h1>Warm cups, cozy corners.</h1>
  <p>all you need is love, or maybe coffee</p>

  <div class="hero-actions">
    <a href="../menu/index.php" class="cta-btn cta-btn-primary">Order Now</a>
    <button type="button" class="cta-btn cta-btn-outline" id="exploreQuizBtn">☕ Coffee Match Quiz</button>
  </div>
</section>

<!-- COFFEE MATCH QUIZ MODAL -->
<div id="quizModal" class="modal-overlay">
  <div class="modal-content" style="max-width: 520px; padding: 30px; text-align: center;">
    <button class="modal-close" id="closeQuizModal">&times;</button>
    <div style="font-size: 2.8rem; margin-bottom: 10px;">☕</div>
    <h2 style="font-family: var(--font-heading); color: var(--color-primary); margin-bottom: 8px;">Find Your Perfect Brew</h2>
    <p style="color: #666; font-size: 0.95rem; margin-bottom: 20px;">Tell us what you're craving and we'll match you with your ideal drink!</p>
    
    <div id="quizStep1">
      <h3 style="font-size: 1rem; margin-bottom: 12px; color: #4a3b32;">1. What temperature do you prefer right now?</h3>
      <div style="display: flex; gap: 12px; justify-content: center; margin-bottom: 20px;">
        <button type="button" class="btn btn-outline quiz-opt" data-temp="iced" style="flex:1;">❄️ Refreshing Iced</button>
        <button type="button" class="btn btn-outline quiz-opt" data-temp="hot" style="flex:1;">🔥 Cozy Hot Brew</button>
      </div>
    </div>

    <div id="quizStep2" style="display: none;">
      <h3 style="font-size: 1rem; margin-bottom: 12px; color: #4a3b32;">2. How sweet or strong do you like it?</h3>
      <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px;">
        <button type="button" class="btn btn-outline quiz-final" data-rec="Dirty Latte" data-desc="Espresso layered over cold fresh milk — bold and silky." data-link="../menu/index.php">☕ Bold &amp; Strong (Dirty Latte)</button>
        <button type="button" class="btn btn-outline quiz-final" data-rec="Caramel Macchiato" data-desc="Rich espresso with creamy vanilla and sweet caramel drizzle." data-link="../menu/index.php">🍯 Sweet &amp; Creamy (Caramel Macchiato)</button>
        <button type="button" class="btn btn-outline quiz-final" data-rec="Matcha Green Tea Latte" data-desc="Ceremonial Uji matcha with steamed milk." data-link="../menu/index.php">🍃 Smooth Non-Coffee (Matcha Latte)</button>
      </div>
    </div>

    <div id="quizResult" style="display: none; background: #faf5ee; padding: 20px; border-radius: 12px; border: 1px solid #e0d5c4;">
      <h3 id="recTitle" style="color: var(--color-accent-dark); font-size: 1.3rem;"></h3>
      <p id="recDesc" style="color: #666; font-size: 0.9rem; margin: 8px 0 16px;"></p>
      <a href="../menu/index.php" class="cta-btn cta-btn-primary" style="display: inline-block;">Order This Now 🛒</a>
    </div>
  </div>
</div>

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
  const quizModal = document.getElementById('quizModal');
  const quizBtn = document.getElementById('exploreQuizBtn');
  const closeQuizBtn = document.getElementById('closeQuizModal');
  const quizStep1 = document.getElementById('quizStep1');
  const quizStep2 = document.getElementById('quizStep2');
  const quizResult = document.getElementById('quizResult');

  quizBtn?.addEventListener('click', () => {
    quizStep1.style.display = 'block';
    quizStep2.style.display = 'none';
    quizResult.style.display = 'none';
    quizModal.style.display = 'flex';
  });

  closeQuizBtn?.addEventListener('click', () => { quizModal.style.display = 'none'; });

  document.querySelectorAll('.quiz-opt').forEach(btn => {
    btn.addEventListener('click', () => {
      quizStep1.style.display = 'none';
      quizStep2.style.display = 'block';
    });
  });

  document.querySelectorAll('.quiz-final').forEach(btn => {
    btn.addEventListener('click', function() {
      quizStep2.style.display = 'none';
      document.getElementById('recTitle').textContent = '🌟 Recommended: ' + this.dataset.rec;
      document.getElementById('recDesc').textContent = this.dataset.desc;
      quizResult.style.display = 'block';
    });
  });
</script>

</body>
</html>
