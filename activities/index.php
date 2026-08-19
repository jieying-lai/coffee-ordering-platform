<?php
session_start();
require_once '../includes/db_connect.php';

// Fetch all active (non-hidden) activities posts ordered by newest
$query = "SELECT ap.*, 
                 (SELECT image_path FROM activities_photos WHERE activity_id = ap.id LIMIT 1) as cover_photo,
                 (SELECT COUNT(*) FROM activities_photos WHERE activity_id = ap.id) as photo_count
          FROM activities_posts ap
          WHERE ap.is_hidden = 0
          ORDER BY ap.created_at DESC";
$res = $conn->query($query);

$activities = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $actId = (int)$row['id'];
        $pStmt = $conn->prepare("SELECT id, image_path FROM activities_photos WHERE activity_id = ?");
        $pStmt->bind_param("i", $actId);
        $pStmt->execute();
        $pRes = $pStmt->get_result();
        $photos = [];
        if ($pRes) {
            while ($pRow = $pRes->fetch_assoc()) {
                $photos[] = $pRow['image_path'];
            }
        }
        $pStmt->close();
        $row['photos'] = $photos;
        $activities[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/programs.css">
  <title>Cozy Coffee Co. — Store Activities &amp; Brand Journal</title>
  <style>
    /* ============ ELEGANT VISUAL-FIRST JOURNAL GRID (3 COLUMNS WITH ROOMY CARD LAYOUT) ============ */
    .activities-container {
      max-width: 1240px;
      margin: 30px auto 70px;
      padding: 0 4%;
      box-sizing: border-box;
    }

    .category-filter-bar {
      display: flex;
      justify-content: center;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 36px;
    }

    .cat-filter-btn {
      padding: 9px 22px;
      border-radius: 30px;
      background: #FFFFFF;
      border: 1.5px solid #E8DDD0;
      color: #665447;
      font-size: 0.88rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.25s ease;
      box-shadow: 0 4px 10px rgba(60, 42, 33, 0.03);
    }

    .cat-filter-btn:hover, .cat-filter-btn.active {
      background: linear-gradient(135deg, #C85A3E 0%, #A8472F 100%);
      color: #FFFFFF;
      border-color: #C85A3E;
      box-shadow: 0 6px 18px rgba(200, 90, 62, 0.3);
      transform: translateY(-2px);
    }

    /* 3 Columns Grid on Large Screen for Wide, Spacious Breathing Room */
    .activities-feed-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 28px;
    }

    @media (max-width: 1024px) {
      .activities-feed-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
      }
    }

    @media (max-width: 640px) {
      .activities-feed-grid {
        grid-template-columns: 1fr;
        gap: 18px;
      }
    }

    .activity-post-card {
      background: #FFFFFF;
      border-radius: 22px;
      border: 1.5px solid #E8DDD0;
      box-shadow: 0 8px 24px rgba(60, 42, 33, 0.04);
      overflow: hidden;
      transition: transform 0.32s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.32s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.32s ease;
      display: flex;
      flex-direction: column;
      cursor: pointer;
    }

    .activity-post-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 18px 42px rgba(60, 42, 33, 0.12);
      border-color: #C85A3E;
    }

    .card-cover-box {
      height: 220px;
      width: 100%;
      position: relative;
      overflow: hidden;
      background: linear-gradient(135deg, #FAF4EB, #EFE3D3);
    }

    .card-cover-box img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform 0.45s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .activity-post-card:hover .card-cover-box img {
      transform: scale(1.08);
    }

    .card-category-badge {
      position: absolute;
      top: 14px;
      left: 14px;
      padding: 4px 12px;
      border-radius: 20px;
      background: rgba(24, 15, 10, 0.82);
      backdrop-filter: blur(8px);
      color: #F2C94C;
      font-size: 0.75rem;
      font-weight: 800;
      border: 1px solid rgba(242, 201, 76, 0.35);
      letter-spacing: 0.4px;
    }

    .card-photo-count-badge {
      position: absolute;
      bottom: 14px;
      right: 14px;
      padding: 4px 10px;
      border-radius: 16px;
      background: rgba(0, 0, 0, 0.7);
      backdrop-filter: blur(8px);
      color: #FFFFFF;
      font-size: 0.72rem;
      font-weight: 700;
    }

    .card-body-content {
      padding: 20px 22px 22px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .brand-official-header {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 12px;
    }

    .brand-avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: #3C2A21;
      border: 1.5px solid #C85A3E;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #F2C94C;
      font-size: 14px;
      font-weight: bold;
      flex-shrink: 0;
    }

    .brand-meta {
      display: flex;
      flex-direction: column;
    }

    .brand-name {
      font-weight: 800;
      font-size: 0.85rem;
      color: #2C1C14;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .brand-date {
      font-size: 0.75rem;
      color: #8C7A6D;
      font-weight: 600;
    }

    .card-title {
      font-family: var(--font-heading, serif);
      font-size: 1.12rem;
      font-weight: 800;
      color: #2C1C14;
      margin: 0 0 8px 0;
      line-height: 1.38;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .card-snippet {
      font-size: 0.88rem;
      color: #7A685A;
      line-height: 1.58;
      margin: 0 0 16px 0;
      flex: 1;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .card-action-btn {
      font-size: 0.85rem;
      font-weight: 800;
      color: #C85A3E;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: transform 0.2s ease;
      margin-top: auto;
    }

    .activity-post-card:hover .card-action-btn {
      transform: translateX(5px);
    }

    /* ============ IN-MODAL SLIDER / CAROUSEL ============ */
    .modal-carousel-box {
      margin-bottom: 16px;
      border-radius: 18px;
      overflow: hidden;
      background: #1C130E;
      box-shadow: 0 10px 28px rgba(0, 0, 0, 0.15);
      border: 1.5px solid #E5D9CC;
    }

    .carousel-stage {
      position: relative;
      width: 100%;
      height: 280px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #140C08;
      box-sizing: border-box;
      padding: 10px;
    }

    .carousel-img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
      display: block;
      border-radius: 10px;
      transition: opacity 0.25s ease;
    }

    .carousel-btn {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.92);
      border: none;
      color: #2C1C14;
      font-size: 24px;
      font-weight: bold;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.35);
      z-index: 10;
    }

    .carousel-btn:hover {
      background: #C85A3E;
      color: #FFFFFF;
    }

    .carousel-btn.prev-btn { left: 14px; }
    .carousel-btn.next-btn { right: 14px; }

    .carousel-counter-badge {
      position: absolute;
      bottom: 12px;
      right: 14px;
      padding: 4px 12px;
      border-radius: 20px;
      background: rgba(0, 0, 0, 0.75);
      backdrop-filter: blur(6px);
      color: #FFFFFF;
      font-size: 0.78rem;
      font-weight: 700;
      z-index: 10;
    }
  </style>
