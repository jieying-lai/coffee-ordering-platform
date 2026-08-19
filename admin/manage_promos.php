<?php
require_once '../includes/admin_auth_check.php';
require_once '../includes/db_connect.php';

$message = '';
$message_type = '';

// Handle AJAX Status Toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_toggle_status'])) {
    header('Content-Type: application/json');
    $promoId = (int)($_POST['promo_id'] ?? 0);
    $newStatus = (int)($_POST['is_active'] ?? 0);

    $stmt = $conn->prepare("UPDATE promo_codes SET is_active = ? WHERE id = ?");
    $stmt->bind_param("ii", $newStatus, $promoId);
    $success = $stmt->execute();
    $stmt->close();

    echo json_encode([
        'status' => $success ? 'success' : 'error',
        'is_active' => $newStatus
    ]);
    exit;
}

// Handle Add / Edit Promo Code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_promo'])) {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $title = trim($_POST['title'] ?? '');
    $type = $_POST['discount_type'] ?? 'fixed';
    $val = (float)($_POST['discount_value'] ?? 0);
    $category = $_POST['category_target'] ?? 'all';
    $minSpend = (float)($_POST['min_spend'] ?? 0);

    if (!empty($code) && !empty($title)) {
        $stmt = $conn->prepare("INSERT INTO promo_codes (code, title, discount_type, discount_value, category_target, min_spend, is_active) VALUES (?, ?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE title = VALUES(title), discount_type = VALUES(discount_type), discount_value = VALUES(discount_value), category_target = VALUES(category_target), min_spend = VALUES(min_spend)");
        $stmt->bind_param("sssdsd", $code, $title, $type, $val, $category, $minSpend);
        if ($stmt->execute()) {
            $message = "Promo code '{$code}' saved successfully!";
            $message_type = "success";
        } else {
            $message = "Failed to save promo code: " . $conn->error;
            $message_type = "error";
        }
        $stmt->close();
    } else {
        $message = "Promo code and title are required.";
        $message_type = "error";
    }
}

