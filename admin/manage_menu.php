<?php
require_once '../includes/admin_auth_check.php';
require_once '../includes/db_connect.php';

$message = '';
$messageType = '';

// ============ DELETE ============
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare('DELETE FROM menu_items WHERE item_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_menu.php?msg=deleted");
    exit();
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted') {
        $message = 'Item deleted successfully.';
        $messageType = 'success';
    } elseif ($_GET['msg'] === 'updated') {
        $message = 'Item updated successfully.';
        $messageType = 'success';
    } elseif ($_GET['msg'] === 'created') {
        $message = 'Item added successfully.';
        $messageType = 'success';
    }
}

// ============ ADD or UPDATE ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemId      = (int) ($_POST['item_id'] ?? 0);
    $categoryId  = (int) ($_POST['category_id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priceRaw    = trim($_POST['price'] ?? '');
    $removeImage = (int) ($_POST['remove_image'] ?? 0);

    if ($categoryId <= 0) {
        $message = 'Please select a valid category.';
        $messageType = 'error';
    } elseif ($name === '') {
        $message = 'Please enter an item name.';
        $messageType = 'error';
    } elseif ($priceRaw === '' || !is_numeric($priceRaw) || (float)$priceRaw <= 0) {
        $message = 'Please enter a valid numeric price.';
        $messageType = 'error';
    } else {
        $price = (float)$priceRaw;
        $imageFilename = $_POST['existing_image'] ?? '';

        if ($removeImage === 1) {
            $imageFilename = '';
        }

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath   = $_FILES['image']['tmp_name'];
            $fileName      = $_FILES['image']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExtension;
                $uploadFileDir = '../images/menu/';
                
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }

                $dest_path = $uploadFileDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $imageFilename = $newFileName;
                } else {
                    $message = 'Error moving uploaded file to server.';
                    $messageType = 'error';
                }
            } else {
                $message = 'Upload failed. Allowed types: ' . implode(', ', $allowedExtensions);
                $messageType = 'error';
            }
        }

        if ($messageType !== 'error') {
            if ($itemId > 0) {
                $stmt = $conn->prepare('UPDATE menu_items SET category_id=?, name=?, description=?, price=?, image=? WHERE item_id=?');
                $stmt->bind_param('issdsi', $categoryId, $name, $description, $price, $imageFilename, $itemId);
                $stmt->execute();
                $stmt->close();
                $message = 'Item updated successfully.';
                $messageType = 'success';
            } else {
                $stmt = $conn->prepare('INSERT INTO menu_items (category_id, name, description, price, image) VALUES (?, ?, ?, ?, ?)');
                $stmt->bind_param('issds', $categoryId, $name, $description, $price, $imageFilename);
                $stmt->execute();
                $stmt->close();
                $message = 'Item added successfully.';
                $messageType = 'success';
            }
        }
    }
}

