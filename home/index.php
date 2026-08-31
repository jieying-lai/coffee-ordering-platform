<?php
// Start session to access logged-in user details
session_start();
require_once '../includes/db_connect.php';

// Fetch real menu items for Quiz recommendations
$quizItemIds = [8, 35, 14, 37, 4];
$quizItemsMap = [];
$qRes = $conn->query("SELECT item_id, name, description, price, image FROM menu_items WHERE item_id IN (" . implode(',', $quizItemIds) . ")");
if ($qRes) {
    while ($r = $qRes->fetch_assoc()) {
        $quizItemsMap[$r['item_id']] = $r;
    }
}
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
  <div class="modal-content" style="max-width: 540px; padding: 28px 24px; text-align: left; border-radius: 22px; max-height: 92vh; overflow-y: auto;">
    <button class="modal-close" id="closeQuizModal">&times;</button>
    
    <div style="text-align: center; margin-bottom: 16px;">
      <div style="font-size: 2.4rem; margin-bottom: 4px;">☕</div>
      <h2 style="font-family: var(--font-heading); color: var(--color-primary); margin-bottom: 4px; font-weight: 800; font-size: 1.4rem;">Find Your Perfect Brew</h2>
      <p style="color: #665447; font-size: 0.88rem; margin: 0;">Answer 2 quick questions to unlock your custom coffee recommendation!</p>
    </div>
    
    <!-- STEP 1: TEMPERATURE PREFERENCE -->
    <div id="quizStep1">
      <h3 style="font-size: 0.95rem; margin-bottom: 14px; color: #2C1C14; font-weight: 800; text-align: center;">1. What temperature or vibe fits your mood right now?</h3>
      <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px;">
        <button type="button" class="btn btn-outline quiz-opt" data-temp="Regular Ice" style="padding: 12px 16px; border-radius: 12px; font-weight: 700; text-align: left; justify-content: flex-start;">❄️ Refreshing &amp; Cold Iced Brew</button>
        <button type="button" class="btn btn-outline quiz-opt" data-temp="Hot" style="padding: 12px 16px; border-radius: 12px; font-weight: 700; text-align: left; justify-content: flex-start;">🔥 Cozy &amp; Steamy Hot Cup</button>
        <button type="button" class="btn btn-outline quiz-opt" data-temp="Regular Ice" style="padding: 12px 16px; border-radius: 12px; font-weight: 700; text-align: left; justify-content: flex-start;">🍃 Surprise Me / Non-Coffee Choice</button>
      </div>
    </div>

    <!-- STEP 2: FLAVOR PROFILE (NO COFFEE NAME SPOILERS) -->
    <div id="quizStep2" style="display: none;">
      <h3 style="font-size: 0.95rem; margin-bottom: 14px; color: #2C1C14; font-weight: 800; text-align: center;">2. What flavor profile are you craving?</h3>
      <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px;">
        <button type="button" class="btn btn-outline quiz-final" data-itemid="8" style="padding: 12px 16px; border-radius: 12px; font-weight: 700; text-align: left; justify-content: flex-start;">☕ Bold, Rich &amp; Layered Espresso</button>
        <button type="button" class="btn btn-outline quiz-final" data-itemid="35" style="padding: 12px 16px; border-radius: 12px; font-weight: 700; text-align: left; justify-content: flex-start;">🍯 Sweet, Creamy &amp; Caramel Drizzle</button>
        <button type="button" class="btn btn-outline quiz-final" data-itemid="14" style="padding: 12px 16px; border-radius: 12px; font-weight: 700; text-align: left; justify-content: flex-start;">🍃 Smooth, Earthy &amp; Honeycomb Crunch</button>
        <button type="button" class="btn btn-outline quiz-final" data-itemid="37" style="padding: 12px 16px; border-radius: 12px; font-weight: 700; text-align: left; justify-content: flex-start;">🍫 Chocolatey, Silky &amp; Velvet Smooth</button>
        <button type="button" class="btn btn-outline quiz-final" data-itemid="4" style="padding: 12px 16px; border-radius: 12px; font-weight: 700; text-align: left; justify-content: flex-start;">🥥 Tropical, Light &amp; Creamy Coconut</button>
      </div>
    </div>

    <!-- QUIZ RESULT WITH IMAGE & MENU ORDER PREFERENCE DROPDOWNS -->
    <div id="quizResult" style="display: none; background: #FFFBF5; padding: 20px; border-radius: 16px; border: 1.5px solid #E8DDD0;">
      <div style="text-align: center; margin-bottom: 14px;">
        <span style="background: rgba(200, 90, 62, 0.12); color: #C85A3E; font-size: 0.75rem; font-weight: 800; padding: 4px 12px; border-radius: 20px; letter-spacing: 0.5px; border: 1px solid rgba(200, 90, 62, 0.25);">
          ✨ YOUR PERFECT MATCH REVEALED
        </span>
      </div>

      <!-- ITEM DETAILS CARD -->
      <div style="display: flex; gap: 14px; align-items: center; margin-bottom: 16px; background: #FFFFFF; padding: 14px; border-radius: 14px; border: 1px solid #E5D9CC;">
        <img id="quizRecImg" src="" alt="Recommended Coffee" style="width: 86px; height: 86px; object-fit: cover; border-radius: 12px; border: 1.5px solid #E8DDD0; background: #FAF4EB;">
        <div style="flex: 1;">
          <h3 id="quizRecName" style="font-family: var(--font-heading); color: #2C1C14; font-size: 1.15rem; margin: 0 0 4px 0; font-weight: 800;"></h3>
          <div id="quizRecPrice" style="color: #C85A3E; font-size: 1.15rem; font-weight: 800; font-family: var(--font-heading); margin-bottom: 4px;"></div>
          <p id="quizRecDesc" style="color: #665447; font-size: 0.82rem; margin: 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"></p>
        </div>
      </div>

      <!-- FORM SUBMITS DIRECTLY TO ../cart/add_to_cart.php -->
      <form id="quizOrderForm" action="../cart/add_to_cart.php" method="POST">
        <input type="hidden" id="quizItemId" name="item_id">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px;">
          <div>
            <label style="font-size: 0.78rem; font-weight: 800; color: #2C1C14; display: block; margin-bottom: 4px;">Temperature Option:</label>
            <select name="temperature" id="quizTempSelect" style="width: 100%; height: 42px; border-radius: 10px; border: 1.5px solid #E5D9CC; background: #FFFFFF; padding: 6px 10px; font-size: 0.88rem; font-weight: 700; color: #2C1C14;">
              <option value="Regular Ice">Regular Ice</option>
              <option value="Less Ice">Less Ice</option>
              <option value="No Ice">No Ice</option>
              <option value="Warm">Warm</option>
              <option value="Hot">Hot</option>
            </select>
          </div>

          <div>
            <label style="font-size: 0.78rem; font-weight: 800; color: #2C1C14; display: block; margin-bottom: 4px;">Sweetness Level:</label>
            <select name="sweetness" id="quizSugarSelect" style="width: 100%; height: 42px; border-radius: 10px; border: 1.5px solid #E5D9CC; background: #FFFFFF; padding: 6px 10px; font-size: 0.88rem; font-weight: 700; color: #2C1C14;">
              <option value="Regular Sugar">Regular Sugar</option>
              <option value="Less Sugar">Less Sugar</option>
              <option value="No Sugar">No Sugar</option>
            </select>
          </div>
        </div>

        <div style="margin-bottom: 14px;">
          <label style="font-size: 0.78rem; font-weight: 800; color: #2C1C14; display: block; margin-bottom: 4px;">Special Remarks:</label>
          <textarea name="remarks" id="quizRemarksInput" rows="2" placeholder="e.g. Extra hot, oat milk alternative, etc." style="width: 100%; border-radius: 10px; border: 1.5px solid #E5D9CC; padding: 8px 12px; font-family: inherit; font-size: 0.85rem; box-sizing: border-box;"></textarea>
        </div>

        <div style="display: flex; gap: 14px; align-items: center;">
          <div style="display: flex; align-items: center; border: 1.5px solid #E5D9CC; border-radius: 10px; overflow: hidden; background: #FFF;">
            <button type="button" id="quizQtyMinus" style="width: 36px; height: 42px; background: #FAF4EB; border: none; font-size: 1.1rem; font-weight: bold; cursor: pointer; color: #2C1C14;">-</button>
            <input type="number" id="quizQtyInput" name="quantity" value="1" min="1" max="99" style="width: 44px; height: 42px; border: none; text-align: center; font-weight: 800; font-size: 0.95rem; background: #FFF;" readonly>
            <button type="button" id="quizQtyPlus" style="width: 36px; height: 42px; background: #FAF4EB; border: none; font-size: 1.1rem; font-weight: bold; cursor: pointer; color: #2C1C14;">+</button>
          </div>

          <button type="submit" class="cta-btn cta-btn-primary" style="flex: 1; height: 44px; border-radius: 10px; font-weight: 800; font-size: 0.95rem; justify-content: center; box-shadow: 0 6px 18px rgba(200, 90, 62, 0.3);">Add to Cart</button>
        </div>
      </form>

      <div style="text-align: center; margin-top: 12px;">
        <button type="button" onclick="resetQuiz()" style="background: none; border: none; color: #8A7769; font-weight: 700; font-size: 0.82rem; cursor: pointer; text-decoration: underline;">↺ Retake Quiz</button>
      </div>
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
  // Menu items JSON map passed from PHP database query
  const quizItemsData = <?php echo json_encode($quizItemsMap); ?>;

  const quizModal = document.getElementById('quizModal');
  const quizBtn = document.getElementById('exploreQuizBtn');
  const closeQuizBtn = document.getElementById('closeQuizModal');
  const quizStep1 = document.getElementById('quizStep1');
  const quizStep2 = document.getElementById('quizStep2');
  const quizResult = document.getElementById('quizResult');

  let selectedTempPreference = 'Regular Ice';

  quizBtn?.addEventListener('click', () => {
    resetQuiz();
    quizModal.style.display = 'flex';
  });

  closeQuizBtn?.addEventListener('click', () => { quizModal.style.display = 'none'; });

  document.querySelectorAll('.quiz-opt').forEach(btn => {
    btn.addEventListener('click', function() {
      selectedTempPreference = this.dataset.temp || 'Regular Ice';
      quizStep1.style.display = 'none';
      quizStep2.style.display = 'block';
    });
  });

  document.querySelectorAll('.quiz-final').forEach(btn => {
    btn.addEventListener('click', function() {
      const itemId = parseInt(this.dataset.itemid);
      const itemData = quizItemsData[itemId];

      if (itemData) {
          document.getElementById('quizItemId').value = itemData.item_id;

          document.getElementById('quizRecName').textContent = itemData.name;
          document.getElementById('quizRecPrice').textContent = 'RM ' + parseFloat(itemData.price).toFixed(2);
          document.getElementById('quizRecDesc').textContent = itemData.description;
          document.getElementById('quizRecImg').src = '../images/menu/' + itemData.image;

          // Pre-select temperature dropdown based on Step 1
          const tempSelect = document.getElementById('quizTempSelect');
          if (tempSelect) tempSelect.value = selectedTempPreference;
      }

      quizStep2.style.display = 'none';
      quizResult.style.display = 'block';
    });
  });

  function resetQuiz() {
    quizStep1.style.display = 'block';
    quizStep2.style.display = 'none';
    quizResult.style.display = 'none';
    document.getElementById('quizQtyInput').value = 1;
    document.getElementById('quizRemarksInput').value = '';
  }

  // Quantity Stepper Handler
  const quizQtyInput = document.getElementById('quizQtyInput');
  document.getElementById('quizQtyMinus')?.addEventListener('click', () => {
      let val = parseInt(quizQtyInput.value) || 1;
      if (val > 1) quizQtyInput.value = val - 1;
  });
  document.getElementById('quizQtyPlus')?.addEventListener('click', () => {
      let val = parseInt(quizQtyInput.value) || 1;
      quizQtyInput.value = val + 1;
  });

  // Handle Quiz Add to Cart Form Submission via AJAX -> ../cart/add_to_cart.php
  document.getElementById('quizOrderForm')?.addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      formData.append('ajax', '1');

      fetch('../cart/add_to_cart.php', {
          method: 'POST',
          body: formData
      })
      .then(r => r.json())
      .then(data => {
          if (data.status === 'success') {
              // Update nav cart badge counts
              document.querySelectorAll('.cart-badge, #globalNotifCount').forEach(el => {
                  if (el && el.classList.contains('cart-badge')) {
                      el.textContent = data.total_quantity || data.cart_count;
                      el.style.display = 'inline-block';
                  }
              });
              quizModal.style.display = 'none';
              // Redirect directly to cart page so user sees their item inside cart!
              window.location.href = '../cart/index.php';
          } else {
              this.submit();
          }
      })
      .catch(err => {
          this.submit();
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

<?php require_once '../includes/footer.php'; ?>

</body>
</html>
