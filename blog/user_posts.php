<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

$targetUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Fetch Target User Info
$uStmt = $conn->prepare('SELECT username FROM users WHERE id = ?');
$uStmt->bind_param('i', $targetUserId);
$uStmt->execute();
$uRes = $uStmt->get_result();
$targetUser = $uRes->fetch_assoc();
$uStmt->close();

if (!$targetUser) {
    die("User not found.");
}

// Fetch Posts by Target User
$pStmt = $conn->prepare('SELECT * FROM blog_posts WHERE user_id = ? ORDER BY created_at DESC');
$pStmt->bind_param('i', $targetUserId);
$pStmt->execute();
$userPosts = $pStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style/mystyle.css">
    <title><?php echo htmlspecialchars($targetUser['username']); ?>'s Coffee History</title>
    <style>
        .blog-container { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        .post-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 10px; padding: 20px; margin-bottom: 25px; }
        .mood-badge { background: #8c6d58; color: #fff; padding: 4px 10px; border-radius: 15px; font-size: 0.85em; }
        .photo-gallery { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px; }
        .photo-gallery img { width: 130px; height: 130px; object-fit: cover; border-radius: 8px; }
    </style>
</head>

<body>
<nav>
  <div class="logo"><a href="../home/index.php">Cozy Coffee Co.</a></div>
  <ul class="nav-links">
    <li><a href="../home/index.php">Home</a></li>
    <li><a href="../menu/index.php">Menu</a></li>
    <li><a href="index.php" class="active">Blog</a></li>
        <li><a href="../benefits/index.php">Benefits</a></li>
    <li>
      <a href="../offers/index.php">Offers ▾</a>
      <div class="dropdown">
        <a href="../offers/index.php#drinks">Drink Offers</a>
        <a href="../offers/index.php#food">Food Offers</a>
        <a href="../offers/index.php#partners">Partner Promotions</a>
      </div>
    </li>
    <li>
      <a href="../activities/index.php">Activities ▾</a>
      <div class="dropdown">
        <a href="../activities/index.php#workshops">Coffee Workshops</a>
        <a href="../activities/index.php#giveback">Cozy Give-Back</a>
      </div>
    </li>
    <li><a href="../contact/index.php">Contact</a></li>
    <li><a href="../cart/index.php">Cart</a></li>
    <?php if ($isLoggedIn): ?>
      <li><a href="../logout.php">Logout (<?php echo htmlspecialchars($currentUsername); ?>)</a></li>
    <?php else: ?>
      <li><a href="../login/index.php">Login</a></li>
    <?php endif; ?>
  </ul>
</nav>

<div class="blog-container">
    <p><a href="index.php" style="color: #8c6d58;">&larr; Back to Blog Feed</a></p>
    <h1><?php echo htmlspecialchars($targetUser['username']); ?>'s Memories</h1>

    <?php if ($userPosts && $userPosts->num_rows > 0): ?>
        <?php while ($post = $userPosts->fetch_assoc()): ?>
            <div class="post-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="mood-badge"><?php echo htmlspecialchars($post['mood']); ?></span>
                    <small style="color: #888;"><?php echo date('M d, Y · g:i A', strtotime($post['created_at'])); ?></small>
                </div>

                <?php if (!empty($post['ordered_item'])): ?>
                    <p style="margin-top: 10px; color: #666;">☕ Ordered: <strong><?php echo htmlspecialchars($post['ordered_item']); ?></strong></p>
                <?php endif; ?>

                <?php if (!empty($post['description'])): ?>
                    <p style="line-height: 1.5; color: #333; margin-top: 10px;"><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
                <?php endif; ?>

                <?php
                $pid = (int) $post['id'];
                $photoStmt = $conn->prepare('SELECT image_path FROM blog_photos WHERE post_id = ?');
                $photoStmt->bind_param('i', $pid);
                $photoStmt->execute();
                $photoRes = $photoStmt->get_result();
                if ($photoRes && $photoRes->num_rows > 0):
                ?>
                    <div class="photo-gallery">
                        <?php while ($photo = $photoRes->fetch_assoc()): ?>
                            <img src="../<?php echo htmlspecialchars($photo['image_path']); ?>" alt="Coffee photo">
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>This user hasn't posted any memories yet.</p>
    <?php endif; ?>
</div>

</body>
</html>