// ============ Fetch categories ============
$categoriesResult = $conn->query('SELECT category_id, category_label FROM categories ORDER BY display_order');
$categoriesList = [];
while ($cat = $categoriesResult->fetch_assoc()) {
    $categoriesList[] = $cat;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/admin.css">
  <title>Cozy Coffee Co. — Manage Menu Items</title>
  <style>
    .manage-menu-container {
      display: flex;
      gap: 28px;
      margin-top: 22px;
    }

    @media (max-width: 1024px) {
      .manage-menu-container {
        flex-direction: column;
      }
      .menu-form-col {
        flex: 1 1 100% !important;
        width: 100% !important;
      }
      .menu-table-col {
        width: 100% !important;
      }
    }

    /* ADD NEW ITEM FORM PANEL (LUXURIOUS CARD WITH AMBIENT SHADOW & GLOW) */
    .menu-form-col {
      flex: 0 0 380px;
      background: #FFFFFF;
      border-radius: 22px;
      border: 1.5px solid #E8DDD0;
      box-shadow: 0 14px 38px rgba(60, 42, 33, 0.08), 0 0 0 1px rgba(200, 90, 62, 0.08);
      overflow: hidden;
      align-self: flex-start;
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .menu-form-col:hover {
      box-shadow: 0 18px 48px rgba(60, 42, 33, 0.12), 0 0 22px rgba(200, 90, 62, 0.12);
    }

    .form-card-header {
      background: linear-gradient(135deg, #FAF4EB 0%, #F4EDE4 100%);
      padding: 20px 24px;
      border-bottom: 1.5px solid #E8DDD0;
    }

    .form-card-header h2 {
      font-family: var(--font-heading, serif);
      color: #2C1C14;
      margin: 0;
      font-size: 1.25rem;
      font-weight: 800;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .form-card-body {
      padding: 24px;
    }

    .menu-table-col {
      flex: 1;
      min-width: 0;
    }

    .admin-form .form-row {
      margin-bottom: 20px;
    }

    .admin-form label {
      display: block;
      font-weight: 700;
      font-size: 0.86rem;
      margin-bottom: 6px;
      color: #4A3B32;
    }

    .admin-form input[type="text"],
    .admin-form input[type="number"],
    .admin-form select,
    .admin-form textarea {
      width: 100%;
      padding: 11px 16px;
      border: 1.5px solid #E8DDD0;
      border-radius: 12px;
      font-family: inherit;
      font-size: 0.92rem;
      box-sizing: border-box;
      background: #FAF7F2;
      color: #2C1C14;
      transition: all 0.2s ease;
    }

    .admin-form input:focus,
    .admin-form select:focus,
    .admin-form textarea:focus {
      outline: none;
      border-color: #C85A3E;
      background: #FFFFFF;
      box-shadow: 0 0 0 4px rgba(200, 90, 62, 0.12);
    }

    .field-error-msg {
      color: #DC2626;
      font-size: 0.8rem;
      font-weight: 700;
      margin-top: 4px;
      display: none;
    }

    /* Controls Header Bar */
    .table-controls {
      display: flex;
      gap: 16px;
      margin-bottom: 20px;
      width: 100%;
    }

    .table-controls input,
    .table-controls select {
      font-family: inherit;
      font-size: 0.9rem;
      padding: 11px 18px;
      background-color: #FFFFFF;
      border: 1.5px solid #E8DDD0;
      border-radius: 14px;
      color: #2C1C14;
      outline: none;
      box-shadow: 0 4px 16px rgba(60, 42, 33, 0.04);
      transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
    }

    .table-controls input:focus,
    .table-controls select:focus {
      border-color: #C85A3E;
      background-color: #FFFFFF;
      box-shadow: 0 0 0 4px rgba(200, 90, 62, 0.14), 0 4px 16px rgba(200, 90, 62, 0.08);
    }

    .table-controls input {
      flex: 2;
    }

    .table-controls select {
      flex: 1;
    }

    /* ELEGANT MENU TABLE STYLING WITH AMBIENT DEEP SHADOW & RESPONSIVE SCROLL */
    .admin-table-wrap {
      background: #FFFFFF;
      border-radius: 22px;
      border: 1.5px solid #E8DDD0;
      overflow-x: auto !important;
      max-width: 100% !important;
      box-shadow: 0 14px 38px rgba(60, 42, 33, 0.08), 0 0 0 1px rgba(200, 90, 62, 0.08);
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .admin-table-wrap:hover {
      box-shadow: 0 18px 48px rgba(60, 42, 33, 0.12), 0 0 22px rgba(200, 90, 62, 0.12);
    }

    .admin-table {
      width: 100%;
      border-collapse: collapse;
    }

    .admin-table thead tr {
      background: linear-gradient(135deg, #FAF4EB 0%, #F4EDE4 100%);
      border-bottom: 1.5px solid #E8DDD0;
      color: #4A3B32;
      font-size: 0.78rem;
      letter-spacing: 1px;
      font-weight: 800;
      text-transform: uppercase;
    }

    .admin-table th {
      padding: 16px 14px;
      text-align: center !important;
    }

    .admin-table tbody td {
      padding: 16px 14px;
      border-bottom: 1px solid #F3EBE1;
      vertical-align: middle !important;
      text-align: center !important;
    }

    .item-row {
      transition: background 0.2s ease;
    }

    .item-row:hover {
      background: #FAF7F2 !important;
    }

    /* 3-LINE DESCRIPTION CLAMP */
    .table-desc-clamp {
      display: -webkit-box;
      -webkit-line-clamp: 3;
      line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: pre-line;
      font-size: 0.83rem;
      color: #665447;
      line-height: 1.45;
      margin-top: 4px;
      max-width: 380px;
      margin-left: auto;
      margin-right: auto;
    }

    .category-badge {
      background: #FAF4EB;
      border: 1.5px solid #E8DDD0;
      color: #5C4A3E;
      font-weight: 800;
      font-size: 0.78rem;
      padding: 6px 14px;
      border-radius: 20px;
      white-space: nowrap;
      display: inline-block;
      box-shadow: 0 2px 6px rgba(60, 42, 33, 0.04);
    }

    /* VIBRANT EMERALD GREEN PRICE BADGE WITH GLOW */
    .price-badge {
      font-weight: 800;
      font-size: 0.92rem;
      color: #059669;
      background: #ECFDF5;
      padding: 6px 14px;
      border-radius: 14px;
      border: 1.5px solid #A7F3D0;
      box-shadow: 0 3px 10px rgba(16, 185, 129, 0.14);
      display: inline-block;
      white-space: nowrap;
    }

    .btn-action-edit {
      padding: 6px 14px;
      background: #FAF4EB;
      border: 1.5px solid #E8DDD0;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 800;
      color: #C85A3E;
      cursor: pointer;
      margin-right: 4px;
      transition: all 0.2s ease;
    }

    .btn-action-edit:hover {
      background: #C85A3E;
      color: #FFFFFF;
      border-color: #C85A3E;
      transform: translateY(-1px);
    }

    .btn-action-delete {
      padding: 6px 14px;
      background: #FEE2E2;
      border: 1.5px solid #FCA5A5;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 800;
      color: #DC2626;
      text-decoration: none;
      display: inline-block;
      transition: all 0.2s ease;
    }

    .btn-action-delete:hover {
      background: #DC2626;
      color: #FFFFFF;
      border-color: #DC2626;
      transform: translateY(-1px);
    }

    /* EDIT MODAL OVERLAY STYLING */
    .admin-modal-overlay {
      display: none;
      position: fixed;
      z-index: 10000;
      left: 0;
      top: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(24, 15, 10, 0.72);
      backdrop-filter: blur(8px);
      justify-content: center;
      align-items: center;
      padding: 30px 20px;
      box-sizing: border-box;
    }

    .admin-modal-overlay.active {
      display: flex;
    }

    .admin-modal-box {
      background: #FFFFFF;
      border-radius: 24px;
      border: 1.5px solid #E5D9CC;
      box-shadow: 0 25px 70px rgba(44, 28, 20, 0.35);
      max-width: 560px;
      width: 100%;
      padding: 30px 32px;
      position: relative;
      max-height: 90vh;
      overflow-y: auto;
    }

    @media (max-width: 1024px) {
      .manage-menu-container {
        flex-direction: column;
      }
      .menu-form-col {
        flex: 1;
        width: 100%;
      }
    }
  </style>
</head>
<body class="admin-page">

<?php $adminActivePage = 'menu'; require_once '../includes/admin_header_nav.php'; ?>

<div class="admin-wrap">
  <div class="admin-page-header">
    <a href="dashboard.php" class="btn-back-dashboard">&larr; Back to Dashboard</a>
    <h1 class="admin-header-title">Manage Menu Items</h1>
    <p class="admin-header-subtitle">Add, edit, search, or delete items shown on the live Menu page.</p>
  </div>

  <?php if ($message): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
          showToast(<?php echo json_encode($message); ?>, <?php echo json_encode($messageType); ?>);
      });
    </script>
  <?php endif; ?>

  <div class="manage-menu-container">
    
    <!-- ============ LEFT SIDE: ADD NEW ITEM FORM PANEL ============ -->
    <div class="menu-form-col">
      <div class="form-card-header">
        <h2><span>✨</span> Add New Item</h2>
      </div>

      <div class="form-card-body">
        <form class="admin-form" id="addItemForm" method="POST" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="remove_image" id="add_remove_image" value="0">

          <!-- CATEGORY DROPDOWN -->
          <div class="form-row">
            <label for="add_category_id">Select Category *</label>
            <select name="category_id" id="add_category_id">
              <option value="">-- Select Category --</option>
              <?php foreach ($categoriesList as $cat): ?>
                <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_label']); ?></option>
              <?php endforeach; ?>
            </select>
            <div id="addCatError" class="field-error-msg">⚠️ Please select a category.</div>
          </div>

          <!-- ITEM NAME INPUT -->
          <div class="form-row">
            <label for="add_name">Item Name *</label>
            <input type="text" name="name" id="add_name" placeholder="e.g. Honeycomb Iced Matcha">
            <div id="addNameError" class="field-error-msg">⚠️ Please enter an item name.</div>
          </div>

          <!-- DESCRIPTION TEXTAREA -->
          <div class="form-row">
            <label for="add_description">Description (Optional)</label>
            <textarea name="description" id="add_description" rows="3" placeholder="Brief taste description &amp; size details..."></textarea>
          </div>

          <!-- PRICE INPUT -->
          <div class="form-row">
            <label for="add_price">Price (RM) *</label>
            <input type="text" name="price" id="add_price" placeholder="e.g. 15.90">
            <div id="addPriceError" class="field-error-msg">⚠️ Please enter a valid numeric price (e.g. 15.90).</div>
          </div>

          <!-- ITEM PICTURE UPLOAD WITH PLACEHOLDER SPACE & REMOVE ICON -->
          <div class="form-row">
            <label>Item Photo (Optional)</label>
            <div style="display: flex; align-items: center; gap: 14px; margin-top: 6px;">
              
              <!-- PHOTO PREVIEW / PLACEHOLDER BOX -->
              <div id="addPhotoPreviewBox" style="width: 86px; height: 86px; border-radius: 14px; border: 1.5px dashed #E5D9CC; background: #FAF7F2; display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: hidden; position: relative; box-sizing: border-box;">
                <img src="" id="addAvatarImg" alt="Item Photo" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                <div id="addPlaceholderText" style="text-align: center; color: #9E8C7D;">
                  <span style="font-size: 1.4rem; display: block; line-height: 1;">📷</span>
                  <span style="font-size: 0.68rem; font-weight: 700; margin-top: 4px; display: block;">No Photo</span>
                </div>
              </div>

              <!-- ACTION BUTTONS: CHOOSE & REMOVE PHOTO -->
              <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-start;">
                <label for="add_image" class="btn-upload-photo" style="cursor: pointer; padding: 8px 16px; background: #FAF4EB; border: 1.5px solid #E8DDD0; border-radius: 20px; font-size: 0.82rem; font-weight: 700; color: #4A3B32; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s ease;">
                  <span>📷</span> Choose Photo
                </label>
                <input type="file" id="add_image" name="image" accept="image/*" onchange="previewAddImage(this)" style="display: none;">
                
                <button type="button" id="addRemovePhotoBtn" onclick="removeAddPhoto()" style="display: none; align-items: center; gap: 4px; padding: 6px 12px; background: #FEE2E2; border: 1.5px solid #FCA5A5; border-radius: 20px; font-size: 0.78rem; font-weight: 700; color: #DC2626; cursor: pointer; transition: all 0.2s ease;">
                  <span>🗑️</span> Remove Photo
                </button>
              </div>

            </div>
          </div>

          <div class="form-actions" style="margin-top: 26px;">
            <button type="submit" class="admin-btn admin-btn-primary" style="width: 100%; padding: 13px; border-radius: 25px; font-size: 0.95rem; font-weight: 800; background: linear-gradient(135deg, #C85A3E 0%, #A8472F 100%); border: none; color: #fff; cursor: pointer; box-shadow: 0 6px 18px rgba(200,90,62,0.3); transition: all 0.2s ease;">
              ✨ Add Menu Item
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============ RIGHT SIDE: LIST TABLE & CONTROLS ============ -->
    <div class="menu-table-col">
      
      <div class="table-controls">
        <input type="text" id="searchInput" onkeyup="filterItems()" placeholder="🔍 Search menu item name...">
        <select id="categoryFilter" onchange="filterItems()">
          <option value="">All Categories</option>
          <?php foreach ($categoriesList as $cat): ?>
            <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_label']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="admin-table-wrap">
        <table class="admin-table" id="menuTable">
          <thead>
            <tr>
              <th style="width: 90px; text-align: center;">Photo</th>
              <th style="text-align: center;">Item Details</th>
              <th style="width: 160px; text-align: center;">Category</th>
              <th style="width: 120px; text-align: center;">Price</th>
              <th style="width: 160px; text-align: center;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $sql = "SELECT m.*, c.category_label 
                    FROM menu_items m 
                    JOIN categories c ON m.category_id = c.category_id 
                    ORDER BY c.display_order ASC, m.name ASC";
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0):
              while ($row = $res->fetch_assoc()):
                $img = !empty($row['image']) ? '../images/menu/' . $row['image'] : '../images/placeholder.jpg';
                $rowJson = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
            ?>
              <tr class="item-row" data-name="<?php echo strtolower(htmlspecialchars($row['name'])); ?>" data-category="<?php echo $row['category_id']; ?>">
                <td style="text-align: center; vertical-align: middle;">
                  <img src="<?php echo htmlspecialchars($img); ?>" alt="" style="width: 56px; height: 56px; border-radius: 12px; object-fit: cover; border: 1.5px solid #E8DDD0; margin: 0 auto; display: block; box-shadow: 0 4px 10px rgba(60,42,33,0.06);">
                </td>
                <td style="text-align: center; vertical-align: middle; padding: 14px 18px;">
                  <div style="font-weight: 800; color: #2C1C14; font-size: 0.98rem; font-family: var(--font-heading);"><?php echo htmlspecialchars($row['name']); ?></div>
                  <!-- 3-LINE DESCRIPTION CLAMP WITH ELLIPSIS -->
                  <div class="table-desc-clamp"><?php echo htmlspecialchars($row['description']); ?></div>
                </td>
                <td style="text-align: center; vertical-align: middle;">
                  <span class="category-badge">
                    <?php echo htmlspecialchars($row['category_label']); ?>
                  </span>
                </td>
                <td style="text-align: center; vertical-align: middle;">
                  <span class="price-badge">
                    RM <?php echo number_format($row['price'], 2); ?>
                  </span>
                </td>
                <td style="text-align: center; vertical-align: middle; white-space: nowrap;">
                  <button type="button" onclick="openEditModal(<?php echo $rowJson; ?>)" class="btn-action-edit">✏️ Edit</button>
                  <a href="manage_menu.php?delete=<?php echo $row['item_id']; ?>" onclick="return confirm('⚠️ Are you sure you want to delete this menu item?');" class="btn-action-delete">🗑️ Delete</a>
                </td>
              </tr>
            <?php 
              endwhile;
            else:
            ?>
              <tr>
                <td colspan="5" style="text-align: center; padding: 40px; color: #7A685A; font-weight: 600;">No menu items found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>

  </div>
</div>

<!-- ============ EDIT ITEM MODAL POPUP ============ -->
<div id="editMenuModal" class="admin-modal-overlay">
  <div class="admin-modal-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; padding-bottom: 14px; border-bottom: 1.5px solid #F4EDE4;">
      <h2 style="font-family: var(--font-heading); color: #2C1C14; margin: 0; font-size: 1.35rem; font-weight: 800; display: flex; align-items: center; gap: 8px;">
        <span>✏️</span> Edit Menu Item
      </h2>
      <button type="button" onclick="closeEditModal()" style="background: transparent; border: none; font-size: 1.6rem; color: #7A685A; cursor: pointer; line-height: 1;">&times;</button>
    </div>

    <form class="admin-form" id="editItemForm" method="POST" enctype="multipart/form-data" novalidate>
      <input type="hidden" name="item_id" id="edit_item_id" value="">
      <input type="hidden" name="existing_image" id="edit_existing_image" value="">
      <input type="hidden" name="remove_image" id="edit_remove_image" value="0">

      <!-- CATEGORY DROPDOWN -->
      <div class="form-row">
        <label for="edit_category_id">Select Category *</label>
        <select name="category_id" id="edit_category_id">
          <option value="">-- Select Category --</option>
          <?php foreach ($categoriesList as $cat): ?>
            <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_label']); ?></option>
          <?php endforeach; ?>
        </select>
        <div id="editCatError" class="field-error-msg">⚠️ Please select a category.</div>
      </div>

      <!-- ITEM NAME INPUT -->
      <div class="form-row">
        <label for="edit_name">Item Name *</label>
        <input type="text" name="name" id="edit_name" placeholder="e.g. Honeycomb Iced Matcha">
        <div id="editNameError" class="field-error-msg">⚠️ Please enter an item name.</div>
      </div>

      <!-- DESCRIPTION TEXTAREA -->
      <div class="form-row">
        <label for="edit_description">Description (Optional)</label>
        <textarea name="description" id="edit_description" rows="4" placeholder="Brief taste description &amp; size details..."></textarea>
      </div>

      <!-- PRICE INPUT -->
      <div class="form-row">
        <label for="edit_price">Price (RM) *</label>
        <input type="text" name="price" id="edit_price" placeholder="e.g. 15.90">
        <div id="editPriceError" class="field-error-msg">⚠️ Please enter a valid numeric price (e.g. 15.90).</div>
      </div>

      <!-- ITEM PICTURE UPLOAD -->
      <div class="form-row">
        <label>Item Photo (Optional)</label>
        <div style="display: flex; align-items: center; gap: 14px; margin-top: 6px;">
          <div id="editPhotoPreviewBox" style="width: 86px; height: 86px; border-radius: 14px; border: 1.5px dashed #E5D9CC; background: #FAF7F2; display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: hidden; position: relative; box-sizing: border-box;">
            <img src="" id="editAvatarImg" alt="Item Photo" style="width: 100%; height: 100%; object-fit: cover; display: none;">
            <div id="editPlaceholderText" style="text-align: center; color: #9E8C7D;">
              <span style="font-size: 1.4rem; display: block; line-height: 1;">📷</span>
              <span style="font-size: 0.68rem; font-weight: 700; margin-top: 4px; display: block;">No Photo</span>
            </div>
          </div>

          <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-start;">
            <label for="edit_image" class="btn-upload-photo" style="cursor: pointer; padding: 8px 16px; background: #FAF4EB; border: 1.5px solid #E8DDD0; border-radius: 20px; font-size: 0.82rem; font-weight: 700; color: #4A3B32; display: inline-flex; align-items: center; gap: 6px;">
              <span>📷</span> Choose New Photo
            </label>
            <input type="file" id="edit_image" name="image" accept="image/*" onchange="previewEditImage(this)" style="display: none;">
            
            <button type="button" id="editRemovePhotoBtn" onclick="removeEditPhoto()" style="display: none; align-items: center; gap: 4px; padding: 6px 12px; background: #FEE2E2; border: 1.5px solid #FCA5A5; border-radius: 20px; font-size: 0.78rem; font-weight: 700; color: #DC2626; cursor: pointer;">
              <span>🗑️</span> Remove Photo
            </button>
          </div>
        </div>
      </div>

      <div class="form-actions" style="margin-top: 26px; display: flex; gap: 12px;">
        <button type="submit" class="admin-btn admin-btn-primary" style="flex: 1; padding: 13px; border-radius: 25px; font-size: 0.95rem; font-weight: 800; background: linear-gradient(135deg, #C85A3E 0%, #A8472F 100%); border: none; color: #fff; cursor: pointer; box-shadow: 0 6px 18px rgba(200,90,62,0.3);">
          💾 Save Changes
        </button>
        <button type="button" onclick="closeEditModal()" style="padding: 13px 24px; border-radius: 25px; font-weight: 700; color: #7A685A; border: 1.5px solid #E8DDD0; background: #FAF7F2; cursor: pointer;">
          Cancel
        </button>
      </div>
    </form>
  </div>
</div>

<script>
/* ADD FORM PHOTO PREVIEW & REMOVE */
function previewAddImage(input) {
  const img = document.getElementById('addAvatarImg');
  const placeholder = document.getElementById('addPlaceholderText');
  const removeBtn = document.getElementById('addRemovePhotoBtn');
  const removeInput = document.getElementById('add_remove_image');

  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      if (img) {
        img.src = e.target.result;
        img.style.display = 'block';
      }
      if (placeholder) placeholder.style.display = 'none';
      if (removeBtn) removeBtn.style.display = 'inline-flex';
      if (removeInput) removeInput.value = '0';
    }
    reader.readAsDataURL(input.files[0]);
  }
}

function removeAddPhoto() {
  const fileInput = document.getElementById('add_image');
  const img = document.getElementById('addAvatarImg');
  const placeholder = document.getElementById('addPlaceholderText');
  const removeBtn = document.getElementById('addRemovePhotoBtn');
  const removeInput = document.getElementById('add_remove_image');

  if (fileInput) fileInput.value = '';
  if (img) {
    img.src = '';
    img.style.display = 'none';
  }
  if (placeholder) placeholder.style.display = 'block';
  if (removeBtn) removeBtn.style.display = 'none';
  if (removeInput) removeInput.value = '1';
}

/* EDIT MODAL PHOTO PREVIEW & REMOVE */
function previewEditImage(input) {
  const img = document.getElementById('editAvatarImg');
  const placeholder = document.getElementById('editPlaceholderText');
  const removeBtn = document.getElementById('editRemovePhotoBtn');
  const removeInput = document.getElementById('edit_remove_image');

  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      if (img) {
        img.src = e.target.result;
        img.style.display = 'block';
      }
      if (placeholder) placeholder.style.display = 'none';
      if (removeBtn) removeBtn.style.display = 'inline-flex';
      if (removeInput) removeInput.value = '0';
    }
    reader.readAsDataURL(input.files[0]);
  }
}

