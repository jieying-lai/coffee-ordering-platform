<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php'; // sets $isLoggedIn, $currentUserId, $currentUsername

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

// HELPER: RENDER USER AVATAR OR GENERATE INITIAL LETTER AVATAR
function renderUserAvatarHtml($pic, $name, $size = 44, $extraClass = '', $extraAttrs = '') {
    $firstLetter = strtoupper(mb_substr(trim($name ?: 'C'), 0, 1));
    
    $hasPic = false;
    $src = '';
    if (!empty($pic) && $pic !== 'default.png') {
        if (strpos($pic, 'uploads/') === 0 || strpos($pic, 'images/') === 0) {
            $path = '../' . ltrim($pic, '/');
            if (file_exists($path)) {
                $hasPic = true;
                $src = $path;
            }
        } else {
            $path = '../images/profiles/' . $pic;
            if (file_exists($path)) {
                $hasPic = true;
                $src = $path;
            }
        }
    }
    
    if ($hasPic) {
        return '<img src="' . htmlspecialchars($src) . '" class="profile-avatar ' . $extraClass . '" style="width:' . $size . 'px; height:' . $size . 'px; border-radius:50%; object-fit:cover; border:2px solid #C85A3E; flex-shrink:0; box-shadow:0 4px 10px rgba(200,90,62,0.2);" alt="Avatar" ' . $extraAttrs . '>';
    } else {
        return '<div class="initial-avatar-badge ' . $extraClass . '" style="width:' . $size . 'px; height:' . $size . 'px; border-radius:50%; background:linear-gradient(135deg, #C85A3E 0%, #A8472F 100%); color:#FFFFFF; font-weight:800; font-size:' . round($size * 0.44) . 'px; display:inline-flex; align-items:center; justify-content:center; border:2px solid #C85A3E; flex-shrink:0; text-transform:uppercase; box-shadow:0 4px 10px rgba(200,90,62,0.2);" ' . $extraAttrs . '>' . htmlspecialchars($firstLetter) . '</div>';
    }
}

// FETCH LAST 4 USER ORDERS (GROUPED BY ORDER_ID AND DATE)
$userOrders = [];
if ($isLoggedIn) {
    $ordersSql = "SELECT o.order_id, o.order_date 
                  FROM orders o 
                  WHERE o.user_id = ? 
                  ORDER BY o.order_date DESC 
                  LIMIT 4";
    $oStmt = $conn->prepare($ordersSql);
    $oStmt->bind_param("i", $currentUserId);
    $oStmt->execute();
    $oRes = $oStmt->get_result();
    
    $orderIndex = 0;
    while ($ord = $oRes->fetch_assoc()) {
        $orderId = (int)$ord['order_id'];
        $orderDate = $ord['order_date'];
        
        if ($orderIndex === 0) {
            $label = '(last ordered)';
        } else {
            $label = '(ordered on ' . date('j F', strtotime($orderDate)) . ')';
        }
        
        $itemsSql = "SELECT DISTINCT m.name 
                     FROM order_items oi 
                     JOIN menu_items m ON m.item_id = oi.item_id 
                     WHERE oi.order_id = ?";
        $iStmt = $conn->prepare($itemsSql);
        $iStmt->bind_param("i", $orderId);
        $iStmt->execute();
        $iRes = $iStmt->get_result();
        $items = [];
        while ($ir = $iRes->fetch_assoc()) {
            $items[] = $ir['name'];
        }
        $iStmt->close();
        
        if (!empty($items)) {
            $userOrders[] = [
                'order_id' => $orderId,
                'order_date' => $orderDate,
                'label' => $label,
                'items' => $items
            ];
            $orderIndex++;
        }
    }
    $oStmt->close();

    if (empty($userOrders)) {
        $placeholderSql = "SELECT name FROM menu_items ORDER BY display_order ASC LIMIT 6";
        $result = $conn->query($placeholderSql);
        $pItems = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $pItems[] = $row['name'];
            }
        }
        if (!empty($pItems)) {
            $userOrders[] = [
                'order_id' => 9999,
                'order_date' => date('Y-m-d H:i:s'),
                'label' => '(menu items)',
                'items' => $pItems
            ];
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
        $orderedItemsArr = $_POST['ordered_items'] ?? [];
        if (!is_array($orderedItemsArr)) {
            if (!empty($_POST['ordered_item'])) {
                $orderedItemsArr = array_filter(array_map('trim', explode(',', $_POST['ordered_item'])));
            } else {
                $orderedItemsArr = [];
            }
        }
        $orderedItem = implode(', ', array_filter(array_map('trim', $orderedItemsArr)));

        if (empty($orderedItem)) {
            $momentErrors[] = 'Please select what you have ordered.';
        }

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
            $momentErrors[] = 'Please select or enter how you are feeling.';
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
                                $filename   = 'blog_' . time() . '_' . $i . '.' . $ext;
                                $targetFile = $uploadDir . $filename;

                                if (move_uploaded_file($_FILES['photos']['tmp_name'][$i], $targetFile)) {
                                    $uploadedFiles[] = 'uploads/blog/' . $filename;
                                } else {
                                    $momentErrors[] = 'Failed to move uploaded file ' . htmlspecialchars($_FILES['photos']['name'][$i]);
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        // Insert into Database
        if (empty($momentErrors)) {
            $stmt = $conn->prepare("INSERT INTO blog_posts (user_id, ordered_item, mood, description, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("isss", $currentUserId, $orderedItem, $mood, $description);

            if ($stmt->execute()) {
                $newPostId = $stmt->insert_id;
                $stmt->close();

                if (!empty($uploadedFiles)) {
                    $pStmt = $conn->prepare("INSERT INTO blog_photos (post_id, image_path) VALUES (?, ?)");
                    foreach ($uploadedFiles as $relPath) {
                        $pStmt->bind_param("is", $newPostId, $relPath);
                        $pStmt->execute();
                    }
                    $pStmt->close();
                }

                header("Location: index.php?msg=posted");
                exit();
            } else {
                $momentErrors[] = 'Database error: ' . $conn->error;
            }
        }
    }
}

// =========================================================================
// HANDLE EDIT MOMENT SUBMISSION
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'edit_moment') {
    $editId = (int)($_POST['edit_post_id'] ?? 0);
    $editItem = trim($_POST['edit_ordered_item'] ?? '');
    $editMood = trim($_POST['edit_mood'] ?? '');
    $editDesc = trim($_POST['edit_description'] ?? '');

    if ($isLoggedIn && $editId > 0) {
        $uStmt = $conn->prepare("UPDATE blog_posts SET ordered_item = ?, mood = CASE WHEN ? != '' THEN ? ELSE mood END, description = ? WHERE id = ? AND user_id = ?");
        $uStmt->bind_param("ssssii", $editItem, $editMood, $editMood, $editDesc, $editId, $currentUserId);
        $uStmt->execute();
        $uStmt->close();

        // Delete Selected Photos during Edit
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

        // New Photos Attachment during Edit
        if (!empty($_FILES['edit_photos']['name'][0])) {
            $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $uploadDir = '../uploads/blog/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $totalFiles = count($_FILES['edit_photos']['name']);
            for ($i = 0; $i < min($totalFiles, 5); $i++) {
                if ($_FILES['edit_photos']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['edit_photos']['name'][$i], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowedExt, true)) {
                        $filename = 'blog_edit_' . time() . '_' . $i . '.' . $ext;
                        $targetFile = $uploadDir . $filename;
                        if (move_uploaded_file($_FILES['edit_photos']['tmp_name'][$i], $targetFile)) {
                            $relPath = 'uploads/blog/' . $filename;
                            $pStmt = $conn->prepare("INSERT INTO blog_photos (post_id, image_path) VALUES (?, ?)");
                            $pStmt->bind_param("is", $editId, $relPath);
                            $pStmt->execute();
                            $pStmt->close();
                        }
                    }
                }
            }
        }

        header("Location: index.php?msg=updated#post-" . $editId);
        exit();
    }
}

