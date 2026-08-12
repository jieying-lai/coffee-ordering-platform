<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

$about = $conn->query('SELECT * FROM about_us WHERE id = 1')->fetch_assoc();
$info  = $conn->query('SELECT * FROM contact_info WHERE id = 1')->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style/mystyle.css">
    <link rel="stylesheet" href="../style/contact.css">
    <title>Cozy Coffee Co. — About &amp; Contact</title>
</head>

<body>
<?php 
  $activePage = 'contact';
  require_once '../includes/header_nav.php'; 
?>

<!-- ============ ABOUT / HERO ============ -->
<section class="about-section">
  <div class="eyebrow"><?php echo htmlspecialchars($about['eyebrow'] ?? 'Our Story'); ?></div>
  <h2><?php echo htmlspecialchars($about['heading'] ?? 'Crafting Cozy Moments Daily'); ?></h2>
  <p><?php echo nl2br(htmlspecialchars($about['body_text'] ?? 'Welcome to Cozy Coffee Co.')); ?></p>
</section>

<!-- ============ CONTACT INFO & LOCATION ============ -->
<section class="contact-section">
  <div class="contact-info" style="grid-column: 1 / -1; max-width: 900px; margin: 0 auto; width: 100%;">
    <h3>Get in Touch &amp; Visit Us</h3>
    <ul>
      <li><strong>Address:</strong> <?php echo htmlspecialchars($info['address'] ?? ''); ?></li>
      <li><strong>Phone:</strong> <?php echo htmlspecialchars($info['phone'] ?? ''); ?></li>
      <li><strong>Email:</strong> <?php echo htmlspecialchars($info['email'] ?? ''); ?></li>
      <li><strong>Hours:</strong> <?php echo htmlspecialchars($info['hours'] ?? ''); ?></li>
    </ul>

    <?php if (!empty($info['map_embed_url'])): ?>
      <div class="contact-map">
        <iframe
          src="<?php echo htmlspecialchars($info['map_embed_url']); ?>"
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade">
        </iframe>
      </div>
    <?php endif; ?>

    <div class="social-links">
      <?php if (!empty($info['instagram_url'])): ?><a href="<?php echo htmlspecialchars($info['instagram_url']); ?>">Instagram</a><?php endif; ?>
      <?php if (!empty($info['facebook_url'])): ?><a href="<?php echo htmlspecialchars($info['facebook_url']); ?>">Facebook</a><?php endif; ?>
      <?php if (!empty($info['tiktok_url'])): ?><a href="<?php echo htmlspecialchars($info['tiktok_url']); ?>">TikTok</a><?php endif; ?>
    </div>
  </div>
</section>

</body>
</html>