function removeEditPhoto() {
  const fileInput = document.getElementById('edit_image');
  const img = document.getElementById('editAvatarImg');
  const placeholder = document.getElementById('editPlaceholderText');
  const removeBtn = document.getElementById('editRemovePhotoBtn');
  const removeInput = document.getElementById('edit_remove_image');

  if (fileInput) fileInput.value = '';
  if (img) {
    img.src = '';
    img.style.display = 'none';
  }
  if (placeholder) placeholder.style.display = 'block';
  if (removeBtn) removeBtn.style.display = 'none';
  if (removeInput) removeInput.value = '1';
}

/* EDIT MODAL POPUP CONTROLS */
function openEditModal(item) {
  document.getElementById('edit_item_id').value = item.item_id;
  document.getElementById('edit_category_id').value = item.category_id;
  document.getElementById('edit_name').value = item.name;
  document.getElementById('edit_description').value = item.description || '';
  document.getElementById('edit_price').value = item.price;
  document.getElementById('edit_existing_image').value = item.image || '';
  document.getElementById('edit_remove_image').value = '0';

  const img = document.getElementById('editAvatarImg');
  const placeholder = document.getElementById('editPlaceholderText');
  const removeBtn = document.getElementById('editRemovePhotoBtn');

  if (item.image && item.image.trim() !== '') {
    img.src = '../images/menu/' + item.image;
    img.style.display = 'block';
    placeholder.style.display = 'none';
    removeBtn.style.display = 'inline-flex';
  } else {
    img.src = '';
    img.style.display = 'none';
    placeholder.style.display = 'block';
    removeBtn.style.display = 'none';
  }

  document.getElementById('editMenuModal').classList.add('active');
}

