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

        // Delete Specifically Selected Photos during Edit
        if (!empty($_POST['delete_photo_ids']) && is_array($_POST['delete_photo_ids'])) {
            foreach ($_POST['delete_photo_ids'] as $delPhotoId) {
                $delPhotoId = (int)$delPhotoId;
                if ($delPhotoId > 0) {
                    $pStmt = $conn->prepare("SELECT image_path FROM blog_photos WHERE id = ? AND post_id = ?");
                    $pStmt->bind_param("ii", $delPhotoId, $editId);
                    $pStmt->execute();
                    $pRes = $pStmt->get_result();
                    if ($pRow = $pRes->fetch_assoc()) {
                        if (file_exists('../' . $pRow['image_path'])) {
                            @unlink('../' . $pRow['image_path']);
                        }
                        $dStmt = $conn->prepare("DELETE FROM blog_photos WHERE id = ?");
                        $dStmt->bind_param("i", $delPhotoId);
                        $dStmt->execute();
                        $dStmt->close();
                    }
                    $pStmt->close();
                }
            }
        }

        // Handle Optional New Photos Attachment during Edit
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
                $insP = $conn->prepare("INSERT INTO blog_photos (post_id, image_path) VALUES (?, ?)");
                foreach ($newPaths as $np) {
                    $insP->bind_param("is", $editId, $np);
                    $insP->execute();
                }
                $insP->close();
            }
        }

        header('Location: index.php?tab=my');
        exit;
    }
}

// HANDLE POST SOFT-DELETE (Move to Recently Deleted for 30 Days)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'delete_post') {
    $deleteId = (int)($_POST['delete_post_id'] ?? 0);
    if ($isLoggedIn && $deleteId > 0) {
        $dStmt = $conn->prepare("UPDATE blog_posts SET is_deleted = 1, deleted_at = NOW() WHERE id = ? AND user_id = ?");
        $dStmt->bind_param("ii", $deleteId, $currentUserId);
        $dStmt->execute();
        $dStmt->close();
        header('Location: index.php?tab=deleted');
        exit;
    }
}

// HANDLE POST RESTORE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'restore_post') {
    $restoreId = (int)($_POST['restore_post_id'] ?? 0);
    if ($isLoggedIn && $restoreId > 0) {
        $rStmt = $conn->prepare("UPDATE blog_posts SET is_deleted = 0, deleted_at = NULL WHERE id = ? AND user_id = ?");
        $rStmt->bind_param("ii", $restoreId, $currentUserId);
        $rStmt->execute();
        $rStmt->close();
        header('Location: index.php?tab=my');
        exit;
    }
}

// HANDLE PERMANENT DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'permanent_delete') {
    $permId = (int)($_POST['perm_delete_id'] ?? 0);
    if ($isLoggedIn && $permId > 0) {
        $pStmt = $conn->prepare("SELECT image_path FROM blog_photos WHERE post_id = ?");
        $pStmt->bind_param("i", $permId);
        $pStmt->execute();
        $pRes = $pStmt->get_result();
        while ($pRow = $pRes->fetch_assoc()) {
            if (file_exists('../' . $pRow['image_path'])) {
                @unlink('../' . $pRow['image_path']);
            }
        }
        $pStmt->close();

        $dpStmt = $conn->prepare("DELETE FROM blog_photos WHERE post_id = ?");
        $dpStmt->bind_param("i", $permId);
        $dpStmt->execute();
        $dpStmt->close();

        $bpStmt = $conn->prepare("DELETE FROM blog_posts WHERE id = ? AND user_id = ?");
        $bpStmt->bind_param("ii", $permId, $currentUserId);
        $bpStmt->execute();
        $bpStmt->close();

        header('Location: index.php?tab=deleted');
        exit;
    }
}

// Auto purge posts soft-deleted > 30 days ago
$conn->query("DELETE FROM blog_posts WHERE is_deleted = 1 AND (deleted_at < NOW() - INTERVAL 30 DAY OR (deleted_at IS NULL AND created_at < NOW() - INTERVAL 30 DAY))");

// Active feed query
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
$activePosts = [];
if ($feedResult) {
    while ($r = $feedResult->fetch_assoc()) {
        $activePosts[] = $r;
    }
}
$feedStmt->close();

