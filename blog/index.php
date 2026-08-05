<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php'; // sets $isLoggedIn, $currentUserId, $currentUsername

$presetMoods = [
    'Happy'     => '😊 Happy',
    'Relaxed'   => '😌 Relaxed',
    'Energized' => '⚡ Energized',
    'Cozy'      => '☕ Cozy',
    'Nostalgic' => '🌿 Nostalgic',
    'Grateful'  => '🙏 Grateful',
];

$momentErrors = [];

// =========================================================================
// USER PURCHASED ITEMS (PLACEHOLDER & FALLBACK)
// =========================================================================
$userPurchases = [];

if ($isLoggedIn) {
    // Pulls top menu items so blog inputs/dropdowns work
    $placeholderSql = "SELECT name FROM menu_items ORDER BY display_order ASC LIMIT 10";
    $result = $conn->query($placeholderSql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $userPurchases[] = $row['name'];
        }
    }
}

// =========================================================================
// HANDLE BLOG MOMENT SUBMISSION
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'moment') {
    if (!$isLoggedIn) {
        $momentErrors[] = 'You need to log in to share your experience.';
    } else {
        $orderedItem = trim($_POST['ordered_item'] ?? '');
        $moodSelects = $_POST['mood_select'] ?? [];
        if (!is_array($moodSelects)) {
            $moodSelects = [$moodSelects];
        }
        $moodCustom  = trim($_POST['mood_custom'] ?? '');
        $description = trim($_POST['description'] ?? '');

        // Resolve Mood Names
        $selectedMoods = [];
        foreach ($moodSelects as $mVal) {
            if ($mVal === 'other') {
                if (!empty($moodCustom)) {
                    $selectedMoods[] = $moodCustom;
                }
            } else {
                $selectedMoods[] = $mVal;
            }
        }
        $mood = implode(', ', $selectedMoods);
        
        if (empty($mood)) {
            $momentErrors[] = 'Please select or enter a mood.';
        } elseif (mb_strlen($mood) > 50) {
            $momentErrors[] = 'Mood selection is too long (max 50 characters).';
        }

        // Validate Word Count (Max 500 words)
        $wordCount = !empty($description) ? count(preg_split('/\s+/', $description)) : 0;
        if ($wordCount > 500) {
            $momentErrors[] = 'Description cannot exceed 500 words.';
        }

        // Handle Up to 5 Photo Uploads
        $uploadedFiles = [];
        if (empty($momentErrors) && !empty($_FILES['photos']['name'][0])) {
            $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $maxSize    = 5 * 1024 * 1024; // 5MB per file
            $totalFiles = count($_FILES['photos']['name']);

            if ($totalFiles > 5) {
                $momentErrors[] = 'You can upload a maximum of 5 photos.';
            } else {
                $uploadDir = '../uploads/blog/';
                if (!is_dir($uploadDir)) {
                    if (!mkdir($uploadDir, 0755, true)) {
                        $momentErrors[] = 'Failed to create upload directory. Check folder permissions.';
                    }
                }

                if (empty($momentErrors)) {
                    for ($i = 0; $i < $totalFiles; $i++) {
                        $fileError = $_FILES['photos']['error'][$i];
                        
                        if ($fileError === UPLOAD_ERR_OK) {
                            $ext  = strtolower(pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION));
                            $size = $_FILES['photos']['size'][$i];

                            if (!in_array($ext, $allowedExt, true)) {
                                $momentErrors[] = 'Photos must be JPG, PNG, GIF, or WEBP files.';
                                break;
                            } elseif ($size > $maxSize) {
                                $momentErrors[] = 'Each photo must be under 5MB.';
                                break;
                            } else {
                                $filename   = 'blog_' . $currentUserId . '_' . time() . '_' . $i . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                                $targetFile = $uploadDir . $filename;

                                if (move_uploaded_file($_FILES['photos']['tmp_name'][$i], $targetFile)) {
                                    $uploadedFiles[] = 'uploads/blog/' . $filename;
                                } else {
                                    $momentErrors[] = 'Failed to save uploaded photo #' . ($i + 1) . ' to server folder.';
                                    break;
                                }
                            }
                        } elseif ($fileError !== UPLOAD_ERR_NO_FILE) {
                            $momentErrors[] = 'Error uploading photo #' . ($i + 1) . ' (Code: ' . $fileError . ').';
                            break;
                        }
                    }
                }
            }
        }

        // Save Post & Photos to Database
        if (empty($momentErrors)) {
            $stmt = $conn->prepare(
                'INSERT INTO blog_posts (user_id, ordered_item, mood, description, created_at) VALUES (?, ?, ?, ?, NOW())'
            );
            $stmt->bind_param('isss', $currentUserId, $orderedItem, $mood, $description);
            
            if ($stmt->execute()) {
                $postId = $stmt->insert_id;
                $stmt->close();

                // Save Photos into blog_photos table
                if (!empty($uploadedFiles)) {
                    $pStmt = $conn->prepare('INSERT INTO blog_photos (post_id, image_path) VALUES (?, ?)');
                    if ($pStmt) {
                        foreach ($uploadedFiles as $path) {
                            $pStmt->bind_param('is', $postId, $path);
                            if (!$pStmt->execute()) {
                                $momentErrors[] = 'Failed to insert photo into database: ' . $pStmt->error;
                            }
                        }
                        $pStmt->close();
                    } else {
                        $momentErrors[] = 'Database query preparation failed for photos: ' . $conn->error;
                    }
                }

                if (empty($momentErrors)) {
                    header('Location: index.php');
                    exit;
                }
            } else {
                $momentErrors[] = 'Failed to create post: ' . $stmt->error;
            }
        }
    }
}