// Fetch all promo codes
$promosRes = $conn->query("SELECT * FROM promo_codes ORDER BY created_at DESC");
$promosList = [];
if ($promosRes) {
    while ($r = $promosRes->fetch_assoc()) {
        $promosList[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/admin.css">
  <title>Cozy Coffee Co. — Manage Promo Codes &amp; Vouchers</title>
</head>

<body class="admin-page">

<!-- ADMIN NAVBAR -->
<?php $adminActivePage = 'promos'; require_once '../includes/admin_header_nav.php'; ?>

<div class="container" style="max-width: 1100px; margin: 30px auto; padding: 0 20px;">
  
  <div class="admin-page-header" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 16px;">
    <div>
      <a href="dashboard.php" class="btn-back-dashboard">&larr; Back to Dashboard</a>
      <h1 class="admin-header-title">Manage Promo Codes</h1>
      <p class="admin-header-subtitle">Create campaign vouchers (e.g. Merdeka67), set fixed/percentage discount values, or pause active codes.</p>
    </div>
    <button type="button" class="btn btn-orange" onclick="openAddModal()" style="font-weight: 800; border-radius: 20px; padding: 10px 20px;">+ Add New Promo Code</button>
  </div>

  <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: 20px; padding: 14px 18px; border-radius: 12px; background: <?php echo $message_type === 'success' ? '#d1fae5' : '#fee2e2'; ?>; color: <?php echo $message_type === 'success' ? '#065f46' : '#991b1b'; ?>; font-weight: 700;">
      <?php echo htmlspecialchars($message); ?>
    </div>
  <?php endif; ?>

  <!-- PROMOS TABLE -->
  <div style="background: #FFFFFF; border-radius: 20px; border: 1.5px solid #E8DDD0; padding: 24px; box-shadow: 0 6px 20px rgba(60,42,33,0.04);">
    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
      <thead>
        <tr style="background: #FAF7F2; border-bottom: 1.5px solid #E8DDD0; color: #2C1C14;">
          <th style="padding: 12px 14px;">Promo Code</th>
          <th style="padding: 12px 14px;">Title / Campaign</th>
          <th style="padding: 12px 14px;">Type &amp; Value</th>
          <th style="padding: 12px 14px;">Target &amp; Min Spend</th>
          <th style="padding: 12px 14px;">Status</th>
          <th style="padding: 12px 14px; text-align: right;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($promosList as $p): ?>
          <tr id="promo-row-<?php echo $p['id']; ?>" style="border-bottom: 1px dashed #FAF4EB;">
            <td style="padding: 14px;">
              <span style="background: #C85A3E; color: #FFF; font-weight: 800; font-size: 0.82rem; padding: 4px 10px; border-radius: 6px; font-family: monospace;"><?php echo htmlspecialchars($p['code']); ?></span>
            </td>
            <td style="padding: 14px; font-weight: 700; color: #2C1C14;"><?php echo htmlspecialchars($p['title']); ?></td>
            <td style="padding: 14px;">
              <?php 
                if ($p['discount_type'] === 'percentage') echo "<strong>" . number_format($p['discount_value'], 0) . "% OFF</strong>";
                elseif ($p['discount_type'] === 'free_item') echo "<strong style='color: #059669;'>FREE Category Item</strong>";
                else echo "<strong>RM " . number_format($p['discount_value'], 2) . " OFF</strong>";
              ?>
            </td>
            <td style="padding: 14px;">
              <div style="font-size: 0.85rem; color: #665447;">Target: <strong><?php echo ucfirst($p['category_target']); ?></strong></div>
              <div style="font-size: 0.78rem; color: #8A7769;">Min Spend: RM <?php echo number_format($p['min_spend'], 2); ?></div>
            </td>
            <td style="padding: 14px;">
              <span class="status-badge-<?php echo $p['id']; ?>" style="background: <?php echo $p['is_active'] ? '#ECFDF5' : '#F3F4F6'; ?>; color: <?php echo $p['is_active'] ? '#065F46' : '#6B7280'; ?>; font-weight: 800; font-size: 0.78rem; padding: 4px 10px; border-radius: 20px;">
                <?php echo $p['is_active'] ? 'ACTIVE' : 'PAUSED'; ?>
              </span>
            </td>
            <td style="padding: 14px; text-align: right;">
              <button type="button" onclick="toggleStatus(<?php echo $p['id']; ?>, <?php echo $p['is_active'] ? 0 : 1; ?>)" class="btn btn-small toggle-btn-<?php echo $p['id']; ?>" style="font-weight: 700; border-radius: 8px; font-size: 0.8rem; background: <?php echo $p['is_active'] ? '#FFF' : '#C85A3E'; ?>; color: <?php echo $p['is_active'] ? '#C85A3E' : '#FFF'; ?>; border: 1.5px solid #C85A3E;">
                <?php echo $p['is_active'] ? '🔒 Pause' : '🔓 Activate'; ?>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ADD PROMO MODAL -->
<div id="promoModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
  <div style="background: #FFFFFF; max-width: 500px; width: 90%; border-radius: 22px; padding: 26px; border: 1.5px solid #E8DDD0; position: relative;">
    <button type="button" onclick="closeAddModal()" style="position: absolute; right: 16px; top: 16px; background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
    <h3 style="font-family: var(--font-heading); color: #2C1C14; margin-top: 0; margin-bottom: 16px; font-size: 1.3rem;">🎟️ Add New Promo Code</h3>
    
    <form action="" method="POST">
      <div style="margin-bottom: 14px;">
        <label style="font-weight: 800; font-size: 0.85rem; color: #2C1C14; display: block; margin-bottom: 4px;">Promo Code (Uppercase, e.g. MERDEKA67)</label>
        <input type="text" name="code" required placeholder="e.g. MERDEKA67" style="width: 100%; height: 42px; border-radius: 10px; border: 1.5px solid #E5D9CC; padding: 8px 12px; font-weight: 800; text-transform: uppercase; box-sizing: border-box;">
      </div>

      <div style="margin-bottom: 14px;">
        <label style="font-weight: 800; font-size: 0.85rem; color: #2C1C14; display: block; margin-bottom: 4px;">Campaign / Title</label>
        <input type="text" name="title" required placeholder="e.g. Merdeka 67 Celebration 20% Off" style="width: 100%; height: 42px; border-radius: 10px; border: 1.5px solid #E5D9CC; padding: 8px 12px; font-weight: 700; box-sizing: border-box;">
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px;">
        <div>
          <label style="font-weight: 800; font-size: 0.85rem; color: #2C1C14; display: block; margin-bottom: 4px;">Discount Type</label>
          <select name="discount_type" style="width: 100%; height: 42px; border-radius: 10px; border: 1.5px solid #E5D9CC; padding: 8px 12px; font-weight: 700;">
            <option value="fixed">Fixed RM Off</option>
            <option value="percentage">Percentage % Off</option>
            <option value="free_item">Free Category Item</option>
          </select>
        </div>

        <div>
          <label style="font-weight: 800; font-size: 0.85rem; color: #2C1C14; display: block; margin-bottom: 4px;">Discount Value / %</label>
          <input type="number" step="0.01" name="discount_value" value="0.00" style="width: 100%; height: 42px; border-radius: 10px; border: 1.5px solid #E5D9CC; padding: 8px 12px; font-weight: 800; box-sizing: border-box;">
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px;">
        <div>
          <label style="font-weight: 800; font-size: 0.85rem; color: #2C1C14; display: block; margin-bottom: 4px;">Category Target</label>
          <select name="category_target" style="width: 100%; height: 42px; border-radius: 10px; border: 1.5px solid #E5D9CC; padding: 8px 12px; font-weight: 700;">
            <option value="all">All Items</option>
            <option value="dessert">Desserts Only</option>
            <option value="coffee">Coffee Only</option>
            <option value="mains">Main Dishes Only</option>
          </select>
        </div>

        <div>
          <label style="font-weight: 800; font-size: 0.85rem; color: #2C1C14; display: block; margin-bottom: 4px;">Min Spend (RM)</label>
          <input type="number" step="0.01" name="min_spend" value="0.00" style="width: 100%; height: 42px; border-radius: 10px; border: 1.5px solid #E5D9CC; padding: 8px 12px; font-weight: 800; box-sizing: border-box;">
        </div>
      </div>

      <button type="submit" name="save_promo" class="btn btn-orange btn-full" style="height: 46px; border-radius: 12px; font-weight: 800; font-size: 1rem;">Save Promo Code 🎟️</button>
    </form>
  </div>
</div>

<script>
function openAddModal() {
    document.getElementById('promoModal').style.display = 'flex';
}
function closeAddModal() {
    document.getElementById('promoModal').style.display = 'none';
}

function toggleStatus(promoId, newStatus) {
    const formData = new FormData();
    formData.append('ajax_toggle_status', '1');
    formData.append('promo_id', promoId);
    formData.append('is_active', newStatus);

    fetch('manage_promos.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            const badge = document.querySelector(`.status-badge-${promoId}`);
            const btn = document.querySelector(`.toggle-btn-${promoId}`);
            
            if (data.is_active === 1) {
                badge.textContent = 'ACTIVE';
                badge.style.background = '#ECFDF5';
                badge.style.color = '#065F46';
                btn.innerHTML = '🔒 Pause';
                btn.style.background = '#FFF';
                btn.style.color = '#C85A3E';
                btn.setAttribute('onclick', `toggleStatus(${promoId}, 0)`);
            } else {
                badge.textContent = 'PAUSED';
                badge.style.background = '#F3F4F6';
                badge.style.color = '#6B7280';
                btn.innerHTML = '🔓 Activate';
                btn.style.background = '#C85A3E';
                btn.style.color = '#FFF';
                btn.setAttribute('onclick', `toggleStatus(${promoId}, 1)`);
            }
        }
    });
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>

</body>
</html>