// Recently Deleted feed query for current logged-in user
$deletedPosts = [];
if ($isLoggedIn) {
    $delStmt = $conn->prepare(
        'SELECT bp.*, u.username, u.profile_pic
         FROM blog_posts bp
         JOIN users u ON u.id = bp.user_id
         WHERE bp.is_deleted = 1
           AND bp.user_id = ?
           AND (bp.deleted_at >= NOW() - INTERVAL 30 DAY OR bp.deleted_at IS NULL)
         ORDER BY bp.deleted_at DESC'
    );
    $delStmt->bind_param('i', $currentUserId);
    $delStmt->execute();
    $delRes = $delStmt->get_result();
    if ($delRes) {
        while ($dr = $delRes->fetch_assoc()) {
            $deletedPosts[] = $dr;
        }
    }
    $delStmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style/mystyle.css">
    <link rel="stylesheet" href="../style/moment-form.css">
    <link rel="stylesheet" href="../style/blog.css">
    <title>Cozy Coffee Co. — Coffee Memories</title>
    <style>
        body { background: #FAF6F0 !important; min-height: 100vh; }
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

    <div class="blog-hero">
        <div class="eyebrow">✦ Community Coffee Journal ✦</div>
        <h1><span class="gold-highlight">Coffee Memories</span></h1>
        <p>Check in and capture your cozy coffee moments with fellow coffee lovers.</p>
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
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
      <h2 style="margin: 0; font-family: var(--font-heading); color: #2C1C14; font-size: 1.6rem; font-weight: 800;">Community Check-ins</h2>
      <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <button type="button" class="btn btn-small btn-orange feed-filter-btn" data-filter="all">All Moments</button>
        <?php if ($isLoggedIn): ?>
          <button type="button" class="btn btn-small btn-outline feed-filter-btn" data-filter="my" data-user="<?php echo $currentUserId; ?>">My Memories</button>
          <button type="button" class="btn btn-small btn-outline feed-filter-btn" data-filter="deleted" data-user="<?php echo $currentUserId; ?>">🗑️ Recently Deleted</button>
        <?php endif; ?>
      </div>
    </div>

    <!-- ACTIVE POSTS FEED -->
    <?php if (!empty($activePosts)): ?>
        <?php foreach ($activePosts as $post): ?>
            <?php
              $pid = (int) $post['id'];
              $postPhotos = [];
              $photoStmt = $conn->prepare('SELECT id, image_path FROM blog_photos WHERE post_id = ?');
              $photoStmt->bind_param('i', $pid);
              $photoStmt->execute();
              $photoRes = $photoStmt->get_result();
              if ($photoRes && $photoRes->num_rows > 0) {
                  while ($photoRow = $photoRes->fetch_assoc()) {
                      $postPhotos[] = $photoRow;
                  }
              }
              $photoStmt->close();
              $photosJson = htmlspecialchars(json_encode($postPhotos), ENT_QUOTES, 'UTF-8');
            ?>
            <div class="post-card" data-user-id="<?php echo $post['user_id']; ?>">
                <div class="post-header">
                    <div class="author-info">
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
                        <button type="button" onclick="openEditPostModal(<?php echo $post['id']; ?>, '<?php echo htmlspecialchars(addslashes($post['ordered_item'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($post['mood'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($post['description'] ?? '')); ?>', <?php echo $photosJson; ?>)" style="margin-top:6px; padding: 4px 14px; font-size:0.8rem; border-radius: 20px; font-weight: 700; color: #C85A3E; border: 1px solid rgba(200,90,62,0.4); background: rgba(200,90,62,0.06); cursor: pointer; transition: all 0.2s ease;">✏️ Edit Post</button>
                      <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($post['ordered_item'])): ?>
                    <p class="ordered-tag" style="margin-top: 8px;">☕ Ordered: <span class="tag-chip tag-chip-sweet"><?php echo htmlspecialchars($post['ordered_item']); ?></span></p>
                <?php endif; ?>

                <?php if (!empty($post['description'])): ?>
                    <p style="line-height: 1.5; color: #333; margin-top: 6px;"><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
                <?php endif; ?>

                <?php if (!empty($postPhotos)): ?>
                    <div class="photo-gallery" id="gallery-<?php echo $pid; ?>">
                        <?php foreach ($postPhotos as $photo): ?>
                            <img src="../<?php echo htmlspecialchars($photo['image_path']); ?>" 
                                 alt="Coffee memory photo" 
                                 onclick="openLightbox(this, 'gallery-<?php echo $pid; ?>')">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color: #666; font-size: 0.95rem;">No check-ins yet. Be the first to post a memory!</p>
    <?php endif; ?>

    <!-- RECENTLY DELETED POST CARDS -->
    <?php if ($isLoggedIn && !empty($deletedPosts)): ?>
      <?php foreach ($deletedPosts as $dPost): ?>
        <?php
          $deletedTime = strtotime($dPost['deleted_at'] ?? $dPost['created_at']);
          $daysPassed = floor((time() - $deletedTime) / (60 * 60 * 24));
          $daysRemaining = max(0, 30 - $daysPassed);
        ?>
        <div class="post-card deleted-post-card" data-user-id="<?php echo $dPost['user_id']; ?>" style="display: none; border-left: 4px solid #DC2626; background: #FFF9F9;">
          <div style="background: rgba(220, 38, 38, 0.08); border-radius: 10px; padding: 10px 14px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; color: #991B1B; font-weight: 700; font-size: 0.84rem;">
            <span>⏳ Recently Deleted (<?php echo $daysRemaining; ?> days remaining until automatic permanent purge)</span>
            <div style="display: flex; gap: 8px;">
              <form action="index.php" method="POST" style="margin: 0;">
                <input type="hidden" name="form_type" value="restore_post">
                <input type="hidden" name="restore_post_id" value="<?php echo $dPost['id']; ?>">
                <button type="submit" style="background: #059669; color: #FFF; border: none; padding: 5px 14px; border-radius: 20px; font-weight: 700; font-size: 0.78rem; cursor: pointer; box-shadow: 0 2px 6px rgba(5,150,105,0.25);">🔄 Restore</button>
              </form>
              <form action="index.php" method="POST" style="margin: 0;" onsubmit="return confirm('Permanently delete this post forever? This action cannot be undone.')">
                <input type="hidden" name="form_type" value="permanent_delete">
                <input type="hidden" name="perm_delete_id" value="<?php echo $dPost['id']; ?>">
                <button type="submit" style="background: rgba(220, 38, 38, 0.15); color: #DC2626; border: 1px solid rgba(220, 38, 38, 0.4); padding: 5px 14px; border-radius: 20px; font-weight: 700; font-size: 0.78rem; cursor: pointer;">❌ Delete Forever</button>
              </form>
            </div>
          </div>

          <div class="post-header">
            <div class="author-info">
              <img src="../images/profiles/<?php echo htmlspecialchars($dPost['profile_pic'] ?: 'default.png'); ?>" class="profile-avatar">
              <div>
                <span class="post-author"><?php echo htmlspecialchars($dPost['username']); ?></span>
                <div style="margin-top: 4px; display:flex; gap: 4px; flex-wrap:wrap;">
                  <?php 
                    $moodList = array_map('trim', explode(',', $dPost['mood']));
                    foreach ($moodList as $mTag):
                      if (empty($mTag)) continue;
                  ?>
                    <span class="tag-chip tag-chip-mood"><?php echo htmlspecialchars($mTag); ?></span>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
            <small style="color: #888;"><?php echo date('M d, Y · g:i A', strtotime($dPost['created_at'])); ?></small>
          </div>

          <?php if (!empty($dPost['ordered_item'])): ?>
            <p class="ordered-tag">☕ Ordered: <span class="tag-chip tag-chip-sweet"><?php echo htmlspecialchars($dPost['ordered_item']); ?></span></p>
          <?php endif; ?>

          <?php if (!empty($dPost['description'])): ?>
            <p style="line-height: 1.5; color: #333; margin-top: 6px;"><?php echo nl2br(htmlspecialchars($dPost['description'])); ?></p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <!-- EMPTY TAB MESSAGES -->
    <div id="emptyMyMemoriesMsg" style="display: none; text-align: center; padding: 40px 20px; background: #ffffff; border-radius: 18px; border: 1.5px dashed #E5D9CC; margin-top: 10px;">
        <div style="font-size: 2.5rem; margin-bottom: 8px;">✨</div>
        <h3 style="color: #2C1C14; margin-bottom: 6px; font-weight: 800;">You haven't posted any coffee memories yet</h3>
        <p style="color: #666; font-size: 0.9rem;">Share your first coffee moment, rating, or photo using the form on the left!</p>
    </div>

    <div id="emptyDeletedMemoriesMsg" style="display: none; text-align: center; padding: 40px 20px; background: #ffffff; border-radius: 18px; border: 1.5px dashed #E5D9CC; margin-top: 10px;">
        <div style="font-size: 2.5rem; margin-bottom: 8px;">🗑️</div>
        <h3 style="color: #2C1C14; margin-bottom: 6px; font-weight: 800;">No Recently Deleted Posts</h3>
        <p style="color: #666; font-size: 0.9rem;">Posts you delete will stay here for 30 days so you can restore them anytime!</p>
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
    <div class="lightbox-counter" id="lightboxCounter">Photo 1 of 1</div>
</div>

<!-- HIDDEN FORM FOR SOFT DELETION -->
<form id="deletePostForm" action="index.php" method="POST" style="display: none;">
  <input type="hidden" name="form_type" value="delete_post">
  <input type="hidden" name="delete_post_id" id="deletePostFormId">
</form>

<!-- EDIT POST MODAL -->
<div id="editPostModal" class="modal-overlay">
  <div class="modal-content" style="max-width: 620px; padding: 36px 32px; border-radius: 24px; border: 1px solid #E5D9CC; background: #FFFFFF; box-shadow: 0 25px 70px rgba(0,0,0,0.3); max-height: 90vh; overflow-y: auto;">
    <button class="modal-close" onclick="document.getElementById('editPostModal').style.display='none'">&times;</button>
    <h3 style="font-family: var(--font-heading); font-size: 1.6rem; color: #2C1C14; margin-bottom: 20px; font-weight: 800;">✏️ Edit Coffee Moment</h3>
    <form action="" method="POST" enctype="multipart/form-data" onsubmit="return validateEditForm()">
      <input type="hidden" name="form_type" value="edit_post">
      <input type="hidden" name="edit_post_id" id="editPostId">
      
      <!-- Container for deleted photo IDs -->
      <div id="deletedPhotoInputs"></div>
      
      <!-- Ordered Item Selection -->
      <div class="form-group" style="margin-bottom: 18px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">Select What You Ordered</label>
        <?php if (!empty($userPurchases)): ?>
          <select id="editOrderedItemSelect" name="edit_ordered_item" class="form-select">
            <option value="">-- Select from menu --</option>
            <?php foreach ($userPurchases as $item): ?>
              <option value="<?php echo htmlspecialchars($item); ?>"><?php echo htmlspecialchars($item); ?></option>
            <?php endforeach; ?>
          </select>
        <?php else: ?>
          <input type="text" name="edit_ordered_item" id="editOrderedItem" class="form-control" placeholder="e.g. Dirty Latte">
        <?php endif; ?>
      </div>

      <!-- Mood Selection -->
      <div class="form-group" style="margin-bottom: 18px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:8px;">Update Feeling / Mood:</label>
        <div class="mood-checkbox-group">
          <?php foreach ($presetMoods as $key => $label): ?>
            <label class="mood-chip edit-mood-chip">
              <input type="checkbox" name="edit_mood_select[]" value="<?php echo htmlspecialchars($key); ?>">
              <span class="chip-label"><?php echo htmlspecialchars($label); ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Description -->
      <div class="form-group" style="margin-bottom: 18px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">Thoughts / Experience (Max 500 words)</label>
        <textarea name="edit_description" id="editDescription" class="form-control" rows="4" placeholder="Tell us about your coffee moment today..." onkeyup="checkEditWordCount()"></textarea>
        <small id="edit_word_counter" style="color: #666; display: block; text-align: right; margin-top: 4px;">0 / 500 words</small>
      </div>

      <!-- Existing Photos Management (Delete specific photos) -->
      <div id="existingPhotosSection" class="existing-photos-section" style="display: none; margin-bottom: 20px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">Current Photos (Click ✖ on any photo to remove it)</label>
        <div id="editExistingPhotosGrid" class="edit-photos-grid"></div>
      </div>

      <!-- Add New Photos -->
      <div class="form-group" style="margin-bottom: 24px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">Add New Photos (Optional, max 5 photos)</label>
        <input type="file" name="edit_photos[]" accept="image/*" multiple onchange="limitFiles(this)" style="font-size:0.88rem;">
      </div>

      <div style="display: flex; gap: 12px; margin-top: 10px;">
        <button type="submit" id="editSubmitBtn" class="btn-primary" style="flex: 2;">Save Changes ☕</button>
        <button type="button" onclick="confirmDeletePostFromModal()" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; border-radius: 12px; border: 1.5px solid rgba(220, 38, 38, 0.4); background: rgba(220, 38, 38, 0.06); color: #DC2626; font-weight: 800; font-size: 0.9rem; cursor: pointer; transition: all 0.2s ease;">
          🗑️ Delete Post
        </button>
      </div>
    </form>
  </div>
</div>

<!-- AUTHOR PROFILE MODAL -->
<div id="authorModal" class="modal-overlay">
  <div class="modal-content" style="max-width: 420px; padding: 25px; text-align: center; border-radius: 20px;">
    <button class="modal-close" onclick="document.getElementById('authorModal').style.display='none'">&times;</button>
    <img id="authorPic" src="" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #C85A3E; margin-bottom: 10px;">
    <h3 id="authorName" style="color: #2C1C14; margin-bottom: 4px; font-weight: 800;"></h3>
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
    
    const counter = document.getElementById('lightboxCounter');
    if (counter) {
        counter.innerText = `Photo ${currentImageIndex + 1} of ${currentGalleryImages.length}`;
        counter.style.display = currentGalleryImages.length > 1 ? 'block' : 'none';
    }

    const prevBtn = document.querySelector('.lightbox-prev');
    const nextBtn = document.querySelector('.lightbox-next');
    if (currentGalleryImages.length <= 1) {
        prevBtn.style.display = 'none';
        nextBtn.style.display = 'none';
    } else {
        prevBtn.style.display = 'flex';
        nextBtn.style.display = 'flex';
    }
}

