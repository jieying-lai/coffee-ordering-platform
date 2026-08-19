<?php
require_once '../includes/admin_auth_check.php';
require_once '../includes/db_connect.php';

// ============ AJAX TOGGLE HIDE / UNHIDE STATUS (NO PAGE REFRESH) ============
if (isset($_GET['ajax_toggle_hide'])) {
    header('Content-Type: application/json');
    $actId = (int)$_GET['ajax_toggle_hide'];
    $status = (int)$_GET['status']; // 1 = Hide, 0 = Unhide
    $stmt = $conn->prepare("UPDATE activities_posts SET is_hidden = ? WHERE id = ?");
    $stmt->bind_param("ii", $status, $actId);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['status' => $ok ? 'success' : 'error', 'new_status' => $status]);
    exit;
}

$message = '';
$messageType = '';

// ============ CREATE ACTIVITY POST ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'create_activity') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? 'event');
    $content = trim($_POST['content'] ?? '');

    if (empty($title) || empty($content)) {
        $message = "Please fill in both the activity title and story content.";
        $messageType = "error";
    } else {
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

            header("Location: manage_activities.php?msg=created");
            exit();
        } else {
            $message = "Failed to create post: " . $stmt->error;
            $messageType = "error";
        }
    }
}

// ============ EDIT ACTIVITY POST ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'edit_activity') {
    $editId = (int)($_POST['edit_activity_id'] ?? 0);
    $title = trim($_POST['edit_title'] ?? '');
    $category = trim($_POST['edit_category'] ?? 'event');
    $content = trim($_POST['edit_content'] ?? '');

    if ($editId <= 0 || empty($title) || empty($content)) {
        $message = "Please fill in both the activity title and story content.";
        $messageType = "error";
    } else {
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

        // Upload new photos for edit
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

        // REDIRECT WITH ANCHOR TO PREVENT SCROLL JUMPING TO TOP
        header("Location: manage_activities.php?msg=updated#act-item-" . $editId);
        exit();
    }
}

// ============ DELETE POST ============
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];

    // Delete photos files from disk first
    $pStmt = $conn->prepare("SELECT image_path FROM activities_photos WHERE activity_id = ?");
    $pStmt->bind_param("i", $delId);
    $pStmt->execute();
    $pRes = $pStmt->get_result();
    if ($pRes) {
        while ($pRow = $pRes->fetch_assoc()) {
            if (file_exists('../' . $pRow['image_path']) && strpos($pRow['image_path'], 'uploads/') === 0) {
                @unlink('../' . $pRow['image_path']);
            }
        }
    }
    $pStmt->close();

    $stmt = $conn->prepare("DELETE FROM activities_posts WHERE id = ?");
    $stmt->bind_param("i", $delId);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_activities.php?msg=deleted");
    exit();
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') {
        $message = "Store activity post published successfully!";
        $messageType = "success";
    } elseif ($_GET['msg'] === 'updated') {
        $message = "Activity post updated successfully!";
        $messageType = "success";
    } elseif ($_GET['msg'] === 'deleted') {
        $message = "Activity post deleted successfully.";
        $messageType = "success";
    }
}

