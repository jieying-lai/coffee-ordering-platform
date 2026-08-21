<?php
require_once '../includes/admin_auth_check.php';
require_once '../includes/db_connect.php';

// ============ AJAX TOGGLE HIDE / MODERATE STATUS (NO PAGE REFRESH) ============
if (isset($_GET['ajax_toggle_hide'])) {
    header('Content-Type: application/json');
    $postId = (int)$_GET['ajax_toggle_hide'];
    $status = (int)$_GET['status']; // 1 = Hide, 0 = Unhide

    $stmt = $conn->prepare('UPDATE blog_posts SET is_hidden = ? WHERE id = ?');
    $stmt->bind_param('ii', $status, $postId);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['status' => $ok ? 'success' : 'error', 'new_status' => $status]);
    exit;
}

$message = '';
$messageType = '';

// ============ SOFT DELETE POST (MOVE TO TRASH) ============
if (isset($_GET['soft_delete'])) {
    $postId = (int)$_GET['soft_delete'];

    $stmt = $conn->prepare('UPDATE blog_posts SET is_deleted = 1 WHERE id = ?');
    $stmt->bind_param('i', $postId);
    if ($stmt->execute()) {
        $message = 'Post moved to Trash Bin.';
        $messageType = 'success';
    } else {
        $message = 'Failed to delete post.';
        $messageType = 'error';
    }
    $stmt->close();
}

// ============ RESTORE POST FROM TRASH ============
if (isset($_GET['restore'])) {
    $postId = (int)$_GET['restore'];

    $stmt = $conn->prepare('UPDATE blog_posts SET is_deleted = 0 WHERE id = ?');
    $stmt->bind_param('i', $postId);
    if ($stmt->execute()) {
        $message = 'Post restored from Trash Bin.';
        $messageType = 'success';
    } else {
        $message = 'Failed to restore post.';
        $messageType = 'error';
    }
    $stmt->close();
}

// ============ PERMANENT DELETE POST ============
if (isset($_GET['perm_delete'])) {
    $postId = (int)$_GET['perm_delete'];

    // 1. Delete photo files from disk
    $photoQuery = $conn->prepare('SELECT image_path FROM blog_photos WHERE post_id = ?');
    $photoQuery->bind_param('i', $postId);
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

    // 2. Delete database records
    $delPhotos = $conn->prepare('DELETE FROM blog_photos WHERE post_id = ?');
    $delPhotos->bind_param('i', $postId);
    $delPhotos->execute();
    $delPhotos->close();

    $delPost = $conn->prepare('DELETE FROM blog_posts WHERE id = ?');
    $delPost->bind_param('i', $postId);
    if ($delPost->execute()) {
        $message = 'Post permanently deleted.';
        $messageType = 'success';
    } else {
        $message = 'Failed to permanently delete post.';
        $messageType = 'error';
    }
    $delPost->close();
}

// Check active view (Active Posts vs Trash Bin Storage)
$viewTrash = isset($_GET['view']) && $_GET['view'] === 'trash';
$deletedFilter = $viewTrash ? 1 : 0;

// Fetch Counts for Active vs Trash Tabs
$activeCountRes = $conn->query("SELECT COUNT(*) as cnt FROM blog_posts WHERE is_deleted = 0");
$activeCount = $activeCountRes ? (int)$activeCountRes->fetch_assoc()['cnt'] : 0;

$trashCountRes = $conn->query("SELECT COUNT(*) as cnt FROM blog_posts WHERE is_deleted = 1");
$trashCount = $trashCountRes ? (int)$trashCountRes->fetch_assoc()['cnt'] : 0;

// ============ FETCH POSTS ============
$postsQuery = "SELECT bp.*, u.username, u.fullname 
              FROM blog_posts bp 
              JOIN users u ON u.id = bp.user_id 
              WHERE bp.is_deleted = $deletedFilter
              ORDER BY bp.created_at DESC";
$posts = $conn->query($postsQuery);