// =========================================================================
// HANDLE SOFT DELETE & RESTORE & PERM DELETE
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'soft_delete') {
    $delId = (int)($_POST['delete_post_id'] ?? 0);
    if ($isLoggedIn && $delId > 0) {
        $stmt = $conn->prepare("UPDATE blog_posts SET is_deleted = 1, deleted_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $delId, $currentUserId);
        $stmt->execute();
        $stmt->close();
        header("Location: index.php?msg=soft_deleted");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'restore_post') {
    $restId = (int)($_POST['restore_post_id'] ?? 0);
    if ($isLoggedIn && $restId > 0) {
        $stmt = $conn->prepare("UPDATE blog_posts SET is_deleted = 0, deleted_at = NULL WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $restId, $currentUserId);
        $stmt->execute();
        $stmt->close();
        header("Location: index.php?msg=restored#post-" . $restId);
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'permanent_delete') {
    $permId = (int)($_POST['perm_delete_id'] ?? 0);
    if ($isLoggedIn && $permId > 0) {
        $photoQuery = $conn->prepare("SELECT image_path FROM blog_photos WHERE post_id = ?");
        $photoQuery->bind_param("i", $permId);
        $photoQuery->execute();
        $photoResult = $photoQuery->get_result();

        if ($photoResult) {
            while ($photo = $photoResult->fetch_assoc()) {
                $filePath = '../' . $photo['image_path'];
                if (file_exists($filePath) && strpos($photo['image_path'], 'uploads/') === 0) {
                    @unlink($filePath);
                }
            }
        }
        $photoQuery->close();

        $delPhotos = $conn->prepare("DELETE FROM blog_photos WHERE post_id = ?");
        $delPhotos->bind_param("i", $permId);
        $delPhotos->execute();
        $delPhotos->close();

        $delPost = $conn->prepare("DELETE FROM blog_posts WHERE id = ? AND user_id = ? AND is_deleted = 1");
        $delPost->bind_param("ii", $permId, $currentUserId);
        $delPost->execute();
        $delPost->close();

        header("Location: index.php?msg=perm_deleted");
        exit();
    }
}

// FETCH ACTIVE PUBLIC POSTS (WITH USER VISITS AND SHARES COUNTS)
$feedQuery = "SELECT bp.*, u.username, u.fullname, u.profile_pic,
                     (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS visits_count,
                     (SELECT COUNT(*) FROM blog_posts p WHERE p.user_id = u.id AND p.is_deleted = 0 AND p.is_hidden = 0) AS shares_count
              FROM blog_posts bp 
              JOIN users u ON u.id = bp.user_id 
              WHERE bp.is_hidden = 0 AND bp.is_deleted = 0
              ORDER BY bp.created_at DESC";
$feedPosts = [];
$res = $conn->query($feedQuery);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $feedPosts[] = $row;
    }
}

// FETCH RECENTLY DELETED POSTS
$deletedPosts = [];
if ($isLoggedIn) {
    $delQuery = "SELECT bp.*, u.username, u.fullname, u.profile_pic,
                        (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS visits_count,
                        (SELECT COUNT(*) FROM blog_posts p WHERE p.user_id = u.id AND p.is_deleted = 0 AND p.is_hidden = 0) AS shares_count
                 FROM blog_posts bp 
                 JOIN users u ON u.id = bp.user_id 
                 WHERE bp.user_id = ? AND bp.is_deleted = 1
                 ORDER BY bp.deleted_at DESC";
    $dStmt = $conn->prepare($delQuery);
    $dStmt->bind_param("i", $currentUserId);
    $dStmt->execute();
    $dRes = $dStmt->get_result();
    if ($dRes) {
        while ($dRow = $dRes->fetch_assoc()) {
            $deletedPosts[] = $dRow;
        }
    }
    $dStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/programs.css">
  <link rel="stylesheet" href="../style/blog.css">
  <title>Cozy Coffee Co. — Community Coffee Journal</title>
  <style>
    body { background: #FAF6F0 !important; min-height: 100vh; }
    
    .ordered-item-chip {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 8px 14px;
      background: #FFFFFF;
      border: 1.5px solid #E8DDD0;
      border-radius: 20px;
      cursor: pointer;
      font-size: 0.85rem;
      font-weight: 700;
      color: #2C1C14;
      transition: all 0.2s ease;
      user-select: none;
      box-shadow: 0 2px 8px rgba(60, 42, 33, 0.04);
    }
    
    .ordered-item-chip:hover {
      border-color: #C85A3E;
      transform: translateY(-1px);
    }

    .ordered-item-chip:has(input:checked) {
      background: linear-gradient(135deg, #C85A3E, #A8472F) !important;
      color: #FFFFFF !important;
      border-color: #C85A3E !important;
      box-shadow: 0 4px 14px rgba(200, 90, 62, 0.35) !important;
    }

    .ordered-item-chip.disabled-chip {
      opacity: 0.45 !important;
      background: #F3F4F6 !important;
      border-color: #E5E7EB !important;
      color: #9CA3AF !important;
      cursor: not-allowed !important;
      box-shadow: none !important;
    }

    /* AUTHOR AVATAR & USERNAME CLICKABLE POPUP STYLE */
    .profile-avatar-clickable, .author-username-clickable, .initial-avatar-badge {
      cursor: pointer !important;
      transition: transform 0.2s ease, filter 0.2s ease;
    }
    .profile-avatar-clickable:hover, .initial-avatar-badge:hover {
      transform: scale(1.08);
      box-shadow: 0 6px 16px rgba(200, 90, 62, 0.4) !important;
    }
    .author-username-clickable:hover {
      color: #C85A3E !important;
      text-decoration: underline !important;
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
                        <div class="form-errors" style="color: #DC2626; background: #FEE2E2; padding: 12px 16px; border-radius: 12px; margin-bottom: 16px; font-size: 0.88rem; font-weight: 700;">
                            <?php foreach ($momentErrors as $err): ?>
                                <p style="margin: 0;"><?php echo htmlspecialchars($err); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form action="index.php" method="POST" enctype="multipart/form-data" class="moment-form" onsubmit="return validateForm(event)">
                        <input type="hidden" name="form_type" value="moment">

                        <!-- Required Purchase Selection Grouped by #Order Number -->
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="font-weight: 800; color: #2C1C14; display: block; margin-bottom: 4px;">Select What You Ordered <span style="color: #DC2626;">*</span></label>
                            <div style="font-size: 0.78rem; color: #7A685A; margin-bottom: 10px;">Select item(s) from your last 4 orders. Multi-selected items must belong to the same order.</div>
                            
                            <div class="ordered-items-selection-wrap" style="display: flex; flex-direction: column; gap: 14px; max-height: 230px; overflow-y: auto; padding: 14px; background: #FAF7F2; border: 1.5px solid #E8DDD0; border-radius: 16px;">
                              <?php if (!empty($userOrders)): ?>
                                <?php foreach ($userOrders as $order): ?>
                                  <div>
                                    <!-- ORDER HEADER WITH LABEL ONCE RIGHT AFTER #ORDER ID -->
                                    <div style="font-size: 0.8rem; font-weight: 800; color: #2C1C14; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                                      <span style="background: #C85A3E; color: #FFF; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 800;">#Order <?php echo $order['order_id']; ?></span>
                                      <span style="font-weight: 600; color: #8C7A6D; font-size: 0.78rem;"><?php echo htmlspecialchars($order['label']); ?></span>
                                    </div>
                                    <!-- CLEAN FOOD ITEM BUTTONS -->
                                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                      <?php foreach ($order['items'] as $itemName): ?>
                                        <label class="ordered-item-chip" data-order-id="<?php echo $order['order_id']; ?>">
                                          <input type="checkbox" name="ordered_items[]" value="<?php echo htmlspecialchars($itemName); ?>" onchange="handleOrderedItemSelection(this)" style="display: none;">
                                          <span class="chip-title">☕ <?php echo htmlspecialchars($itemName); ?></span>
                                        </label>
                                      <?php endforeach; ?>
                                    </div>
                                  </div>
                                <?php endforeach; ?>
                              <?php else: ?>
                                <div style="color: #7A685A; font-size: 0.85rem; padding: 10px; text-align: center;">
                                  No recent orders found. <a href="../menu/index.php" style="color: #C85A3E; font-weight: 700;">Browse menu to order!</a>
                                </div>
                              <?php endif; ?>
                            </div>
                            <div id="orderedItemsError" class="field-error-msg" style="color: #DC2626; font-size: 0.8rem; font-weight: 700; margin-top: 6px; display: none;">⚠️ Please select what you have ordered.</div>
                        </div>

                        <!-- Mood Selection -->
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="font-weight: 800; color: #2C1C14; display: block; margin-bottom: 6px;">How are you feeling? <span style="color: #DC2626;">*</span></label>
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
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="description" style="font-weight: 800; color: #2C1C14; display: block; margin-bottom: 6px;">Your thoughts (Optional, max 500 words)</label>
                            <textarea id="description" name="description" rows="4" placeholder="Tell us about your coffee moment today..." onkeyup="checkWordCount()"></textarea>
                            <small id="word_counter" style="color: #666; margin-top: 4px; display: block;">0 / 500 words</small>
                        </div>

                        <!-- CLEAN & UN-CRAMPED PHOTO UPLOAD SECTION -->
                        <div class="form-group" style="margin-bottom: 26px;">
                            <label style="font-weight: 800; color: #2C1C14; display: block; margin-bottom: 8px;">Upload Photos (Optional, max 5 photos total)</label>
                            
                            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                                <label for="photos" class="btn-custom-upload" style="cursor: pointer; padding: 10px 18px; background: #FAF4EB; border: 1.5px solid #E8DDD0; border-radius: 20px; font-size: 0.85rem; font-weight: 800; color: #4A3B32; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(60,42,33,0.04);">
                                    <span>📷</span> Add Photos
                                </label>
                                <input type="file" id="photos" name="photos[]" accept="image/*" multiple onchange="handleFileAccumulate('create')" style="display: none;">
                                <span id="photoCountLabel" style="font-size: 0.82rem; color: #7A685A; font-weight: 600;">No photos selected</span>
                            </div>
                            
                            <!-- ACCUMULATED LIVE PHOTO PREVIEW GRID -->
                            <div id="createPreviewGrid" style="display: none; gap: 10px; flex-wrap: wrap; margin-top: 14px; padding: 12px; background: #FAF7F2; border: 1.5px dashed #E8DDD0; border-radius: 14px;"></div>
                        </div>

                        <button type="submit" class="cta-btn cta-btn-primary" style="width: 100%; justify-content: center; height: 48px; border-radius: 25px;">Share Moment ✨</button>
                    </form>
                </section>
            <?php endif; ?>
        </div>

        <!-- RIGHT COLUMN: FEED POST CARDS -->
        <div class="feed-column">
            
            <div class="feed-filter-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <h2 style="font-family: var(--font-heading); color: #2C1C14; font-size: 1.3rem; margin: 0; font-weight: 800;">
                        Community Feed
                    </h2>
                </div>
                
                <?php if ($isLoggedIn): ?>
                    <div style="display: flex; gap: 6px; background: #FFFFFF; padding: 4px; border-radius: 20px; border: 1.5px solid #E8DDD0;">
                        <button type="button" id="tabAllPosts" onclick="switchFeedTab('all')" style="padding: 6px 14px; border-radius: 16px; border: none; font-weight: 800; font-size: 0.8rem; cursor: pointer; background: #C85A3E; color: #FFFFFF;">
                            All Moments
                        </button>
                        <button type="button" id="tabMyPosts" onclick="switchFeedTab('mine')" style="padding: 6px 14px; border-radius: 16px; border: none; font-weight: 800; font-size: 0.8rem; cursor: pointer; background: transparent; color: #665447;">
                            My Posts
                        </button>
                        <?php if (!empty($deletedPosts)): ?>
                            <button type="button" id="tabDeletedPosts" onclick="switchFeedTab('deleted')" style="padding: 6px 14px; border-radius: 16px; border: none; font-weight: 800; font-size: 0.8rem; cursor: pointer; background: transparent; color: #DC2626;">
                                🗑️ Recently Deleted (<?php echo count($deletedPosts); ?>)
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- PUBLIC FEED POSTS -->
            <?php if (!empty($feedPosts)): ?>
                <?php foreach ($feedPosts as $post): ?>
                    <?php 
                        $pid = (int)$post['id'];
                        $isOwnPost = ($isLoggedIn && (int)$post['user_id'] === (int)$currentUserId);
                        
                        $pPhotosRes = $conn->query("SELECT id, image_path FROM blog_photos WHERE post_id = $pid");
                        $postPhotos = [];
                        if ($pPhotosRes) {
                            while ($pr = $pPhotosRes->fetch_assoc()) {
                                $postPhotos[] = $pr;
                            }
                        }
                        $photosJson = htmlspecialchars(json_encode($postPhotos), ENT_QUOTES, 'UTF-8');
                        
                        $vCount = (int)($post['visits_count'] ?? 0);
                        $sCount = (int)($post['shares_count'] ?? 0);
                        
                        $authorDisplayName = !empty($post['fullname']) ? $post['fullname'] : $post['username'];
                        
                        $uNameEsc = htmlspecialchars(addslashes($post['username']), ENT_QUOTES);
                        $fNameEsc = htmlspecialchars(addslashes($post['fullname'] ?? ''), ENT_QUOTES);
                        $pPicEsc  = htmlspecialchars(addslashes($post['profile_pic'] ?: 'default.png'), ENT_QUOTES);
                        $clickAttr = "onclick=\"openUserSummaryModal('$uNameEsc', '$fNameEsc', '$pPicEsc', $vCount, $sCount)\"";
                    ?>
                    <div class="post-card" id="post-<?php echo $pid; ?>" data-user-id="<?php echo $post['user_id']; ?>" data-username="<?php echo htmlspecialchars(strtolower($post['username'])); ?>">
                        <div class="post-header">
                            <div class="author-info">
                                <!-- AVATAR (PHOTO OR INITIAL LETTER) -->
                                <?php echo renderUserAvatarHtml($post['profile_pic'], $authorDisplayName, 44, 'profile-avatar-clickable', $clickAttr); ?>
                                <div>
                                    <!-- DISPLAY FULL NAME AS PRIMARY TITLE IN FEED LIST -->
                                    <span class="post-author author-username-clickable" 
                                          title="Click to view user summary for <?php echo htmlspecialchars($authorDisplayName); ?>" 
                                          <?php echo $clickAttr; ?>><?php echo htmlspecialchars($authorDisplayName); ?></span>
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
                            
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <small style="color: #888; font-weight: 600;"><?php echo date('M d, Y · g:i A', strtotime($post['created_at'])); ?></small>
                                
                                <?php if ($isOwnPost): ?>
                                    <div style="display: flex; gap: 6px;">
                                        <button type="button" onclick="openUserEditModal(<?php echo $pid; ?>, '<?php echo htmlspecialchars(addslashes($post['ordered_item'])); ?>', '<?php echo htmlspecialchars(addslashes($post['mood'])); ?>', '<?php echo htmlspecialchars(addslashes($post['description'])); ?>', <?php echo $photosJson; ?>)" style="padding: 4px 10px; background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE; border-radius: 12px; font-size: 0.75rem; font-weight: 800; cursor: pointer;">Edit</button>
                                        
                                        <form action="index.php" method="POST" style="margin: 0;" onsubmit="return confirm('Move this post to Recently Deleted?')">
                                            <input type="hidden" name="form_type" value="soft_delete">
                                            <input type="hidden" name="delete_post_id" value="<?php echo $pid; ?>">
                                            <button type="submit" style="padding: 4px 10px; background: #FEF2F2; color: #DC2626; border: 1px solid #FCA5A5; border-radius: 12px; font-size: 0.75rem; font-weight: 800; cursor: pointer;">Delete</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- ORDERED ITEMS SEPARATE BADGE PILLS -->
                        <?php if (!empty($post['ordered_item'])): ?>
                            <?php $orderedItemsList = array_filter(array_map('trim', explode(',', $post['ordered_item']))); ?>
                            <div class="ordered-items-feed-row" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 10px;">
                                <span style="font-weight: 800; font-size: 0.8rem; color: #8C7A6D;">Ordered:</span>
                                <?php foreach ($orderedItemsList as $singleOrdItem): ?>
                                    <span class="ordered-item-badge-pill" style="padding: 4px 12px; border-radius: 18px; background: #FAF4EB; border: 1.5px solid #E8DDD0; color: #4A3B32; font-size: 0.82rem; font-weight: 800; display: inline-flex; align-items: center; gap: 4px; box-shadow: 0 2px 6px rgba(60,42,33,0.03);">
                                        ☕ <?php echo htmlspecialchars($singleOrdItem); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($post['description'])): ?>
                            <p style="line-height: 1.55; color: #333; margin-top: 10px; font-size: 0.92rem;"><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
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
                  $dvCount = (int)($dPost['visits_count'] ?? 0);
                  $dsCount = (int)($dPost['shares_count'] ?? 0);
                  
                  $dAuthorDisplayName = !empty($dPost['fullname']) ? $dPost['fullname'] : $dPost['username'];

                  $duNameEsc = htmlspecialchars(addslashes($dPost['username']), ENT_QUOTES);
                  $dfNameEsc = htmlspecialchars(addslashes($dPost['fullname'] ?? ''), ENT_QUOTES);
                  $dpPicEsc  = htmlspecialchars(addslashes($dPost['profile_pic'] ?: 'default.png'), ENT_QUOTES);
                  $dClickAttr = "onclick=\"openUserSummaryModal('$duNameEsc', '$dfNameEsc', '$dpPicEsc', $dvCount, $dsCount)\"";
                ?>
                <div class="post-card deleted-post-card" data-user-id="<?php echo $dPost['user_id']; ?>" data-username="<?php echo htmlspecialchars(strtolower($dPost['username'])); ?>" style="display: none; border-left: 4px solid #DC2626; background: #FFF9F9;">
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
                      <?php echo renderUserAvatarHtml($dPost['profile_pic'], $dAuthorDisplayName, 44, 'profile-avatar-clickable', $dClickAttr); ?>
                      <div>
                        <span class="post-author author-username-clickable" 
                              title="Click to view user summary for <?php echo htmlspecialchars($dAuthorDisplayName); ?>" 
                              <?php echo $dClickAttr; ?>><?php echo htmlspecialchars($dAuthorDisplayName); ?></span>
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
                    <div class="ordered-items-feed-row" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 10px;">
                        <span style="font-weight: 800; font-size: 0.8rem; color: #8C7A6D;">Ordered:</span>
                        <?php foreach (array_filter(array_map('trim', explode(',', $dPost['ordered_item']))) as $singleOrdItem): ?>
                            <span class="ordered-item-badge-pill" style="padding: 4px 12px; border-radius: 18px; background: #FAF4EB; border: 1.5px solid #E8DDD0; color: #4A3B32; font-size: 0.82rem; font-weight: 800; display: inline-flex; align-items: center; gap: 4px;">
                                ☕ <?php echo htmlspecialchars($singleOrdItem); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                  <?php endif; ?>

                  <?php if (!empty($dPost['description'])): ?>
                    <p style="line-height: 1.5; color: #333; margin-top: 6px;"><?php echo nl2br(htmlspecialchars($dPost['description'])); ?></p>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>

        </div>

    </div>

</div>

<!-- USER SUMMARY POPUP MODAL -->
<div id="userSummaryModal" class="program-modal-overlay" onclick="closeModalOnOutsideClick(event, 'userSummaryModal')">
  <div class="program-modal-card" style="max-width: 360px; padding: 30px 24px; text-align: center; border-radius: 28px;">
    <button type="button" class="program-modal-close" onclick="closeModal('userSummaryModal')">&times;</button>
    
    <div id="summaryAvatarWrap" style="margin-bottom: 12px; display: flex; justify-content: center;"></div>
    
    <!-- FIRST LINE: FULL NAME (HEADER) -->
    <h3 id="summaryFullname" style="font-family: var(--font-heading); color: #2C1C14; margin: 0 0 2px 0; font-size: 1.25rem; font-weight: 800;">Full Name</h3>
    <!-- SECOND LINE: @USERNAME (SUBTITLE BELOW) -->
    <div id="summaryUsername" style="font-size: 0.84rem; color: #8A7769; font-weight: 600; margin-bottom: 22px;">@username</div>
    
    <!-- 2 STAT CARDS SIDE BY SIDE -->
    <div style="display: flex; gap: 10px; justify-content: center;">
      <div style="flex: 1; background: #FAF7F2; border: 1.5px solid #E8DDD0; border-radius: 18px; padding: 14px 8px;">
        <div style="font-size: 1.4rem; margin-bottom: 2px;">☕</div>
        <div id="summaryVisitsCount" style="font-weight: 800; font-size: 1.15rem; color: #C85A3E;">0 times</div>
        <div style="font-size: 0.74rem; color: #7A685A; font-weight: 700; margin-top: 2px;">Orders</div>
      </div>
      
      <div style="flex: 1; background: #FAF7F2; border: 1.5px solid #E8DDD0; border-radius: 18px; padding: 14px 8px;">
        <div style="font-size: 1.4rem; margin-bottom: 2px;">✨</div>
        <div id="summarySharesCount" style="font-weight: 800; font-size: 1.15rem; color: #C85A3E;">0 times</div>
        <div style="font-size: 0.74rem; color: #7A685A; font-weight: 700; margin-top: 2px;">Moments Shared</div>
      </div>
    </div>
  </div>
</div>

<!-- USER EDIT MOMENT MODAL -->
<div id="userEditModal" class="program-modal-overlay" onclick="closeModalOnOutsideClick(event, 'userEditModal')">
  <div class="program-modal-card" style="max-width: 520px; padding: 26px;">
    <button type="button" class="program-modal-close" onclick="closeModal('userEditModal')">&times;</button>
    <h3 style="font-family: var(--font-heading); color: #2C1C14; margin-top: 0; margin-bottom: 18px; font-size: 1.25rem; font-weight: 800;">Edit Coffee Moment</h3>
    
    <form action="index.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="form_type" value="edit_moment">
      <input type="hidden" name="edit_post_id" id="editPostId">
      <div id="editDeletedPhotoInputs"></div>

      <div class="form-group" style="margin-bottom: 14px;">
        <label style="font-weight: 800; color: #2C1C14; display: block; margin-bottom: 4px;">Ordered Items</label>
        <input type="text" name="edit_ordered_item" id="editOrderedItem" style="width: 100%; padding: 10px 14px; border: 1.5px solid #E8DDD0; border-radius: 12px; font-size: 0.9rem;">
      </div>

      <div class="form-group" style="margin-bottom: 14px;">
        <label style="font-weight: 800; color: #2C1C14; display: block; margin-bottom: 4px;">Mood / Feeling</label>
        <input type="text" name="edit_mood" id="editMood" style="width: 100%; padding: 10px 14px; border: 1.5px solid #E8DDD0; border-radius: 12px; font-size: 0.9rem;">
      </div>

      <div class="form-group" style="margin-bottom: 14px;">
        <label style="font-weight: 800; color: #2C1C14; display: block; margin-bottom: 4px;">Description</label>
        <textarea name="edit_description" id="editDescription" rows="4" style="width: 100%; padding: 10px 14px; border: 1.5px solid #E8DDD0; border-radius: 12px; font-size: 0.9rem;"></textarea>
      </div>

      <div id="editExistingPhotosSection" style="display:none; margin-bottom:16px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">Current Photos (Click ✖ to remove)</label>
        <div id="editExistingPhotosGrid" style="display:flex; gap:10px; flex-wrap:wrap;"></div>
      </div>

      <div class="form-group" style="margin-bottom: 20px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">Add New Photos (Optional, max 5 total)</label>
        <input type="file" name="edit_photos[]" id="editPhotosInput" accept="image/*" multiple onchange="handleFileAccumulate('edit')">
        <div id="editPreviewGrid" style="display: none; gap: 10px; flex-wrap: wrap; margin-top: 12px; padding: 12px; background: #FAF7F2; border: 1.5px dashed #E8DDD0; border-radius: 14px;"></div>
      </div>

      <button type="submit" class="cta-btn cta-btn-primary" style="width: 100%; justify-content: center; height: 44px; border-radius: 25px;">Save Post Changes ✨</button>
    </form>
  </div>
</div>

<script>
let createStagedFiles = [];
let editStagedFiles = [];

// CLICK AUTHOR AVATAR TO OPEN USER SUMMARY POPUP MODAL
function openUserSummaryModal(username, fullname, profilePic, visits, shares) {
    const dispName = fullname || username;
    document.getElementById('summaryFullname').textContent = dispName;
    document.getElementById('summaryUsername').textContent = '@' + username;
    
    const initialLetter = (dispName || 'C').charAt(0).toUpperCase();
    const avatarWrap = document.getElementById('summaryAvatarWrap');
    
    let hasPic = false;
    let picPath = '';
    if (profilePic && profilePic !== 'default.png') {
        picPath = '../images/profiles/' + profilePic;
        if (profilePic.indexOf('uploads/') === 0 || profilePic.indexOf('images/') === 0) {
            picPath = '../' + profilePic.replace(/^\//, '');
        }
        hasPic = true;
    }
    
    if (hasPic) {
        avatarWrap.innerHTML = `<img src="${picPath}" style="width:76px; height:76px; border-radius:50%; object-fit:cover; border:3px solid #C85A3E; box-shadow:0 8px 20px rgba(200,90,62,0.22);">`;
    } else {
        avatarWrap.innerHTML = `<div style="width:76px; height:76px; border-radius:50%; background:linear-gradient(135deg, #C85A3E 0%, #A8472F 100%); color:#FFF; font-weight:800; font-size:32px; display:inline-flex; align-items:center; justify-content:center; border:3px solid #C85A3E; box-shadow:0 8px 20px rgba(200,90,62,0.22); margin:0 auto;">${initialLetter}</div>`;
    }
    
    document.getElementById('summaryVisitsCount').textContent = visits + ' times';
    document.getElementById('summarySharesCount').textContent = shares + ' times';
    
    openModal('userSummaryModal');
}

function validateForm(e) {
    const checkedItems = document.querySelectorAll('input[name="ordered_items[]"]:checked');
    const errEl = document.getElementById('orderedItemsError');
    
    if (checkedItems.length === 0) {
        if (errEl) errEl.style.display = 'block';
        showToast('⚠️ Please select what you have ordered.', 'error');
        if (e) e.preventDefault();
        return false;
    } else {
        if (errEl) errEl.style.display = 'none';
    }

    const checkedMoods = document.querySelectorAll('input[name="mood_select[]"]:checked');
    if (checkedMoods.length === 0) {
        showToast('⚠️ Please select how you are feeling.', 'error');
        if (e) e.preventDefault();
        return false;
    }

    syncInputFiles('create');
    return true;
}

// MULTI-SELECT ORDERED ITEMS SAME ORDER DATE LOGIC
function handleOrderedItemSelection(changedCb) {
    const allChips = document.querySelectorAll('.ordered-item-chip');
    const checkedCbs = document.querySelectorAll('.ordered-item-chip input[type="checkbox"]:checked');
    const errEl = document.getElementById('orderedItemsError');
    
    if (errEl && checkedCbs.length > 0) {
        errEl.style.display = 'none';
    }

    if (checkedCbs.length === 0) {
        allChips.forEach(chip => {
            chip.classList.remove('disabled-chip');
            const input = chip.querySelector('input');
            if (input) input.disabled = false;
        });
        return;
    }

    const activeOrderId = checkedCbs[0].closest('.ordered-item-chip').getAttribute('data-order-id');

    allChips.forEach(chip => {
        const chipOrderId = chip.getAttribute('data-order-id');
        const input = chip.querySelector('input');
        
        if (chipOrderId !== activeOrderId) {
            chip.classList.add('disabled-chip');
            if (input) {
                input.checked = false;
                input.disabled = true;
            }
        } else {
            chip.classList.remove('disabled-chip');
            if (input) input.disabled = false;
        }
    });
}

// Click listener for disabled greyed-out chips
document.addEventListener('click', function(e) {
    const disabledChip = e.target.closest('.ordered-item-chip.disabled-chip');
    if (disabledChip) {
        e.preventDefault();
        showToast('⚠️ You can only select items from the same order.', 'error');
    }
});

function toggleCustomMood(checkbox) {
    const customInput = document.getElementById('mood_custom');
    if (customInput) {
        customInput.style.display = checkbox.checked ? 'block' : 'none';
        if (checkbox.checked) customInput.focus();
    }
}

function checkWordCount() {
    const text = document.getElementById('description').value.trim();
    const words = text ? text.split(/\s+/).length : 0;
    const counter = document.getElementById('word_counter');
    if (counter) {
        counter.textContent = words + ' / 500 words';
        counter.style.color = words > 500 ? '#DC2626' : '#666';
    }
}

function handleFileAccumulate(prefix) {
    const inputId = prefix === 'create' ? 'photos' : 'editPhotosInput';
    const input = document.getElementById(inputId);
    if (!input || !input.files || input.files.length === 0) return;

    let stagedArr = prefix === 'create' ? createStagedFiles : editStagedFiles;
    const newFiles = Array.from(input.files).filter(f => f.type.startsWith('image/'));

    if (stagedArr.length + newFiles.length > 5) {
        showToast('Maximum 5 photo uploads total allowed per post.', 'error');
        const allowedCount = 5 - stagedArr.length;
        if (allowedCount <= 0) {
            input.value = '';
            return;
        }
        newFiles.splice(allowedCount);
    }

    newFiles.forEach(f => stagedArr.push(f));
    syncInputFiles(prefix);
    renderStagedPreviews(prefix);
}

function syncInputFiles(prefix) {
    const inputId = prefix === 'create' ? 'photos' : 'editPhotosInput';
    const input = document.getElementById(inputId);
    const stagedArr = prefix === 'create' ? createStagedFiles : editStagedFiles;
    const countLabel = document.getElementById('photoCountLabel');
    if (!input) return;

    try {
        const dt = new DataTransfer();
        stagedArr.forEach(file => dt.items.add(file));
        input.files = dt.files;
        if (countLabel && prefix === 'create') {
            countLabel.textContent = stagedArr.length > 0 ? `${stagedArr.length} photo(s) selected` : 'No photos selected';
        }
    } catch(e) {
        console.error("DataTransfer error:", e);
    }
}

function removeStagedFile(prefix, index) {
    let stagedArr = prefix === 'create' ? createStagedFiles : editStagedFiles;
    if (index >= 0 && index < stagedArr.length) {
        stagedArr.splice(index, 1);
        syncInputFiles(prefix);
        renderStagedPreviews(prefix);
    }
}

function renderStagedPreviews(prefix) {
    const gridId = prefix === 'create' ? 'createPreviewGrid' : 'editPreviewGrid';
    const grid = document.getElementById(gridId);
    const stagedArr = prefix === 'create' ? createStagedFiles : editStagedFiles;

    if (!grid) return;
    grid.innerHTML = '';

    if (stagedArr.length === 0) {
        grid.style.display = 'none';
        return;
    }

    grid.style.display = 'flex';
    stagedArr.forEach((file, idx) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewCard = document.createElement('div');
            previewCard.style.cssText = 'position: relative; width: 70px; height: 70px; border-radius: 10px; overflow: hidden; border: 1.5px solid #C85A3E; flex-shrink: 0; background: #FAF4EB;';
            
            previewCard.innerHTML = `
                <img src="${e.target.result}" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
                <button type="button" onclick="removeStagedFile('${prefix}', ${idx})" style="position: absolute; top: 3px; right: 3px; width: 20px; height: 20px; border-radius: 50%; background: #DC2626; color: #FFFFFF; border: none; font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 12px;" title="Remove photo">&times;</button>
            `;
            grid.appendChild(previewCard);
        };
        reader.readAsDataURL(file);
    });
}

function switchFeedTab(tab) {
    const btnAll = document.getElementById('tabAllPosts');
    const btnMine = document.getElementById('tabMyPosts');
    const btnDel = document.getElementById('tabDeletedPosts');

    const posts = document.querySelectorAll('.post-card');
    const currentUserId = "<?php echo (int)$currentUserId; ?>";

    if (tab === 'all') {
        if (btnAll) { btnAll.style.background = '#C85A3E'; btnAll.style.color = '#FFF'; }
        if (btnMine) { btnMine.style.background = 'transparent'; btnMine.style.color = '#665447'; }
        if (btnDel) { btnDel.style.background = 'transparent'; btnDel.style.color = '#DC2626'; }

        posts.forEach(p => {
            if (p.classList.contains('deleted-post-card')) {
                p.style.display = 'none';
            } else {
                p.style.display = 'block';
            }
        });
    } else if (tab === 'mine') {
        if (btnAll) { btnAll.style.background = 'transparent'; btnAll.style.color = '#665447'; }
        if (btnMine) { btnMine.style.background = '#C85A3E'; btnMine.style.color = '#FFF'; }
        if (btnDel) { btnDel.style.background = 'transparent'; btnDel.style.color = '#DC2626'; }

        posts.forEach(p => {
            if (p.classList.contains('deleted-post-card')) {
                p.style.display = 'none';
            } else if (p.getAttribute('data-user-id') === currentUserId) {
                p.style.display = 'block';
            } else {
                p.style.display = 'none';
            }
        });
    } else if (tab === 'deleted') {
        if (btnAll) { btnAll.style.background = 'transparent'; btnAll.style.color = '#665447'; }
        if (btnMine) { btnMine.style.background = 'transparent'; btnMine.style.color = '#665447'; }
        if (btnDel) { btnDel.style.background = '#DC2626'; btnDel.style.color = '#FFF'; }

        posts.forEach(p => {
            if (p.classList.contains('deleted-post-card')) {
                p.style.display = 'block';
            } else {
                p.style.display = 'none';
            }
        });
    }
}

function openUserEditModal(id, item, mood, desc, photos) {
    document.getElementById('editPostId').value = id;
    document.getElementById('editOrderedItem').value = item;
    document.getElementById('editMood').value = mood;
    document.getElementById('editDescription').value = desc;

    editStagedFiles = [];
    renderStagedPreviews('edit');

    const delContainer = document.getElementById('editDeletedPhotoInputs');
    if (delContainer) delContainer.innerHTML = '';

    const photoSection = document.getElementById('editExistingPhotosSection');
    const photoGrid = document.getElementById('editExistingPhotosGrid');
    if (photoGrid) {
        photoGrid.innerHTML = '';
        if (photos && photos.length > 0) {
            photos.forEach(p => {
                const div = document.createElement('div');
                div.style.cssText = 'position:relative; width:70px; height:70px; border-radius:10px; overflow:hidden; border:1.5px solid #E8DDD0;';
                div.id = 'blog-photo-' + p.id;
                div.innerHTML = `
                    <img src="../${p.image_path}" style="width:100%; height:100%; object-fit:cover;">
                    <button type="button" onclick="removeBlogPhoto(${p.id})" style="position:absolute; top:3px; right:3px; width:20px; height:20px; border-radius:50%; background:#DC2626; color:#FFF; border:none; font-weight:bold; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:12px;">&times;</button>
                `;
                photoGrid.appendChild(div);
            });
            if (photoSection) photoSection.style.display = 'block';
        } else {
            if (photoSection) photoSection.style.display = 'none';
        }
    }

    openModal('userEditModal');
}

function removeBlogPhoto(photoId) {
    const el = document.getElementById('blog-photo-' + photoId);
    if (el) el.remove();
    const container = document.getElementById('editDeletedPhotoInputs');
    if (container) {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'delete_photo_ids[]';
        hidden.value = photoId;
        container.appendChild(hidden);
    }
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('active');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('active');
}

function closeModalOnOutsideClick(e, modalId) {
    if (e.target.id === modalId) {
        closeModal(modalId);
    }
}

function showToast(msg, type = 'success') {
    let wrap = document.getElementById('toastWrap');
    if (!wrap) {
        wrap = document.createElement('div');
        wrap.id = 'toastWrap';
        wrap.className = 'toast-notification-wrap';
        document.body.appendChild(wrap);
    }
    const toast = document.createElement('div');
    toast.className = `toast-item ${type}`;
    const icon = type === 'success' ? '✅' : '⚠️';
    toast.innerHTML = `<span>${icon}</span> <span>${escapeHtml(msg)}</span>`;
    wrap.appendChild(toast);
    setTimeout(() => {
        toast.remove();
    }, 4000);
}

function escapeHtml(str) {
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}
</script>

<?php require_once '../includes/footer.php'; ?>

</body>
</html>