// Fetch all activity posts with photo count and first photo cover
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

    .admin-activities-wrap {
      max-width: 1240px;
      margin: 20px auto 60px;
      padding: 0 4%;
      box-sizing: border-box;
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

    /* Activity Card Items */
    .admin-activity-list {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .admin-activity-item {
      background: #FFFFFF;
      border-radius: 20px;
      border: 1.5px solid #E8DDD0;
      padding: 22px;
      box-shadow: 0 6px 20px rgba(60, 42, 33, 0.04);
      display: flex;
      gap: 22px;
      align-items: flex-start;
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .admin-activity-item:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 28px rgba(60, 42, 33, 0.1);
    }

    .activity-cover-img {
      width: 140px;
      height: 100px;
      border-radius: 14px;
      object-fit: cover;
      border: 1.5px solid #E8DDD0;
      background: #FAF4EB;
      flex-shrink: 0;
    }

    .activity-main-info {
      flex: 1;
      min-width: 0;
    }

    .activity-meta-row {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 8px;
      flex-wrap: wrap;
    }

    .cat-pill {
      background: #FAF4EB;
      color: #C85A3E;
      border: 1px solid rgba(200, 90, 62, 0.3);
      font-size: 0.72rem;
      font-weight: 800;
      padding: 3px 10px;
      border-radius: 20px;
    }

    .activity-title-text {
      font-family: var(--font-heading, serif);
      font-size: 1.2rem;
      font-weight: 800;
      color: #2C1C14;
      margin: 0 0 6px 0;
    }

    .activity-snippet-text {
      color: #665447;
      font-size: 0.88rem;
      line-height: 1.5;
      margin: 0;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .activity-actions-col {
      display: flex;
      flex-direction: column;
      gap: 8px;
      width: 130px;
      flex-shrink: 0;
    }

    /* EDIT POST BUTTON CHANGED TO MODERN BLUE STYLE */
    .btn-action-edit {
      padding: 8px 14px;
      background: #EFF6FF;
      color: #1D4ED8;
      border: 1.5px solid #BFDBFE;
      border-radius: 10px;
      font-weight: 700;
      font-size: 0.8rem;
      cursor: pointer;
      text-align: center;
      transition: all 0.2s ease;
    }
    .btn-action-edit:hover {
      background: #2563EB;
      color: #FFFFFF;
      border-color: #1D4ED8;
      box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }

    .btn-action-hide {
      padding: 8px 14px;
      background: #FAF7F2;
      color: #4A3B32;
      border: 1.5px solid #E8DDD0;
      border-radius: 10px;
      font-weight: 700;
      font-size: 0.8rem;
      cursor: pointer;
      text-align: center;
      transition: all 0.2s ease;
    }

    .btn-action-delete {
      padding: 8px 14px;
      background: #FEF2F2;
      color: #DC2626;
      border: 1.5px solid #FCA5A5;
      border-radius: 10px;
      font-weight: 700;
      font-size: 0.8rem;
      cursor: pointer;
      text-align: center;
      text-decoration: none;
      transition: all 0.2s ease;
    }
    .btn-action-delete:hover {
      background: #DC2626;
      color: #FFFFFF;
    }

    /* POP-UP MODAL OVERLAY & POP-UP CONTAINER */
    .admin-modal-overlay {
      display: none;
      position: fixed !important;
      top: 0 !important;
      left: 0 !important;
      width: 100vw !important;
      height: 100vh !important;
      background: rgba(24, 15, 10, 0.75) !important;
      backdrop-filter: blur(8px) !important;
      z-index: 99999 !important;
      justify-content: center !important;
      align-items: center !important;
      padding: 20px !important;
      box-sizing: border-box !important;
    }

    .admin-modal-overlay.active {
      display: flex !important;
    }

    .admin-modal-box {
      background: #FFFFFF !important;
      border-radius: 24px !important;
      border: 1.5px solid #E8DDD0 !important;
      box-shadow: 0 25px 70px rgba(44, 28, 20, 0.35) !important;
      max-width: 540px !important;
      width: 100% !important;
      padding: 28px 30px !important;
      position: relative !important;
      max-height: 90vh !important;
      overflow-y: auto !important;
      animation: modalPopIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }

    @keyframes modalPopIn {
      from { opacity: 0; transform: scale(0.92) translateY(10px); }
      to { opacity: 1; transform: scale(1) translateY(0); }
    }

    .admin-modal-box input[type="text"],
    .admin-modal-box select,
    .admin-modal-box textarea {
      width: 100%;
      padding: 11px 16px;
      border: 1.5px solid #E8DDD0;
      border-radius: 12px;
      font-family: inherit;
      font-size: 0.92rem;
      box-sizing: border-box;
      background: #FAF7F2;
      color: #2C1C14;
      outline: none;
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

<?php $adminActivePage = 'activities'; require_once '../includes/admin_header_nav.php'; ?>

<div class="admin-activities-wrap">
  
  <div class="admin-page-header">
    <a href="dashboard.php" class="btn-back-dashboard">&larr; Back to Dashboard</a>
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; width: 100%;">
      <div>
        <h1 class="admin-header-title">Manage Store Activities</h1>
        <p class="admin-header-subtitle">Publish official store events, Merdeka promos, barista workshops, and daily brand updates.</p>
      </div>
      <button type="button" class="btn-create-post" onclick="openAdminModal('createActivityModal')">
        + Create Activity Post
      </button>
    </div>
  </div>

  <?php if (!empty($message)): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
          showToast(<?php echo json_encode($message); ?>, <?php echo json_encode($messageType); ?>);
      });
    </script>
  <?php endif; ?>

  <!-- FILTER & SORT CONTROLS BAR (CATEGORY + TIME PERIOD) -->
  <div class="admin-filter-sort-bar" style="display: flex; gap: 14px; align-items: center; justify-content: space-between; flex-wrap: wrap; margin-bottom: 24px; padding: 16px 22px; background: #FFFFFF; border: 1.5px solid #E8DDD0; border-radius: 18px; box-shadow: 0 4px 14px rgba(60, 42, 33, 0.03);">
    
    <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
      <!-- CATEGORY FILTER DROPDOWN -->
      <div style="display: flex; align-items: center; gap: 8px;">
        <label style="font-weight: 700; font-size: 0.88rem; color: #4A3B32;">Category:</label>
        <select id="adminCatFilter" onchange="applyAdminFilterSort()" style="padding: 9px 14px; border: 1.5px solid #E8DDD0; border-radius: 10px; font-size: 0.88rem; font-weight: 700; background: #FAF7F2; color: #2C1C14; outline: none; cursor: pointer;">
          <option value="all">All Categories</option>
          <option value="event">Event</option>
          <option value="promotion">Promotion</option>
          <option value="workshop">Workshop</option>
          <option value="daily">Daily Moment</option>
          <option value="announcement">Store Announcement</option>
        </select>
      </div>

      <!-- DATE RANGE FILTER (THIS WEEK, THIS MONTH, ALL TIME) -->
      <div style="display: flex; align-items: center; gap: 8px;">
        <label style="font-weight: 700; font-size: 0.88rem; color: #4A3B32;">Time Period:</label>
        <select id="adminPeriodFilter" onchange="applyAdminFilterSort()" style="padding: 9px 14px; border: 1.5px solid #E8DDD0; border-radius: 10px; font-size: 0.88rem; font-weight: 700; background: #FAF7F2; color: #2C1C14; outline: none; cursor: pointer;">
          <option value="all">All Time</option>
          <option value="week">This Week</option>
          <option value="month">This Month</option>
        </select>
      </div>
    </div>

    <div style="font-size: 0.85rem; color: #8C7A6D; font-weight: 700;" id="filterResultsCount">
      Showing <?php echo $result ? $result->num_rows : 0; ?> activity post(s)
    </div>

  </div>

  <!-- ACTIVITY CARDS LIST -->
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
            'event' => 'Event',
            'promotion' => 'Promotion',
            'workshop' => 'Workshop',
            'daily' => 'Daily',
            'announcement' => 'Announcement'
          ];
          $catLabel = $catBadges[$row['category']] ?? 'Activity';
          $cover = !empty($row['cover_photo']) ? '../' . $row['cover_photo'] : '../images/1.jpg';
          $isHidden = ((int)$row['is_hidden'] === 1);
        ?>
        <div class="admin-activity-item" id="act-item-<?php echo $row['id']; ?>" data-category="<?php echo htmlspecialchars($row['category']); ?>" data-created="<?php echo strtotime($row['created_at']); ?>">
          <img src="<?php echo htmlspecialchars($cover); ?>" class="activity-cover-img" alt="Activity Cover">
          
          <div class="activity-main-info">
            <div class="activity-meta-row">
              <span class="cat-pill"><?php echo $catLabel; ?></span>
              <small style="color: #8C7A6D; font-weight: 600;"><?php echo date('M d, Y · g:i A', strtotime($row['created_at'])); ?></small>
              <small style="color: #888;">(📷 <?php echo $row['photo_count']; ?> photos)</small>
              
              <!-- CLEAN STATUS BADGE WITHOUT EMOJI -->
              <span id="act-status-badge-<?php echo $row['id']; ?>">
                <?php if ($isHidden): ?>
                  <span style="background: #FEE2E2; color: #991B1B; padding: 3px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 800;">Hidden</span>
                <?php else: ?>
                  <span style="background: #D1FAE5; color: #065F46; padding: 3px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 800;">Visible</span>
                <?php endif; ?>
              </span>
            </div>

            <h3 class="activity-title-text"><?php echo htmlspecialchars($row['title']); ?></h3>
            <p class="activity-snippet-text"><?php echo htmlspecialchars($row['content']); ?></p>

            <!-- PHOTO THUMBNAIL STRIP FOR ADMIN DISPLAY -->
            <?php if (!empty($actPhotos)): ?>
              <div style="display: flex; gap: 8px; margin-top: 10px; flex-wrap: wrap;">
                <?php foreach ($actPhotos as $ap): ?>
                  <img src="../<?php echo htmlspecialchars($ap['image_path']); ?>" alt="" style="width: 44px; height: 44px; border-radius: 8px; object-fit: cover; border: 1.5px solid #E8DDD0; background: #FAF4EB;">
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <div class="activity-actions-col">
            <button type="button" class="btn-action-edit" onclick="openAdminEditModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['title'])); ?>', '<?php echo $row['category']; ?>', '<?php echo htmlspecialchars(addslashes($row['content'])); ?>', <?php echo $photosJson; ?>)">
              Edit Post
            </button>
            <button type="button" class="btn-action-hide" id="act-hide-btn-<?php echo $row['id']; ?>" onclick="toggleActivityHide(this, <?php echo $row['id']; ?>, <?php echo (int)$row['is_hidden']; ?>)">
              <?php echo $isHidden ? 'Unhide' : 'Hide'; ?>
            </button>
            <a href="manage_activities.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('⚠️ Are you sure you want to delete this activity post?')" class="btn-action-delete">Delete</a>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="text-align: center; color: #7A685A; padding: 40px; background: #FFFFFF; border-radius: 20px; border: 1.5px solid #E8DDD0;">No store activities published yet. Click "+ Create Activity Post" above to publish one!</p>
    <?php endif; ?>
  </div>

</div>

<!-- CREATE ACTIVITY MODAL -->
<div id="createActivityModal" class="admin-modal-overlay">
  <div class="admin-modal-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1.5px solid #F4EDE4;">
      <h2 style="font-family: var(--font-heading); color: #2C1C14; margin: 0; font-size: 1.25rem; font-weight: 800;">
        Create Store Activity Post
      </h2>
      <button type="button" onclick="closeAdminModal('createActivityModal')" style="background: transparent; border: none; font-size: 1.6rem; color: #7A685A; cursor: pointer; line-height: 1;">&times;</button>
    </div>

    <form id="createActivityForm" action="manage_activities.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="form_type" value="create_activity">
      
      <div style="margin-bottom: 18px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:8px;">Activity Title</label>
        <input type="text" name="title" id="createTitle" placeholder="e.g. Monthly Corporate Coffee Day with XX Enterprise">
        <div id="createTitleError" class="form-field-error" style="color: #DC2626; font-size: 0.8rem; font-weight: 700; margin-top: 6px; display: none;">Please enter an activity title.</div>
      </div>

      <div style="margin-bottom: 18px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:8px;">Category Tag</label>
        <select name="category">
          <option value="event">Event</option>
          <option value="promotion">Promotion</option>
          <option value="workshop">Workshop</option>
          <option value="daily">Daily Moment</option>
          <option value="announcement">Store Announcement</option>
        </select>
      </div>

      <div style="margin-bottom: 18px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:8px;">Story Content &amp; Details</label>
        <textarea name="content" id="createContent" rows="5" placeholder="Write full details about this store activity..."></textarea>
        <div id="createContentError" class="form-field-error" style="color: #DC2626; font-size: 0.8rem; font-weight: 700; margin-top: 6px; display: none;">Please enter story content &amp; details.</div>
      </div>

      <div style="margin-bottom: 24px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:8px;">Upload Photos (Optional, max 5 photos total)</label>
        <input type="file" name="photos[]" id="createPhotosInput" accept="image/*" multiple onchange="handleFileAccumulate('create')">
        
        <!-- ACCUMULATED LIVE PHOTO PREVIEW GRID -->
        <div id="createPreviewGrid" style="display: none; gap: 12px; flex-wrap: wrap; margin-top: 14px; padding: 12px; background: #FAF7F2; border: 1.5px dashed #E8DDD0; border-radius: 14px;"></div>
      </div>

      <button type="submit" class="btn-create-post" style="width: 100%; justify-content: center; height: 48px; border-radius: 25px; font-size: 0.95rem;">Publish Activity</button>
    </form>
  </div>
</div>

<!-- EDIT ACTIVITY MODAL -->
<div id="editActivityModal" class="admin-modal-overlay">
  <div class="admin-modal-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1.5px solid #F4EDE4;">
      <h2 style="font-family: var(--font-heading); color: #2C1C14; margin: 0; font-size: 1.25rem; font-weight: 800;">
        Edit Store Activity Post
      </h2>
      <button type="button" onclick="closeAdminModal('editActivityModal')" style="background: transparent; border: none; font-size: 1.6rem; color: #7A685A; cursor: pointer; line-height: 1;">&times;</button>
    </div>

    <form id="editActivityForm" action="manage_activities.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="form_type" value="edit_activity">
      <input type="hidden" name="edit_activity_id" id="editActivityId">
      
      <div id="editDeletedPhotoInputs"></div>

      <div style="margin-bottom: 18px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:8px;">Activity Title</label>
        <input type="text" name="edit_title" id="editTitle">
        <div id="editTitleError" class="form-field-error" style="color: #DC2626; font-size: 0.8rem; font-weight: 700; margin-top: 6px; display: none;">Please enter an activity title.</div>
      </div>

      <div style="margin-bottom: 18px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:8px;">Category Tag</label>
        <select name="edit_category" id="editCategory">
          <option value="event">Event</option>
          <option value="promotion">Promotion</option>
          <option value="workshop">Workshop</option>
          <option value="daily">Daily Moment</option>
          <option value="announcement">Store Announcement</option>
        </select>
      </div>

      <div style="margin-bottom: 18px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:8px;">Story Content &amp; Details</label>
        <textarea name="edit_content" id="editContent" rows="5"></textarea>
        <div id="editContentError" class="form-field-error" style="color: #DC2626; font-size: 0.8rem; font-weight: 700; margin-top: 6px; display: none;">Please enter story content &amp; details.</div>
      </div>

      <div id="editExistingPhotosSection" style="display:none; margin-bottom:20px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:8px;">Current Photos (Click ✖ on any photo to remove)</label>
        <div id="editExistingPhotosGrid" style="display:flex; gap:12px; flex-wrap:wrap;"></div>
      </div>

      <div style="margin-bottom: 24px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:8px;">Add New Photos (Optional, max 5 photos total)</label>
        <input type="file" name="edit_photos[]" id="editPhotosInput" accept="image/*" multiple onchange="handleFileAccumulate('edit')">
        
        <!-- ACCUMULATED LIVE PHOTO PREVIEW GRID -->
        <div id="editPreviewGrid" style="display: none; gap: 12px; flex-wrap: wrap; margin-top: 14px; padding: 12px; background: #FAF7F2; border: 1.5px dashed #E8DDD0; border-radius: 14px;"></div>
      </div>

      <button type="submit" class="btn-create-post" style="width: 100%; justify-content: center; height: 48px; border-radius: 25px; font-size: 0.95rem;">Save Changes</button>
    </form>
  </div>
</div>

<script>
let createStagedFiles = [];
let editStagedFiles = [];

// Form Validation & Sync DataTransfer on Form Submit + Scroll Position Preservation
document.addEventListener('DOMContentLoaded', function() {
    // Restore scroll position after edit submission
    const savedScroll = sessionStorage.getItem('admin_act_scroll');
    if (savedScroll) {
        window.scrollTo({ top: parseInt(savedScroll), behavior: 'instant' });
        sessionStorage.removeItem('admin_act_scroll');
    } else if (window.location.hash) {
        const targetEl = document.querySelector(window.location.hash);
        if (targetEl) {
            targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    ['createActivityForm', 'editActivityForm'].forEach(formId => {
        const form = document.getElementById(formId);
        if (form) {
            form.addEventListener('submit', function(e) {
                const prefix = formId === 'createActivityForm' ? 'create' : 'edit';
                if (!validateActivityForm(prefix, e)) {
                    return false;
                }
                syncInputFiles(prefix);
                if (prefix === 'edit') {
                    sessionStorage.setItem('admin_act_scroll', window.scrollY);
                }
            });
        }
    });

    // Realtime error message clearing on input
    const inputsMap = [
        { id: 'createTitle', errId: 'createTitleError' },
        { id: 'createContent', errId: 'createContentError' },
        { id: 'editTitle', errId: 'editTitleError' },
        { id: 'editContent', errId: 'editContentError' }
    ];

    inputsMap.forEach(item => {
        const inputEl = document.getElementById(item.id);
        const errEl = document.getElementById(item.errId);
        if (inputEl) {
            inputEl.addEventListener('input', function() {
                if (inputEl.value.trim() !== '') {
                    if (errEl) errEl.style.display = 'none';
                    inputEl.style.borderColor = '#E8DDD0';
                }
            });
        }
    });
});

// INSTANT CLIENT-SIDE CATEGORY AND TIME PERIOD FILTERING
function applyAdminFilterSort() {
    const catVal = document.getElementById('adminCatFilter').value;
    const periodVal = document.getElementById('adminPeriodFilter').value;
    const items = document.querySelectorAll('.admin-activity-item');
    const nowSec = Math.floor(Date.now() / 1000);
    const oneWeekSec = 7 * 24 * 60 * 60;
    
    // Calculate start of current month in Unix Timestamp
    const now = new Date();
    const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
    const startOfMonthSec = Math.floor(startOfMonth.getTime() / 1000);

    let visibleCount = 0;

    items.forEach(item => {
        const itemCat = item.getAttribute('data-category');
        const itemCreated = parseInt(item.getAttribute('data-created') || '0', 10);

        let matchesCat = (catVal === 'all' || itemCat === catVal);
        let matchesPeriod = true;

        if (periodVal === 'week') {
            matchesPeriod = (nowSec - itemCreated) <= oneWeekSec;
        } else if (periodVal === 'month') {
            matchesPeriod = (itemCreated >= startOfMonthSec);
        }

        if (matchesCat && matchesPeriod) {
            item.style.display = 'flex';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    const countEl = document.getElementById('filterResultsCount');
    if (countEl) {
        countEl.textContent = `Showing ${visibleCount} activity post(s)`;
    }

    const listContainer = document.querySelector('.admin-activity-list');
    let noMatchMsg = document.getElementById('adminNoFilterMatchMsg');
    if (visibleCount === 0) {
        if (!noMatchMsg) {
            noMatchMsg = document.createElement('div');
            noMatchMsg.id = 'adminNoFilterMatchMsg';
            noMatchMsg.style.cssText = 'text-align: center; color: #7A685A; padding: 40px; background: #FFFFFF; border-radius: 20px; border: 1.5px solid #E8DDD0; font-weight: 700;';
            noMatchMsg.textContent = 'No activity posts match your selected category and time filter.';
            listContainer.appendChild(noMatchMsg);
        } else {
            noMatchMsg.style.display = 'block';
        }
    } else if (noMatchMsg) {
        noMatchMsg.style.display = 'none';
    }
}

function validateActivityForm(prefix, e) {
    const titleInput = document.getElementById(prefix === 'create' ? 'createTitle' : 'editTitle');
    const contentInput = document.getElementById(prefix === 'create' ? 'createContent' : 'editContent');
    const titleErr = document.getElementById(prefix === 'create' ? 'createTitleError' : 'editTitleError');
    const contentErr = document.getElementById(prefix === 'create' ? 'createContentError' : 'editContentError');

    const titleVal = titleInput ? titleInput.value.trim() : '';
    const contentVal = contentInput ? contentInput.value.trim() : '';
    let isValid = true;

    if (!titleVal) {
        if (titleErr) titleErr.style.display = 'block';
        if (titleInput) titleInput.style.borderColor = '#DC2626';
        isValid = false;
    } else {
        if (titleErr) titleErr.style.display = 'none';
        if (titleInput) titleInput.style.borderColor = '#E8DDD0';
    }

    if (!contentVal) {
        if (contentErr) contentErr.style.display = 'block';
        if (contentInput) contentInput.style.borderColor = '#DC2626';
        isValid = false;
    } else {
        if (contentErr) contentErr.style.display = 'none';
        if (contentInput) contentInput.style.borderColor = '#E8DDD0';
    }

    if (!isValid && e) {
        e.preventDefault();
        if (!titleVal && titleInput) titleInput.focus();
        else if (!contentVal && contentInput) contentInput.focus();
    }

    return isValid;
}

function openAdminModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        const prefix = modalId === 'createActivityModal' ? 'create' : 'edit';
        const titleErr = document.getElementById(prefix + 'TitleError');
        const contentErr = document.getElementById(prefix + 'ContentError');
        const titleInput = document.getElementById(prefix === 'create' ? 'createTitle' : 'editTitle');
        const contentInput = document.getElementById(prefix === 'create' ? 'createContent' : 'editContent');
        if (titleErr) titleErr.style.display = 'none';
        if (contentErr) contentErr.style.display = 'none';
        if (titleInput) titleInput.style.borderColor = '#E8DDD0';
        if (contentInput) contentInput.style.borderColor = '#E8DDD0';
        if (modalId === 'createActivityModal') {
            createStagedFiles = [];
            renderStagedPreviews('create');
        }
    }
}

function closeAdminModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        if (modalId === 'createActivityModal') {
            createStagedFiles = [];
            renderStagedPreviews('create');
        } else if (modalId === 'editActivityModal') {
            editStagedFiles = [];
            renderStagedPreviews('edit');
        }
    }
}

function toggleActivityHide(btnEl, actId, isHidden) {
    const newStatus = isHidden === 1 ? 0 : 1;
    btnEl.disabled = true;
    btnEl.style.opacity = '0.6';

    fetch(`manage_activities.php?ajax_toggle_hide=${actId}&status=${newStatus}`)
    .then(r => r.json())
    .then(data => {
        btnEl.disabled = false;
        btnEl.style.opacity = '1';
        if (data.status === 'success') {
            const badgeEl = document.getElementById(`act-status-badge-${actId}`);
            if (newStatus === 1) {
                btnEl.innerHTML = 'Unhide';
                btnEl.onclick = function() { toggleActivityHide(this, actId, 1); };
                if (badgeEl) badgeEl.innerHTML = '<span style="background: #FEE2E2; color: #991B1B; padding: 3px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 800;">Hidden</span>';
            } else {
                btnEl.innerHTML = 'Hide';
                btnEl.onclick = function() { toggleActivityHide(this, actId, 0); };
                if (badgeEl) badgeEl.innerHTML = '<span style="background: #D1FAE5; color: #065F46; padding: 3px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 800;">Visible</span>';
            }
        } else {
            alert('Failed to update status.');
        }
    })
    .catch(err => {
        btnEl.disabled = false;
        btnEl.style.opacity = '1';
        alert('Network error.');
    });
}

function openAdminEditModal(id, title, category, content, photos) {
    document.getElementById('editActivityId').value = id;
    document.getElementById('editTitle').value = title;
    document.getElementById('editCategory').value = category;
    document.getElementById('editContent').value = content;
    
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

// ACCUMULATIVE STAGED MULTI-FILE UPLOADING SYSTEM
function handleFileAccumulate(prefix) {
    const inputId = prefix === 'create' ? 'createPhotosInput' : 'editPhotosInput';
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
    const inputId = prefix === 'create' ? 'createPhotosInput' : 'editPhotosInput';
    const input = document.getElementById(inputId);
    const stagedArr = prefix === 'create' ? createStagedFiles : editStagedFiles;
    if (!input) return;

    try {
        const dt = new DataTransfer();
        stagedArr.forEach(file => dt.items.add(file));
        input.files = dt.files;
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
            previewCard.style.cssText = 'position: relative; width: 80px; height: 80px; border-radius: 12px; overflow: hidden; border: 2px solid #C85A3E; box-shadow: 0 4px 12px rgba(200,90,62,0.25); flex-shrink: 0; background: #FAF4EB;';
            
            previewCard.innerHTML = `
                <img src="${e.target.result}" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
                <button type="button" onclick="removeStagedFile('${prefix}', ${idx})" style="position: absolute; top: 4px; right: 4px; width: 22px; height: 22px; border-radius: 50%; background: #DC2626; color: #FFFFFF; border: none; font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 13px; box-shadow: 0 2px 6px rgba(0,0,0,0.3);" title="Remove photo">&times;</button>
                <span style="position: absolute; bottom: 3px; right: 3px; background: rgba(200,90,62,0.92); color: #ffffff; font-size: 0.6rem; font-weight: 800; padding: 2px 5px; border-radius: 5px;">NEW</span>
            `;
            grid.appendChild(previewCard);
        };
        reader.readAsDataURL(file);
    });
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

<?php require_once '../includes/admin_footer.php'; ?>

</body>
</html>
