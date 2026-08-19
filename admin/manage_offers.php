<?php
require_once '../includes/admin_auth_check.php';
require_once '../includes/db_connect.php';

// Helper for menu item image path
function getMenuItemImage($img) {
    if (empty($img)) return '../images/default.jpg';
    if (preg_match('/^https?:\/\//i', $img)) return $img;
    $paths = [
        '../images/menu/' . $img,
        '../images/' . $img,
        '../uploads/' . $img
    ];
    foreach ($paths as $p) {
        if (file_exists($p)) return $p;
    }
    return '../images/' . $img;
}

// ============ AJAX TOGGLE ACTIVE STATUS ============
if (isset($_GET['ajax_toggle_status'])) {
    header('Content-Type: application/json');
    $offerId = (int)$_GET['ajax_toggle_status'];
    $status = (int)$_GET['status']; // 1 = Active, 0 = Inactive
    $stmt = $conn->prepare("UPDATE special_offers SET is_active = ? WHERE id = ?");
    $stmt->bind_param("ii", $status, $offerId);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['status' => $ok ? 'success' : 'error', 'new_status' => $status]);
    exit;
}

// ============ AJAX UPDATE OFFER (NO REFRESH / NO SCROLL JUMP) ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['is_ajax_submit'])) {
    header('Content-Type: application/json');
    $formType = $_POST['form_type'] ?? '';

    if ($formType === 'edit_offer') {
        $editId = (int)($_POST['edit_offer_id'] ?? 0);
        $itemId = (int)($_POST['edit_item_id'] ?? 0);
        $offerTitle = trim($_POST['edit_offer_title'] ?? 'Member Special');
        $discountType = trim($_POST['edit_discount_type'] ?? 'percentage');
        $discountValue = (float)($_POST['edit_discount_value'] ?? 0);
        $categoryType = trim($_POST['edit_category_type'] ?? 'drink');

        if ($editId > 0 && $itemId > 0 && $discountValue > 0) {
            $stmt = $conn->prepare("UPDATE special_offers SET item_id = ?, offer_title = ?, discount_type = ?, discount_value = ?, category_type = ? WHERE id = ?");
            $stmt->bind_param("issdsi", $itemId, $offerTitle, $discountType, $discountValue, $categoryType, $editId);
            $ok = $stmt->execute();
            $stmt->close();
            echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Special offer updated successfully!' : 'Failed to update offer.']);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Please enter valid inputs for editing.']);
            exit;
        }
    }
}

// ============ CREATE SPECIAL OFFER (POST -> PRG REDIRECT TO PREVENT DUPLICATES) ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'create_offer') {
    $itemId = (int)($_POST['item_id'] ?? 0);
    $offerTitle = trim($_POST['offer_title'] ?? 'Member Special');
    $discountType = trim($_POST['discount_type'] ?? 'percentage');
    $discountValue = (float)($_POST['discount_value'] ?? 0);
    $categoryType = trim($_POST['category_type'] ?? 'drink');

    if ($itemId > 0 && $discountValue > 0) {
        $stmt = $conn->prepare("INSERT INTO special_offers (item_id, offer_title, discount_type, discount_value, category_type, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
        $stmt->bind_param("issds", $itemId, $offerTitle, $discountType, $discountValue, $categoryType);
        $stmt->execute();
        $stmt->close();

        header("Location: manage_offers.php?msg=created");
        exit();
    }
}

// ============ DELETE OFFER (GET -> PRG REDIRECT) ============
if (isset($_GET['delete'])) {
    $offerId = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM special_offers WHERE id = ?");
    $stmt->bind_param("i", $offerId);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_offers.php?msg=deleted");
    exit();
}

$message = '';
$messageType = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') {
        $message = "Special offer created successfully!";
        $messageType = "success";
    } elseif ($_GET['msg'] === 'updated') {
        $message = "Special offer updated successfully!";
        $messageType = "success";
    } elseif ($_GET['msg'] === 'deleted') {
        $message = "Special offer removed successfully.";
        $messageType = "success";
    }
}

// Fetch categories for category filter dropdown
$categoriesList = [];
$catRes = $conn->query("SELECT category_id, category_label FROM categories ORDER BY display_order ASC");
if ($catRes) {
    while ($c = $catRes->fetch_assoc()) {
        $categoriesList[] = $c;
    }
}