</head>

<body>
<?php 
  $activePage = 'activities';
  require_once '../includes/header_nav.php'; 
?>

<!-- HERO SHOWCASE HEADER -->
<section class="program-hero">
  <div class="eyebrow">✦ Official Store Journal ✦</div>
  <h1>Cozy Moments &amp; <span class="highlight-text">Store Activities</span></h1>
  <p class="lede">Discover our official brand events, monthly Merdeka promotions, barista workshops, and everyday coffee stories.</p>
</section>

<div class="activities-container">

  <!-- CATEGORY FILTER BAR -->
  <div class="category-filter-bar">
    <button type="button" class="cat-filter-btn active" data-cat="all">All Stories</button>
    <button type="button" class="cat-filter-btn" data-cat="event">Events</button>
    <button type="button" class="cat-filter-btn" data-cat="promotion">Promotions</button>
    <button type="button" class="cat-filter-btn" data-cat="workshop">Workshops</button>
    <button type="button" class="cat-filter-btn" data-cat="daily">Daily Moments</button>
    <button type="button" class="cat-filter-btn" data-cat="announcement">Announcements</button>
  </div>

  <!-- ELEGANT 3-COLUMN VISUAL JOURNAL GRID -->
  <div class="activities-feed-grid">
    <?php if (!empty($activities)): ?>
      <?php foreach ($activities as $act): ?>
        <?php
          $catBadges = [
            'event' => 'Event',
            'promotion' => 'Promotion',
            'workshop' => 'Workshop',
            'daily' => 'Daily',
            'announcement' => 'Announcement'
          ];
          $badgeText = $catBadges[$act['category']] ?? 'Activity';
          $coverPhoto = !empty($act['cover_photo']) ? '../' . $act['cover_photo'] : '../images/1.jpg';
          $photosJson = htmlspecialchars(json_encode($act['photos']), ENT_QUOTES, 'UTF-8');
        ?>
        <div class="activity-post-card" data-category="<?php echo htmlspecialchars($act['category']); ?>" onclick="openActivityModal('<?php echo htmlspecialchars(addslashes($act['title'])); ?>', '<?php echo $badgeText; ?>', '<?php echo date('M d, Y · g:i A', strtotime($act['created_at'])); ?>', '<?php echo htmlspecialchars(addslashes($act['content'])); ?>', <?php echo $photosJson; ?>)">
          <div class="card-cover-box">
            <img src="<?php echo htmlspecialchars($coverPhoto); ?>" alt="<?php echo htmlspecialchars($act['title']); ?>">
            <span class="card-category-badge"><?php echo $badgeText; ?></span>
            <?php if (!empty($act['photos']) && count($act['photos']) > 1): ?>
              <span class="card-photo-count-badge">📷 <?php echo count($act['photos']); ?></span>
            <?php endif; ?>
          </div>

          <div class="card-body-content">
            <h3 class="card-title"><?php echo htmlspecialchars($act['title']); ?></h3>
            <p class="card-snippet"><?php echo htmlspecialchars($act['content']); ?></p>
            
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: auto; padding-top: 12px; border-top: 1px dashed #F0E6DC;">
              <span style="font-size: 0.78rem; color: #8C7A6D; font-weight: 600;">
                📅 <?php echo date('M d, Y', strtotime($act['created_at'])); ?>
              </span>
              <div class="card-action-btn" style="margin-top: 0;">
                Read Full Story &rarr;
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="grid-column: 1 / -1; text-align: center; color: #888; padding: 50px; background: #FFFFFF; border-radius: 20px; border: 1.5px solid #E8DDD0;">No store activities published yet. Stay tuned!</p>
    <?php endif; ?>
  </div>

