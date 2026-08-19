<?php
require_once '../includes/db_connect.php';

$about = $conn->query('SELECT * FROM about_us WHERE id = 1')->fetch_assoc();
$info  = $conn->query('SELECT * FROM contact_info WHERE id = 1')->fetch_assoc();

$igUrl = !empty($info['instagram_url']) && $info['instagram_url'] !== '#' ? $info['instagram_url'] : 'https://www.instagram.com/cozy.coffee.co';
$fbUrl = !empty($info['facebook_url']) && $info['facebook_url'] !== '#' ? $info['facebook_url'] : 'https://www.facebook.com/cozy.coffee.co';
$ttUrl = !empty($info['tiktok_url']) && $info['tiktok_url'] !== '#' ? $info['tiktok_url'] : 'https://www.tiktok.com/@cozy.coffee.co';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style/mystyle.css">
    <link rel="stylesheet" href="../style/contact.css">
    <title>Cozy Coffee Co. — About &amp; Contact</title>
    <style>
      .social-icon-btn {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 4px 14px rgba(60, 42, 33, 0.08);
        border: 1.5px solid #E8DDD0;
        background: #FFFFFF;
        text-decoration: none;
      }

      .social-icon-btn.instagram-btn { color: #E1306C; }
      .social-icon-btn.instagram-btn:hover {
        background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
        color: #FFFFFF;
        border-color: transparent;
        transform: translateY(-4px) scale(1.08);
        box-shadow: 0 10px 24px rgba(225, 48, 108, 0.4);
      }

      .social-icon-btn.facebook-btn { color: #1877F2; }
      .social-icon-btn.facebook-btn:hover {
        background: #1877F2;
        color: #FFFFFF;
        border-color: #1877F2;
        transform: translateY(-4px) scale(1.08);
        box-shadow: 0 10px 24px rgba(24, 119, 242, 0.4);
      }

      .social-icon-btn.tiktok-btn { color: #111111; }
      .social-icon-btn.tiktok-btn:hover {
        background: #000000;
        color: #FFFFFF;
        border-color: #000000;
        transform: translateY(-4px) scale(1.08);
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.4);
      }
    </style>
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

    <h4 style="margin-top: 26px; margin-bottom: 14px; font-family: var(--font-heading); color: #2C1C14; font-size: 1.05rem; font-weight: 800;">Follow Our Social Media ✦</h4>
    
    <div class="social-links" style="display: flex; gap: 16px; align-items: center;">
      
      <!-- INSTAGRAM LOGO ICON BUTTON -->
      <a href="<?php echo htmlspecialchars($igUrl); ?>" target="_blank" rel="noopener noreferrer" title="Instagram — @cozy.coffee.co" class="social-icon-btn instagram-btn">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
          <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
          <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
        </svg>
      </a>

      <!-- FACEBOOK LOGO ICON BUTTON -->
      <a href="<?php echo htmlspecialchars($fbUrl); ?>" target="_blank" rel="noopener noreferrer" title="Facebook — cozy.coffee.co" class="social-icon-btn facebook-btn">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
          <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
        </svg>
      </a>

      <!-- TIKTOK LOGO ICON BUTTON -->
      <a href="<?php echo htmlspecialchars($ttUrl); ?>" target="_blank" rel="noopener noreferrer" title="TikTok — @cozy.coffee.co" class="social-icon-btn tiktok-btn">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
          <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 3 15.68 6.34 6.34 0 0 0 9.34 22a6.34 6.34 0 0 0 6.34-6.34V9.37a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-3.86-.8z"/>
        </svg>
      </a>

    </div>
  </div>
</section>

<?php require_once '../includes/footer.php'; ?>

</body>
</html>
