<?php
require_once '../includes/admin_auth_check.php';
require_once '../includes/db_connect.php';

// ============ AJAX TOGGLE HIDE / MODERATE STATUS (NO PAGE REFRESH) ============
if (isset($_GET['ajax_toggle_hide'])) {
    header('Content-Type: application/json');
    $postId = (int) $_GET['ajax_toggle_hide'];
    $status = (int) $_GET['status']; // 1 = Hide, 0 = Unhide

    $stmt = $conn->prepare('UPDATE blog_posts SET is_hidden = ? WHERE id = ?');
    $stmt->bind_param('ii', $status, $postId);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['status' => $ok ? 'success' : 'error', 'new_status' => $status]);
    exit;
}

$message = '';
$messageType = '';

// ============ TOGGLE HIDE / MODERATE ============
if (isset($_GET['toggle_hide'])) {
    $postId = (int) $_GET['toggle_hide'];
    $status = (int) $_GET['status']; // 1 = Hide, 0 = Unhide

    $stmt = $conn->prepare('UPDATE blog_posts SET is_hidden = ? WHERE id = ?');
    $stmt->bind_param('ii', $status, $postId);
    if ($stmt->execute()) {
        $message = $status === 1 ? 'Post marked as hidden (Community Violation).' : 'Post unmarked and visible to public.';
        $messageType = 'success';
    } else {
        $message = 'Failed to update post status.';
        $messageType = 'error';
    }
    $stmt->close();
}

