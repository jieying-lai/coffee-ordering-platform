<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php'; // sets $isLoggedIn, $currentUserId, $currentUsername

// FIX: this must be set BEFORE it's used to build the feed query below.
// It was previously assigned after $feedQuery was built, so it was
// empty at the time the query ran, producing invalid SQL.
$currentUserId = $_SESSION['user_id'] ?? 0;

$presetMoods = [
    'Happy'     => '😊 Happy',
    'Relaxed'   => '😌 Relaxed',
    'Energized' => '⚡ Energized',
    'Cozy'      => '☕ Cozy',
    'Nostalgic' => '🌿 Nostalgic',
    'Grateful'  => '🙏 Grateful',
];

$momentErrors = [];

// Query actual user past ordered items if logged in, falling back to top menu items
if ($isLoggedIn) {
    $orderSql = "SELECT DISTINCT m.name 
                 FROM orders o 
                 JOIN order_items oi ON oi.order_id = o.order_id 
                 JOIN menu_items m ON m.item_id = oi.item_id 
                 WHERE o.user_id = ? 
                 ORDER BY o.order_date DESC";
    $oStmt = $conn->prepare($orderSql);
    $oStmt->bind_param("i", $currentUserId);
    $oStmt->execute();
    $oRes = $oStmt->get_result();
    while ($r = $oRes->fetch_assoc()) {
        $userPurchases[] = $r['name'];
    }
    $oStmt->close();

    if (empty($userPurchases)) {
        $placeholderSql = "SELECT name FROM menu_items ORDER BY display_order ASC LIMIT 10";
        $result = $conn->query($placeholderSql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $userPurchases[] = $row['name'];
            }
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

// HANDLE POST EDITING
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'edit_post') {
    $editId = (int)($_POST['edit_post_id'] ?? 0);
    $editDesc = trim($_POST['edit_description'] ?? '');
    $editItem = trim($_POST['edit_ordered_item'] ?? '');
    $editMoodSelects = $_POST['edit_mood_select'] ?? [];
    if (!is_array($editMoodSelects)) {
        $editMoodSelects = [$editMoodSelects];
    }
    $editMoodCustom = trim($_POST['edit_mood_custom'] ?? '');

    $selectedMoods = [];
    foreach ($editMoodSelects as $mVal) {
        if ($mVal === 'other') {
            if (!empty($editMoodCustom)) {
                $selectedMoods[] = $editMoodCustom;
            }
        } else {
            $selectedMoods[] = $mVal;
        }
    }
    $editMood = implode(', ', $selectedMoods);

    if ($isLoggedIn && $editId > 0) {
        $uStmt = $conn->prepare("UPDATE blog_posts SET ordered_item = ?, mood = CASE WHEN ? != '' THEN ? ELSE mood END, description = ? WHERE id = ? AND user_id = ?");
        $uStmt->bind_param("ssssii", $editItem, $editMood, $editMood, $editDesc, $editId, $currentUserId);
        $uStmt->execute();
        $uStmt->close();

        // Handle Optional Photo Replacement during Edit
        if (!empty($_FILES['edit_photos']['name'][0])) {
            $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $uploadDir = '../uploads/blog/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $totalFiles = count($_FILES['edit_photos']['name']);
            $newPaths = [];
            for ($i = 0; $i < min($totalFiles, 5); $i++) {
                if ($_FILES['edit_photos']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['edit_photos']['name'][$i], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowedExt, true)) {
                        $filename = 'blog_edit_' . $currentUserId . '_' . time() . '_' . $i . '.' . $ext;
                        $targetFile = $uploadDir . $filename;
                        if (move_uploaded_file($_FILES['edit_photos']['tmp_name'][$i], $targetFile)) {
                            $newPaths[] = 'uploads/blog/' . $filename;
                        }
                    }
                }
            }

            if (!empty($newPaths)) {
                // Delete old photos
                $delP = $conn->prepare("DELETE FROM blog_photos WHERE post_id = ?");
                $delP->bind_param("i", $editId);
                $delP->execute();
                $delP->close();

                // Insert new photos
                $insP = $conn->prepare("INSERT INTO blog_photos (post_id, image_path) VALUES (?, ?)");
                foreach ($newPaths as $np) {
                    $insP->bind_param("is", $editId, $np);
                    $insP->execute();
                }
                $insP->close();
            }
        }

        header('Location: index.php');
        exit;
    }
}

