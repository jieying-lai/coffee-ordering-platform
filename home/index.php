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
  <div class="eyebrow">✦ Small Batch · Slow Roasted ✦</div>
  <h1><span class="gold-highlight">Warm cups,</span> cozy corners.</h1>
  <p>All you need is love, or maybe freshly brewed coffee</p>

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
  <p class="subtitle">Click any area below to view our gallery photos &amp; details.</p>

  <div class="gallery-grid">
    <div class="gallery-card home-gallery-trigger" data-title="Our Roastery Corner" data-desc="Where our single-origin arabica beans are micro-roasted daily to perfection by master roasters." data-images="../images/1.jpg|../images/menu/hero1.jpg|../images/hero2.jpg" style="cursor: pointer;">
      <div class="gallery-photo">
        <img src="../images/1.jpg" alt="Our Roastery Corner">
      </div>
      <div class="photo-info">
        <h3>Our Roastery Corner 🔍</h3>
        <p>Where the beans are freshly roasted every morning. Click to view photos.</p>
      </div>
    </div>

    <div class="gallery-card home-gallery-trigger" data-title="Fresh Brews Daily" data-desc="Handcrafted espresso, pour-overs, and specialty beverages prepared live by our passionate baristas." data-images="../images/5.jpg|../images/4.jpg|../images/2.jpg" style="cursor: pointer;">
      <div class="gallery-photo">
        <img src="../images/5.jpg" alt="Fresh Brews Daily">
      </div>
      <div class="photo-info">
        <h3>Fresh Brews Daily 🔍</h3>
        <p>Handcrafted drinks made with love by our baristas. Click to view photos.</p>
      </div>
    </div>

    <div class="gallery-card home-gallery-trigger" data-title="Cozy Seating Area" data-desc="Unwind in our warm lounge with plush seating, ambient warm lighting, and fast WiFi for work or study." data-images="../images/area2.jpg|../images/3.jpg|../images/area1.jpg" style="cursor: pointer;">
      <div class="gallery-photo">
        <img src="../images/area2.jpg" alt="Cozy Seating Area">
      </div>
      <div class="photo-info">
        <h3>Cozy Seating Area 🔍</h3>
        <p>A warm space for your quiet afternoon or catch-ups. Click to view photos.</p>
      </div>
    </div>
  </div>
</section>

<!-- HOME GALLERY LIGHTBOX MODAL -->
<div id="homeGalleryModal" class="modal-overlay" onclick="if(event.target === this) closeHomeGallery();">
  <div class="modal-content" style="max-width: 650px; padding: 25px; text-align: center; position: relative;">
    <button class="modal-close" onclick="closeHomeGallery()">&times;</button>
    <h3 id="homeGalleryTitle" style="color: var(--color-primary); margin-bottom: 6px; font-family: var(--font-heading); font-size: 1.4rem;"></h3>
    <p id="homeGalleryDesc" style="color: #666; font-size: 0.9rem; margin-bottom: 16px;"></p>

    <!-- PHOTO CONTAINER WITH NAVIGATION ARROWS -->
    <div style="position: relative; width: 100%; height: 340px; border-radius: 14px; overflow: hidden; background: #222; box-shadow: 0 8px 20px rgba(0,0,0,0.15);">
      <img id="homeGalleryImg" src="" style="width: 100%; height: 100%; object-fit: cover; transition: opacity 0.3s ease;">
      
      <button type="button" onclick="prevHomePhoto()" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.55); color: #fff; border: none; font-size: 1.6rem; width: 42px; height: 42px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); transition: background 0.2s ease;">❮</button>
      
      <button type="button" onclick="nextHomePhoto()" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.55); color: #fff; border: none; font-size: 1.6rem; width: 42px; height: 42px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); transition: background 0.2s ease;">❯</button>
    </div>

    <div style="margin-top: 14px; display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; color: #777;">
      <span id="homeGalleryCounter">Photo 1 of 3</span>
    </div>
  </div>
</div>

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

  // GALLERY LIGHTBOX SCRIPT
  let homeGalleryImages = [];
  let homeGalleryIdx = 0;

  document.querySelectorAll('.home-gallery-trigger').forEach(card => {
    card.addEventListener('click', function() {
      const title = this.dataset.title;
      const desc = this.dataset.desc;
      homeGalleryImages = this.dataset.images.split('|');
      homeGalleryIdx = 0;

      document.getElementById('homeGalleryTitle').textContent = title;
      document.getElementById('homeGalleryDesc').textContent = desc;
      updateHomeGalleryPhoto();

      document.getElementById('homeGalleryModal').style.display = 'flex';
    });
  });

  function updateHomeGalleryPhoto() {
    const imgEl = document.getElementById('homeGalleryImg');
    imgEl.style.opacity = '0.4';
    setTimeout(() => {
      imgEl.src = homeGalleryImages[homeGalleryIdx];
      imgEl.style.opacity = '1';
    }, 150);
    document.getElementById('homeGalleryCounter').textContent = `Photo ${homeGalleryIdx + 1} of ${homeGalleryImages.length}`;
  }

  function prevHomePhoto() {
    homeGalleryIdx = (homeGalleryIdx - 1 + homeGalleryImages.length) % homeGalleryImages.length;
    updateHomeGalleryPhoto();
  }

  function nextHomePhoto() {
    homeGalleryIdx = (homeGalleryIdx + 1) % homeGalleryImages.length;
    updateHomeGalleryPhoto();
  }

  function closeHomeGallery() {
    document.getElementById('homeGalleryModal').style.display = 'none';
  }
</script>

</body>
</html>
