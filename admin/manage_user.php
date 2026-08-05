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
    $image       = trim($_POST['image']);

    if ($name === '' || $price <= 0) {
        $message = 'Name and a valid price are required.';
        $messageType = 'error';
    } elseif ($itemId > 0) {
        // UPDATE existing item
        $stmt = $conn->prepare('UPDATE menu_items SET category_id=?, name=?, description=?, price=?, image=? WHERE item_id=?');
        $stmt->bind_param('issdsi', $categoryId, $name, $description, $price, $image, $itemId);
        $stmt->execute();
        $stmt->close();
        $message = 'Item updated.';
        $messageType = 'success';
    } else {
        // INSERT new item
        $stmt = $conn->prepare('INSERT INTO menu_items (category_id, name, description, price, image) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('issds', $categoryId, $name, $description, $price, $image);
        $stmt->execute();
        $stmt->close();
        $message = 'Item added.';
        $messageType = 'success';
    }
}

// ============ Fetch categories for the dropdown ============
$categories = $conn->query('SELECT category_id, category_label FROM categories ORDER BY display_order');

// ============ If editing, load that item's current data ============
$editItem = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM menu_items WHERE item_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editItem = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ============ Fetch all items (with category label) for the table ============
$items = $conn->query('
    SELECT m.*, c.category_label
    FROM menu_items m
    JOIN categories c ON m.category_id = c.category_id
    ORDER BY c.display_order, m.display_order
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
  <p class="admin-subtitle">Add, edit, or delete items shown on the live Menu page.</p>

  <?php if ($message): ?>
    <div class="admin-alert admin-alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <!-- ============ ADD / EDIT FORM ============ -->
  <form class="admin-form" method="POST" style="margin-bottom: 36px;">
    <?php if ($editItem): ?>
      <input type="hidden" name="item_id" value="<?php echo $editItem['item_id']; ?>">
    <?php endif; ?>

    <div class="form-row">
      <label for="category_id">Category</label>
      <select name="category_id" id="category_id" required>
        <?php while ($cat = $categories->fetch_assoc()): ?>
          <option value="<?php echo $cat['category_id']; ?>"
            <?php if ($editItem && $editItem['category_id'] == $cat['category_id']) echo 'selected'; ?>>
            <?php echo htmlspecialchars($cat['category_label']); ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="form-row">
      <label for="name">Item Name</label>
      <input type="text" name="name" id="name" required
        value="<?php echo $editItem ? htmlspecialchars($editItem['name']) : ''; ?>">
    </div>

    <div class="form-row">
      <label for="description">Description</label>
      <textarea name="description" id="description"><?php echo $editItem ? htmlspecialchars($editItem['description']) : ''; ?></textarea>
    </div>

    <div class="form-row">
      <label for="price">Price (RM)</label>
      <input type="number" step="0.10" min="0" name="price" id="price" required
        value="<?php echo $editItem ? htmlspecialchars($editItem['price']) : ''; ?>">
    </div>

    <div class="form-row">
      <label for="image">Image filename (inside images/menu/)</label>
      <input type="text" name="image" id="image" placeholder="e.g. 35.png"
        value="<?php echo $editItem ? htmlspecialchars($editItem['image']) : ''; ?>">
    </div>

    <div class="form-actions">
      <button type="submit" class="admin-btn admin-btn-primary">
        <?php echo $editItem ? 'Update Item' : 'Add Item'; ?>
      </button>
      <?php if ($editItem): ?>
        <a href="manage_menu.php" class="admin-btn admin-btn-outline">Cancel</a>
      <?php endif; ?>
    </div>
  </form>

  <!-- ============ ITEMS TABLE ============ -->
  <div class="admin-table-wrap">
    <table class="admin-table">
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
          <tr>
            <td><img src="../images/menu/<?php echo htmlspecialchars($item['image']); ?>" alt=""></td>
            <td><?php echo htmlspecialchars($item['name']); ?></td>
            <td><?php echo htmlspecialchars($item['category_label']); ?></td>
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

</body>
</html>