// DISTINCT MOOD BADGE COLORS
$moodColorMap = [
    'happy'     => ['bg' => '#FEF3C7', 'color' => '#D97706', 'border' => '#FCD34D'],
    'relaxed'   => ['bg' => '#E0F2FE', 'color' => '#0284C7', 'border' => '#BAE6FD'],
    'energized' => ['bg' => '#FFEDD5', 'color' => '#EA580C', 'border' => '#FED7AA'],
    'cozy'      => ['bg' => '#FAF4EB', 'color' => '#8C5A3E', 'border' => '#E8DDD0'],
    'nostalgic' => ['bg' => '#DCFCE7', 'color' => '#15803D', 'border' => '#BBF7D0'],
    'grateful'  => ['bg' => '#F3E8FF', 'color' => '#7E22CE', 'border' => '#E9D5FF'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/admin.css">
  <title>Cozy Coffee Co. — Manage Community Blog</title>
  <style>
    body.admin-page {
      background: #FAF6F0 !important;
      color: #2C1C14;
    }

    .admin-blog-wrap {
      max-width: 1240px;
      margin: 20px auto 60px;
      padding: 0 4%;
      box-sizing: border-box;
    }

    /* TABS NAV */
    .blog-tab-btn {
      padding: 9px 20px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 800;
      font-size: 0.85rem;
      transition: all 0.25s ease;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .blog-tab-btn.active-tab {
      background: linear-gradient(135deg, #C85A3E 0%, #A8472F 100%);
      color: #FFFFFF !important;
      box-shadow: 0 4px 14px rgba(200, 90, 62, 0.3);
    }

    .blog-tab-btn.inactive-tab {
      color: #665447;
      background: transparent;
    }
    .blog-tab-btn.inactive-tab:hover {
      background: #FAF4EB;
      color: #C85A3E;
    }

    .blog-tab-btn.trash-active-tab {
      background: #DC2626;
      color: #FFFFFF !important;
      box-shadow: 0 4px 14px rgba(220, 38, 38, 0.3);
    }

    /* FILTER BAR */
    .admin-filter-bar {
      display: flex;
      gap: 16px;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      margin-bottom: 22px;
      padding: 16px 22px;
      background: #FFFFFF;
      border: 1.5px solid #E8DDD0;
      border-radius: 18px;
      box-shadow: 0 4px 14px rgba(60, 42, 33, 0.03);
    }

    .admin-search-input {
      padding: 9px 14px;
      border: 1.5px solid #E8DDD0;
      border-radius: 10px;
      font-size: 0.88rem;
      outline: none;
      background: #FAF7F2;
      color: #2C1C14;
      min-width: 260px;
    }

    /* ACTION BUTTONS */
    .btn-action-hide {
      padding: 7px 12px;
      background: #FEF3C7;
      color: #D97706;
      border: 1.5px solid #FCD34D;
      border-radius: 10px;
      font-weight: 800;
      font-size: 0.8rem;
      cursor: pointer;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
    .btn-action-hide:hover {
      background: #FDE68A;
    }

    .btn-action-unhide {
      padding: 7px 12px;
      background: #ECFDF5;
      color: #059669;
      border: 1.5px solid #A7F3D0;
      border-radius: 10px;
      font-weight: 800;
      font-size: 0.8rem;
      cursor: pointer;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
    .btn-action-unhide:hover {
      background: #D1FAE5;
    }

    .btn-action-delete {
      padding: 7px 12px;
      background: #FEF2F2;
      color: #DC2626;
      border: 1.5px solid #FCA5A5;
      border-radius: 10px;
      font-weight: 700;
      font-size: 0.8rem;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
    .btn-action-delete:hover {
      background: #DC2626;
      color: #FFFFFF;
    }

    .btn-action-restore {
      padding: 7px 12px;
      background: #EFF6FF;
      color: #1D4ED8;
      border: 1.5px solid #BFDBFE;
      border-radius: 10px;
      font-weight: 700;
      font-size: 0.8rem;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
    .btn-action-restore:hover {
      background: #2563EB;
      color: #FFFFFF;
    }
  </style>
</head>
<body class="admin-page">

<?php $adminActivePage = 'blog'; require_once '../includes/admin_header_nav.php'; ?>

<div class="admin-blog-wrap">

  <!-- PAGE HEADER -->
  <div class="admin-page-header">
    <a href="dashboard.php" class="btn-back-dashboard">&larr; Back to Dashboard</a>
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; width: 100%;">
      <div>
        <h1 class="admin-header-title">Manage Community Blog</h1>
        <p class="admin-header-subtitle">Moderate community moments, handle policy violations, or manage trash storage.</p>
      </div>

      <!-- TABS SWITCHER -->
      <div style="display: flex; gap: 6px; background: #FFFFFF; padding: 6px; border-radius: 30px; border: 1.5px solid #E8DDD0; box-shadow: 0 4px 12px rgba(60,42,33,0.03);">
        <a href="manage_blog.php" class="blog-tab-btn <?php echo !$viewTrash ? 'active-tab' : 'inactive-tab'; ?>">
          📝 Active Posts (<?php echo $activeCount; ?>)
        </a>
        <a href="manage_blog.php?view=trash" class="blog-tab-btn <?php echo $viewTrash ? 'trash-active-tab' : 'inactive-tab'; ?>">
          🗑️ Trash Bin (<?php echo $trashCount; ?>)
        </a>
      </div>
    </div>
  </div>

  <?php if (!empty($message)): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
          showToast(<?php echo json_encode($message); ?>, <?php echo json_encode($messageType); ?>);
      });
    </script>
  <?php endif; ?>

  <!-- FILTER & SEARCH CONTROL BAR (SEARCH USERNAME ONLY) -->
  <div class="admin-filter-bar">
    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
      <input type="text" id="blogSearchInput" class="admin-search-input" onkeyup="filterBlogTable()" placeholder="🔍 Search username...">
    </div>

    <div style="font-size: 0.85rem; color: #8C7A6D; font-weight: 700;" id="filterResultsCount">
      Showing <?php echo $posts ? $posts->num_rows : 0; ?> community post(s)
    </div>
  </div>

  <!-- BLOG POSTS LIST TABLE WRAPPER -->
  <div class="admin-table-wrap" style="background: #FFFFFF; border-radius: 20px; border: 1.5px solid #E8DDD0; padding: 20px; box-shadow: 0 6px 20px rgba(60,42,33,0.04);">
    <table class="admin-table" id="blogTable" style="width: 100%; border-collapse: collapse;">
      <thead>
        <tr>
          <th style="text-align: left; padding: 14px 16px;">Author</th>
          <th style="text-align: left; padding: 14px 16px;">Ordered Item &amp; Feeling</th>
          <th style="text-align: left; padding: 14px 16px; width: 28%;">Content</th>
          <th style="text-align: center; padding: 14px 16px;">Photos</th>
          <th style="text-align: center; padding: 14px 16px;">Posted Date</th>
          <th style="text-align: center; padding: 14px 16px;">Status</th>
          <th style="text-align: center; padding: 14px 16px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($posts && $posts->num_rows > 0): ?>
          <?php while ($p = $posts->fetch_assoc()): ?>
            <?php
              $pId = (int)$p['id'];
              $isHidden = ((int)$p['is_hidden'] === 1);
              $postedDate = !empty($p['created_at']) ? date('M d, Y · g:i A', strtotime($p['created_at'])) : 'N/A';
              
              // Fetch Photos attached to this post
              $photoRes = $conn->query("SELECT image_path FROM blog_photos WHERE post_id = $pId");
              $blogPhotos = [];
              if ($photoRes) {
                  while ($pr = $photoRes->fetch_assoc()) {
                      $blogPhotos[] = $pr['image_path'];
                  }
              }
            ?>
            <tr id="blog-row-<?php echo $pId; ?>" data-username="<?php echo htmlspecialchars(strtolower($p['username'])); ?>">
              
              <!-- AUTHOR USER INFO (NO AVATAR PHOTO) -->
              <td style="padding: 16px; vertical-align: middle;">
                <div style="font-weight: 800; color: #2C1C14; font-size: 0.9rem;">
                  @<?php echo htmlspecialchars($p['username']); ?>
                </div>
                <?php if (!empty($p['fullname'])): ?>
                  <div style="font-size: 0.78rem; color: #8A7769; margin-top: 2px;">
                    <?php echo htmlspecialchars($p['fullname']); ?>
                  </div>
                <?php endif; ?>
              </td>

              <!-- ORDERED ITEM (PLAIN TEXT, NO EMOJI) & DISTINCT COLOR FEELING BADGES -->
              <td style="padding: 16px; vertical-align: middle;">
                <div style="font-weight: 700; color: #2C1C14; font-size: 0.88rem; margin-bottom: 6px;">
                  <?php echo htmlspecialchars($p['ordered_item'] ?: '—'); ?>
                </div>
                <div>
                  <?php 
                    $moodArray = array_map('trim', explode(',', $p['mood']));
                    foreach ($moodArray as $singleMood):
                      if (empty($singleMood)) continue;
                      $cleanKey = strtolower(preg_replace('/[^a-zA-Z]/', '', $singleMood));
                      $c = $moodColorMap[$cleanKey] ?? ['bg' => '#FAF4EB', 'color' => '#8C5A3E', 'border' => '#E8DDD0'];
                  ?>
                    <span style="background: <?php echo $c['bg']; ?>; color: <?php echo $c['color']; ?>; border: 1px solid <?php echo $c['border']; ?>; font-weight: 800; padding: 3px 10px; border-radius: 16px; font-size: 0.75rem; display: inline-block; margin: 2px 2px 2px 0;">
                      <?php echo htmlspecialchars($singleMood); ?>
                    </span>
                  <?php endforeach; ?>
                </div>
              </td>

              <!-- CONTENT PREVIEW -->
              <td style="padding: 16px; vertical-align: middle;">
                <div style="color: #4A3B32; font-size: 0.86rem; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;" title="<?php echo htmlspecialchars($p['description']); ?>">
                  <?php echo htmlspecialchars($p['description'] ?: '—'); ?>
                </div>
              </td>

              <!-- PHOTOS GALLERY THUMBNAILS -->
              <td style="padding: 16px; vertical-align: middle; text-align: center;">
                <?php if (!empty($blogPhotos)): ?>
                  <div style="display: flex; gap: 6px; justify-content: center; flex-wrap: wrap;">
                    <?php foreach ($blogPhotos as $bPic): ?>
                      <a href="../<?php echo htmlspecialchars($bPic); ?>" target="_blank" title="Click to view full photo">
                        <img src="../<?php echo htmlspecialchars($bPic); ?>" alt="Photo" style="width: 42px; height: 42px; border-radius: 8px; object-fit: cover; border: 1.5px solid #E8DDD0; background: #FAF4EB;">
                      </a>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <span style="font-size: 0.8rem; color: #A09080;">—</span>
                <?php endif; ?>
              </td>

              <!-- POSTED DATE -->
              <td style="padding: 16px; vertical-align: middle; text-align: center; white-space: nowrap;">
                <span style="font-size: 0.82rem; color: #7A685A; font-weight: 600;">
                  📅 <?php echo $postedDate; ?>
                </span>
              </td>

              <!-- STATUS BADGE -->
              <td style="padding: 16px; vertical-align: middle; text-align: center; white-space: nowrap;">
                <span id="blog-status-badge-<?php echo $pId; ?>">
                  <?php if ($isHidden): ?>
                    <span style="background: #FEE2E2; color: #991B1B; font-weight: 800; font-size: 0.78rem; padding: 4px 12px; border-radius: 20px; border: 1px solid #FCA5A5;">Violation (Hidden)</span>
                  <?php else: ?>
                    <span style="background: #ECFDF5; color: #065F46; font-weight: 800; font-size: 0.78rem; padding: 4px 12px; border-radius: 20px; border: 1px solid #A7F3D0;">Visible</span>
                  <?php endif; ?>
                </span>
              </td>

              <!-- ACTIONS COL -->
              <td style="padding: 16px; vertical-align: middle; text-align: center; white-space: nowrap;">
                <div style="display: flex; gap: 6px; justify-content: center; align-items: center;">
                  <?php if (!$viewTrash): ?>
                    <!-- HIDE / UNHIDE TOGGLE BUTTON -->
                    <button type="button" class="<?php echo $isHidden ? 'btn-action-unhide' : 'btn-action-hide'; ?>" id="blog-hide-btn-<?php echo $pId; ?>" onclick="toggleBlogHide(this, <?php echo $pId; ?>, <?php echo $isHidden ? 1 : 0; ?>)">
                      <?php echo $isHidden ? 'Unhide' : 'Hide'; ?>
                    </button>

                    <!-- SOFT DELETE (MOVE TO TRASH) -->
                    <a href="manage_blog.php?soft_delete=<?php echo $pId; ?>" onclick="return confirm('⚠️ Move this post to Trash Bin?');" class="btn-action-delete" onclick="saveScrollPosition()">
                      Delete
                    </a>
                  <?php else: ?>
                    <!-- RESTORE BUTTON -->
                    <a href="manage_blog.php?restore=<?php echo $pId; ?>&view=trash" class="btn-action-restore" onclick="saveScrollPosition()">
                      Restore
                    </a>
                    
                    <!-- PERMANENT DELETE BUTTON -->
                    <a href="manage_blog.php?perm_delete=<?php echo $pId; ?>&view=trash" onclick="return confirm('⚠️ Permanently delete this post and photos? This cannot be undone.');" class="btn-action-delete" onclick="saveScrollPosition()">
                      Perm. Delete
                    </a>
                  <?php endif; ?>
                </div>
              </td>

            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" style="text-align: center; padding: 40px; color: #7A685A; font-weight: 600;">
              No community blog posts found in this view.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<script>
// Restore Scroll Position After Action Triggers
document.addEventListener('DOMContentLoaded', function() {
    const savedScroll = sessionStorage.getItem('admin_blog_scroll');
    if (savedScroll) {
        window.scrollTo({ top: parseInt(savedScroll), behavior: 'instant' });
        sessionStorage.removeItem('admin_blog_scroll');
    }
});

function saveScrollPosition() {
    sessionStorage.setItem('admin_blog_scroll', window.scrollY);
}

// AJAX HIDE / UNHIDE TOGGLE (NO PAGE REFRESH)
function toggleBlogHide(btnEl, postId, isHidden) {
    const newStatus = isHidden === 1 ? 0 : 1;
    if (newStatus === 1 && !confirm('⚠️ Mark and hide this post for community guidelines violation?')) {
        return;
    }
    btnEl.disabled = true;
    btnEl.style.opacity = '0.6';

    fetch(`manage_blog.php?ajax_toggle_hide=${postId}&status=${newStatus}`)
    .then(r => r.json())
    .then(data => {
        btnEl.disabled = false;
        btnEl.style.opacity = '1';
        if (data.status === 'success') {
            const badgeEl = document.getElementById(`blog-status-badge-${postId}`);
            if (newStatus === 1) {
                btnEl.innerHTML = 'Unhide';
                btnEl.className = 'btn-action-unhide';
                btnEl.onclick = function() { toggleBlogHide(this, postId, 1); };
                if (badgeEl) badgeEl.innerHTML = '<span style="background: #FEE2E2; color: #991B1B; font-weight: 800; font-size: 0.78rem; padding: 4px 12px; border-radius: 20px; border: 1px solid #FCA5A5;">Violation (Hidden)</span>';
                showToast('Post marked as violation (hidden).', 'success');
            } else {
                btnEl.innerHTML = 'Hide';
                btnEl.className = 'btn-action-hide';
                btnEl.onclick = function() { toggleBlogHide(this, postId, 0); };
                if (badgeEl) badgeEl.innerHTML = '<span style="background: #ECFDF5; color: #065F46; font-weight: 800; font-size: 0.78rem; padding: 4px 12px; border-radius: 20px; border: 1px solid #A7F3D0;">Visible</span>';
                showToast('Post unmarked and visible to public.', 'success');
            }
        } else {
            showToast('Failed to update status.', 'error');
        }
    })
    .catch(err => {
        btnEl.disabled = false;
        btnEl.style.opacity = '1';
        showToast('Network error.', 'error');
    });
}

// REALTIME CLIENT-SIDE SEARCH FILTERING (SEARCH USERNAME ONLY)
function filterBlogTable() {
  const searchVal = document.getElementById('blogSearchInput').value.toLowerCase().trim();
  const rows = document.querySelectorAll('#blogTable tbody tr');
  let count = 0;

  rows.forEach(row => {
    if (row.children.length === 1) return; // Skip empty row
    const username = (row.getAttribute('data-username') || '').toLowerCase();
    if (!searchVal || username.includes(searchVal)) {
      row.style.display = '';
      count++;
    } else {
      row.style.display = 'none';
    }
  });

  const countEl = document.getElementById('filterResultsCount');
  if (countEl) {
      countEl.textContent = `Showing ${count} community post(s)`;
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

<?php require_once '../includes/admin_footer.php'; ?>

</body>
</html>