function openEditPostModal(id, item, moodStr, desc, photos) {
    document.getElementById('editPostId').value = id;
    document.getElementById('deletePostFormId').value = id;
    
    // Clear previous deleted photo hidden inputs
    const deletedContainer = document.getElementById('deletedPhotoInputs');
    if (deletedContainer) deletedContainer.innerHTML = '';
    
    const selectElem = document.getElementById('editOrderedItemSelect');
    const inputElem = document.getElementById('editOrderedItem');
    if (selectElem) {
        selectElem.value = item;
    } else if (inputElem) {
        inputElem.value = item;
    }
    
    const descElem = document.getElementById('editDescription');
    if (descElem) {
        descElem.value = desc;
        checkEditWordCount();
    }
    
    const moods = moodStr ? moodStr.split(',').map(m => m.trim()) : [];
    document.querySelectorAll('#editPostModal input[name="edit_mood_select[]"]').forEach(cb => {
        cb.checked = moods.includes(cb.value);
    });

    // Populate existing photos preview grid with delete buttons
    const photoSection = document.getElementById('existingPhotosSection');
    const photoGrid = document.getElementById('editExistingPhotosGrid');
    if (photoGrid) {
        photoGrid.innerHTML = '';
        if (photos && photos.length > 0) {
            photos.forEach(photo => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'edit-photo-thumb';
                itemDiv.id = 'photo-thumb-' + photo.id;
                itemDiv.innerHTML = `
                    <img src="../${photo.image_path}" alt="Existing photo">
                    <button type="button" class="btn-delete-photo-thumb" onclick="removePhotoFromEdit(${photo.id})" title="Delete this photo">&times;</button>
                `;
                photoGrid.appendChild(itemDiv);
            });
            if (photoSection) photoSection.style.display = 'block';
        } else {
            if (photoSection) photoSection.style.display = 'none';
        }
    }

    document.getElementById('editPostModal').style.display = 'flex';
}

