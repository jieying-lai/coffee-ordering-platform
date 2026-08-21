<?php
require_once '../includes/admin_auth_check.php';
require_once '../includes/db_connect.php';

$message = '';
$messageType = '';

function cleanSocialHandle($input) {
    $input = trim($input);
    if ($input === '' || $input === '#') return '';

    // If full URL was pasted, extract username path
    if (preg_match('#(?:instagram\.com|facebook\.com|tiktok\.com\/@?)\/([a-zA-Z0-9_.-]+)#i', $input, $matches)) {
        return ltrim($matches[1], '@');
    }

    // Strip leading @, slashes, or domain prefixes
    $handle = preg_replace('#^https?://[^/]+/|^@|/$#i', '', $input);
    return trim($handle);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address   = trim($_POST['address'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $hours     = trim($_POST['hours'] ?? '');
    $mapUrl    = trim($_POST['map_embed_url'] ?? '');
    
    $igHandle  = cleanSocialHandle($_POST['instagram_handle'] ?? '');
    $fbHandle  = cleanSocialHandle($_POST['facebook_handle'] ?? '');
    $ttHandle  = cleanSocialHandle($_POST['tiktok_handle'] ?? '');

    $instagram = !empty($igHandle) ? 'https://www.instagram.com/' . $igHandle : '#';
    $facebook  = !empty($fbHandle) ? 'https://www.facebook.com/' . $fbHandle : '#';
    $tiktok    = !empty($ttHandle) ? 'https://www.tiktok.com/@' . $ttHandle : '#';

    // Server-side Validation
    if ($address === '' || $phone === '' || $email === '' || $hours === '') {
        $message = 'Address, phone, email, and opening hours are required.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } else {
        $stmt = $conn->prepare('UPDATE contact_info SET address=?, phone=?, email=?, hours=?, map_embed_url=?, instagram_url=?, facebook_url=?, tiktok_url=? WHERE id=1');
        $stmt->bind_param('ssssssss', $address, $phone, $email, $hours, $mapUrl, $instagram, $facebook, $tiktok);
        if ($stmt->execute()) {
            $message = 'Contact page details updated successfully.';
            $messageType = 'success';
        } else {
            $message = 'Failed to update contact page details: ' . $conn->error;
            $messageType = 'error';
        }
        $stmt->close();
    }
}

$info = $conn->query('SELECT * FROM contact_info WHERE id = 1')->fetch_assoc();

$igHandleVal = cleanSocialHandle($info['instagram_url'] ?? '');
$fbHandleVal = cleanSocialHandle($info['facebook_url'] ?? '');
$ttHandleVal = cleanSocialHandle($info['tiktok_url'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/admin.css">
  <link rel="stylesheet" href="../style/programs.css">
  <title>Cozy Coffee Co. — Manage Contact Page</title>
  <style>
    body.admin-page {
      background: #FAF6F0 !important;
      color: #2C1C14;
    }

    .admin-contact-wrap {
      max-width: 1100px;
      margin: 20px auto 60px;
      padding: 0 4%;
      box-sizing: border-box;
    }

    .contact-card-box {
      background: #FFFFFF;
      border: 1.5px solid #E8DDD0;
      border-radius: 20px;
      padding: 28px;
      margin-bottom: 24px;
      box-shadow: 0 6px 20px rgba(60, 42, 33, 0.04);
    }

    .contact-card-title {
      font-family: var(--font-heading);
      font-size: 1.15rem;
      font-weight: 800;
      color: #2C1C14;
      margin-top: 0;
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 8px;
      border-bottom: 1.5px solid #FAF4EB;
      padding-bottom: 12px;
    }

    .field-error-msg {
      color: #DC2626;
      font-size: 0.78rem;
      font-weight: 700;
      margin-top: 4px;
      display: none;
    }

    .social-input-group {
      display: flex;
      align-items: center;
      gap: 8px;
      background: #FAF7F2;
      border: 1.5px solid #E8DDD0;
      border-radius: 12px;
      padding: 4px 14px;
      transition: all 0.2s ease;
    }

    .social-input-group:focus-within {
      border-color: #C85A3E;
      background: #FFFFFF;
      box-shadow: 0 0 0 3px rgba(200, 90, 62, 0.12);
    }

    .social-input-prefix {
      font-size: 0.85rem;
      font-weight: 700;
      color: #8C7A6D;
      user-select: none;
      display: flex;
      align-items: center;
      gap: 6px;
      flex-shrink: 0;
    }

    .social-input-group input {
      border: none !important;
      background: transparent !important;
      padding: 8px 0 !important;
      box-shadow: none !important;
      width: 100%;
      outline: none;
      font-size: 0.9rem;
      color: #2C1C14;
      font-weight: 600;
    }

    .map-preview-wrap {
      width: 100%;
      height: 240px;
      border-radius: 14px;
      overflow: hidden;
      border: 1.5px solid #E8DDD0;
      background: #FAF7F2;
      margin-top: 12px;
    }

    .map-preview-wrap iframe {
      width: 100%;
      height: 100%;
      border: 0;
    }
  </style>
</head>
<body class="admin-page">

<?php $adminActivePage = 'contact'; require_once '../includes/admin_header_nav.php'; ?>

<div class="admin-contact-wrap">

  <!-- PAGE HEADER -->
  <div class="admin-page-header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
    <div>
      <a href="dashboard.php" class="btn-back-dashboard">&larr; Back to Dashboard</a>
      <h1 class="admin-header-title">Manage Contact Page</h1>
      <p class="admin-header-subtitle">Update store location, contact details, operating hours, and social media handles for live customers.</p>
    </div>

    <a href="../contact/index.php" target="_blank" style="padding: 9px 18px; background: #FAF4EB; border: 1.5px solid #E8DDD0; border-radius: 20px; color: #4A3B32; font-weight: 800; font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(60,42,33,0.04);">
      🔗 View Live Contact Page
    </a>
  </div>

  <?php if (!empty($message)): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
          showToast(<?php echo json_encode($message); ?>, <?php echo json_encode($messageType); ?>);
      });
    </script>
  <?php endif; ?>

  <form method="POST" onsubmit="return validateContactForm(event)">

    <!-- SECTION 1: STORE CONTACT DETAILS -->
    <div class="contact-card-box">
      <h2 class="contact-card-title">📍 Store Location & Contact Information</h2>

      <div class="form-group" style="margin-bottom: 16px;">
        <label style="font-weight: 800; color: #2C1C14; display: block; margin-bottom: 6px;">Store Address <span style="color: #DC2626;">*</span></label>
        <input type="text" name="address" id="contactAddress" value="<?php echo htmlspecialchars($info['address'] ?? ''); ?>" oninput="validateSingleField('contactAddress')" style="width: 100%; padding: 10px 14px; border: 1.5px solid #E8DDD0; border-radius: 12px; font-size: 0.9rem; transition: all 0.2s ease;">
        <div id="contactAddressError" class="field-error-msg">⚠️ Store address is required and cannot be left empty.</div>
      </div>

      <div style="display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 16px;">
        <div class="form-group" style="flex: 1; min-width: 260px; margin: 0;">
          <label style="font-weight: 800; color: #2C1C14; display: block; margin-bottom: 6px;">Phone Number <span style="color: #DC2626;">*</span></label>
          <input type="text" name="phone" id="contactPhone" value="<?php echo htmlspecialchars($info['phone'] ?? ''); ?>" oninput="validateSingleField('contactPhone')" style="width: 100%; padding: 10px 14px; border: 1.5px solid #E8DDD0; border-radius: 12px; font-size: 0.9rem; transition: all 0.2s ease;">
          <div id="contactPhoneError" class="field-error-msg">⚠️ Phone number is required and cannot be left empty.</div>
        </div>

        <div class="form-group" style="flex: 1; min-width: 260px; margin: 0;">
          <label style="font-weight: 800; color: #2C1C14; display: block; margin-bottom: 6px;">Email Address <span style="color: #DC2626;">*</span></label>
          <input type="email" name="email" id="contactEmail" value="<?php echo htmlspecialchars($info['email'] ?? ''); ?>" oninput="validateSingleField('contactEmail')" style="width: 100%; padding: 10px 14px; border: 1.5px solid #E8DDD0; border-radius: 12px; font-size: 0.9rem; transition: all 0.2s ease;">
          <div id="contactEmailError" class="field-error-msg">⚠️ Email address is required and cannot be left empty.</div>
        </div>
      </div>

      <div class="form-group" style="margin-bottom: 0;">
        <label style="font-weight: 800; color: #2C1C14; display: block; margin-bottom: 6px;">Opening Hours <span style="color: #DC2626;">*</span></label>
        <input type="text" name="hours" id="contactHours" value="<?php echo htmlspecialchars($info['hours'] ?? ''); ?>" oninput="validateSingleField('contactHours')" placeholder="e.g. Mon - Sun: 8:00 AM - 10:00 PM" style="width: 100%; padding: 10px 14px; border: 1.5px solid #E8DDD0; border-radius: 12px; font-size: 0.9rem; transition: all 0.2s ease;">
        <div id="contactHoursError" class="field-error-msg">⚠️ Opening hours are required and cannot be left empty.</div>
      </div>
    </div>

    <!-- SECTION 2: GOOGLE MAPS EMBED & LIVE PREVIEW -->
    <div class="contact-card-box">
      <h2 class="contact-card-title">🗺️ Google Maps Location Embed</h2>

      <div class="form-group" style="margin-bottom: 12px;">
        <label style="font-weight: 800; color: #2C1C14; display: block; margin-bottom: 6px;">Google Maps Embed URL</label>
        <textarea name="map_embed_url" id="contactMapUrl" rows="3" oninput="updateMapPreview()" placeholder="Paste Google Maps iframe src URL here..." style="width: 100%; padding: 10px 14px; border: 1.5px solid #E8DDD0; border-radius: 12px; font-size: 0.88rem; font-family: monospace; transition: all 0.2s ease;"><?php echo htmlspecialchars($info['map_embed_url'] ?? ''); ?></textarea>
        <small style="color: #8A7769; font-size: 0.78rem; display: block; margin-top: 4px;">💡 Tip: Get this link from Google Maps &gt; Share &gt; Embed a map &gt; copy only the `src="..."` URL.</small>
      </div>

      <div style="font-weight: 800; color: #2C1C14; font-size: 0.85rem; margin-top: 14px;">Live Map Preview:</div>
      <div class="map-preview-wrap">
        <iframe id="mapPreviewIframe" src="<?php echo htmlspecialchars($info['map_embed_url'] ?? ''); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      </div>
    </div>

    <!-- SECTION 3: SOCIAL MEDIA CHANNELS (ENTER PROFILE USERNAME ONLY) -->
    <div class="contact-card-box">
      <h2 class="contact-card-title">🌐 Social Media Channels</h2>
      <div style="font-size: 0.8rem; color: #8A7769; margin-bottom: 16px;">
        💡 Type only your account Profile ID below. Full URLs are automatically constructed when saving.
      </div>

      <div class="form-group" style="margin-bottom: 16px;">
        <label style="font-weight: 800; color: #2C1C14; display: block; margin-bottom: 6px;">Instagram Profile ID</label>
        <div class="social-input-group">
          <span class="social-input-prefix">instagram.com/</span>
          <input type="text" name="instagram_handle" id="contactIg" value="<?php echo htmlspecialchars($igHandleVal); ?>" placeholder="cozy.coffee.co">
        </div>
      </div>

      <div class="form-group" style="margin-bottom: 16px;">
        <label style="font-weight: 800; color: #2C1C14; display: block; margin-bottom: 6px;">Facebook Page ID</label>
        <div class="social-input-group">
          <span class="social-input-prefix">facebook.com/</span>
          <input type="text" name="facebook_handle" id="contactFb" value="<?php echo htmlspecialchars($fbHandleVal); ?>" placeholder="cozy.coffee.co">
        </div>
      </div>

      <div class="form-group" style="margin-bottom: 0;">
        <label style="font-weight: 800; color: #2C1C14; display: block; margin-bottom: 6px;">TikTok Username</label>
        <div class="social-input-group">
          <span class="social-input-prefix">tiktok.com/@</span>
          <input type="text" name="tiktok_handle" id="contactTt" value="<?php echo htmlspecialchars($ttHandleVal); ?>" placeholder="cozy.coffee.co">
        </div>
      </div>
    </div>

    <!-- SUBMIT ACTIONS -->
    <div style="display: flex; justify-content: flex-end; gap: 14px;">
      <button type="submit" class="cta-btn cta-btn-primary" style="padding: 12px 32px; border-radius: 25px; font-size: 0.95rem; font-weight: 800; box-shadow: 0 4px 14px rgba(200,90,62,0.35);">
        Save Contact Changes ✨
      </button>
    </div>

  </form>

</div>

<script>
function updateMapPreview() {
    const url = document.getElementById('contactMapUrl').value.trim();
    const iframe = document.getElementById('mapPreviewIframe');
    if (iframe) {
        iframe.src = url || 'about:blank';
    }
}

function validateSingleField(fieldId) {
    const input = document.getElementById(fieldId);
    if (!input) return true;
    const val = input.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (fieldId === 'contactAddress') {
        if (!val) {
            setFieldError('contactAddress', 'Store address is required and cannot be left empty.');
            return false;
        } else {
            setFieldSuccess('contactAddress');
            return true;
        }
    }

    if (fieldId === 'contactPhone') {
        if (!val) {
            setFieldError('contactPhone', 'Phone number is required and cannot be left empty.');
            return false;
        } else {
            setFieldSuccess('contactPhone');
            return true;
        }
    }

    if (fieldId === 'contactEmail') {
        if (!val) {
            setFieldError('contactEmail', 'Email address is required and cannot be left empty.');
            return false;
        } else if (!emailRegex.test(val)) {
            setFieldError('contactEmail', 'Please enter a valid email address.');
            return false;
        } else {
            setFieldSuccess('contactEmail');
            return true;
        }
    }

    if (fieldId === 'contactHours') {
        if (!val) {
            setFieldError('contactHours', 'Opening hours are required and cannot be left empty.');
            return false;
        } else {
            setFieldSuccess('contactHours');
            return true;
        }
    }

    return true;
}

function setFieldError(fieldId, errorMsg) {
    const input = document.getElementById(fieldId);
    const errDiv = document.getElementById(fieldId + 'Error');
    if (input) {
        input.style.borderColor = '#DC2626';
        input.style.backgroundColor = '#FEE2E2';
    }
    if (errDiv) {
        errDiv.textContent = '⚠️ ' + errorMsg;
        errDiv.style.display = 'block';
    }
}

function setFieldSuccess(fieldId) {
    const input = document.getElementById(fieldId);
    const errDiv = document.getElementById(fieldId + 'Error');
    if (input) {
        input.style.borderColor = '#10B981';
        input.style.backgroundColor = '#F0FDF4';
    }
    if (errDiv) {
        errDiv.style.display = 'none';
    }
}

function validateContactForm(e) {
    const isAddrValid = validateSingleField('contactAddress');
    const isPhoneValid = validateSingleField('contactPhone');
    const isEmailValid = validateSingleField('contactEmail');
    const isHoursValid = validateSingleField('contactHours');

    if (!isAddrValid || !isPhoneValid || !isEmailValid || !isHoursValid) {
        if (e) e.preventDefault();
        showToast('⚠️ Please fill in all required fields before saving.', 'error');
        return false;
    }
    return true;
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