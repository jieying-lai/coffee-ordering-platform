<?php
require_once '../includes/admin_auth_check.php';
require_once '../includes/db_connect.php';

// ============ AJAX TOGGLE ACTIVE STATUS ============
if (isset($_GET['ajax_toggle_status'])) {
    header('Content-Type: application/json');
    $promoId = (int)$_GET['ajax_toggle_status'];
    $status = (int)$_GET['status']; // 1 = Active, 0 = Inactive
    $stmt = $conn->prepare("UPDATE promo_codes SET is_active = ? WHERE id = ?");
    $stmt->bind_param("ii", $status, $promoId);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['status' => $ok ? 'success' : 'error', 'new_status' => $status]);
    exit;
}

// ============ ADD NEW PROMO CODE (POST -> PRG REDIRECT) ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'create_promo') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $title = trim($_POST['title'] ?? '');
    $discountType = $_POST['discount_type'] ?? 'fixed';
    $discountValue = (float)($_POST['discount_value'] ?? 0);
    $categoryTarget = $_POST['category_target'] ?? 'all';
    $minSpend = (float)($_POST['min_spend'] ?? 0);

    if (!empty($code) && !empty($title)) {
        // Check if code already exists
        $checkStmt = $conn->prepare("SELECT id FROM promo_codes WHERE code = ?");
        $checkStmt->bind_param("s", $code);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();
        
        if ($checkRes && $checkRes->num_rows > 0) {
            $checkStmt->close();
            header("Location: manage_promos.php?msg=exists");
            exit();
        }
        $checkStmt->close();

        $stmt = $conn->prepare("INSERT INTO promo_codes (code, title, discount_type, discount_value, category_target, min_spend, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
        $stmt->bind_param("sssdsd", $code, $title, $discountType, $discountValue, $categoryTarget, $minSpend);
        $stmt->execute();
        $stmt->close();

        header("Location: manage_promos.php?msg=created");
        exit();
    }
}

// ============ EDIT PROMO CODE (POST -> PRG REDIRECT) ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'edit_promo') {
    $promoId = (int)($_POST['edit_promo_id'] ?? 0);
    $code = strtoupper(trim($_POST['edit_code'] ?? ''));
    $title = trim($_POST['edit_title'] ?? '');
    $discountType = $_POST['edit_discount_type'] ?? 'fixed';
    $discountValue = (float)($_POST['edit_discount_value'] ?? 0);
    $categoryTarget = $_POST['edit_category_target'] ?? 'all';
    $minSpend = (float)($_POST['edit_min_spend'] ?? 0);

    if ($promoId > 0 && !empty($code) && !empty($title)) {
        $stmt = $conn->prepare("UPDATE promo_codes SET code = ?, title = ?, discount_type = ?, discount_value = ?, category_target = ?, min_spend = ? WHERE id = ?");
        $stmt->bind_param("sssdsdi", $code, $title, $discountType, $discountValue, $categoryTarget, $minSpend, $promoId);
        $stmt->execute();
        $stmt->close();

        header("Location: manage_promos.php?msg=updated#promo-row-" . $promoId);
        exit();
    }
}

// ============ DELETE PROMO CODE (GET -> PRG REDIRECT) ============
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM promo_codes WHERE id = ?");
    $stmt->bind_param("i", $delId);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_promos.php?msg=deleted");
    exit();
}

// Handle Flash Messages
$message = '';
$messageType = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') {
        $message = "Promo code created successfully!";
        $messageType = "success";
    } elseif ($_GET['msg'] === 'updated') {
        $message = "Promo code updated successfully!";
        $messageType = "success";
    } elseif ($_GET['msg'] === 'deleted') {
        $message = "Promo code removed successfully.";
        $messageType = "success";
    } elseif ($_GET['msg'] === 'exists') {
        $message = "Promo code already exists. Please choose a different code.";
        $messageType = "error";
    }
}

