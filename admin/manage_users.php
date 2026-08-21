<?php
require_once '../includes/admin_auth_check.php';
require_once '../includes/db_connect.php';

$message = '';
$messageType = '';

// ============ DELETE USER ACCOUNT ============
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $message = 'User account deleted successfully.';
        $messageType = 'success';
    } else {
        $message = 'Failed to delete user account.';
        $messageType = 'error';
    }
    $stmt->close();
}

// ============ UPDATE USER ACCOUNT (SERVER-SIDE VALIDATION) ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
    $id       = (int) $_POST['id'];
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $gender   = trim($_POST['gender'] ?? '');
    $birthday = !empty($_POST['birthday']) ? $_POST['birthday'] : null;

    // Validation checks
    if ($fullname === '' || $email === '' || $username === '') {
        $message = 'Full name, email, and username are required.';
        $messageType = 'error';
    } elseif (mb_strlen($fullname) < 2) {
        $message = 'Full name must be at least 2 characters.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } elseif (!preg_match('/^[a-zA-Z0-9_.-]{3,30}$/', $username)) {
        $message = 'Username must be 3-30 characters (letters, numbers, underscores, dots, or dashes).';
        $messageType = 'error';
    } elseif (!empty($phone) && !preg_match('/^[0-9+\s-]{7,20}$/', $phone)) {
        $message = 'Please enter a valid phone number (digits, spaces, dashes, or +).';
        $messageType = 'error';
    } elseif (!empty($birthday) && strtotime($birthday) > time()) {
        $message = 'Birthday cannot be a future date.';
        $messageType = 'error';
    } else {
        // Check for duplicate email or username for other users
        $dupStmt = $conn->prepare('SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?');
        $dupStmt->bind_param('ssi', $email, $username, $id);
        $dupStmt->execute();
        $dupRes = $dupStmt->get_result();
        if ($dupRes && $dupRes->num_rows > 0) {
            $message = 'That email address or username is already taken by another account.';
            $messageType = 'error';
            $dupStmt->close();
        } else {
            $dupStmt->close();
            $stmt = $conn->prepare('UPDATE users SET fullname=?, email=?, username=?, phone=?, gender=?, birthday=? WHERE id=?');
            $stmt->bind_param('ssssssi', $fullname, $email, $username, $phone, $gender, $birthday, $id);
            if ($stmt->execute()) {
                $message = 'User account updated successfully.';
                $messageType = 'success';
            } else {
                $message = 'Failed to update user account: ' . $conn->error;
                $messageType = 'error';
            }
            $stmt->close();
        }
    }
}

// ============ Fetch Edit User Data if requested ============
$editUser = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ============ Fetch All Users ============
$users = $conn->query('SELECT id, fullname, email, username, phone, gender, birthday, profile_pic, created_at FROM users ORDER BY created_at DESC');