// ============ SOFT DELETE POST (MOVE TO TRASH) ============
if (isset($_GET['soft_delete'])) {
    $postId = (int) $_GET['soft_delete'];

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
    $postId = (int) $_GET['restore'];

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
    $postId = (int) $_GET['perm_delete'];

    // 1. Delete photo files from disk
    $photoQuery = $conn->prepare('SELECT image_path FROM blog_photos WHERE post_id = ?');
    $photoQuery->bind_param('i', $postId);
    $photoQuery->execute();
    $photoResult = $photoQuery->get_result();

    while ($photo = $photoResult->fetch_assoc()) {
        $filePath = '../' . $photo['image_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
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

// Check active view (Active Posts vs Trash Storage)
$viewTrash = isset($_GET['view']) && $_GET['view'] === 'trash';
$deletedFilter = $viewTrash ? 1 : 0;

// ============ FETCH POSTS ============
$postsQuery = "SELECT bp.*, u.username, u.profile_pic 
              FROM blog_posts bp 
              JOIN users u ON u.id = bp.user_id 
              WHERE bp.is_deleted = $deletedFilter
              ORDER BY bp.created_at DESC";
$posts = $conn->query($postsQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/admin.css">
  <link rel="stylesheet" href="../style/blog.css">
  <title>Cozy Coffee Co. — Manage Blog Posts</title>
</head>
<body class="admin-page">

<?php $adminActivePage = 'blog'; require_once '../includes/admin_header_nav.php'; ?>

<div class="admin-wrap">
  <div class="admin-page-header">
    <a href="dashboard.php" class="btn-back-dashboard">&larr; Back to Dashboard</a>
    <h1 class="admin-header-title">Manage Community Blog</h1>
    <p class="admin-header-subtitle">Moderate community posts by marking policy violations or managing trash storage.</p>
  </div>

  <?php if ($message): ?>
    <div class="admin-alert admin-alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <!-- Filter & View Controls -->
  <div class="table-controls">
    <input type="text" id="searchInput" onkeyup="filterPosts()" placeholder="Search username, item, mood, or content...">
    <div>
      <a href="manage_blog.php" class="admin-tab <?php echo !$viewTrash ? 'active' : ''; ?>">Active Posts</a>
      <a href="manage_blog.php?view=trash" class="admin-tab <?php echo $viewTrash ? 'active' : ''; ?>">Trash Bin Storage</a>
    </div>
  </div>

  <!-- BLOG POSTS TABLE -->
  <div class="admin-table-wrap">
    <table class="admin-table" id="blogTable">
      <thead>
        <tr>
          <th>User</th>
          <th>Ordered Item</th>
          <th>Mood</th>
          <th>Content</th>
          <th>Photos</th>
          <th>Posted At</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$posts || $posts->num_rows === 0): ?>
          <tr><td colspan="7">No blog posts found in this view.</td></tr>
        <?php endif; ?>
        <?php while ($p = $posts->fetch_assoc()): ?>
          <tr class="blog-row" data-search="<?php echo strtolower(htmlspecialchars($p['username'] . ' ' . $p['ordered_item'] . ' ' . $p['mood'] . ' ' . $p['description'])); ?>">
            <td>
              <strong>@<?php echo htmlspecialchars($p['username']); ?></strong>
              <span id="blog-status-badge-<?php echo $p['id']; ?>">
                <?php if (!empty($p['is_hidden'])): ?>
                  <br><span class="status-badge status-hidden">Violation (Hidden)</span>
                <?php endif; ?>
              </span>
            </td>
            <td><?php echo htmlspecialchars($p['ordered_item'] ?: '—'); ?></td>
            <td class="col-mood">
              <div class="mood-container">
                <?php 
                $moodArray = array_map('trim', explode(',', $p['mood']));
                foreach ($moodArray as $singleMood):
                  if (empty($singleMood)) continue;
                ?>
                  <span class="mood-badge"><?php echo htmlspecialchars($singleMood); ?></span>
                <?php endforeach; ?>
              </div>
            </td>
            <td class="post-preview-desc" title="<?php echo htmlspecialchars($p['description']); ?>">
              <?php echo htmlspecialchars($p['description'] ?: '—'); ?>
            </td>
            <td>
              <div class="thumb-gallery">
                <?php
                $pid = (int)$p['id'];
                $photos = $conn->query("SELECT image_path FROM blog_photos WHERE post_id = $pid");
                if ($photos && $photos->num_rows > 0):
                  while ($img = $photos->fetch_assoc()):
                ?>
                    <a href="../<?php echo htmlspecialchars($img['image_path']); ?>" target="_blank">
                      <img src="../<?php echo htmlspecialchars($img['image_path']); ?>" alt="Thumbnail">
                    </a>
                <?php 
                  endwhile;
                else: 
                  echo '—';
                endif; 
                ?>
              </div>
            </td>
            <td><?php echo date('d M Y, g:i A', strtotime($p['created_at'])); ?></td>
            <td class="admin-actions">
              <?php if (!$viewTrash): ?>
                <button type="button" class="admin-btn admin-btn-sm <?php echo empty($p['is_hidden']) ? 'btn-hide' : 'btn-unhide'; ?>" id="blog-hide-btn-<?php echo $p['id']; ?>" onclick="toggleBlogHide(this, <?php echo $p['id']; ?>, <?php echo (int)$p['is_hidden']; ?>)">
                  <?php echo empty($p['is_hidden']) ? '🔒 Hide' : '🔓 Unhide'; ?>
                </button>

                <a href="manage_blog.php?soft_delete=<?php echo $p['id']; ?>" 
                   class="admin-btn admin-btn-danger admin-btn-sm" 
                   onclick="return confirm('Move this post to Trash Bin?');">Delete</a>
              <?php else: ?>
                <a href="manage_blog.php?restore=<?php echo $p['id']; ?>&view=trash" 
                   class="admin-btn admin-btn-outline admin-btn-sm">Restore</a>
                <a href="manage_blog.php?perm_delete=<?php echo $p['id']; ?>&view=trash" 
                   class="admin-btn admin-btn-danger admin-btn-sm" 
                   onclick="return confirm('Permanently delete this post and photos? This cannot be undone.');">Perm. Delete</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function toggleBlogHide(btnEl, postId, isHidden) {
    const newStatus = isHidden === 1 ? 0 : 1;
    if (newStatus === 1 && !confirm('Hide this post for community guidelines violation?')) {
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
                btnEl.innerHTML = '🔓 Unhide';
                btnEl.className = 'admin-btn admin-btn-sm btn-unhide';
                btnEl.onclick = function() { toggleBlogHide(this, postId, 1); };
                if (badgeEl) badgeEl.innerHTML = '<br><span class="status-badge status-hidden">Violation (Hidden)</span>';
            } else {
                btnEl.innerHTML = '🔒 Hide';
                btnEl.className = 'admin-btn admin-btn-sm btn-hide';
                btnEl.onclick = function() { toggleBlogHide(this, postId, 0); };
                if (badgeEl) badgeEl.innerHTML = '';
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

function filterPosts() {
  const searchVal = document.getElementById('searchInput').value.toLowerCase().trim();
  const rows = document.querySelectorAll('#blogTable .blog-row');

  rows.forEach(row => {
    const searchData = row.getAttribute('data-search');
    if (searchData.includes(searchVal)) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>

</body>
</html>