function removePhotoFromEdit(photoId) {
    const thumb = document.getElementById('photo-thumb-' + photoId);
    if (thumb) {
        thumb.style.transform = 'scale(0.7)';
        thumb.style.opacity = '0';
        setTimeout(() => { thumb.remove(); }, 200);
    }
    
    const container = document.getElementById('deletedPhotoInputs');
    if (container) {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'delete_photo_ids[]';
        hidden.value = photoId;
        container.appendChild(hidden);
    }
}

function confirmDeletePostFromModal() {
    const id = document.getElementById('editPostId').value;
    if (!id) return;
    
    if (confirm("Are you sure you want to delete this coffee moment?\n\nIt will be moved to 'Recently Deleted' where you can restore it within 30 days.")) {
        document.getElementById('deletePostFormId').value = id;
        document.getElementById('deletePostForm').submit();
    }
}

function checkEditWordCount() {
    const text = document.getElementById('editDescription').value.trim();
    const words = text ? text.split(/\s+/).length : 0;
    const counter = document.getElementById('edit_word_counter');
    const btn = document.getElementById('editSubmitBtn');
    if (counter) {
        counter.innerText = words + " / 500 words";
        if (words > 500) {
            counter.style.color = '#dc2626';
            counter.innerText = "✖ Exceeded limit (" + words + " / 500 words)";
            if (btn) btn.disabled = true;
        } else {
            counter.style.color = '#666';
            if (btn) btn.disabled = false;
        }
    }
}