// Default Sort Order: Newest Added Date First
$sortOrder = isset($_GET['sort']) && strtolower($_GET['sort']) === 'asc' ? 'ASC' : 'DESC';
$promosRes = $conn->query("SELECT * FROM promo_codes ORDER BY created_at {$sortOrder}");
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
  <title>Cozy Coffee Co. — Manage Promo Codes</title>
  <style>
    body.admin-page {
      background: #FAF6F0 !important;
      color: #2C1C14;
    }

    .admin-promos-wrap {
      max-width: 1240px;
      margin: 20px auto 60px;
      padding: 0 4%;
      box-sizing: border-box;
    }

    .btn-create-promo {
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

    .btn-create-promo:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 24px rgba(200, 90, 62, 0.45);
      background: linear-gradient(135deg, #B64C32 0%, #8C3722 100%);
    }

    /* FILTER & SORT BAR */
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

    .filter-input-group {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }

    .admin-search-input {
      padding: 9px 14px;
      border: 1.5px solid #E8DDD0;
      border-radius: 10px;
      font-size: 0.88rem;
      outline: none;
      background: #FAF7F2;
      color: #2C1C14;
      min-width: 240px;
    }

    .admin-select-input {
      padding: 9px 14px;
      border: 1.5px solid #E8DDD0;
      border-radius: 10px;
      font-size: 0.88rem;
      font-weight: 700;
      background: #FAF7F2;
      color: #2C1C14;
      outline: none;
      cursor: pointer;
    }

    /* POP-UP MODAL OVERLAY & CONTAINER */
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
      max-width: 520px !important;
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
    .admin-modal-box input[type="number"],
    .admin-modal-box select {
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

    /* ACTION BUTTONS */
    .btn-action-edit {
      padding: 7px 14px;
      background: #EFF6FF;
      color: #1D4ED8;
      border: 1.5px solid #BFDBFE;
      border-radius: 10px;
      font-weight: 700;
      font-size: 0.8rem;
      cursor: pointer;
      transition: all 0.2s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 4px;
    }

    .btn-action-edit:hover {
      background: #2563EB;
      color: #FFFFFF;
      border-color: #1D4ED8;
    }

    .btn-action-status.btn-status-active {
      background: #FEF3C7 !important;
      color: #D97706 !important;
      border: 1.5px solid #FCD34D !important;
      font-weight: 800 !important;
    }
    .btn-action-status.btn-status-active:hover {
      background: #FDE68A !important;
    }

    .btn-action-status.btn-status-inactive {
      background: #ECFDF5 !important;
      color: #059669 !important;
      border: 1.5px solid #A7F3D0 !important;
      font-weight: 800 !important;
    }
    .btn-action-status.btn-status-inactive:hover {
      background: #D1FAE5 !important;
    }

    .btn-action-delete {
      padding: 7px 14px;
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
      justify-content: center;
      gap: 4px;
    }

    .btn-action-delete:hover {
      background: #DC2626;
      color: #FFFFFF;
    }

    .code-pill {
      background: #C85A3E;
      color: #FFFFFF;
      font-weight: 800;
      font-size: 0.85rem;
      padding: 4px 12px;
      border-radius: 8px;
      font-family: monospace;
      letter-spacing: 0.5px;
      display: inline-block;
    }

    .form-field-error {
      color: #DC2626;
      font-size: 0.8rem;
      font-weight: 700;
      margin-top: 4px;
      display: none;
    }
  </style>
</head>
<body class="admin-page">

<?php $adminActivePage = 'promos'; require_once '../includes/admin_header_nav.php'; ?>

<div class="admin-promos-wrap">

  <!-- PAGE HEADER -->
  <div class="admin-page-header">
    <a href="dashboard.php" class="btn-back-dashboard">&larr; Back to Dashboard</a>
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; width: 100%;">
      <div>
        <h1 class="admin-header-title">Manage Promo Codes</h1>
        <p class="admin-header-subtitle">Create campaign vouchers (e.g. MERDEKA67), set percentage or fixed discount values, or pause active codes.</p>
      </div>
      <button type="button" class="btn-create-promo" onclick="openPromoModal('createPromoModal')">
        + Add New Promo Code
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

  <!-- FILTER & SORT CONTROLS BAR -->
  <div class="admin-filter-bar">
    <div class="filter-input-group">
      <!-- SEARCH INPUT -->
      <input type="text" id="promoSearchInput" class="admin-search-input" onkeyup="filterPromoTable()" placeholder="🔍 Search promo code or title...">
      
      <!-- TARGET CATEGORY FILTER -->
      <select id="promoCategoryFilter" class="admin-select-input" onchange="filterPromoTable()">
        <option value="all">All Category Targets</option>
        <option value="all_items">All Items Target</option>
        <option value="coffee">Coffee Only</option>
        <option value="dessert">Desserts Only</option>
        <option value="mains">Main Dishes Only</option>
      </select>
    </div>

    <!-- ADDED DATE SORT SELECTOR -->
    <div style="display: flex; align-items: center; gap: 8px;">
      <label style="font-weight: 700; font-size: 0.88rem; color: #4A3B32;">Sort Added Date:</label>
      <select id="promoSortSelect" class="admin-select-input" onchange="onSortChange(this.value)">
        <option value="desc" <?php echo $sortOrder === 'DESC' ? 'selected' : ''; ?>>Newest Added First</option>
        <option value="asc" <?php echo $sortOrder === 'ASC' ? 'selected' : ''; ?>>Oldest Added First</option>
      </select>
    </div>
  </div>

  <!-- PROMOS LIST TABLE WRAPPER (SPACIOUS 6-COLUMN LAYOUT) -->
  <div class="admin-table-wrap" style="background: #FFFFFF; border-radius: 20px; border: 1.5px solid #E8DDD0; padding: 20px; box-shadow: 0 6px 20px rgba(60,42,33,0.04);">
    <table class="admin-table" id="promoTable" style="width: 100%; border-collapse: collapse;">
      <thead>
        <tr>
          <th style="text-align: left; padding: 14px 16px;">Promo Details</th>
          <th style="text-align: left; padding: 14px 16px;">Discount</th>
          <th style="text-align: left; padding: 14px 16px;">Target &amp; Min Spend</th>
          <th style="text-align: center; padding: 14px 16px;">Added Date</th>
          <th style="text-align: center; padding: 14px 16px;">Status</th>
          <th style="text-align: center; padding: 14px 16px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($promosList)): ?>
          <?php foreach ($promosList as $p): ?>
            <?php
              $pId = (int)$p['id'];
              $isActive = ((int)$p['is_active'] === 1);
              $addedDate = !empty($p['created_at']) ? date('M d, Y · g:i A', strtotime($p['created_at'])) : 'N/A';
              $promoJson = htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8');
            ?>
            <tr id="promo-row-<?php echo $pId; ?>" data-code="<?php echo htmlspecialchars(strtolower($p['code'])); ?>" data-title="<?php echo htmlspecialchars(strtolower($p['title'])); ?>" data-target="<?php echo htmlspecialchars(strtolower($p['category_target'])); ?>">
              
              <!-- PROMO CODE & TITLE COMBINED -->
              <td style="padding: 16px; vertical-align: middle;">
                <span class="code-pill"><?php echo htmlspecialchars($p['code']); ?></span>
                <div style="font-weight: 700; color: #2C1C14; font-size: 0.9rem; margin-top: 6px;">
                  <?php echo htmlspecialchars($p['title']); ?>
                </div>
              </td>

              <!-- DISCOUNT VALUE -->
              <td style="padding: 16px; vertical-align: middle;">
                <?php 
                  if ($p['discount_type'] === 'percentage') {
                    echo "<strong style='color:#059669; font-size:1.05rem;'>" . number_format($p['discount_value'], 0) . "% OFF</strong>";
                  } elseif ($p['discount_type'] === 'free_item') {
                    echo "<strong style='color:#059669; font-size:0.95rem;'>FREE Category Item</strong>";
                  } else {
                    echo "<strong style='color:#059669; font-size:1.05rem;'>RM " . number_format($p['discount_value'], 2) . " OFF</strong>";
                  }
                ?>
              </td>

              <!-- TARGET CATEGORY & MIN SPEND -->
              <td style="padding: 16px; vertical-align: middle;">
                <div style="font-size: 0.86rem; color: #4A3B32;">Target: <strong><?php echo ucfirst($p['category_target']); ?></strong></div>
                <div style="font-size: 0.78rem; color: #8A7769; margin-top: 3px;">Min Spend: <strong>RM <?php echo number_format($p['min_spend'], 2); ?></strong></div>
              </td>

              <!-- ADDED DATE LISTED -->
              <td style="padding: 16px; vertical-align: middle; text-align: center; white-space: nowrap;">
                <span style="font-size: 0.84rem; color: #7A685A; font-weight: 600;">
                  📅 <?php echo $addedDate; ?>
                </span>
              </td>

              <!-- STATUS BADGE -->
              <td style="padding: 16px; vertical-align: middle; text-align: center; white-space: nowrap;">
                <span id="promo-status-badge-<?php echo $pId; ?>">
                  <?php if ($isActive): ?>
                    <span style="background: #ECFDF5; color: #065F46; font-weight: 800; font-size: 0.78rem; padding: 4px 12px; border-radius: 20px; border: 1px solid #A7F3D0;">Active</span>
                  <?php else: ?>
                    <span style="background: #FEE2E2; color: #991B1B; font-weight: 800; font-size: 0.78rem; padding: 4px 12px; border-radius: 20px; border: 1px solid #FCA5A5;">Paused</span>
                  <?php endif; ?>
                </span>
              </td>

              <!-- ACTIONS COL -->
              <td style="padding: 16px; vertical-align: middle; text-align: center; white-space: nowrap;">
                <div style="display: flex; gap: 6px; justify-content: center; align-items: center;">
                  <!-- EDIT BUTTON -->
                  <button type="button" class="btn-action-edit" onclick="openEditPromoModal(<?php echo $promoJson; ?>)">
                    Edit
                  </button>

                  <!-- PAUSE / ACTIVATE TOGGLE BUTTON -->
                  <button type="button" class="btn-action-status <?php echo $isActive ? 'btn-status-active' : 'btn-status-inactive'; ?>" id="promo-status-btn-<?php echo $pId; ?>" onclick="togglePromoStatus(this, <?php echo $pId; ?>, <?php echo $isActive ? 1 : 0; ?>)" style="padding: 7px 14px; border-radius: 10px; font-size: 0.8rem; cursor: pointer; transition: all 0.2s ease;">
                    <?php echo $isActive ? 'Pause' : 'Activate'; ?>
                  </button>

                  <!-- DELETE BUTTON -->
                  <a href="manage_promos.php?delete=<?php echo $pId; ?>" onclick="return confirm('⚠️ Are you sure you want to delete this promo code?')" class="btn-action-delete">
                    Delete
                  </a>
                </div>
              </td>

            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" style="text-align: center; padding: 40px; color: #7A685A; font-weight: 600;">No promo codes created yet. Click "+ Add New Promo Code" above to publish one!</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<!-- CREATE PROMO POP-UP MODAL -->
<div id="createPromoModal" class="admin-modal-overlay">
  <div class="admin-modal-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1.5px solid #F4EDE4;">
      <h2 style="font-family: var(--font-heading); color: #2C1C14; margin: 0; font-size: 1.25rem; font-weight: 800;">
        Create New Promo Code
      </h2>
      <button type="button" onclick="closePromoModal('createPromoModal')" style="background: transparent; border: none; font-size: 1.6rem; color: #7A685A; cursor: pointer; line-height: 1;">&times;</button>
    </div>

    <form id="createPromoForm" action="manage_promos.php" method="POST" onsubmit="return validatePromoForm('create', event)">
      <input type="hidden" name="form_type" value="create_promo">

      <!-- PROMO CODE -->
      <div style="margin-bottom: 16px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">Promo Code (Uppercase)</label>
        <input type="text" name="code" id="createCode" placeholder="e.g. MERDEKA67" style="text-transform: uppercase; font-weight: 800; font-family: monospace;">
        <div id="createCodeError" class="form-field-error">Please enter a promo code.</div>
      </div>

      <!-- CAMPAIGN TITLE -->
      <div style="margin-bottom: 16px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">Campaign Title</label>
        <input type="text" name="title" id="createTitle" placeholder="e.g. Merdeka Special 20% Off">
        <div id="createTitleError" class="form-field-error">Please enter a campaign title.</div>
      </div>

      <!-- DISCOUNT TYPE & VALUE -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
        <div>
          <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">Discount Type</label>
          <select name="discount_type" id="createDiscountType" onchange="updateValueLabel('create')">
            <option value="fixed">Fixed RM Off</option>
            <option value="percentage">Percentage % Off</option>
            <option value="free_item">Free Category Item</option>
          </select>
        </div>

        <div>
          <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;" id="createValueLabel">Discount Value (RM)</label>
          <input type="number" step="0.01" name="discount_value" id="createDiscountValue" value="5.00">
          <div id="createValueError" class="form-field-error">Enter a valid positive number.</div>
        </div>
      </div>

      <!-- CATEGORY TARGET & MIN SPEND -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 22px;">
        <div>
          <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">Category Target</label>
          <select name="category_target" id="createCategoryTarget">
            <option value="all">All Items</option>
            <option value="coffee">Coffee Only</option>
            <option value="dessert">Desserts Only</option>
            <option value="mains">Main Dishes Only</option>
          </select>
        </div>

        <div>
          <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">Min Spend (RM)</label>
          <input type="number" step="0.01" name="min_spend" id="createMinSpend" value="0.00">
        </div>
      </div>

      <button type="submit" class="btn-create-promo" style="width: 100%; justify-content: center; height: 48px; border-radius: 25px; font-size: 0.95rem;">Save &amp; Publish</button>
    </form>
  </div>
</div>

<!-- EDIT PROMO POP-UP MODAL -->
<div id="editPromoModal" class="admin-modal-overlay">
  <div class="admin-modal-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1.5px solid #F4EDE4;">
      <h2 style="font-family: var(--font-heading); color: #2C1C14; margin: 0; font-size: 1.25rem; font-weight: 800;">
        Edit Promo Code
      </h2>
      <button type="button" onclick="closePromoModal('editPromoModal')" style="background: transparent; border: none; font-size: 1.6rem; color: #7A685A; cursor: pointer; line-height: 1;">&times;</button>
    </div>

    <form id="editPromoForm" action="manage_promos.php" method="POST" onsubmit="return validatePromoForm('edit', event)">
      <input type="hidden" name="form_type" value="edit_promo">
      <input type="hidden" name="edit_promo_id" id="editPromoId">

      <!-- PROMO CODE -->
      <div style="margin-bottom: 16px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">Promo Code (Uppercase)</label>
        <input type="text" name="edit_code" id="editCode" style="text-transform: uppercase; font-weight: 800; font-family: monospace;">
        <div id="editCodeError" class="form-field-error">Please enter a promo code.</div>
      </div>

      <!-- CAMPAIGN TITLE -->
      <div style="margin-bottom: 16px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">Campaign Title</label>
        <input type="text" name="edit_title" id="editTitle">
        <div id="editTitleError" class="form-field-error">Please enter a campaign title.</div>
      </div>

      <!-- DISCOUNT TYPE & VALUE -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
        <div>
          <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">Discount Type</label>
          <select name="edit_discount_type" id="editDiscountType" onchange="updateValueLabel('edit')">
            <option value="fixed">Fixed RM Off</option>
            <option value="percentage">Percentage % Off</option>
            <option value="free_item">Free Category Item</option>
          </select>
        </div>

        <div>
          <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;" id="editValueLabel">Discount Value (RM)</label>
          <input type="number" step="0.01" name="edit_discount_value" id="editDiscountValue">
          <div id="editValueError" class="form-field-error">Enter a valid positive number.</div>
        </div>
      </div>

      <!-- CATEGORY TARGET & MIN SPEND -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 22px;">
        <div>
          <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">Category Target</label>
          <select name="edit_category_target" id="editCategoryTarget">
            <option value="all">All Items</option>
            <option value="coffee">Coffee Only</option>
            <option value="dessert">Desserts Only</option>
            <option value="mains">Main Dishes Only</option>
          </select>
        </div>

        <div>
          <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">Min Spend (RM)</label>
          <input type="number" step="0.01" name="edit_min_spend" id="editMinSpend">
        </div>
      </div>

      <button type="submit" class="btn-create-promo" style="width: 100%; justify-content: center; height: 48px; border-radius: 25px; font-size: 0.95rem;">Update Promo Code</button>
    </form>
  </div>
</div>

<script>
// Restore Scroll Position After Edit
document.addEventListener('DOMContentLoaded', function() {
    const savedScroll = sessionStorage.getItem('admin_promo_scroll');
    if (savedScroll) {
        window.scrollTo({ top: parseInt(savedScroll), behavior: 'instant' });
        sessionStorage.removeItem('admin_promo_scroll');
    } else if (window.location.hash) {
        const targetEl = document.querySelector(window.location.hash);
        if (targetEl) {
            targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    const editForm = document.getElementById('editPromoForm');
    if (editForm) {
        editForm.addEventListener('submit', function() {
            sessionStorage.setItem('admin_promo_scroll', window.scrollY);
        });
    }

    // Realtime error message clearing
    ['createCode', 'createTitle', 'editCode', 'editTitle'].forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.addEventListener('input', function() {
                const err = document.getElementById(id + 'Error');
                if (err && input.value.trim() !== '') {
                    err.style.display = 'none';
                    input.style.borderColor = '#E8DDD0';
                }
            });
        }
    });
});

function openPromoModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('active');
}

function closePromoModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('active');
}

function openEditPromoModal(p) {
    document.getElementById('editPromoId').value = p.id;
    document.getElementById('editCode').value = p.code;
    document.getElementById('editTitle').value = p.title;
    document.getElementById('editDiscountType').value = p.discount_type;
    document.getElementById('editDiscountValue').value = p.discount_value;
    document.getElementById('editCategoryTarget').value = p.category_target;
    document.getElementById('editMinSpend').value = p.min_spend;

    updateValueLabel('edit');
    openPromoModal('editPromoModal');
}

function updateValueLabel(prefix) {
    const type = document.getElementById(prefix === 'create' ? 'createDiscountType' : 'editDiscountType').value;
    const label = document.getElementById(prefix === 'create' ? 'createValueLabel' : 'editValueLabel');
    if (type === 'percentage') {
        label.textContent = 'Discount Percentage (%)';
    } else if (type === 'free_item') {
        label.textContent = 'Value Equivalent (RM)';
    } else {
        label.textContent = 'Discount Value (RM)';
    }
}

function validatePromoForm(prefix, event) {
    let isValid = true;
    const codeInput = document.getElementById(prefix === 'create' ? 'createCode' : 'editCode');
    const titleInput = document.getElementById(prefix === 'create' ? 'createTitle' : 'editTitle');
    const valInput = document.getElementById(prefix === 'create' ? 'createDiscountValue' : 'editDiscountValue');

    const codeErr = document.getElementById(prefix === 'create' ? 'createCodeError' : 'editCodeError');
    const titleErr = document.getElementById(prefix === 'create' ? 'createTitleError' : 'editTitleError');
    const valErr = document.getElementById(prefix === 'create' ? 'createValueError' : 'editValueError');

    if (!codeInput.value.trim()) {
        codeErr.style.display = 'block';
        codeInput.style.borderColor = '#DC2626';
        isValid = false;
    } else {
        codeErr.style.display = 'none';
        codeInput.style.borderColor = '#E8DDD0';
    }

    if (!titleInput.value.trim()) {
        titleErr.style.display = 'block';
        titleInput.style.borderColor = '#DC2626';
        isValid = false;
    } else {
        titleErr.style.display = 'none';
        titleInput.style.borderColor = '#E8DDD0';
    }

    const num = parseFloat(valInput.value);
    if (valInput.value.trim() === '' || isNaN(num) || num < 0) {
        valErr.style.display = 'block';
        valInput.style.borderColor = '#DC2626';
        isValid = false;
    } else {
        valErr.style.display = 'none';
        valInput.style.borderColor = '#E8DDD0';
    }

    if (!isValid) {
        event.preventDefault();
    }
    return isValid;
}