</div>

<!-- STORY DETAIL POPUP MODAL WITH SLIDER -->
<div id="activityDetailModal" class="program-modal-overlay" onclick="closeModalOnOutsideClick(event, 'activityDetailModal')">
  <div class="program-modal-card" style="max-width: 660px; padding: 26px; max-height: 92vh;">
    <button type="button" class="program-modal-close" onclick="closeModal('activityDetailModal')">&times;</button>
    
    <div style="display: flex; align-items: center; margin-bottom: 18px; padding-right: 48px;">
      <div class="brand-official-header" style="margin: 0;">
        <div class="brand-avatar">☕</div>
        <div class="brand-meta">
          <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <span class="brand-name" style="font-size: 0.92rem;">Cozy Coffee Co. Official <span style="color: #C85A3E;">✦</span></span>
            <span id="modalCategoryBadge" class="modal-header-badge" style="margin: 0; padding: 3px 10px; font-size: 0.75rem;"></span>
          </div>
          <span class="brand-date" id="modalPostDate"></span>
        </div>
      </div>
    </div>

    <!-- IN-MODAL PHOTO SLIDER / CAROUSEL (TOP SECTION) -->
    <div id="modalCarouselBox" class="modal-carousel-box" style="display: none;">
      <div class="carousel-stage">
        <img id="carouselCurrentImg" src="" alt="Story photo" class="carousel-img">
        <button type="button" id="carouselPrevBtn" class="carousel-btn prev-btn" onclick="prevCarouselPhoto()">&lsaquo;</button>
        <button type="button" id="carouselNextBtn" class="carousel-btn next-btn" onclick="nextCarouselPhoto()">&rsaquo;</button>
        <span id="carouselCounterBadge" class="carousel-counter-badge">1 / 1</span>
      </div>

      <!-- INTERACTIVE PHOTO THUMBNAILS STRIP -->
      <div id="modalThumbStrip" style="display: flex; gap: 8px; padding: 10px 14px; background: #261A13; overflow-x: auto;"></div>
    </div>

    <h3 id="modalTitle" style="font-family: var(--font-heading); font-size: 1.4rem; color: #2C1C14; margin-bottom: 14px; font-weight: 800; line-height: 1.35;"></h3>

    <!-- FULL STORY CONTENT -->
    <div id="modalContent" style="color: #4A3B32; font-size: 0.95rem; line-height: 1.7; margin-bottom: 20px; white-space: pre-line;"></div>

    <div class="modal-action-row" style="margin-top: 24px;">
      <button type="button" class="cta-btn cta-btn-primary" onclick="closeModal('activityDetailModal')" style="font-size: 0.9rem; padding: 12px 28px; width: 100%;">Close Story ☕</button>
    </div>
  </div>