function validateEditForm() {
    const text = document.getElementById('editDescription').value.trim();
    const words = text ? text.split(/\s+/).length : 0;
    if (words > 500) {
        alert('Description cannot exceed 500 words.');
        return false;
    }

    const checkedMoods = document.querySelectorAll('#editPostModal input[name="edit_mood_select[]"]:checked');
    if (checkedMoods.length === 0) {
        alert('Please select at least one mood.');
        return false;
    }

    return true;
}

function openAuthorModal(userId, username, picSrc) {
    document.getElementById('authorName').textContent = username;
    document.getElementById('authorPic').src = picSrc;
    document.getElementById('authorModal').style.display = 'flex';
}

// Feed Filter JS (All Moments vs My Memories vs Recently Deleted)
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
        const activePosts = document.querySelectorAll('.post-card:not(.deleted-post-card)');
        const deletedPosts = document.querySelectorAll('.deleted-post-card');
        let visibleCount = 0;

        if (filter === 'all') {
            activePosts.forEach(p => { p.style.display = 'block'; visibleCount++; });
            deletedPosts.forEach(p => { p.style.display = 'none'; });
        } else if (filter === 'my') {
            activePosts.forEach(p => {
                if (p.dataset.userId === currentUserId) {
                    p.style.display = 'block'; visibleCount++;
                } else {
                    p.style.display = 'none';
                }
            });
            deletedPosts.forEach(p => { p.style.display = 'none'; });
        } else if (filter === 'deleted') {
            activePosts.forEach(p => { p.style.display = 'none'; });
            deletedPosts.forEach(p => {
                p.style.display = 'block'; visibleCount++;
            });
        }

        const emptyMsg = document.getElementById('emptyMyMemoriesMsg');
        if (emptyMsg) {
            emptyMsg.style.display = (visibleCount === 0 && filter === 'my') ? 'block' : 'none';
        }
        const emptyDeletedMsg = document.getElementById('emptyDeletedMemoriesMsg');
        if (emptyDeletedMsg) {
            emptyDeletedMsg.style.display = (visibleCount === 0 && filter === 'deleted') ? 'block' : 'none';
        }
    });
});

// Check URL tab parameter on page load
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    if (tab === 'deleted') {
        const delBtn = document.querySelector('.feed-filter-btn[data-filter="deleted"]');
        if (delBtn) delBtn.click();
    } else if (tab === 'my') {
        const myBtn = document.querySelector('.feed-filter-btn[data-filter="my"]');
        if (myBtn) myBtn.click();
    }
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

<?php require_once '../includes/footer.php'; ?>

</body>
</html>
