<?php
require_once '../includes/admin_auth_check.php';
require_once '../includes/db_connect.php';

$message = '';
$messageType = '';

// ============ DELETE ============
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    $message = 'User account deleted.';
    $messageType = 'success';
}

// ============ UPDATE ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = (int) $_POST['id'];
    $fullname = trim($_POST['fullname']);
    $email    = trim($_POST['email']);
    $username = trim($_POST['username']);
    $phone    = trim($_POST['phone']);
    $gender   = trim($_POST['gender']);
    $birthday = $_POST['birthday'] !== '' ? $_POST['birthday'] : null;

    if ($fullname === '' || $email === '' || $username === '') {
        $message = 'Full name, email, and username are required.';
        $messageType = 'error';
    } else {
        $stmt = $conn->prepare('UPDATE users SET fullname=?, email=?, username=?, phone=?, gender=?, birthday=? WHERE id=?');
        $stmt->bind_param('ssssssi', $fullname, $email, $username, $phone, $gender, $birthday, $id);
        $stmt->execute();
        $stmt->close();
        $message = 'User updated.';
        $messageType = 'success';
    }
}

// ============ If editing, load that user's current data ============
$editUser = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ============ Fetch all users for the table ============
$users = $conn->query('SELECT id, fullname, email, username, phone, gender, profile_pic, created_at FROM users ORDER BY created_at DESC');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/admin.css">
  <title>Cozy Coffee Co. — Manage Users</title>
  <style>
    /* Expand wrapper width */
    .admin-wrap {
      max-width: 1400px !important;
      width: 92%;
      margin: 0 auto;
      padding: 30px 0;
    }

    /* Search Control Bar */
    .table-controls {
      display: flex;
      gap: 16px;
      margin-bottom: 20px;
      width: 100%;
    }

    .table-controls input {
      font-family: inherit;
      font-size: 15px;
      padding: 10px 16px;
      background-color: #f7f4f0;
      border: 1px solid #e0d8cf;
      border-radius: 8px;
      color: #333;
      outline: none;
      width: 100%;
      max-width: 400px;
      transition: border-color 0.2s ease, background-color 0.2s ease;
    }

    .table-controls input:focus {
      border-color: #a37252;
      background-color: #ffffff;
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
  <h1>Manage Users</h1>
  <p class="admin-subtitle">View, edit, or remove registered customer accounts.</p>

  <?php if ($message): ?>
    <div class="admin-alert admin-alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <!-- ============ EDIT FORM (only shows when editing) ============ -->
  <?php if ($editUser): ?>
    <form class="admin-form" method="POST" style="margin-bottom: 36px;">
      <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">

      <div class="form-row">
        <label for="fullname">Full Name</label>
        <input type="text" name="fullname" id="fullname" required
          value="<?php echo htmlspecialchars($editUser['fullname']); ?>">
      </div>

      <div class="form-row">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" required
          value="<?php echo htmlspecialchars($editUser['email']); ?>">
      </div>

      <div class="form-row">
        <label for="username">Username</label>
        <input type="text" name="username" id="username" required
          value="<?php echo htmlspecialchars($editUser['username']); ?>">
      </div>

      <div class="form-row">
        <label for="phone">Phone</label>
        <input type="text" name="phone" id="phone"
          value="<?php echo htmlspecialchars($editUser['phone'] ?? ''); ?>">
      </div>

      <div class="form-row">
        <label for="gender">Gender</label>
        <select name="gender" id="gender">
          <option value="" <?php if (!$editUser['gender']) echo 'selected'; ?>>—</option>
          <option value="Female" <?php if ($editUser['gender'] === 'Female') echo 'selected'; ?>>Female</option>
          <option value="Male" <?php if ($editUser['gender'] === 'Male') echo 'selected'; ?>>Male</option>
          <option value="Other" <?php if ($editUser['gender'] === 'Other') echo 'selected'; ?>>Other</option>
        </select>
      </div>

      <div class="form-row">
        <label for="birthday">Birthday</label>
        <input type="date" name="birthday" id="birthday"
          value="<?php echo htmlspecialchars($editUser['birthday'] ?? ''); ?>">
      </div>

      <p style="font-size:12px; color:var(--color-text-soft); margin-top:-8px; margin-bottom:16px;">
        Note: password and profile picture can't be changed here — the user manages those themselves.
      </p>

      <div class="form-actions">
        <button type="submit" class="admin-btn admin-btn-primary">Update User</button>
        <a href="manage_users.php" class="admin-btn admin-btn-outline">Cancel</a>
      </div>
    </form>
  <?php endif; ?>

  <!-- Search Control Bar -->
  <div class="table-controls">
    <input type="text" id="searchInput" onkeyup="filterUsers()" placeholder="Search name, email, or username...">
  </div>

  <!-- ============ USERS TABLE ============ -->
  <div class="admin-table-wrap">
    <table class="admin-table" id="usersTable">
      <thead>
        <tr>
          <th>Photo</th>
          <th>Full Name</th>
          <th>Email</th>
          <th>Username</th>
          <th>Phone</th>
          <th>Gender</th>
          <th>Joined</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($users->num_rows === 0): ?>
          <tr><td colspan="8">No registered users yet.</td></tr>
        <?php endif; ?>
        <?php while ($u = $users->fetch_assoc()): ?>
          <tr class="user-row" data-search="<?php echo strtolower(htmlspecialchars($u['fullname'] . ' ' . $u['email'] . ' ' . $u['username'])); ?>">
            <td><img src="../images/profiles/<?php echo htmlspecialchars($u['profile_pic'] ?: 'default.png'); ?>" alt="" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;"></td>
            <td><strong><?php echo htmlspecialchars($u['fullname']); ?></strong></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td><?php echo htmlspecialchars($u['username']); ?></td>
            <td><?php echo htmlspecialchars($u['phone'] ?? '—'); ?></td>
            <td><?php echo htmlspecialchars($u['gender'] ?? '—'); ?></td>
            <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
            <td class="admin-actions">
              <a href="manage_users.php?edit=<?php echo $u['id']; ?>" class="admin-btn admin-btn-outline admin-btn-sm">Edit</a>
              <a href="manage_users.php?delete=<?php echo $u['id']; ?>"
                 class="admin-btn admin-btn-danger admin-btn-sm"
                 onclick="return confirm('Delete this account? This cannot be undone.');">Delete</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

</div>

<script>
function filterUsers() {
  const searchVal = document.getElementById('searchInput').value.toLowerCase().trim();
  const rows = document.querySelectorAll('#usersTable .user-row');

  rows.forEach(row => {
    const searchData = row.getAttribute('data-search');
    if (searchData.includes(searchVal)) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}
</script>

</body>
</html>