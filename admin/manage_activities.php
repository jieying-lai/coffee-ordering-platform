<?php
require_once '../includes/admin_auth_check.php';
require_once '../includes/db_connect.php';

$message = '';
$messageType = '';

// ============ CREATE ACTIVITY POST ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'create_activity') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? 'event');
    $content = trim($_POST['content'] ?? '');

    if (!empty($title) && !empty($content)) {
        $stmt = $conn->prepare("INSERT INTO activities_posts (title, category, content, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $title, $category, $content);
        
        if ($stmt->execute()) {
            $activityId = $stmt->insert_id;
            $stmt->close();

            // Upload Photos
            if (!empty($_FILES['photos']['name'][0])) {
                $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $uploadDir = '../uploads/activities/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $totalFiles = count($_FILES['photos']['name']);
                $pStmt = $conn->prepare("INSERT INTO activities_photos (activity_id, image_path) VALUES (?, ?)");
                for ($i = 0; $i < min($totalFiles, 5); $i++) {
                    if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION));
                        if (in_array($ext, $allowedExt, true)) {
                            $filename = 'act_' . time() . '_' . $i . '.' . $ext;
                            $targetFile = $uploadDir . $filename;
                            if (move_uploaded_file($_FILES['photos']['tmp_name'][$i], $targetFile)) {
                                $relPath = 'uploads/activities/' . $filename;
                                $pStmt->bind_param("is", $activityId, $relPath);
                                $pStmt->execute();
                            }
                        }
                    }
                }
                $pStmt->close();
            }

            $message = "Store activity post published successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to create post: " . $stmt->error;
            $messageType = "error";
        }
    } else {
        $message = "Please fill in both title and content.";
        $messageType = "error";
    }
}

// ============ EDIT ACTIVITY POST ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'edit_activity') {
    $editId = (int)($_POST['edit_activity_id'] ?? 0);
    $title = trim($_POST['edit_title'] ?? '');
    $category = trim($_POST['edit_category'] ?? 'event');
    $content = trim($_POST['edit_content'] ?? '');

    if ($editId > 0 && !empty($title) && !empty($content)) {
        $uStmt = $conn->prepare("UPDATE activities_posts SET title = ?, category = ?, content = ? WHERE id = ?");
        $uStmt->bind_param("sssi", $title, $category, $content, $editId);
        $uStmt->execute();
        $uStmt->close();

        // Delete specifically selected photos
        if (!empty($_POST['delete_photo_ids']) && is_array($_POST['delete_photo_ids'])) {
            foreach ($_POST['delete_photo_ids'] as $delPhotoId) {
                $delPhotoId = (int)$delPhotoId;
                if ($delPhotoId > 0) {
                    $pStmt = $conn->prepare("SELECT image_path FROM activities_photos WHERE id = ? AND activity_id = ?");
                    $pStmt->bind_param("ii", $delPhotoId, $editId);
                    $pStmt->execute();
                    $pRes = $pStmt->get_result();
                    if ($pRow = $pRes->fetch_assoc()) {
                        if (file_exists('../' . $pRow['image_path']) && strpos($pRow['image_path'], 'uploads/') === 0) {
                            @unlink('../' . $pRow['image_path']);
                        }
                        $dStmt = $conn->prepare("DELETE FROM activities_photos WHERE id = ?");
                        $dStmt->bind_param("i", $delPhotoId);
                        $dStmt->execute();
                        $dStmt->close();
                    }
                    $pStmt->close();
                }
            }
        }

        // Upload new photos
        if (!empty($_FILES['edit_photos']['name'][0])) {
            $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $uploadDir = '../uploads/activities/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $totalFiles = count($_FILES['edit_photos']['name']);
            $pStmt = $conn->prepare("INSERT INTO activities_photos (activity_id, image_path) VALUES (?, ?)");
            for ($i = 0; $i < min($totalFiles, 5); $i++) {
                if ($_FILES['edit_photos']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['edit_photos']['name'][$i], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowedExt, true)) {
                        $filename = 'act_edit_' . time() . '_' . $i . '.' . $ext;
                        $targetFile = $uploadDir . $filename;
                        if (move_uploaded_file($_FILES['edit_photos']['tmp_name'][$i], $targetFile)) {
                            $relPath = 'uploads/activities/' . $filename;
                            $pStmt->bind_param("is", $editId, $relPath);
                            $pStmt->execute();
                        }
                    }
                }
            }
            $pStmt->close();
        }

        $message = "Activity post updated successfully!";
        $messageType = "success";
    }
}

