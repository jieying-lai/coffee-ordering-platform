<?php
require_once '../includes/admin_auth_check.php';
require_once '../includes/db_connect.php';

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

<nav class="admin-nav-bar" style="background: var(--color-primary, #3C2A21); color: #fff; padding: 14px 5%; position: sticky; top: 0; z-index: 9999; box-shadow: 0 4px 14px rgba(0,0,0,0.15);">
  <div style="max-width: 1400px; margin: 0 auto; width: 100%; display: flex; justify-content: space-between; align-items: center;">
    
    <div style="font-weight: 800; font-size: 1.15rem; color: #fff;">
      <a href="dashboard.php" style="color: #fff; text-decoration: none; display: flex; align-items: center; gap: 8px;">
        <span>☕</span> Cozy Barista Admin Portal
      </a>
    </div>

    <button class="admin-hamburger" id="adminNavToggle" aria-label="Toggle Admin Menu" style="display: none; flex-direction: column; justify-content: space-between; width: 28px; height: 20px; background: transparent; border: none; cursor: pointer; padding: 0;">
      <span style="display: block; height: 3px; width: 100%; background: #ffffff; border-radius: 3px;"></span>
      <span style="display: block; height: 3px; width: 100%; background: #ffffff; border-radius: 3px;"></span>
      <span style="display: block; height: 3px; width: 100%; background: #ffffff; border-radius: 3px;"></span>
    </button>

    <div class="admin-nav-links" id="adminNavMenu">
      <a href="dashboard.php">📋 Dashboard</a>
      <a href="manage_orders.php">📦 Orders</a>
      <a href="manage_chat.php">💬 Customer Chat</a>
      <a href="manage_menu.php">☕ Menu</a>
      <a href="manage_users.php">👤 Users</a>
      <a href="manage_blog.php" class="admin-nav-active">📸 Blog</a>
      <a href="manage_contact.php">📍 Contact/About</a>
      <a href="logout.php" style="color: #f87171 !important; text-decoration: none; padding: 6px 12px; border-radius: 6px; background: rgba(239, 68, 68, 0.15);">Logout</a>
    </div>

  </div>
</nav>

<script>
  document.getElementById('adminNavToggle')?.addEventListener('click', function() {
    document.getElementById('adminNavMenu')?.classList.toggle('admin-menu-active');
  });
</script>

<div class="admin-wrap">
  <a href="dashboard.php" class="admin-back">← Back to Dashboard</a>
  <h1>Manage Blog Posts</h1>
  <p class="admin-subtitle">Moderate community posts by marking policy violations or managing trash storage.</p>

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
              <?php if (!empty($p['is_hidden'])): ?>
                <br><span class="status-badge status-hidden">Violation (Hidden)</span>
              <?php endif; ?>
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
                <?php if (empty($p['is_hidden'])): ?>
                  <a href="manage_blog.php?toggle_hide=<?php echo $p['id']; ?>&status=1" 
                     class="admin-btn admin-btn-sm btn-hide"
                     onclick="return confirm('Hide this post for community guidelines violation?');">Hide</a>
                <?php else: ?>
                  <a href="manage_blog.php?toggle_hide=<?php echo $p['id']; ?>&status=0" 
                     class="admin-btn admin-btn-sm btn-unhide">Unhide</a>
                <?php endif; ?>

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

</body>
</html>