// HELPER: RENDER USER AVATAR OR GENERATE INITIAL LETTER AVATAR
function renderUserAvatarHtml($pic, $name, $size = 42, $extraClass = '') {
    $firstLetter = strtoupper(mb_substr(trim($name ?: 'C'), 0, 1));
    
    $hasPic = false;
    $src = '';
    if (!empty($pic) && $pic !== 'default.png') {
        if (strpos($pic, 'uploads/') === 0 || strpos($pic, 'images/') === 0) {
            $path = '../' . ltrim($pic, '/');
            if (file_exists($path)) {
                $hasPic = true;
                $src = $path;
            }
        } else {
            $path = '../images/profiles/' . $pic;
            if (file_exists($path)) {
                $hasPic = true;
                $src = $path;
            }
        }
    }
    
    if ($hasPic) {
        return '<img src="' . htmlspecialchars($src) . '" class="user-avatar-img ' . $extraClass . '" style="width:' . $size . 'px; height:' . $size . 'px; border-radius:50%; object-fit:cover; border:1.5px solid #E8DDD0; flex-shrink:0;" alt="Avatar">';
    } else {
        return '<div class="initial-avatar-badge ' . $extraClass . '" style="width:' . $size . 'px; height:' . $size . 'px; border-radius:50%; background:linear-gradient(135deg, #C85A3E 0%, #A8472F 100%); color:#FFFFFF; font-weight:800; font-size:' . round($size * 0.44) . 'px; display:inline-flex; align-items:center; justify-content:center; border:1.5px solid #E8DDD0; flex-shrink:0; text-transform:uppercase; box-shadow:0 2px 8px rgba(60,42,33,0.08);">' . htmlspecialchars($firstLetter) . '</div>';
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
  <link rel="stylesheet" href="../style/programs.css">
  <title>Cozy Coffee Co. — Manage Users</title>
  <style>
    body.admin-page {
      background: #FAF6F0 !important;
      color: #2C1C14;
    }

    .admin-users-wrap {
      max-width: 1240px;
      margin: 20px auto 60px;
      padding: 0 4%;
      box-sizing: border-box;
    }

    /* FILTER BAR */
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

    .admin-search-input {
      padding: 9px 16px;
      border: 1.5px solid #E8DDD0;
      border-radius: 10px;
      font-size: 0.88rem;
      outline: none;
      background: #FAF7F2;
      color: #2C1C14;
      min-width: 320px;
      transition: all 0.2s ease;
    }

    .admin-search-input:focus {
      border-color: #C85A3E;
      background: #FFFFFF;
      box-shadow: 0 0 0 3px rgba(200, 90, 62, 0.12);
    }

    /* USER AVATAR & INFO */
    .user-avatar-cell {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .user-avatar-img {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      object-fit: cover;
      border: 1.5px solid #E8DDD0;
      background: #FAF4EB;
      box-shadow: 0 2px 8px rgba(60, 42, 33, 0.06);
      flex-shrink: 0;
    }

    /* ACTION BUTTONS */
    .btn-user-edit {
      padding: 6px 14px;
      background: #EFF6FF;
      color: #1D4ED8;
      border: 1.5px solid #BFDBFE;
      border-radius: 10px;
      font-weight: 800;
      font-size: 0.8rem;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
    .btn-user-edit:hover {
      background: #2563EB;
      color: #FFFFFF;
    }

    .btn-user-delete {
      padding: 6px 14px;
      background: #FEF2F2;
      color: #DC2626;
      border: 1.5px solid #FCA5A5;
      border-radius: 10px;
      font-weight: 800;
      font-size: 0.8rem;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
    .btn-user-delete:hover {
      background: #DC2626;
      color: #FFFFFF;
    }

    .field-error-msg {
      color: #DC2626;
      font-size: 0.78rem;
      font-weight: 700;
      margin-top: 4px;
      display: none;
    }
  </style>
</head>
<body class="admin-page">

<?php $adminActivePage = 'users'; require_once '../includes/admin_header_nav.php'; ?>

<div class="admin-users-wrap">

  <!-- PAGE HEADER -->
  <div class="admin-page-header">
    <a href="dashboard.php" class="btn-back-dashboard">&larr; Back to Dashboard</a>
    <div>
      <h1 class="admin-header-title">Manage User Accounts</h1>
      <p class="admin-header-subtitle">View, update details, or remove registered customer accounts.</p>
    </div>
  </div>

  <?php if (!empty($message)): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
          showToast(<?php echo json_encode($message); ?>, <?php echo json_encode($messageType); ?>);
      });
    </script>
  <?php endif; ?>

  <!-- FILTER & SEARCH BAR -->
  <div class="admin-filter-bar">
    <input type="text" id="userSearchInput" class="admin-search-input" onkeyup="filterUserTable()" placeholder="🔍 Search name, email, username, or phone...">

    <div style="font-size: 0.85rem; color: #8C7A6D; font-weight: 700;" id="filterUsersCount">
      Showing <?php echo $users ? $users->num_rows : 0; ?> registered user(s)
    </div>
  </div>

  <!-- USERS TABLE WRAPPER -->
  <div class="admin-table-wrap" style="background: #FFFFFF; border-radius: 20px; border: 1.5px solid #E8DDD0; padding: 20px; box-shadow: 0 6px 20px rgba(60,42,33,0.04);">
    <table class="admin-table" id="usersTable" style="width: 100%; border-collapse: collapse;">
      <thead>
        <tr>
          <th style="text-align: left; padding: 14px 16px;">Customer</th>
          <th style="text-align: left; padding: 14px 16px;">Contact Info</th>
          <th style="text-align: center; padding: 14px 16px;">Gender</th>
          <th style="text-align: center; padding: 14px 16px;">Birthday</th>
          <th style="text-align: center; padding: 14px 16px;">Joined Date</th>
          <th style="text-align: center; padding: 14px 16px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($users && $users->num_rows > 0): ?>
          <?php while ($u = $users->fetch_assoc()): ?>
            <?php
              $uId = (int)$u['id'];
              $displayName = !empty($u['fullname']) ? $u['fullname'] : $u['username'];
              $joinedDate = !empty($u['created_at']) ? date('M d, Y', strtotime($u['created_at'])) : 'N/A';
              $birthdayFormatted = !empty($u['birthday']) ? date('M d, Y', strtotime($u['birthday'])) : '—';
              $searchData = strtolower(htmlspecialchars($u['fullname'] . ' ' . $u['email'] . ' ' . $u['username'] . ' ' . ($u['phone'] ?? '')));
            ?>
            <tr id="user-row-<?php echo $uId; ?>" data-search="<?php echo $searchData; ?>">
              
              <!-- CUSTOMER AVATAR (PHOTO OR INITIAL LETTER) & NAMES -->
              <td style="padding: 16px; vertical-align: middle;">
                <div class="user-avatar-cell">
                  <?php echo renderUserAvatarHtml($u['profile_pic'], $displayName, 42); ?>
                  <div>
                    <div style="font-weight: 800; color: #2C1C14; font-size: 0.92rem;">
                      <?php echo htmlspecialchars($u['fullname']); ?>
                    </div>
                    <div style="font-size: 0.78rem; color: #8A7769; font-weight: 600; margin-top: 1px;">
                      @<?php echo htmlspecialchars($u['username']); ?>
                    </div>
                  </div>
                </div>
              </td>

              <!-- CONTACT INFO (EMAIL & PHONE) -->
              <td style="padding: 16px; vertical-align: middle;">
                <div style="font-size: 0.88rem; color: #2C1C14; font-weight: 600;">
                  ✉️ <?php echo htmlspecialchars($u['email']); ?>
                </div>
                <?php if (!empty($u['phone'])): ?>
                  <div style="font-size: 0.78rem; color: #7A685A; margin-top: 2px;">
                    📞 <?php echo htmlspecialchars($u['phone']); ?>
                  </div>
                <?php endif; ?>
              </td>

              <!-- GENDER -->
              <td style="padding: 16px; vertical-align: middle; text-align: center;">
                <?php if (!empty($u['gender'])): ?>
                  <span style="background: #FAF4EB; color: #8C5A3E; border: 1px solid #E8DDD0; font-size: 0.78rem; font-weight: 800; padding: 3px 10px; border-radius: 14px; display: inline-block;">
                    <?php echo htmlspecialchars($u['gender']); ?>
                  </span>
                <?php else: ?>
                  <span style="color: #A09080; font-size: 0.82rem;">—</span>
                <?php endif; ?>
              </td>

              <!-- BIRTHDAY -->
              <td style="padding: 16px; vertical-align: middle; text-align: center; white-space: nowrap;">
                <span style="font-size: 0.84rem; color: #5A4A3E; font-weight: 600;">
                  🎂 <?php echo $birthdayFormatted; ?>
                </span>
              </td>

              <!-- JOINED DATE -->
              <td style="padding: 16px; vertical-align: middle; text-align: center; white-space: nowrap;">
                <span style="font-size: 0.84rem; color: #7A685A; font-weight: 600;">
                  📅 <?php echo $joinedDate; ?>
                </span>
              </td>

              <!-- ACTIONS -->
              <td style="padding: 16px; vertical-align: middle; text-align: center; white-space: nowrap;">
                <div style="display: flex; gap: 6px; justify-content: center; align-items: center;">
                  <button type="button" class="btn-user-edit" onclick="openEditUserModal(<?php echo $uId; ?>, '<?php echo htmlspecialchars(addslashes($u['fullname'])); ?>', '<?php echo htmlspecialchars(addslashes($u['email'])); ?>', '<?php echo htmlspecialchars(addslashes($u['username'])); ?>', '<?php echo htmlspecialchars(addslashes($u['phone'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($u['gender'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($u['birthday'] ?? '')); ?>')">
                    Edit
                  </button>

                  <a href="manage_users.php?delete=<?php echo $uId; ?>" onclick="saveScrollPosition(); return confirm('⚠️ Permanently delete this customer account? This cannot be undone.');" class="btn-user-delete">
                    Delete
                  </a>
                </div>
              </td>

            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" style="text-align: center; padding: 40px; color: #7A685A; font-weight: 600;">
              No registered user accounts found.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<!-- EDIT USER POPUP MODAL WITH INLINE FIELD VALIDATION MESSAGES -->
<div id="editUserModal" class="program-modal-overlay" onclick="closeModalOnOutsideClick(event, 'editUserModal')">
  <div class="program-modal-card" style="max-width: 500px; padding: 28px;">
    <button type="button" class="program-modal-close" onclick="closeModal('editUserModal')">&times;</button>
    <h3 style="font-family: var(--font-heading); color: #2C1C14; margin-top: 0; margin-bottom: 18px; font-size: 1.25rem; font-weight: 800;">Edit User Account</h3>
    
    <form action="manage_users.php" method="POST" onsubmit="return validateEditUserForm(event)">
      <input type="hidden" name="action" value="update_user">
      <input type="hidden" name="id" id="editUserId">

      <!-- FULL NAME FIELD -->
      <div class="form-group" style="margin-bottom: 14px;">
        <label style="font-weight: 800; color: #2C1C14; display: block; margin-bottom: 4px;">Full Name <span style="color: #DC2626;">*</span></label>
        <input type="text" name="fullname" id="editFullname" oninput="validateSingleField('editFullname')" style="width: 100%; padding: 10px 14px; border: 1.5px solid #E8DDD0; border-radius: 12px; font-size: 0.9rem; transition: all 0.2s ease;">
        <div id="editFullnameError" class="field-error-msg">⚠️ Full name is required and cannot be left empty.</div>
      </div>

      <!-- EMAIL ADDRESS FIELD -->
      <div class="form-group" style="margin-bottom: 14px;">
        <label style="font-weight: 800; color: #2C1C14; display: block; margin-bottom: 4px;">Email Address <span style="color: #DC2626;">*</span></label>
        <input type="email" name="email" id="editEmail" oninput="validateSingleField('editEmail')" style="width: 100%; padding: 10px 14px; border: 1.5px solid #E8DDD0; border-radius: 12px; font-size: 0.9rem; transition: all 0.2s ease;">
        <div id="editEmailError" class="field-error-msg">⚠️ Email address is required and cannot be left empty.</div>
      </div>

      <!-- USERNAME FIELD -->
      <div class="form-group" style="margin-bottom: 14px;">
        <label style="font-weight: 800; color: #2C1C14; display: block; margin-bottom: 4px;">Username <span style="color: #DC2626;">*</span></label>
        <input type="text" name="username" id="editUsername" oninput="validateSingleField('editUsername')" style="width: 100%; padding: 10px 14px; border: 1.5px solid #E8DDD0; border-radius: 12px; font-size: 0.9rem; transition: all 0.2s ease;">
        <div id="editUsernameError" class="field-error-msg">⚠️ Username is required and cannot be left empty.</div>
      </div>

      <!-- PHONE NUMBER FIELD -->
      <div class="form-group" style="margin-bottom: 14px;">
        <label style="font-weight: 800; color: #2C1C14; display: block; margin-bottom: 4px;">Phone Number</label>
        <input type="text" name="phone" id="editPhone" placeholder="e.g. 012-3456789" oninput="validateSingleField('editPhone')" style="width: 100%; padding: 10px 14px; border: 1.5px solid #E8DDD0; border-radius: 12px; font-size: 0.9rem; transition: all 0.2s ease;">
        <div id="editPhoneError" class="field-error-msg">⚠️ Please enter a valid phone number format.</div>
      </div>

      <!-- GENDER & BIRTHDAY FIELDS -->
      <div style="display: flex; gap: 12px; margin-bottom: 18px;">
        <div class="form-group" style="flex: 1; margin: 0;">
          <label style="font-weight: 800; color: #2C1C14; display: block; margin-bottom: 4px;">Gender</label>
          <select name="gender" id="editGender" style="width: 100%; padding: 10px 14px; border: 1.5px solid #E8DDD0; border-radius: 12px; font-size: 0.9rem; background: #FFF;">
            <option value="">— Select —</option>
            <option value="Female">Female</option>
            <option value="Male">Male</option>
            <option value="Other">Other</option>
          </select>
        </div>

        <div class="form-group" style="flex: 1; margin: 0;">
          <label style="font-weight: 800; color: #2C1C14; display: block; margin-bottom: 4px;">Birthday</label>
          <input type="date" name="birthday" id="editBirthday" onchange="validateSingleField('editBirthday')" style="width: 100%; padding: 10px 14px; border: 1.5px solid #E8DDD0; border-radius: 12px; font-size: 0.9rem; transition: all 0.2s ease;">
          <div id="editBirthdayError" class="field-error-msg">⚠️ Birthday cannot be a future date.</div>
        </div>
      </div>

      <div style="font-size: 0.78rem; color: #8A7769; margin-bottom: 18px;">
        💡 Password and profile avatar are managed directly by the customer.
      </div>

      <button type="submit" class="cta-btn cta-btn-primary" style="width: 100%; justify-content: center; height: 44px; border-radius: 25px;">Update Account Details ✨</button>
    </form>
  </div>
</div>

<script>
// Scroll Position Restoration
document.addEventListener('DOMContentLoaded', function() {
    const savedScroll = sessionStorage.getItem('admin_users_scroll');
    if (savedScroll) {
        window.scrollTo({ top: parseInt(savedScroll), behavior: 'instant' });
        sessionStorage.removeItem('admin_users_scroll');
    }
    
    // Auto-open modal if ?edit=ID is in URL
    <?php if ($editUser): ?>
      openEditUserModal(
        <?php echo (int)$editUser['id']; ?>,
        <?php echo json_encode($editUser['fullname']); ?>,
        <?php echo json_encode($editUser['email']); ?>,
        <?php echo json_encode($editUser['username']); ?>,
        <?php echo json_encode($editUser['phone'] ?? ''); ?>,
        <?php echo json_encode($editUser['gender'] ?? ''); ?>,
        <?php echo json_encode($editUser['birthday'] ?? ''); ?>
      );
    <?php endif; ?>
});

function saveScrollPosition() {
    sessionStorage.setItem('admin_users_scroll', window.scrollY);
}

function openEditUserModal(id, fullname, email, username, phone, gender, birthday) {
    document.getElementById('editUserId').value = id;
    document.getElementById('editFullname').value = fullname;
    document.getElementById('editEmail').value = email;
    document.getElementById('editUsername').value = username;
    document.getElementById('editPhone').value = phone || '';
    document.getElementById('editGender').value = gender || '';
    document.getElementById('editBirthday').value = birthday || '';

    // Reset validation field highlights & error messages
    clearFieldError('editFullname');
    clearFieldError('editEmail');
    clearFieldError('editUsername');
    clearFieldError('editPhone');
    clearFieldError('editBirthday');

    openModal('editUserModal');
}

// REALTIME SINGLE FIELD VALIDATION
function validateSingleField(fieldId) {
    const input = document.getElementById(fieldId);
    if (!input) return true;
    const val = input.value.trim();

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const usernameRegex = /^[a-zA-Z0-9_.-]{3,30}$/;
    const phoneRegex = /^[0-9+\s-]{7,20}$/;

    if (fieldId === 'editFullname') {
        if (!val) {
            setFieldError('editFullname', 'Full name is required and cannot be left empty.');
            return false;
        } else if (val.length < 2) {
            setFieldError('editFullname', 'Full name must be at least 2 characters.');
            return false;
        } else {
            setFieldSuccess('editFullname');
            return true;
        }
    }

    if (fieldId === 'editEmail') {
        if (!val) {
            setFieldError('editEmail', 'Email address is required and cannot be left empty.');
            return false;
        } else if (!emailRegex.test(val)) {
            setFieldError('editEmail', 'Please enter a valid email address (e.g. name@domain.com).');
            return false;
        } else {
            setFieldSuccess('editEmail');
            return true;
        }
    }

    if (fieldId === 'editUsername') {
        if (!val) {
            setFieldError('editUsername', 'Username is required and cannot be left empty.');
            return false;
        } else if (!usernameRegex.test(val)) {
            setFieldError('editUsername', 'Username must be 3-30 valid characters (letters, numbers, _, ., -).');
            return false;
        } else {
            setFieldSuccess('editUsername');
            return true;
        }
    }

    if (fieldId === 'editPhone') {
        if (val && !phoneRegex.test(val)) {
            setFieldError('editPhone', 'Please enter a valid phone number (digits, spaces, or dashes).');
            return false;
        } else {
            if (val) setFieldSuccess('editPhone'); else clearFieldError('editPhone');
            return true;
        }
    }

    if (fieldId === 'editBirthday') {
        if (val) {
            const bDate = new Date(val);
            const today = new Date();
            today.setHours(23, 59, 59, 999);
            if (bDate > today) {
                setFieldError('editBirthday', 'Birthday cannot be set to a future date.');
                return false;
            } else {
                setFieldSuccess('editBirthday');
                return true;
            }
        } else {
            clearFieldError('editBirthday');
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

function clearFieldError(fieldId) {
    const input = document.getElementById(fieldId);
    const errDiv = document.getElementById(fieldId + 'Error');
    if (input) {
        input.style.borderColor = '#E8DDD0';
        input.style.backgroundColor = '#FFFFFF';
    }
    if (errDiv) {
        errDiv.style.display = 'none';
    }
}

// CLIENT-SIDE VALIDATION FOR EDIT USER FORM ON SUBMIT
function validateEditUserForm(e) {
    const isFullnameValid = validateSingleField('editFullname');
    const isEmailValid = validateSingleField('editEmail');
    const isUsernameValid = validateSingleField('editUsername');
    const isPhoneValid = validateSingleField('editPhone');
    const isBirthdayValid = validateSingleField('editBirthday');

    if (!isFullnameValid || !isEmailValid || !isUsernameValid || !isPhoneValid || !isBirthdayValid) {
        if (e) e.preventDefault();
        showToast('⚠️ Please correct the errors in the form before saving.', 'error');
        return false;
    }

    saveScrollPosition();
    return true;
}

// REALTIME SEARCH FILTERING
function filterUserTable() {
  const searchVal = document.getElementById('userSearchInput').value.toLowerCase().trim();
  const rows = document.querySelectorAll('#usersTable tbody tr');
  let count = 0;

  rows.forEach(row => {
    if (row.children.length === 1) return;
    const searchData = row.getAttribute('data-search') || '';
    if (!searchVal || searchData.includes(searchVal)) {
      row.style.display = '';
      count++;
    } else {
      row.style.display = 'none';
    }
  });

  const countEl = document.getElementById('filterUsersCount');
  if (countEl) {
      countEl.textContent = `Showing ${count} registered user(s)`;
  }
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('active');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('active');
}

function closeModalOnOutsideClick(e, modalId) {
    if (e.target.id === modalId) {
        closeModal(modalId);
    }
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