// FIX: converted to a prepared statement — $currentUserId is now
// guaranteed to be set (see top of file) and is safely bound instead
// of being concatenated directly into the SQL string.
$feedStmt = $conn->prepare(
    'SELECT bp.*, u.username, u.profile_pic
     FROM blog_posts bp
     JOIN users u ON u.id = bp.user_id
     WHERE bp.is_deleted = 0
       AND (bp.is_hidden = 0 OR bp.user_id = ?)
     ORDER BY bp.created_at DESC'
);
$feedStmt->bind_param('i', $currentUserId);
$feedStmt->execute();
$feedResult = $feedStmt->get_result();
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
        body { background: linear-gradient(135deg, #F9F4EC 0%, #EFE5D6 50%, #F5ECDF 100%) !important; min-height: 100vh; }
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
<?php 
  $activePage = 'blog';
  require_once '../includes/header_nav.php'; 
?>

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
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h2 style="margin: 0;">Community Check-ins</h2>
      <div style="display: flex; gap: 8px;">
        <button type="button" class="btn btn-small btn-orange feed-filter-btn" data-filter="all">All Moments</button>
        <?php if ($isLoggedIn): ?>
          <button type="button" class="btn btn-small btn-outline feed-filter-btn" data-filter="my" data-user="<?php echo $currentUserId; ?>">My Memories</button>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($feedResult && $feedResult->num_rows > 0): ?>
        <?php while ($post = $feedResult->fetch_assoc()): ?>
            <div class="post-card" data-user-id="<?php echo $post['user_id']; ?>">
                <div class="post-header">
                    <div class="author-info">
                    <!-- Clickable Profile Picture -->
                    <a href="javascript:void(0)" onclick="openAuthorModal(<?php echo $post['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($post['username'])); ?>', '../images/profiles/<?php echo htmlspecialchars($post['profile_pic'] ?: 'default.png'); ?>')">
                        <img src="../images/profiles/<?php echo htmlspecialchars($post['profile_pic'] ?: 'default.png'); ?>" 
                            alt="<?php echo htmlspecialchars($post['username']); ?>'s profile picture" 
                            class="profile-avatar">
                    </a>
                    <div>
                        <a href="javascript:void(0)" onclick="openAuthorModal(<?php echo $post['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($post['username'])); ?>', '../images/profiles/<?php echo htmlspecialchars($post['profile_pic'] ?: 'default.png'); ?>')" class="post-author">
                            <?php echo htmlspecialchars($post['username']); ?>
                        </a>
                        <div style="margin-top: 4px; display:flex; gap: 4px; flex-wrap:wrap;">
                          <?php 
                            $moodList = array_map('trim', explode(',', $post['mood']));
                            foreach ($moodList as $mTag):
                              if (empty($mTag)) continue;
                          ?>
                            <span class="tag-chip tag-chip-mood"><?php echo htmlspecialchars($mTag); ?></span>
                          <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                    <div style="text-align: right;">
                      <small style="color: #888; display:block;"><?php echo date('M d, Y · g:i A', strtotime($post['created_at'])); ?></small>
                      <?php if ($isLoggedIn && $post['user_id'] == $currentUserId): ?>
                        <button type="button" class="btn btn-outline btn-small" onclick="openEditPostModal(<?php echo $post['id']; ?>, '<?php echo htmlspecialchars(addslashes($post['ordered_item'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($post['description'] ?? '')); ?>')" style="margin-top:4px; padding: 2px 8px; font-size:0.75rem;">✏️ Edit</button>
                      <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($post['ordered_item'])): ?>
                    <p class="ordered-tag" style="margin-top: 8px;">☕ Ordered: <span class="tag-chip tag-chip-sweet"><?php echo htmlspecialchars($post['ordered_item']); ?></span></p>
                <?php endif; ?>

                <?php if (!empty($post['description'])): ?>
                    <p style="line-height: 1.5; color: #333; margin-top: 6px;"><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
                <?php endif; ?>

                <!-- Display Attached Photos -->
                <?php
                $pid = (int) $post['id'];
                $photoStmt = $conn->prepare('SELECT image_path FROM blog_photos WHERE post_id = ?');
                $photoStmt->bind_param('i', $pid);
                $photoStmt->execute();
                $photoRes = $photoStmt->get_result();
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

    <!-- EMPTY MY MEMORIES TAB MESSAGE -->
    <div id="emptyMyMemoriesMsg" style="display: none; text-align: center; padding: 40px 20px; background: #ffffff; border-radius: 14px; border: 1px dashed var(--color-border); margin-top: 10px;">
        <div style="font-size: 2.5rem; margin-bottom: 8px;">✨</div>
        <h3 style="color: var(--color-primary); margin-bottom: 6px;">You haven't posted any coffee memories yet</h3>
        <p style="color: #666; font-size: 0.9rem;">Share your first coffee moment, rating, or photo using the form on the left!</p>
    </div>
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

<!-- EDIT POST MODAL -->
<div id="editPostModal" class="modal-overlay">
  <div class="modal-content" style="max-width: 520px; padding: 25px; max-height: 88vh; overflow-y: auto;">
    <button class="modal-close" onclick="document.getElementById('editPostModal').style.display='none'">&times;</button>
    <h3 style="color: var(--color-primary); margin-bottom: 15px;">✏️ Edit Coffee Moment</h3>
    <form action="" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="form_type" value="edit_post">
      <input type="hidden" name="edit_post_id" id="editPostId">
      
      <!-- History / Ordered Item Selection -->
      <div class="form-group" style="margin-bottom: 14px;">
        <label style="font-size:0.9rem; font-weight:700; color:#444; display:block; margin-bottom:6px;">Select What You Ordered</label>
        <?php if (!empty($userPurchases)): ?>
          <select id="editOrderedItemSelect" name="edit_ordered_item" style="width:100%; border-radius:8px; padding:10px; border:1px solid #d0c4b8;">
            <option value="">-- Select from menu --</option>
            <?php foreach ($userPurchases as $item): ?>
              <option value="<?php echo htmlspecialchars($item); ?>"><?php echo htmlspecialchars($item); ?></option>
            <?php endforeach; ?>
          </select>
        <?php else: ?>
          <input type="text" name="edit_ordered_item" id="editOrderedItem" class="search-input" style="width:100%; border-radius:8px; padding:8px 12px;" placeholder="e.g. Dirty Latte">
        <?php endif; ?>
      </div>

      <!-- Mood Selection -->
      <div class="form-group" style="margin-bottom: 14px;">
        <label style="font-size:0.9rem; font-weight:700; color:#444; display:block; margin-bottom:6px;">Update Feeling / Mood:</label>
        <div class="mood-checkbox-group">
          <?php foreach ($presetMoods as $key => $label): ?>
            <label class="mood-chip">
              <input type="checkbox" name="edit_mood_select[]" value="<?php echo htmlspecialchars($key); ?>">
              <span class="chip-label"><?php echo htmlspecialchars($label); ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Description -->
      <div class="form-group" style="margin-bottom: 16px;">
        <label style="font-size:0.9rem; font-weight:700; color:#444; display:block; margin-bottom:6px;">Thoughts / Experience</label>
        <textarea name="edit_description" id="editDescription" rows="4" style="width:100%; border-radius:8px; padding:10px; border: 1px solid var(--color-border);" required></textarea>
      </div>

      <!-- Replace / Update Photos -->
      <div class="form-group" style="margin-bottom: 16px;">
        <label style="font-size:0.9rem; font-weight:700; color:#444; display:block; margin-bottom:6px;">Replace Photos (Optional, max 5)</label>
        <input type="file" name="edit_photos[]" accept="image/*" multiple style="font-size:0.85rem;">
      </div>

      <button type="submit" class="btn btn-orange btn-full" style="font-weight:700;">Save Changes ☕</button>
    </form>
  </div>
</div>

<!-- AUTHOR PROFILE MODAL -->
<div id="authorModal" class="modal-overlay">
  <div class="modal-content" style="max-width: 420px; padding: 25px; text-align: center;">
    <button class="modal-close" onclick="document.getElementById('authorModal').style.display='none'">&times;</button>
    <img id="authorPic" src="" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid var(--color-accent); margin-bottom: 10px;">
    <h3 id="authorName" style="color: var(--color-primary); margin-bottom: 4px;"></h3>
    <p style="color: #666; font-size: 0.88rem; margin-bottom: 15px;">☕ Cozy Coffee Community Member</p>
    <div style="background: #faf5ee; padding: 12px; border-radius: 10px; border: 1px solid #e0d5c4; font-size: 0.88rem; color: #555;">
      Member shares coffee moments, ratings &amp; reviews with fellow enthusiasts!
    </div>
  </div>
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

function openEditPostModal(id, item, desc) {
    document.getElementById('editPostId').value = id;
    document.getElementById('editOrderedItem').value = item;
    document.getElementById('editDescription').value = desc;
    document.getElementById('editPostModal').style.display = 'flex';
}

function openAuthorModal(userId, username, picSrc) {
    document.getElementById('authorName').textContent = username;
    document.getElementById('authorPic').src = picSrc;
    document.getElementById('authorModal').style.display = 'flex';
}

// Feed Filter JS (All Moments vs My Memories)
document.querySelectorAll('.feed-filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.feed-filter-btn').forEach(b => {
            b.classList.remove('btn-orange');
            b.classList.add('btn-outline');
        });
        this.classList.remove('btn-outline');
        this.classList.add('btn-orange');

        const filter = this.dataset.filter;
        const currentUserId = this.dataset.user;
        const posts = document.querySelectorAll('.post-card');
        let visibleCount = 0;

        posts.forEach(post => {
            if (filter === 'all') {
                post.style.display = 'block';
                visibleCount++;
            } else if (filter === 'my') {
                if (post.dataset.userId === currentUserId) {
                    post.style.display = 'block';
                    visibleCount++;
                } else {
                    post.style.display = 'none';
                }
            }
        });

        const emptyMsg = document.getElementById('emptyMyMemoriesMsg');
        if (emptyMsg) {
            emptyMsg.style.display = (visibleCount === 0) ? 'block' : 'none';
        }
    });
});

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
</script>

</body>
</html>