// ============ TOGGLE HIDE / UNHIDE ============
if (isset($_GET['toggle_hide'])) {
    $actId = (int)$_GET['toggle_hide'];
    $status = (int)$_GET['status']; // 1 = Hide, 0 = Unhide
    $stmt = $conn->prepare("UPDATE activities_posts SET is_hidden = ? WHERE id = ?");
    $stmt->bind_param("ii", $status, $actId);
    if ($stmt->execute()) {
        $message = $status === 1 ? 'Activity post hidden from website.' : 'Activity post made visible on website.';
        $messageType = 'success';
    }
    $stmt->close();
}

// ============ DELETE POST ============
if (isset($_GET['delete'])) {
    $actId = (int)$_GET['delete'];
    
    $pStmt = $conn->prepare("SELECT image_path FROM activities_photos WHERE activity_id = ?");
    $pStmt->bind_param("i", $actId);
    $pStmt->execute();
    $pRes = $pStmt->get_result();
    while ($pRow = $pRes->fetch_assoc()) {
        if (file_exists('../' . $pRow['image_path']) && strpos($pRow['image_path'], 'uploads/') === 0) {
            @unlink('../' . $pRow['image_path']);
        }
    }
    $pStmt->close();

    $dStmt = $conn->prepare("DELETE FROM activities_posts WHERE id = ?");
    $dStmt->bind_param("i", $actId);
    if ($dStmt->execute()) {
        $message = 'Activity post deleted permanently.';
        $messageType = 'success';
    }
    $dStmt->close();
}