</div>

<script>
let modalPhotosList = [];
let currentPhotoIndex = 0;

// Category Filter Handling
document.addEventListener('DOMContentLoaded', function() {
    const filterBtns = document.querySelectorAll('.cat-filter-btn');
    const cards = document.querySelectorAll('.activity-post-card');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const cat = this.getAttribute('data-cat');
            cards.forEach(card => {
                const cardCat = card.getAttribute('data-category');
                if (cat === 'all' || cardCat === cat) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
});

function openActivityModal(title, categoryBadge, postDate, content, photos) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalCategoryBadge').textContent = categoryBadge;
    document.getElementById('modalPostDate').textContent = postDate;
    document.getElementById('modalContent').textContent = content;

    const carouselBox = document.getElementById('modalCarouselBox');
    modalPhotosList = photos && photos.length > 0 ? photos : [];
    currentPhotoIndex = 0;

    if (modalPhotosList.length > 0) {
        updateCarouselStage();
        if (carouselBox) carouselBox.style.display = 'block';
    } else {
        if (carouselBox) carouselBox.style.display = 'none';
    }

    openModal('activityDetailModal');
}

function updateCarouselStage() {
    if (modalPhotosList.length === 0) return;
    const imgEl = document.getElementById('carouselCurrentImg');
    imgEl.style.opacity = '0.5';
    imgEl.src = '../' + modalPhotosList[currentPhotoIndex];
    setTimeout(() => { imgEl.style.opacity = '1'; }, 50);

    const counter = document.getElementById('carouselCounterBadge');
    if (counter) {
        counter.textContent = `${currentPhotoIndex + 1} / ${modalPhotosList.length}`;
    }

    const prevBtn = document.getElementById('carouselPrevBtn');
    const nextBtn = document.getElementById('carouselNextBtn');
    if (modalPhotosList.length <= 1) {
        if (prevBtn) prevBtn.style.display = 'none';
        if (nextBtn) nextBtn.style.display = 'none';
    } else {
        if (prevBtn) prevBtn.style.display = 'flex';
        if (nextBtn) nextBtn.style.display = 'flex';
    }

    renderModalThumbnails();
}

function renderModalThumbnails() {
    const strip = document.getElementById('modalThumbStrip');
    if (!strip) return;
    if (modalPhotosList.length <= 1) {
        strip.style.display = 'none';
        return;
    }
    strip.style.display = 'flex';
    let html = '';
    modalPhotosList.forEach((p, idx) => {
        const isSel = idx === currentPhotoIndex;
        html += `
            <img src="../${p}" onclick="setCarouselIndex(${idx})" style="width: 52px; height: 52px; border-radius: 8px; object-fit: cover; cursor: pointer; border: 2px solid ${isSel ? '#C85A3E' : 'transparent'}; opacity: ${isSel ? '1' : '0.6'}; transition: all 0.2s ease;" alt="Thumbnail">
        `;
    });
    strip.innerHTML = html;
}

function setCarouselIndex(idx) {
    if (idx >= 0 && idx < modalPhotosList.length) {
        currentPhotoIndex = idx;
        updateCarouselStage();
    }
}

function prevCarouselPhoto() {
    if (modalPhotosList.length <= 1) return;
    currentPhotoIndex--;
    if (currentPhotoIndex < 0) {
        currentPhotoIndex = modalPhotosList.length - 1;
    }
    updateCarouselStage();
}

function nextCarouselPhoto() {
    if (modalPhotosList.length <= 1) return;
    currentPhotoIndex++;
    if (currentPhotoIndex >= modalPhotosList.length) {
        currentPhotoIndex = 0;
    }
    updateCarouselStage();
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function closeModalOnOutsideClick(e, modalId) {
    if (e.target.id === modalId) {
        closeModal(modalId);
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>

</body>
</html>