// Fetch all menu items joined with categories for searchable item select
$allItems = [];
$menuItemsRes = $conn->query("SELECT mi.item_id, mi.name, mi.price, mi.image, mi.category_id, c.category_label 
                              FROM menu_items mi 
                              LEFT JOIN categories c ON c.category_id = mi.category_id 
                              ORDER BY c.display_order ASC, mi.name ASC");
if ($menuItemsRes) {
    while ($m = $menuItemsRes->fetch_assoc()) {
        $m['image_path'] = getMenuItemImage($m['image']);
        $allItems[] = $m;
    }
}

// Fetch all special offers joined with menu_items
$offersQuery = "SELECT so.*, mi.name as item_name, mi.price as original_price, mi.image as item_image, mi.category_id, c.category_label
                FROM special_offers so 
                JOIN menu_items mi ON so.item_id = mi.item_id 
                LEFT JOIN categories c ON c.category_id = mi.category_id
                ORDER BY so.category_type ASC, so.created_at DESC";
$offersRes = $conn->query($offersQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/admin.css">
  <title>Cozy Coffee Co. — Manage Special Offers</title>
  <style>
    body.admin-page {
      background: #FAF6F0 !important;
      color: #2C1C14;
    }

    .admin-offers-wrap {
      max-width: 1240px;
      margin: 20px auto 60px;
      padding: 0 4%;
      box-sizing: border-box;
    }

    .btn-create-offer {
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

    .btn-create-offer:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 24px rgba(200, 90, 62, 0.45);
      background: linear-gradient(135deg, #B64C32 0%, #8C3722 100%);
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

    /* Offers Grid / Table Cards */
    .admin-offer-list {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
    }

    @media (max-width: 980px) {
      .admin-offer-list { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 640px) {
      .admin-offer-list { grid-template-columns: 1fr; }
    }

    .admin-offer-card {
      background: #FFFFFF;
      border-radius: 18px;
      border: 1.5px solid #E8DDD0;
      padding: 20px;
      box-shadow: 0 6px 20px rgba(60, 42, 33, 0.04);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: relative;
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .admin-offer-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 28px rgba(60, 42, 33, 0.1);
    }

    .offer-badge-tag {
      align-self: flex-start;
      background: #FAF4EB;
      color: #C85A3E;
      border: 1px solid rgba(200, 90, 62, 0.3);
      font-size: 0.72rem;
      font-weight: 800;
      padding: 4px 12px;
      border-radius: 20px;
      margin-bottom: 12px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .offer-item-preview {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 14px;
    }

    .offer-item-img {
      width: 68px;
      height: 68px;
      border-radius: 12px;
      object-fit: cover;
      border: 1.5px solid #E8DDD0;
      background: #FAF4EB;
    }

    .offer-item-title {
      font-family: var(--font-heading, serif);
      font-weight: 800;
      font-size: 1.1rem;
      color: #2C1C14;
      margin: 0 0 4px 0;
    }

    .offer-pricing-detail {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-top: 4px;
    }

    .offer-action-row {
      display: flex;
      gap: 8px;
      margin-top: 14px;
      padding-top: 14px;
      border-top: 1px dashed #E8DDD0;
    }

    .btn-action-sm {
      flex: 1;
      padding: 8px 10px;
      border-radius: 10px;
      font-weight: 700;
      font-size: 0.8rem;
      border: 1px solid #E8DDD0;
      cursor: pointer;
      text-align: center;
      text-decoration: none;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 4px;
    }

    .btn-action-edit {
      background: #FAF4EB;
      color: #5C4A3E;
      border-color: #E8DDD0;
    }
    .btn-action-edit:hover {
      background: #C85A3E;
      color: #FFFFFF;
      border-color: #A8472F;
    }

    /* PAUSE / ACTIVATE STATUS BUTTON COLORED STATES */
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
      background: #FEF2F2;
      color: #DC2626;
      border-color: #FCA5A5;
    }
    .btn-action-delete:hover {
      background: #DC2626;
      color: #FFFFFF;
    }

    /* DISCOUNT TYPE SELECT BUTTONS */
    .disc-type-group {
      display: flex;
      gap: 10px;
      margin-top: 6px;
    }

    .disc-type-btn {
      flex: 1;
      padding: 10px 14px;
      border-radius: 12px;
      border: 1.5px solid #E8DDD0;
      background: #FAF7F2;
      color: #4A3B32;
      font-weight: 700;
      font-size: 0.88rem;
      cursor: pointer;
      transition: all 0.2s ease;
      text-align: center;
    }

    .disc-type-btn.active {
      background: linear-gradient(135deg, #C85A3E 0%, #A8472F 100%);
      color: #FFFFFF;
      border-color: #A8472F;
      box-shadow: 0 4px 14px rgba(200, 90, 62, 0.25);
    }

    /* SEARCHABLE CUSTOM ITEM SELECTOR WITH THUMBNAIL PICTURES */
    .custom-item-picker {
      border: 1.5px solid #E8DDD0;
      border-radius: 14px;
      background: #FAF7F2;
      overflow: hidden;
      margin-top: 6px;
    }

    .item-search-input {
      width: 100%;
      padding: 10px 14px;
      border: none;
      border-bottom: 1.5px solid #E8DDD0;
      outline: none;
      font-size: 0.88rem;
      background: #FFFFFF;
      box-sizing: border-box;
    }

    .item-options-list {
      max-height: 180px;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
    }

    .item-option-row {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 14px;
      cursor: pointer;
      border-bottom: 1px solid #F3EBE1;
      transition: background 0.2s ease;
    }

    .item-option-row:hover,
    .item-option-row.selected {
      background: #FAF4EB;
    }

    .item-option-thumb {
      width: 36px;
      height: 36px;
      border-radius: 8px;
      object-fit: cover;
      border: 1px solid #E8DDD0;
      background: #FAF4EB;
      flex-shrink: 0;
    }

    .item-option-info {
      flex: 1;
      min-width: 0;
    }

    .item-option-name {
      font-weight: 800;
      font-size: 0.9rem;
      color: #2C1C14;
    }

    .item-option-price {
      font-size: 0.8rem;
      color: #C85A3E;
      font-weight: 700;
      margin-top: 2px;
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

<?php $adminActivePage = 'offers'; require_once '../includes/admin_header_nav.php'; ?>

<div class="admin-offers-wrap">

  <div class="admin-page-header">
    <a href="dashboard.php" class="btn-back-dashboard">&larr; Back to Dashboard</a>
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; width: 100%;">
      <div>
        <h1 class="admin-header-title">Manage Special Offers</h1>
        <p class="admin-header-subtitle">Configure promotional discount pricing, promotional tags, and deal sections.</p>
      </div>
      <button type="button" class="btn-create-offer" onclick="openOfferModal('createOfferModal')">
        + Add Special Offer
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

  <!-- OFFERS LIST GRID -->
  <div class="admin-offer-list">
    <?php if ($offersRes && $offersRes->num_rows > 0): ?>
      <?php while ($row = $offersRes->fetch_assoc()): 
        $origP = (float)$row['original_price'];
        $discVal = (float)$row['discount_value'];
        $discType = $row['discount_type'];
        
        if ($discType === 'percentage') {
          $finalP = max(0, $origP - ($origP * ($discVal / 100)));
        } else {
          $finalP = max(0, $discVal);
        }
        $imgSrc = getMenuItemImage($row['item_image']);
        $isActive = ((int)$row['is_active'] === 1);
      ?>
        <div class="admin-offer-card" id="offer-card-<?php echo $row['id']; ?>">
          <div>
            <div class="offer-badge-tag"><?php echo htmlspecialchars($row['offer_title']); ?></div>

            <div class="offer-item-preview">
              <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="" class="offer-item-img">
              <div>
                <h3 class="offer-item-title"><?php echo htmlspecialchars($row['item_name']); ?></h3>
                
                <!-- CLEAN PRICE DISPLAY: NO "Original Price" TEXT, GREY CROSSED-OUT PRICE & NEW PRICE BESIDE IT -->
                <div class="offer-pricing-detail">
                  <span style="text-decoration: line-through; color: #8C7A6D; font-size: 0.95rem; font-weight: 600;">RM <?php echo number_format($origP, 2); ?></span>
                  <span style="color: #059669; font-weight: 800; font-size: 1.15rem; font-family: var(--font-heading, serif);">RM <?php echo number_format($finalP, 2); ?></span>
                </div>
              </div>
            </div>

            <div style="font-size: 0.8rem; color: #7A685A; margin-bottom: 8px;">
              Section: <strong><?php echo ucfirst($row['category_type']); ?> Offer</strong> | Status: 
              <span id="offer-status-badge-<?php echo $row['id']; ?>">
                <?php if ($isActive): ?>
                  <span style="color: #059669; font-weight: 800;">Active</span>
                <?php else: ?>
                  <span style="color: #DC2626; font-weight: 800;">Inactive</span>
                <?php endif; ?>
              </span>
            </div>
          </div>

          <div class="offer-action-row">
            <button type="button" class="btn-action-sm btn-action-edit" onclick="openEditOfferModal(<?php echo $row['id']; ?>, <?php echo $row['item_id']; ?>, '<?php echo htmlspecialchars(addslashes($row['offer_title'])); ?>', '<?php echo $row['discount_type']; ?>', <?php echo $row['discount_value']; ?>, '<?php echo $row['category_type']; ?>', <?php echo (int)($row['category_id'] ?? 0); ?>)">
              Edit
            </button>
            <button type="button" class="btn-action-sm btn-action-status <?php echo $isActive ? 'btn-status-active' : 'btn-status-inactive'; ?>" id="offer-status-btn-<?php echo $row['id']; ?>" onclick="toggleOfferStatus(this, <?php echo $row['id']; ?>, <?php echo (int)$row['is_active']; ?>)">
              <?php echo $isActive ? 'Pause' : 'Activate'; ?>
            </button>
            <a href="manage_offers.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('⚠️ Are you sure you want to delete this special offer?')" class="btn-action-sm btn-action-delete">Delete</a>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="grid-column: 1 / -1; text-align: center; color: #7A685A; padding: 40px; background: #FFFFFF; border-radius: 18px; border: 1.5px solid #E8DDD0;">No special offers created yet. Click "+ Add Special Offer" above to configure one!</p>
    <?php endif; ?>
  </div>

</div>

<!-- CREATE OFFER POP-UP MODAL -->
<div id="createOfferModal" class="admin-modal-overlay">
  <div class="admin-modal-box" style="max-width: 520px; text-align: left;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1.5px solid #F4EDE4;">
      <h2 style="font-family: var(--font-heading); color: #2C1C14; margin: 0; font-size: 1.25rem; font-weight: 800;">
        Create Special Offer
      </h2>
      <button type="button" onclick="closeOfferModal('createOfferModal')" style="background: transparent; border: none; font-size: 1.6rem; color: #7A685A; cursor: pointer; line-height: 1;">&times;</button>
    </div>

    <form id="createOfferForm" action="manage_offers.php" method="POST" onsubmit="return validateOfferForm('create', event)">
      <input type="hidden" name="form_type" value="create_offer">
      <input type="hidden" name="item_id" id="createItemId">

      <!-- STEP 1: SELECT CATEGORY FIRST -->
      <div style="margin-bottom: 16px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">1. Select Category First</label>
        <select id="createCategorySelect" onchange="onCategoryChange('create')" style="width:100%; padding:11px 14px; border:1.5px solid #E8DDD0; border-radius:12px; font-size:0.92rem; outline:none; background:#FAF7F2;">
          <option value="">-- Choose Category First --</option>
          <?php foreach ($categoriesList as $cat): ?>
            <option value="<?php echo $cat['category_id']; ?>">
              <?php echo htmlspecialchars($cat['category_label']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div id="createCategoryError" class="form-field-error">Please select a category first.</div>
      </div>

      <!-- STEP 2: SEARCH & SELECT MENU ITEM WITH THUMBNAIL PICTURES -->
      <div style="margin-bottom: 16px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">2. Select Menu Item</label>
        <div class="custom-item-picker">
          <input type="text" id="createItemSearch" class="item-search-input" placeholder="Type to search menu item..." onkeyup="filterItemsList('create')">
          <div id="createItemOptionsList" class="item-options-list">
            <div style="padding:14px; color:#8C7A6D; text-align:center; font-size:0.85rem;">Please select a category first above.</div>
          </div>
        </div>
        <div id="createItemError" class="form-field-error">Please select a menu item.</div>
      </div>

      <!-- TAG TITLE -->
      <div style="margin-bottom: 16px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">Tag Title</label>
        <input type="text" name="offer_title" id="createOfferTitle" value="Member Special (15% OFF)" placeholder="e.g. Member Special, Merdeka Deal, Chef Choice" style="width:100%; padding:11px 14px; border:1.5px solid #E8DDD0; border-radius:12px; font-size:0.92rem; outline:none; background:#FAF7F2; box-sizing:border-box;">
        <div id="createTitleError" class="form-field-error">Please enter a tag title.</div>
      </div>

      <!-- DISCOUNT TYPE (BUTTON GROUP: PERCENTAGE VS FIXED AMOUNT) -->
      <div style="margin-bottom: 16px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">Discount Type</label>
        <div class="disc-type-group">
          <button type="button" id="createTypeBtnPct" class="disc-type-btn active" onclick="selectDiscountType('create', 'percentage')">
            Percentage (% OFF)
          </button>
          <button type="button" id="createTypeBtnFix" class="disc-type-btn" onclick="selectDiscountType('create', 'fixed_price')">
            Fixed Amount (RM Price)
          </button>
        </div>
        <input type="hidden" name="discount_type" id="createDiscountTypeVal" value="percentage">
      </div>

      <!-- DISCOUNT VALUE (NUMERIC VALIDATION) -->
      <div style="margin-bottom: 16px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;" id="createDiscountValLabel">Discount Value (% OFF)</label>
        <input type="number" step="0.01" name="discount_value" id="createDiscountValue" placeholder="e.g. 15 for 15% OFF or 8.50 for RM8.50" value="15.00" oninput="validateDiscountValueInput('create')" style="width:100%; padding:11px 14px; border:1.5px solid #E8DDD0; border-radius:12px; font-size:0.92rem; outline:none; background:#FAF7F2; box-sizing:border-box;">
        <div id="createValueError" class="form-field-error">Please enter a valid positive discount number.</div>
      </div>

      <div style="margin-bottom: 22px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">Display Section</label>
        <select name="category_type" id="createCategoryType" style="width:100%; padding:11px 14px; border:1.5px solid #E8DDD0; border-radius:12px; font-size:0.92rem; outline:none; background:#FAF7F2;">
          <option value="drink">Drink Offers Section</option>
          <option value="food">Food Offers Section</option>
        </select>
      </div>

      <button type="submit" class="btn-create-offer" style="width: 100%; justify-content: center; height: 48px; border-radius: 25px; font-size: 0.95rem;">Save &amp; Publish</button>
    </form>
  </div>
</div>

<!-- EDIT OFFER POP-UP MODAL -->
<div id="editOfferModal" class="admin-modal-overlay">
  <div class="admin-modal-box" style="max-width: 520px; text-align: left;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1.5px solid #F4EDE4;">
      <h2 style="font-family: var(--font-heading); color: #2C1C14; margin: 0; font-size: 1.25rem; font-weight: 800;">
        Edit Special Offer
      </h2>
      <button type="button" onclick="closeOfferModal('editOfferModal')" style="background: transparent; border: none; font-size: 1.6rem; color: #7A685A; cursor: pointer; line-height: 1;">&times;</button>
    </div>

    <form id="editOfferForm" action="manage_offers.php" method="POST" onsubmit="return handleEditFormSubmit(event)">
      <input type="hidden" name="form_type" value="edit_offer">
      <input type="hidden" name="is_ajax_submit" value="1">
      <input type="hidden" name="edit_offer_id" id="editOfferId">
      <input type="hidden" name="edit_item_id" id="editItemId">

      <!-- STEP 1: SELECT CATEGORY FIRST -->
      <div style="margin-bottom: 16px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">1. Select Category First</label>
        <select id="editCategorySelect" onchange="onCategoryChange('edit')" style="width:100%; padding:11px 14px; border:1.5px solid #E8DDD0; border-radius:12px; font-size:0.92rem; outline:none; background:#FAF7F2;">
          <option value="">-- Choose Category First --</option>
          <?php foreach ($categoriesList as $cat): ?>
            <option value="<?php echo $cat['category_id']; ?>">
              <?php echo htmlspecialchars($cat['category_label']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div id="editCategoryError" class="form-field-error">Please select a category first.</div>
      </div>

      <!-- STEP 2: SEARCH & SELECT MENU ITEM WITH THUMBNAIL PICTURES -->
      <div style="margin-bottom: 16px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">2. Select Menu Item</label>
        <div class="custom-item-picker">
          <input type="text" id="editItemSearch" class="item-search-input" placeholder="Type to search menu item..." onkeyup="filterItemsList('edit')">
          <div id="editItemOptionsList" class="item-options-list">
            <div style="padding:14px; color:#8C7A6D; text-align:center; font-size:0.85rem;">Please select a category first above.</div>
          </div>
        </div>
        <div id="editItemError" class="form-field-error">Please select a menu item.</div>
      </div>

      <!-- TAG TITLE -->
      <div style="margin-bottom: 16px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">Tag Title</label>
        <input type="text" name="edit_offer_title" id="editOfferTitle" style="width:100%; padding:11px 14px; border:1.5px solid #E8DDD0; border-radius:12px; font-size:0.92rem; outline:none; background:#FAF7F2; box-sizing:border-box;">
        <div id="editTitleError" class="form-field-error">Please enter a tag title.</div>
      </div>

      <!-- DISCOUNT TYPE (BUTTON GROUP: PERCENTAGE VS FIXED AMOUNT) -->
      <div style="margin-bottom: 16px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">Discount Type</label>
        <div class="disc-type-group">
          <button type="button" id="editTypeBtnPct" class="disc-type-btn active" onclick="selectDiscountType('edit', 'percentage')">
            Percentage (% OFF)
          </button>
          <button type="button" id="editTypeBtnFix" class="disc-type-btn" onclick="selectDiscountType('edit', 'fixed_price')">
            Fixed Amount (RM Price)
          </button>
        </div>
        <input type="hidden" name="edit_discount_type" id="editDiscountTypeVal" value="percentage">
      </div>

      <!-- DISCOUNT VALUE (NUMERIC VALIDATION) -->
      <div style="margin-bottom: 16px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;" id="editDiscountValLabel">Discount Value (% OFF)</label>
        <input type="number" step="0.01" name="edit_discount_value" id="editDiscountValue" oninput="validateDiscountValueInput('edit')" style="width:100%; padding:11px 14px; border:1.5px solid #E8DDD0; border-radius:12px; font-size:0.92rem; outline:none; background:#FAF7F2; box-sizing:border-box;">
        <div id="editValueError" class="form-field-error">Please enter a valid positive discount number.</div>
      </div>

      <div style="margin-bottom: 22px;">
        <label style="font-weight:700; color:#2C1C14; display:block; margin-bottom:6px;">Display Section</label>
        <select name="edit_category_type" id="editCategoryType" style="width:100%; padding:11px 14px; border:1.5px solid #E8DDD0; border-radius:12px; font-size:0.92rem; outline:none; background:#FAF7F2;">
          <option value="drink">Drink Offers Section</option>
          <option value="food">Food Offers Section</option>
        </select>
      </div>

      <button type="submit" class="btn-create-offer" style="width: 100%; justify-content: center; height: 48px; border-radius: 25px; font-size: 0.95rem;">Update</button>
    </form>
  </div>
</div>

<script>
// JSON Master List of All Menu Items with Category and Thumbnails
const allMenuItems = <?php echo json_encode($allItems); ?>;

function openOfferModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}

function closeOfferModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

function selectDiscountType(prefix, type) {
    const hiddenInput = document.getElementById(prefix === 'create' ? 'createDiscountTypeVal' : 'editDiscountTypeVal');
    const btnPct = document.getElementById(prefix === 'create' ? 'createTypeBtnPct' : 'editTypeBtnPct');
    const btnFix = document.getElementById(prefix === 'create' ? 'createTypeBtnFix' : 'editTypeBtnFix');
    const valLabel = document.getElementById(prefix === 'create' ? 'createDiscountValLabel' : 'editDiscountValLabel');

    hiddenInput.value = type;
    if (type === 'percentage') {
        btnPct.classList.add('active');
        btnFix.classList.remove('active');
        valLabel.textContent = 'Discount Percentage (% OFF)';
    } else {
        btnFix.classList.add('active');
        btnPct.classList.remove('active');
        valLabel.textContent = 'Fixed Offer Price (RM Special Price)';
    }
    validateDiscountValueInput(prefix);
}

function onCategoryChange(prefix) {
    const catSelect = document.getElementById(prefix === 'create' ? 'createCategorySelect' : 'editCategorySelect');
    const catId = catSelect.value;
    const searchInput = document.getElementById(prefix === 'create' ? 'createItemSearch' : 'editItemSearch');
    const itemIdHidden = document.getElementById(prefix === 'create' ? 'createItemId' : 'editItemId');

    searchInput.value = '';
    itemIdHidden.value = '';
    filterItemsList(prefix);
}

function filterItemsList(prefix) {
    const catSelect = document.getElementById(prefix === 'create' ? 'createCategorySelect' : 'editCategorySelect');
    const catId = catSelect.value;
    const searchInput = document.getElementById(prefix === 'create' ? 'createItemSearch' : 'editItemSearch');
    const searchVal = searchInput.value.toLowerCase().trim();
    const optionsContainer = document.getElementById(prefix === 'create' ? 'createItemOptionsList' : 'editItemOptionsList');
    const selectedItemId = document.getElementById(prefix === 'create' ? 'createItemId' : 'editItemId').value;

    if (!catId) {
        optionsContainer.innerHTML = '<div style="padding:14px; color:#8C7A6D; text-align:center; font-size:0.85rem;">Please select a category first above.</div>';
        return;
    }

    const filtered = allMenuItems.filter(item => {
        const matchesCat = String(item.category_id) === String(catId);
        const matchesSearch = !searchVal || item.name.toLowerCase().includes(searchVal);
        return matchesCat && matchesSearch;
    });

    if (filtered.length === 0) {
        optionsContainer.innerHTML = '<div style="padding:14px; color:#8C7A6D; text-align:center; font-size:0.85rem;">No menu items found in this category.</div>';
        return;
    }

    let html = '';
    filtered.forEach(item => {
        const isSel = String(item.item_id) === String(selectedItemId);
        html += `
            <div class="item-option-row ${isSel ? 'selected' : ''}" onclick="selectMenuItem('${prefix}', ${item.item_id}, '${escapeJsString(item.name)}')">
                <img src="${item.image_path}" alt="" class="item-option-thumb">
                <div class="item-option-info">
                    <div class="item-option-name">${escapeHtml(item.name)}</div>
                    <div class="item-option-price"><span style="text-decoration:line-through; color:#8C7A6D;">RM ${parseFloat(item.price).toFixed(2)}</span></div>
                </div>
            </div>
        `;
    });

    optionsContainer.innerHTML = html;
}

function selectMenuItem(prefix, itemId, itemName) {
    const hiddenId = document.getElementById(prefix === 'create' ? 'createItemId' : 'editItemId');
    const searchInput = document.getElementById(prefix === 'create' ? 'createItemSearch' : 'editItemSearch');
    hiddenId.value = itemId;
    searchInput.value = itemName;
    filterItemsList(prefix);
    document.getElementById(prefix === 'create' ? 'createItemError' : 'editItemError').style.display = 'none';
}

function validateDiscountValueInput(prefix) {
    const valInput = document.getElementById(prefix === 'create' ? 'createDiscountValue' : 'editDiscountValue');
    const errDiv = document.getElementById(prefix === 'create' ? 'createValueError' : 'editValueError');
    const discType = document.getElementById(prefix === 'create' ? 'createDiscountTypeVal' : 'editDiscountTypeVal').value;
    const num = parseFloat(valInput.value);

    if (valInput.value.trim() === '' || isNaN(num) || num <= 0) {
        errDiv.textContent = 'Please enter a valid positive discount number (e.g. 15 for 15% or 8.50).';
        errDiv.style.display = 'block';
        return false;
    } else if (discType === 'percentage' && num > 100) {
        errDiv.textContent = 'Percentage discount cannot exceed 100%.';
        errDiv.style.display = 'block';
        return false;
    } else {
        errDiv.style.display = 'none';
        return true;
    }
}

function validateOfferForm(prefix, event) {
    let isValid = true;

    const catId = document.getElementById(prefix === 'create' ? 'createCategorySelect' : 'editCategorySelect').value;
    const catErr = document.getElementById(prefix === 'create' ? 'createCategoryError' : 'editCategoryError');
    if (!catId) {
        catErr.style.display = 'block';
        isValid = false;
    } else {
        catErr.style.display = 'none';
    }

    const itemId = document.getElementById(prefix === 'create' ? 'createItemId' : 'editItemId').value;
    const itemErr = document.getElementById(prefix === 'create' ? 'createItemError' : 'editItemError');
    if (!itemId) {
        itemErr.style.display = 'block';
        isValid = false;
    } else {
        itemErr.style.display = 'none';
    }

    const titleVal = document.getElementById(prefix === 'create' ? 'createOfferTitle' : 'editOfferTitle').value.trim();
    const titleErr = document.getElementById(prefix === 'create' ? 'createTitleError' : 'editTitleError');
    if (!titleVal) {
        titleErr.style.display = 'block';
        isValid = false;
    } else {
        titleErr.style.display = 'none';
    }

    const valValid = validateDiscountValueInput(prefix);
    if (!valValid) isValid = false;

    if (!isValid) {
        event.preventDefault();
    }
    return isValid;
}

function openEditOfferModal(offerId, itemId, offerTitle, discountType, discountValue, categoryType, categoryId) {
    document.getElementById('editOfferId').value = offerId;
    document.getElementById('editOfferTitle').value = offerTitle;
    document.getElementById('editDiscountValue').value = discountValue;
    document.getElementById('editCategoryType').value = categoryType;

    selectDiscountType('edit', discountType);

    if (categoryId && categoryId > 0) {
        document.getElementById('editCategorySelect').value = categoryId;
    } else {
        const itemObj = allMenuItems.find(i => String(i.item_id) === String(itemId));
        if (itemObj) {
            document.getElementById('editCategorySelect').value = itemObj.category_id;
        }
    }

    const itemObj = allMenuItems.find(i => String(i.item_id) === String(itemId));
    if (itemObj) {
        selectMenuItem('edit', itemId, itemObj.name);
    } else {
        document.getElementById('editItemId').value = itemId;
    }

    openOfferModal('editOfferModal');
}

// ASYNC AJAX UPDATE FORM SUBMIT (NO PAGE RELOAD / NO SCROLL JUMP & CLEAN PRG REDIRECT)
function handleEditFormSubmit(e) {
    e.preventDefault();
    if (!validateOfferForm('edit', e)) return false;

    const form = document.getElementById('editOfferForm');
    const formData = new FormData(form);

    fetch('manage_offers.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            closeOfferModal('editOfferModal');
            window.location.href = 'manage_offers.php?msg=updated';
        } else {
            alert(data.message || 'Error updating offer.');
        }
    })
    .catch(err => {
        console.error('Error updating offer:', err);
        form.submit();
    });
    return false;
}

function toggleOfferStatus(btnEl, offerId, currentStatus) {
    const newStatus = currentStatus === 1 ? 0 : 1;
    fetch(`manage_offers.php?ajax_toggle_status=${offerId}&status=${newStatus}`)
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                const badgeEl = document.getElementById(`offer-status-badge-${offerId}`);
                if (badgeEl) {
                    badgeEl.innerHTML = newStatus === 1 ? '<span style="color: #059669; font-weight: 800;">Active</span>' : '<span style="color: #DC2626; font-weight: 800;">Inactive</span>';
                }
                btnEl.textContent = newStatus === 1 ? 'Pause' : 'Activate';
                if (newStatus === 1) {
                    btnEl.className = 'btn-action-sm btn-action-status btn-status-active';
                } else {
                    btnEl.className = 'btn-action-sm btn-action-status btn-status-inactive';
                }
                btnEl.setAttribute('onclick', `toggleOfferStatus(this, ${offerId}, ${newStatus})`);
            }
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

function escapeJsString(str) {
    return str.replace(/'/g, "\\'").replace(/"/g, '\\"');
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>

</body>
</html>