// TOGGLE ACTIVE STATUS (AJAX WITHOUT PAGE RELOAD)
function togglePromoStatus(btnEl, promoId, currentStatus) {
    const newStatus = currentStatus === 1 ? 0 : 1;
    btnEl.disabled = true;
    btnEl.style.opacity = '0.6';

    fetch(`manage_promos.php?ajax_toggle_status=${promoId}&status=${newStatus}`)
        .then(r => r.json())
        .then(data => {
            btnEl.disabled = false;
            btnEl.style.opacity = '1';
            if (data.status === 'success') {
                const badgeEl = document.getElementById(`promo-status-badge-${promoId}`);
                if (newStatus === 1) {
                    if (badgeEl) badgeEl.innerHTML = '<span style="background: #ECFDF5; color: #065F46; font-weight: 800; font-size: 0.78rem; padding: 4px 12px; border-radius: 20px; border: 1px solid #A7F3D0;">Active</span>';
                    btnEl.textContent = 'Pause';
                    btnEl.className = 'btn-action-status btn-status-active';
                } else {
                    if (badgeEl) badgeEl.innerHTML = '<span style="background: #FEE2E2; color: #991B1B; font-weight: 800; font-size: 0.78rem; padding: 4px 12px; border-radius: 20px; border: 1px solid #FCA5A5;">Paused</span>';
                    btnEl.textContent = 'Activate';
                    btnEl.className = 'btn-action-status btn-status-inactive';
                }
                btnEl.setAttribute('onclick', `togglePromoStatus(this, ${promoId}, ${newStatus})`);
            } else {
                showToast('Failed to update promo status.', 'error');
            }
        })
        .catch(err => {
            btnEl.disabled = false;
            btnEl.style.opacity = '1';
            showToast('Network error.', 'error');
        });
}

// INSTANT CLIENT-SIDE FILTERING & SORTING
function filterPromoTable() {
    const searchVal = document.getElementById('promoSearchInput').value.toLowerCase().trim();
    const catVal = document.getElementById('promoCategoryFilter').value.toLowerCase();
    const rows = document.querySelectorAll('#promoTable tbody tr');

    rows.forEach(row => {
        if (row.children.length === 1) return; // Skip no data row
        const code = row.getAttribute('data-code') || '';
        const title = row.getAttribute('data-title') || '';
        const target = row.getAttribute('data-target') || '';

        const matchesSearch = !searchVal || code.includes(searchVal) || title.includes(searchVal);
        let matchesCat = true;
        if (catVal !== 'all') {
            if (catVal === 'all_items') matchesCat = (target === 'all');
            else matchesCat = (target === catVal);
        }

        if (matchesSearch && matchesCat) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function onSortChange(sortVal) {
    window.location.href = `manage_promos.php?sort=${sortVal}`;
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