// Fetch all activity posts
$query = "SELECT ap.*, 
                 (SELECT image_path FROM activities_photos WHERE activity_id = ap.id LIMIT 1) as cover_photo,
                 (SELECT COUNT(*) FROM activities_photos WHERE activity_id = ap.id) as photo_count
          FROM activities_posts ap 
          ORDER BY ap.created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/admin.css">
  <title>Cozy Coffee Co. — Manage Store Activities</title>
  <style>
    body.admin-page {
      background: #FAF6F0 !important;
      color: #2C1C14;
    }

    /* Modern Admin Activity Card Layout */
    .admin-activities-wrap {
      max-width: 1240px;
      margin: 30px auto 60px;
      padding: 0 4%;
      box-sizing: border-box;
    }

    .admin-hero-card {
      background: linear-gradient(135deg, #2D1E17 0%, #1F130E 60%, #160D09 100%);
      color: #FAF7F2;
      border-radius: 18px;
      padding: 20px 28px;
      margin-bottom: 22px;
      box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
      border: 1px solid rgba(242, 201, 76, 0.2);
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
    }

    .admin-hero-card .eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 3px 12px;
      border-radius: 20px;
      background: rgba(242, 201, 76, 0.15);
      border: 1px solid rgba(242, 201, 76, 0.35);
      color: #F2C94C;
      font-size: 0.72rem;
      letter-spacing: 1.5px;
      font-weight: 800;
      text-transform: uppercase;
      margin-bottom: 6px;
    }

    .admin-hero-card h1 {
      font-family: var(--font-heading);
      font-size: 1.45rem;
      color: #FAF7F2;
      margin: 0 0 4px 0;
      font-weight: 800;
    }

    .admin-hero-card p {
      color: #D6C7B8;
      font-size: 0.88rem;
      margin: 0;
    }

    .btn-create-post {
      background: linear-gradient(135deg, #C85A3E 0%, #A8472F 100%);
      color: #FFFFFF !important;
      font-weight: 800;
      font-size: 0.88rem;
      padding: 10px 22px;
      border-radius: 30px;
      border: none;
      cursor: pointer;
      box-shadow: 0 6px 18px rgba(200, 90, 62, 0.35);
      transition: all 0.25s ease;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .btn-create-post:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 24px rgba(200, 90, 62, 0.45);
      background: linear-gradient(135deg, #B64C32 0%, #8C3722 100%);
    }

    /* Activity Post Cards List */
    .admin-activity-list {
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    .admin-activity-item {
      background: #FFFFFF;
      border-radius: 18px;
      border: 1.5px solid #E8DDD0;
      padding: 20px 24px;
      box-shadow: 0 6px 20px rgba(60, 42, 33, 0.04);
      display: flex;
      gap: 20px;
      align-items: flex-start;
      transition: all 0.25s ease;
    }

    .admin-activity-item:hover {
      box-shadow: 0 12px 30px rgba(60, 42, 33, 0.08);
      border-color: #C85A3E;
    }

    .activity-cover-img {
      width: 140px;
      height: 140px;
      border-radius: 14px;
      object-fit: cover;
      border: 1.5px solid #E5D9CC;
      flex-shrink: 0;
      background: #FAF4EB;
    }

    .activity-main-info {
      flex: 1;
    }

    .activity-meta-row {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 6px;
      flex-wrap: wrap;
    }

    .cat-pill {
      font-size: 0.75rem;
      font-weight: 800;
      padding: 3px 10px;
      border-radius: 20px;
      background: rgba(200, 90, 62, 0.12);
      color: #C85A3E;
      border: 1px solid rgba(200, 90, 62, 0.3);
    }

    .activity-title-text {
      font-family: var(--font-heading);
      font-size: 1.15rem;
      color: #2C1C14;
      font-weight: 800;
      margin: 0 0 6px 0;
      line-height: 1.3;
    }

    .activity-snippet-text {
      color: #665447;
      font-size: 0.9rem;
      line-height: 1.55;
      margin: 0;
      display: -webkit-box;
      -webkit-line-clamp: 4;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .activity-actions-col {
      display: flex;
      flex-direction: column;
      gap: 8px;
      min-width: 140px;
      align-items: flex-end;
    }

    .btn-action-edit {
      padding: 8px 18px;
      border-radius: 10px;
      border: 1.5px solid rgba(200, 90, 62, 0.5);
      color: #C85A3E;
      background: rgba(200, 90, 62, 0.06);
      font-weight: 800;
      font-size: 0.85rem;
      cursor: pointer;
      width: 100%;
      text-align: center;
      transition: all 0.2s ease;
    }

    .btn-action-edit:hover {
      background: #C85A3E;
      color: #FFFFFF;
    }

    .btn-action-hide {
      padding: 8px 18px;
      border-radius: 10px;
      border: 1.5px solid rgba(217, 119, 6, 0.5);
      color: #D97706;
      background: rgba(217, 119, 6, 0.06);
      font-weight: 800;
      font-size: 0.85rem;
      text-decoration: none;
      width: 100%;
      text-align: center;
      box-sizing: border-box;
      transition: all 0.2s ease;
    }

    .btn-action-hide:hover {
      background: #D97706;
      color: #FFFFFF;
    }

    .btn-action-delete {
      padding: 8px 18px;
      border-radius: 10px;
      border: 1.5px solid rgba(220, 38, 38, 0.5);
      color: #DC2626;
      background: rgba(220, 38, 38, 0.06);
      font-weight: 800;
      font-size: 0.85rem;
      text-decoration: none;
      width: 100%;
      text-align: center;
      box-sizing: border-box;
      transition: all 0.2s ease;
    }

    .btn-action-delete:hover {
      background: #DC2626;
      color: #FFFFFF;
    }

    /* Modal Form Controls Styling */
    .admin-modal-overlay {
      display: none;
      position: fixed;
      z-index: 10000;
      left: 0;
      top: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(24, 15, 10, 0.72);
      backdrop-filter: blur(10px);
      justify-content: center;
      align-items: center;
      padding: 50px 20px 20px;
      box-sizing: border-box;
    }

    .admin-modal-overlay.active {
      display: flex;
    }

    .admin-modal-box {
      background: #FFFFFF;
      border-radius: 24px;
      border: 1.5px solid #E5D9CC;
      box-shadow: 0 25px 70px rgba(44, 28, 20, 0.35);
      max-width: 580px;
      width: 92%;
      padding: 30px 28px;
      position: relative;
      max-height: 80vh;
      overflow-y: auto;
      box-sizing: border-box;
      margin-top: 40px;
    }

    .admin-modal-box h3 {
      font-family: var(--font-heading);
      font-size: 1.5rem;
      color: #2C1C14;
      margin: 0 0 20px 0;
      font-weight: 800;
    }

    .admin-modal-box input[type="text"],
    .admin-modal-box select,
    .admin-modal-box textarea {
      width: 100%;
      border-radius: 12px;
      border: 1.5px solid #E5D9CC;
      background: #FAF7F2;
      padding: 12px 16px;
      font-size: 0.95rem;
      color: #2C1C14;
      font-family: inherit;
      box-sizing: border-box;
      transition: all 0.2s ease;
    }

    .admin-modal-box input[type="text"]:focus,
    .admin-modal-box select:focus,
    .admin-modal-box textarea:focus {
      outline: none;
      border-color: #C85A3E;
      background: #FFFFFF;
      box-shadow: 0 0 0 4px rgba(200, 90, 62, 0.12);
    }

    /* Universal File Selector Button styling */
    .admin-modal-box input[type="file"] {
      width: 100%;
      padding: 10px 14px;
      background-color: #FAF7F2;
      border: 1.5px dashed #E5D9CC;
      border-radius: 12px;
      cursor: pointer;
      font-size: 0.9rem;
      color: #665447;
      box-sizing: border-box;
    }

    .admin-modal-box input[type="file"]::file-selector-button {
      background: linear-gradient(135deg, #C85A3E 0%, #A8472F 100%);
      color: #ffffff;
      border: none;
      padding: 8px 16px;
      border-radius: 8px;
      cursor: pointer;
      margin-right: 14px;
      font-weight: 800;
      box-shadow: 0 4px 12px rgba(200, 90, 62, 0.25);
    }
  </style>
</head>
<body class="admin-page">

<!-- ADMIN NAVBAR -->
<nav class="admin-nav-bar" style="background: #2D1E17; color: #fff; padding: 14px 5%; sticky: top: 0; z-index: 9999; box-shadow: 0 4px 14px rgba(0,0,0,0.2); border-bottom: 1px solid rgba(242,201,76,0.2);">
  <div style="max-width: 1400px; margin: 0 auto; width: 100%; display: flex; justify-content: space-between; align-items: center;">
    <div style="font-weight: 800; font-size: 1.15rem; color: #fff;">
      <a href="dashboard.php" style="color: #fff; text-decoration: none; display: flex; align-items: center; gap: 8px;">
        <span>☕</span> Cozy Barista Admin Portal
      </a>
    </div>
    <div class="admin-nav-links" id="adminNavMenu">
      <a href="dashboard.php">📋 Dashboard</a>
      <a href="manage_orders.php">📦 Orders</a>
      <a href="manage_chat.php">💬 Customer Chat</a>
      <a href="manage_menu.php">☕ Menu</a>
      <a href="manage_activities.php" class="admin-nav-active">📢 Store Activities</a>
      <a href="manage_blog.php">📸 Blog</a>
      <a href="manage_users.php">👤 Users</a>
      <a href="logout.php" style="color: #f87171 !important; text-decoration: none; padding: 6px 12px; border-radius: 6px; background: rgba(239, 68, 68, 0.15);">Logout</a>
    </div>
  </div>
</nav>

<div class="admin-activities-wrap">
  
  <!-- HERO SCALED CARD HEADER -->
  <div class="admin-hero-card">
    <div>
      <div class="eyebrow">✦ Official Brand Journal ✦</div>
      <h1>📢 Manage Store Activities</h1>
      <p>Publish official store events, Merdeka promos, barista workshops, and daily brand updates.</p>
    </div>
    <button type="button" class="btn-create-post" onclick="openAdminModal('createActivityModal')">
      ✨ Create Activity Post
    </button>
  </div>

  <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?>" style="margin-bottom: 24px; padding: 16px 20px; border-radius: 14px; font-weight: 700;">
      <?php echo htmlspecialchars($message); ?>
    </div>
  <?php endif; ?>

  <!-- ACTIVITY CARDS LIST (LUXURIOUS DESIGN) -->
  <div class="admin-activity-list">
    <?php if ($result && $result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <?php
          $actId = (int)$row['id'];
          $pStmt = $conn->prepare("SELECT id, image_path FROM activities_photos WHERE activity_id = ?");
          $pStmt->bind_param("i", $actId);
          $pStmt->execute();
          $pRes = $pStmt->get_result();
          $actPhotos = [];
          if ($pRes) {
              while ($pr = $pRes->fetch_assoc()) {
                  $actPhotos[] = $pr;
              }
          }
          $pStmt->close();
          $photosJson = htmlspecialchars(json_encode($actPhotos), ENT_QUOTES, 'UTF-8');
          
          $catBadges = [
            'event' => '🎉 Event',
            'promotion' => '🏷️ Promotion',
            'workshop' => '🧑‍🍳 Workshop',
            'daily' => '☕ Daily',
            'announcement' => '📢 Announcement'
          ];
          $catLabel = $catBadges[$row['category']] ?? '📍 Activity';
          $cover = !empty($row['cover_photo']) ? '../' . $row['cover_photo'] : '../images/1.jpg';
        ?>
        <div class="admin-activity-item">
          <img src="<?php echo htmlspecialchars($cover); ?>" class="activity-cover-img" alt="Activity Cover">
          
          <div class="activity-main-info">
            <div class="activity-meta-row">
              <span class="cat-pill"><?php echo $catLabel; ?></span>
              <small style="color: #8A7769; font-weight: 600;"><?php echo date('M d, Y · g:i A', strtotime($row['created_at'])); ?></small>
              <small style="color: #888;">(📷 <?php echo $row['photo_count']; ?> photos)</small>
              
              <?php if ((int)$row['is_hidden'] === 1): ?>
                <span style="background: #FEE2E2; color: #991B1B; padding: 3px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 800;">🔒 Hidden from website</span>
              <?php else: ?>
                <span style="background: #D1FAE5; color: #065F46; padding: 3px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 800;">🌐 Active / Visible</span>
              <?php endif; ?>
            </div>

            <h3 class="activity-title-text"><?php echo htmlspecialchars($row['title']); ?></h3>
            <p class="activity-snippet-text"><?php echo htmlspecialchars($row['content']); ?></p>
          </div>

          <div class="activity-actions-col">
            <button type="button" class="btn-action-edit" onclick="openAdminEditModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['title'])); ?>', '<?php echo $row['category']; ?>', '<?php echo htmlspecialchars(addslashes($row['content'])); ?>', <?php echo $photosJson; ?>)">
              ✏️ Edit Post
            </button>
            <?php if ((int)$row['is_hidden'] === 1): ?>
              <a href="manage_activities.php?toggle_hide=<?php echo $row['id']; ?>&status=0" class="btn-action-hide">👁️ Unhide</a>
            <?php else: ?>
              <a href="manage_activities.php?toggle_hide=<?php echo $row['id']; ?>&status=1" class="btn-action-hide">🔒 Hide Post</a>
            <?php endif; ?>
            <a href="manage_activities.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Permanently delete this activity post?')" class="btn-action-delete">🗑️ Delete</a>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="text-align: center; color: #888; padding: 40px; background: #FFF; border-radius: 20px;">No store activities published yet. Click "Create Activity Post" above to publish one!</p>
    <?php endif; ?>
  </div>

</div>

<!-- CREATE ACTIVITY MODAL (HIGH-END REFINED) -->
<div id="createActivityModal" class="admin-modal-overlay">
  <div class="admin-modal-box">
    <button class="modal-close" onclick="closeAdminModal('createActivityModal')">&times;</button>
    <h3>✨ Create Store Activity Post</h3>
    <form action="" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="form_type" value="create_activity">
      
      <div style="margin-bottom: 18px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:8px;">Activity Title</label>
        <input type="text" name="title" required placeholder="e.g. Monthly Corporate Coffee Day with XX Enterprise">
      </div>

      <div style="margin-bottom: 18px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:8px;">Category Tag</label>
        <select name="category">
          <option value="event">🎉 Event</option>
          <option value="promotion">🏷️ Promotion</option>
          <option value="workshop">🧑‍🍳 Workshop</option>
          <option value="daily">☕ Daily Moment</option>
          <option value="announcement">📢 Store Announcement</option>
        </select>
      </div>

      <div style="margin-bottom: 18px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:8px;">Story Content &amp; Details</label>
        <textarea name="content" rows="5" required placeholder="Write full details about this store activity..."></textarea>
      </div>

      <div style="margin-bottom: 24px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:8px;">Upload Photos (Optional, max 5 photos)</label>
        <input type="file" name="photos[]" accept="image/*" multiple>
      </div>

      <button type="submit" class="btn-create-post" style="width: 100%; justify-content: center; height: 50px; border-radius: 12px;">Publish Activity 📢</button>
    </form>
  </div>
</div>

<!-- EDIT ACTIVITY MODAL (HIGH-END REFINED) -->
<div id="editActivityModal" class="admin-modal-overlay">
  <div class="admin-modal-box">
    <button class="modal-close" onclick="closeAdminModal('editActivityModal')">&times;</button>
    <h3>✏️ Edit Store Activity Post</h3>
    <form action="" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="form_type" value="edit_activity">
      <input type="hidden" name="edit_activity_id" id="editActivityId">
      
      <div id="editDeletedPhotoInputs"></div>

      <div style="margin-bottom: 18px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:8px;">Activity Title</label>
        <input type="text" name="edit_title" id="editTitle" required>
      </div>

      <div style="margin-bottom: 18px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:8px;">Category Tag</label>
        <select name="edit_category" id="editCategory">
          <option value="event">🎉 Event</option>
          <option value="promotion">🏷️ Promotion</option>
          <option value="workshop">🧑‍🍳 Workshop</option>
          <option value="daily">☕ Daily Moment</option>
          <option value="announcement">📢 Store Announcement</option>
        </select>
      </div>

      <div style="margin-bottom: 18px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:8px;">Story Content &amp; Details</label>
        <textarea name="edit_content" id="editContent" rows="5" required></textarea>
      </div>

      <div id="editExistingPhotosSection" style="display:none; margin-bottom:20px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:8px;">Current Photos (Click ✖ on any photo to remove)</label>
        <div id="editExistingPhotosGrid" style="display:flex; gap:12px; flex-wrap:wrap;"></div>
      </div>

      <div style="margin-bottom: 24px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:8px;">Add New Photos (Optional, max 5 photos)</label>
        <input type="file" name="edit_photos[]" accept="image/*" multiple>
      </div>

      <button type="submit" class="btn-create-post" style="width: 100%; justify-content: center; height: 50px; border-radius: 12px;">Save Changes ☕</button>
    </form>
  </div>
</div>

<script>
function openAdminModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('active');
}

function closeAdminModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('active');
}

function openAdminEditModal(id, title, category, content, photos) {
    document.getElementById('editActivityId').value = id;
    document.getElementById('editTitle').value = title;
    document.getElementById('editCategory').value = category;
    document.getElementById('editContent').value = content;
    
    const delContainer = document.getElementById('editDeletedPhotoInputs');
    if (delContainer) delContainer.innerHTML = '';

    const photoSection = document.getElementById('editExistingPhotosSection');
    const photoGrid = document.getElementById('editExistingPhotosGrid');
    if (photoGrid) {
        photoGrid.innerHTML = '';
        if (photos && photos.length > 0) {
            photos.forEach(p => {
                const div = document.createElement('div');
                div.style.cssText = 'position:relative; width:90px; height:90px; border-radius:12px; overflow:hidden; border:1.5px solid #E5D9CC; box-shadow:0 4px 12px rgba(0,0,0,0.08);';
                div.id = 'act-photo-' + p.id;
                div.innerHTML = `
                    <img src="../${p.image_path}" style="width:100%; height:100%; object-fit:cover;">
                    <button type="button" onclick="removeActPhoto(${p.id})" style="position:absolute; top:4px; right:4px; width:24px; height:24px; border-radius:50%; background:#DC2626; color:#FFF; border:none; font-weight:bold; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:13px;">&times;</button>
                `;
                photoGrid.appendChild(div);
            });
            if (photoSection) photoSection.style.display = 'block';
        } else {
            if (photoSection) photoSection.style.display = 'none';
        }
    }

    openAdminModal('editActivityModal');
}

function removeActPhoto(photoId) {
    const el = document.getElementById('act-photo-' + photoId);
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
</script>

</body>
</html>