$feedQuery = "SELECT bp.*, u.username, u.profile_pic 
        FROM blog_posts bp 
        JOIN users u ON u.id = bp.user_id 
        WHERE bp.is_deleted = 0 
          AND (bp.is_hidden = 0 OR bp.user_id = $currentUserId)
        ORDER BY bp.created_at DESC";
$feedResult = $conn->query($feedQuery);
$currentUserId = $_SESSION['user_id'] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style/mystyle.css">
    <link rel="stylesheet" href="../style/moment-form.css">
    <title>Cozy Coffee Co. — Coffee Memories</title>
    <style>
        .blog-container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .blog-header { text-align: center; margin-bottom: 35px; }

        /* Side-by-Side Layout Grid */
        .blog-layout { 
            display: flex; 
            gap: 35px; 
            align-items: flex-start; 
        }
        .form-column { 
            flex: 1; 
            min-width: 320px; 
            position: sticky; 
            top: 20px; 
        }
        .feed-column { 
            flex: 1.4; 
            min-width: 320px; 
        }

        /* Post Cards & Author Profile */
        .post-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 10px; padding: 20px; margin-bottom: 25px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .post-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        
        .author-info { display: flex; align-items: center; gap: 12px; }
        .profile-avatar { 
            width: 44px; 
            height: 44px; 
            border-radius: 50%; 
            object-fit: cover; 
            border: 2px solid #e0d5c4;
            background-color: #f6f1e9;
            flex-shrink: 0;
        }
        .post-author { font-weight: bold; color: #5a3e2b; text-decoration: none; font-size: 1.05em; display: block; }
        .post-author:hover { text-decoration: underline; }
        
        .mood-badge { background: #8c6d58; color: #fff; padding: 3px 9px; border-radius: 12px; font-size: 0.8em; display: inline-block; margin-top: 2px; }
        .ordered-tag { font-style: italic; color: #666; margin: 8px 0; }
        
        /* Photo Gallery Thumbnails */
        .photo-gallery { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px; }
        .photo-gallery img { 
            width: 120px; 
            height: 120px; 
            object-fit: cover; 
            border-radius: 8px; 
            cursor: pointer; 
            transition: transform 0.2s ease, opacity 0.2s ease; 
        }
        .photo-gallery img:hover { transform: scale(1.03); opacity: 0.9; }

        .auth-banner { background: #fdf8f3; border: 1px solid #e6d7c3; padding: 25px; border-radius: 10px; text-align: center; }
        .auth-banner a { color: #8c6d58; font-weight: bold; text-decoration: underline; }
        
        /* Mood Selection Styling */
        .mood-checkbox-group { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 6px; }
        .mood-chip { display: inline-block; position: relative; cursor: pointer; }
        .mood-chip input[type="checkbox"] { position: absolute; opacity: 0; width: 0; height: 0; pointer-events: none; }
        .mood-chip .chip-label { 
            display: inline-block; 
            padding: 7px 14px; 
            border-radius: 20px; 
            background: #f6f1e9; 
            border: 1px solid #e0d5c4; 
            color: #5a3e2b; 
            font-size: 0.9rem; 
            user-select: none; 
            transition: all 0.2s ease; 
        }
        .mood-chip:hover .chip-label { background: #efe6d8; }
        .mood-chip input[type="checkbox"]:checked + .chip-label { 
            background: #8c6d58; 
            color: #ffffff; 
            border-color: #8c6d58; 
            font-weight: 600; 
            box-shadow: 0 2px 6px rgba(140, 109, 88, 0.3);
        }

        /* Lightbox Modal CSS */
        .lightbox-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.88);
            justify-content: center;
            align-items: center;
        }
        .lightbox-modal.active { display: flex; }
        .lightbox-content {
            max-width: 85%;
            max-height: 85vh;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.6);
        }
        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: #ffffff;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            user-select: none;
            transition: color 0.2s;
            line-height: 1;
        }
        .lightbox-close:hover { color: #d0d0d0; }
        .lightbox-prev, .lightbox-next {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: #ffffff;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            padding: 12px 18px;
            user-select: none;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            border: none;
            transition: background 0.2s;
        }
        .lightbox-prev:hover, .lightbox-next:hover { background: rgba(255, 255, 255, 0.35); }
        .lightbox-prev { left: 25px; }
        .lightbox-next { right: 25px; }

        @media (max-width: 880px) {
            .blog-layout { flex-direction: column; }
            .form-column { position: static; width: 100%; }
            .feed-column { width: 100%; }
        }
    </style>
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
    <li><a href="index.php" class="active">Blog</a></li>
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

<div class="blog-container">

    <div class="blog-header">
        <h1>Coffee Memories</h1>
        <p>Check in and capture your cozy moments.</p>
    </div>

    <!-- MAIN SIDE-BY-SIDE LAYOUT -->
    <div class="blog-layout">

        <!-- LEFT COLUMN: POST CREATION FORM -->
        <div class="form-column">
            <?php if (!$isLoggedIn): ?>
                <div class="auth-banner">
                    <h3>Share Your Cozy Moment</h3>
                    <p style="margin: 15px 0;">Want to check in and share your coffee moment?</p>
                    <p><a href="../login/index.php">Log In</a> or <a href="../register/index.php">Create an Account</a> to post.</p>
                </div>
            <?php else: ?>
                <section class="moment-section">
                    <h3>Share Your Cozy Moment</h3>

                    <?php if (!empty($momentErrors)): ?>
                        <div class="form-errors" style="color: red; margin-bottom: 15px;">
                            <?php foreach ($momentErrors as $err): ?>
                                <p><?php echo htmlspecialchars($err); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form action="index.php" method="POST" enctype="multipart/form-data" class="moment-form" onsubmit="return validateForm()">
                        <input type="hidden" name="form_type" value="moment">

                        <!-- Purchase Selection -->
                        <div class="form-group">
                            <label for="ordered_item">Select What You Ordered (Optional)</label>
                            <?php if (!empty($userPurchases)): ?>
                                <select id="ordered_item" name="ordered_item">
                                    <option value="">-- Select from menu --</option>
                                    <?php foreach ($userPurchases as $item): ?>
                                        <option value="<?php echo htmlspecialchars($item); ?>"><?php echo htmlspecialchars($item); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="text" id="ordered_item" name="ordered_item" placeholder="e.g. Iced Honey Oat Latte">
                            <?php endif; ?>
                        </div>

                        <!-- Mood Selection -->
                        <div class="form-group">
                            <label>How are you feeling?</label>
                            <div class="mood-checkbox-group">
                                <?php foreach ($presetMoods as $key => $label): ?>
                                    <label class="mood-chip">
                                        <input type="checkbox" name="mood_select[]" value="<?php echo htmlspecialchars($key); ?>">
                                        <span class="chip-label"><?php echo htmlspecialchars($label); ?></span>
                                    </label>
                                <?php endforeach; ?>
                                <label class="mood-chip">
                                    <input type="checkbox" name="mood_select[]" value="other" id="mood_other_checkbox" onchange="toggleCustomMood(this)">
                                    <span class="chip-label">✨ Other...</span>
                                </label>
                            </div>
                            <input type="text" id="mood_custom" name="mood_custom" maxlength="30" placeholder="Enter custom mood (max 30 chars)" style="display: none; margin-top: 10px;">
                        </div>

                        <!-- Description -->
                        <div class="form-group">
                            <label for="description">Your thoughts (Optional, max 500 words)</label>
                            <textarea id="description" name="description" rows="4" placeholder="Tell us about your coffee moment today..." onkeyup="checkWordCount()"></textarea>
                            <small id="word_counter" style="color: #666;">0 / 500 words</small>
                        </div>

                        <!-- Photos -->
                        <div class="form-group">
                            <label for="photos">Add photos (Optional, max 5 photos)</label>
                            <input type="file" id="photos" name="photos[]" accept="image/*" multiple onchange="limitFiles(this)">
                        </div>

                        <button type="submit" class="submit-btn">Post Memory</button>
                    </form>
                </section>
            <?php endif; ?>
        </div>

        <!-- RIGHT COLUMN: COMMUNITY CHECK-INS FEED -->
<div class="feed-column">
    <h2>Community Check-ins</h2>

    <?php if ($feedResult && $feedResult->num_rows > 0): ?>
        <?php while ($post = $feedResult->fetch_assoc()): ?>
            <div class="post-card">
                <div class="post-header">
                    <div class="author-info">
                    <!-- Clickable Profile Picture -->
                    <a href="user_posts.php?user_id=<?php echo $post['user_id']; ?>">
                        <img src="../images/profiles/<?php echo htmlspecialchars($post['profile_pic'] ?: 'default.png'); ?>" 
                            alt="<?php echo htmlspecialchars($post['username']); ?>'s profile picture" 
                            class="profile-avatar">
                    </a>
                    <div>
                        <a href="user_posts.php?user_id=<?php echo $post['user_id']; ?>" class="post-author">
                            <?php echo htmlspecialchars($post['username']); ?>
                        </a>
                        <span class="mood-badge"><?php echo htmlspecialchars($post['mood']); ?></span>
                    </div>
                </div>
                    <small style="color: #888;"><?php echo date('M d, Y · g:i A', strtotime($post['created_at'])); ?></small>
                </div>

                <?php if (!empty($post['ordered_item'])): ?>
                    <p class="ordered-tag">☕ Ordered: <strong><?php echo htmlspecialchars($post['ordered_item']); ?></strong></p>
                <?php endif; ?>

                <?php if (!empty($post['description'])): ?>
                    <p style="line-height: 1.5; color: #333; margin-top: 6px;"><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
                <?php endif; ?>

                <!-- Display Attached Photos -->
                <?php
                $pid = (int)$post['id'];
                $photoRes = $conn->query("SELECT image_path FROM blog_photos WHERE post_id = $pid");
                if ($photoRes && $photoRes->num_rows > 0):
                ?>
                    <div class="photo-gallery" id="gallery-<?php echo $pid; ?>">
                        <?php while ($photo = $photoRes->fetch_assoc()): ?>
                            <img src="../<?php echo htmlspecialchars($photo['image_path']); ?>" 
                                 alt="Coffee memory photo" 
                                 onclick="openLightbox(this, 'gallery-<?php echo $pid; ?>')">
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="color: #666;">No check-ins yet. Be the first to post a memory!</p>
    <?php endif; ?>
</div>

    </div>

</div>

<!-- LIGHTBOX MODAL CONTAINER -->
<div id="lightboxModal" class="lightbox-modal" onclick="closeLightboxOnOutsideClick(event)">
    <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
    <button class="lightbox-prev" onclick="changePhoto(-1)">&#10094;</button>
    <img class="lightbox-content" id="lightboxImg" src="" alt="Enlarged photo view">
    <button class="lightbox-next" onclick="changePhoto(1)">&#10095;</button>
</div>

<script>
let currentGalleryImages = [];
let currentImageIndex = 0;

function openLightbox(clickedImg, galleryId) {
    const gallery = document.getElementById(galleryId);
    const images = Array.from(gallery.querySelectorAll('img'));
    currentGalleryImages = images.map(img => img.src);
    currentImageIndex = images.indexOf(clickedImg);

    updateLightboxImage();
    document.getElementById('lightboxModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    document.getElementById('lightboxModal').classList.remove('active');
    document.body.style.overflow = '';
}

function closeLightboxOnOutsideClick(event) {
    if (event.target.id === 'lightboxModal') {
        closeLightbox();
    }
}

function changePhoto(direction) {
    if (currentGalleryImages.length <= 1) return;
    
    currentImageIndex += direction;
    if (currentImageIndex < 0) {
        currentImageIndex = currentGalleryImages.length - 1;
    } else if (currentImageIndex >= currentGalleryImages.length) {
        currentImageIndex = 0;
    }
    updateLightboxImage();
}

function updateLightboxImage() {
    const lightboxImg = document.getElementById('lightboxImg');
    lightboxImg.src = currentGalleryImages[currentImageIndex];
    
    const prevBtn = document.querySelector('.lightbox-prev');
    const nextBtn = document.querySelector('.lightbox-next');
    if (currentGalleryImages.length <= 1) {
        prevBtn.style.display = 'none';
        nextBtn.style.display = 'none';
    } else {
        prevBtn.style.display = 'block';
        nextBtn.style.display = 'block';
    }
}

document.addEventListener('keydown', function(event) {
    const modal = document.getElementById('lightboxModal');
    if (modal.classList.contains('active')) {
        if (event.key === 'Escape') {
            closeLightbox();
        } else if (event.key === 'ArrowLeft') {
            changePhoto(-1);
        } else if (event.key === 'ArrowRight') {
            changePhoto(1);
        }
    }
});

function toggleCustomMood(checkbox) {
    const customInput = document.getElementById('mood_custom');
    if (checkbox.checked) {
        customInput.style.display = 'block';
        customInput.required = true;
        customInput.focus();
    } else {
        customInput.style.display = 'none';
        customInput.required = false;
        customInput.value = '';
    }
}

function checkWordCount() {
    const text = document.getElementById('description').value.trim();
    const words = text ? text.split(/\s+/).length : 0;
    const counter = document.getElementById('word_counter');
    counter.innerText = words + " / 500 words";
    counter.style.color = (words > 500) ? 'red' : '#666';
}

function limitFiles(input) {
    if (input.files.length > 5) {
        alert('You can only select up to 5 photos.');
        input.value = '';
    }
}

function validateForm() {
    const text = document.getElementById('description').value.trim();
    const words = text ? text.split(/\s+/).length : 0;
    if (words > 500) {
        alert('Description cannot exceed 500 words.');
        return false;
    }

    const checkedMoods = document.querySelectorAll('input[name="mood_select[]"]:checked');
    if (checkedMoods.length === 0) {
        alert('Please select at least one mood.');
        return false;
    }

    return true;
}

document.querySelector('.hamburger').addEventListener('click', () => {
    const nav = document.querySelector('.nav-links');
    nav.style.display = nav.style.display === 'flex' ? 'none' : 'flex';
});
</script>

</body>
</html>