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
    $message = 'Item deleted.';
    $messageType = 'success';
}

// ============ ADD or UPDATE ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemId      = (int) ($_POST['item_id'] ?? 0);
    $categoryId  = (int) $_POST['category_id'];
    $name        = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price       = (float) $_POST['price'];

    if ($name === '' || $price <= 0) {
        $message = 'Name and a valid price are required.';
        $messageType = 'error';
    } else {
        $imageFilename = $_POST['existing_image'] ?? '';

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

// ============ If editing, load that item ============
$editItem = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM menu_items WHERE item_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editItem = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$previewImage = ($editItem && !empty($editItem['image'])) 
    ? '../images/menu/' . htmlspecialchars($editItem['image']) 
    : '../images/placeholder.png';

// ============ Fetch all items ============
$items = $conn->query('
    SELECT m.*, c.category_label
    FROM menu_items m
    JOIN categories c ON m.category_id = c.category_id
    ORDER BY c.display_order, m.item_id DESC
');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/admin.css">
  <title>Cozy Coffee Co. — Manage Menu</title>
  <style>
    /* Expand top wrapper to utilize available width */
    .admin-wrap {
      max-width: 1400px !important;
      width: 92%;
      margin: 0 auto;
      padding: 30px 0;
    }

    /* Layout structure */
    .manage-menu-container {
      display: flex;
      gap: 32px;
      align-items: flex-start;
      margin-top: 24px;
      width: 100%;
    }

    /* Left Column (Form) */
    .menu-form-col {
      flex: 0 0 380px;
      background: #ffffff;
      padding: 24px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    }

    /* Right Column (Table Area) */
    .menu-table-col {
      flex: 1;
      min-width: 0; /* Prevents overflow issues */
    }

    /* Controls Header Bar */
    .table-controls {
      display: flex;
      gap: 16px;
      margin-bottom: 20px;
      width: 100%;
    }

    /* Match inputs with admin theme styles */
    .table-controls input,
    .table-controls select {
      font-family: inherit;
      font-size: 15px;
      padding: 10px 16px;
      background-color: #f7f4f0;
      border: 1px solid #e0d8cf;
      border-radius: 8px;
      color: #333;
      outline: none;
      transition: border-color 0.2s ease, background-color 0.2s ease;
    }

    .table-controls input:focus,
    .table-controls select:focus {
      border-color: #a37252;
      background-color: #ffffff;
    }

    .table-controls input {
      flex: 2;
    }

    .table-controls select {
      flex: 1;
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

<div class="admin-topbar">
  <div class="admin-logo">Cozy Coffee Co. — Admin</div>
  <div>
    <span class="admin-user">Logged in as <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
    <a href="logout.php" class="logout-link">Logout</a>
  </div>
</div>

<div class="admin-wrap">
  <a href="dashboard.php" class="admin-back">← Back to Dashboard</a>
  <h1>Manage Menu</h1>
  <p class="admin-subtitle">Add, edit, search, or delete items shown on the live Menu page.</p>

  <?php if ($message): ?>
    <div class="admin-alert admin-alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <div class="manage-menu-container">
    
    <!-- ============ LEFT SIDE: ADD / EDIT FORM ============ -->
    <div class="menu-form-col">
      <h2><?php echo $editItem ? 'Edit Item' : 'Add New Item'; ?></h2>
      <form class="admin-form" method="POST" enctype="multipart/form-data">
        <?php if ($editItem): ?>
          <input type="hidden" name="item_id" value="<?php echo $editItem['item_id']; ?>">
          <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($editItem['image']); ?>">
        <?php endif; ?>

        <div class="form-row">
          <label for="category_id">Category</label>
          <select name="category_id" id="category_id" required>
            <option value="">-- Select Category --</option>
            <?php foreach ($categoriesList as $cat): ?>
              <option value="<?php echo $cat['category_id']; ?>"
                <?php if ($editItem && $editItem['category_id'] == $cat['category_id']) echo 'selected'; ?>>
                <?php echo htmlspecialchars($cat['category_label']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-row">
          <label for="name">Item Name</label>
          <input type="text" name="name" id="name" required
            value="<?php echo $editItem ? htmlspecialchars($editItem['name']) : ''; ?>">
        </div>

        <div class="form-row">
          <label for="description">Description</label>
          <textarea name="description" id="description" rows="3"><?php echo $editItem ? htmlspecialchars($editItem['description']) : ''; ?></textarea>
        </div>

        <div class="form-row">
          <label for="price">Price (RM)</label>
          <input type="number" step="0.10" min="0" name="price" id="price" required
            value="<?php echo $editItem ? htmlspecialchars($editItem['price']) : ''; ?>">
        </div>

        <!-- ITEM PICTURE UPLOAD -->
        <div class="form-row">
          <label>Item Photo</label>
          <div class="avatar-section">
            <div class="avatar-preview">
              <img src="<?php echo $previewImage; ?>" id="avatarImg" alt="Item Picture" style="width: 90px; height: 90px; border-radius: 8px; object-fit: cover;">
            </div>
            <div class="avatar-upload" style="margin-top: 8px;">
              <label for="image" class="btn-upload" style="cursor: pointer; padding: 6px 12px; background: #eee; border-radius: 4px; display: inline-block;">📷 Choose Photo</label>
              <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)" style="display: none;">
            </div>
          </div>
        </div>

        <div class="form-actions" style="margin-top: 20px;">
          <button type="submit" class="admin-btn admin-btn-primary" style="width: 100%;">
            <?php echo $editItem ? 'Update Item' : 'Add Item'; ?>
          </button>
          <?php if ($editItem): ?>
            <a href="manage_menu.php" class="admin-btn admin-btn-outline" style="display: block; text-align: center; margin-top: 8px;">Cancel Edit</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- ============ RIGHT SIDE: LIST TABLE & CONTROLS ============ -->
    <div class="menu-table-col">
      
      <!-- Search and Filter Controls -->
      <div class="table-controls">
        <input type="text" id="searchInput" onkeyup="filterItems()" placeholder="Search item name...">
        <select id="categoryFilter" onchange="filterItems()">
          <option value="">All Categories</option>
          <?php foreach ($categoriesList as $cat): ?>
            <option value="<?php echo htmlspecialchars($cat['category_label']); ?>">
              <?php echo htmlspecialchars($cat['category_label']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Items Table -->
      <div class="admin-table-wrap">
        <table class="admin-table" id="menuTable">
          <thead>
            <tr>
              <th>Photo</th>
              <th>Name</th>
              <th>Category</th>
              <th>Price</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($item = $items->fetch_assoc()): ?>
              <tr class="item-row" 
                  data-name="<?php echo strtolower(htmlspecialchars($item['name'])); ?>" 
                  data-category="<?php echo htmlspecialchars($item['category_label']); ?>">
                <td>
                  <?php if (!empty($item['image'])): ?>
                    <?php
                      $imagePath = (preg_match('/^https?:\/\//i', $item['image']))
                          ? $item['image']
                          : '../images/menu/' . $item['image'];
                    ?>
                    <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="" style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px;">
                  <?php else: ?>
                    <span style="font-size: 12px; color: #888;">No Image</span>
                  <?php endif; ?>
                </td>
                <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                <td><span class="badge"><?php echo htmlspecialchars($item['category_label']); ?></span></td>
                <td>RM <?php echo number_format($item['price'], 2); ?></td>
                <td class="admin-actions">
                  <a href="manage_menu.php?edit=<?php echo $item['item_id']; ?>" class="admin-btn admin-btn-outline admin-btn-sm">Edit</a>
                  <a href="manage_menu.php?delete=<?php echo $item['item_id']; ?>"
                     class="admin-btn admin-btn-danger admin-btn-sm"
                     onclick="return confirm('Delete this item?');">Delete</a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

    </div>

  </div>
</div>

<script>
function previewImage(input) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('avatarImg').src = e.target.result;
    }
    reader.readAsDataURL(input.files[0]);
  }
}

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
</script>

</body>
</html>