function closeEditModal() {
  document.getElementById('editMenuModal').classList.remove('active');
}

/* SEARCH & CATEGORY FILTER */
function filterItems() {
  const searchVal = document.getElementById('searchInput').value.toLowerCase().trim();
  const categoryVal = document.getElementById('categoryFilter').value;
  const rows = document.querySelectorAll('#menuTable .item-row');

  rows.forEach(row => {
    const itemName = row.getAttribute('data-name');
    const itemCategory = row.getAttribute('data-category');

    const matchesSearch = itemName.includes(searchVal);
    const matchesCategory = (categoryVal === '' || itemCategory === categoryVal);

    if (matchesSearch && matchesCategory) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}

/* VALIDATION BINDINGS FOR ADD & EDIT FORMS */
document.addEventListener('DOMContentLoaded', function() {
  setupValidation('addItemForm', 'add_category_id', 'add_name', 'add_price', 'addCatError', 'addNameError', 'addPriceError');
  setupValidation('editItemForm', 'edit_category_id', 'edit_name', 'edit_price', 'editCatError', 'editNameError', 'editPriceError');
});

function setupValidation(formId, catId, nameId, priceId, catErrId, nameErrId, priceErrId) {
  const form = document.getElementById(formId);
  if (!form) return;

  const categorySelect = document.getElementById(catId);
  const nameInput = document.getElementById(nameId);
  const priceInput = document.getElementById(priceId);

  const catError = document.getElementById(catErrId);
  const nameError = document.getElementById(nameErrId);
  const priceError = document.getElementById(priceErrId);

  categorySelect?.addEventListener('change', function() {
    if (this.value !== '') {
      catError.style.display = 'none';
      this.style.borderColor = '#E8DDD0';
    }
  });

  nameInput?.addEventListener('input', function() {
    if (this.value.trim() !== '') {
      nameError.style.display = 'none';
      this.style.borderColor = '#E8DDD0';
    }
  });

  priceInput?.addEventListener('input', function() {
    const val = this.value.trim();
    if (val !== '' && !isNaN(val) && parseFloat(val) > 0) {
      priceError.style.display = 'none';
      this.style.borderColor = '#E8DDD0';
    } else if (val !== '') {
      priceError.textContent = '⚠️ Please enter a valid numeric price (e.g. 15.90).';
      priceError.style.display = 'block';
      this.style.borderColor = '#DC2626';
    }
  });

  form.addEventListener('submit', function(e) {
    let isValid = true;

    if (!categorySelect || categorySelect.value === '') {
      catError.style.display = 'block';
      categorySelect.style.borderColor = '#DC2626';
      isValid = false;
    } else {
      catError.style.display = 'none';
      categorySelect.style.borderColor = '#E8DDD0';
    }

    if (!nameInput || nameInput.value.trim() === '') {
      nameError.style.display = 'block';
      nameInput.style.borderColor = '#DC2626';
      isValid = false;
    } else {
      nameError.style.display = 'none';
      nameInput.style.borderColor = '#E8DDD0';
    }

    const priceVal = priceInput ? priceInput.value.trim() : '';
    if (priceVal === '' || isNaN(priceVal) || parseFloat(priceVal) <= 0) {
      priceError.textContent = priceVal === '' ? '⚠️ Please enter a price.' : '⚠️ Please enter a valid numeric price (e.g. 15.90).';
      priceError.style.display = 'block';
      priceInput.style.borderColor = '#DC2626';
      isValid = false;
    } else {
      priceError.style.display = 'none';
      priceInput.style.borderColor = '#E8DDD0';
    }

    if (!isValid) {
      e.preventDefault();
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
</script>

<?php require_once '../includes/admin_footer.php'; ?>

</body>
</html>