<?php
// Shared Standardized Header Navigation Component
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPath = $_SERVER['PHP_SELF'];
$basePrefix = (strpos($currentPath, '/home/') !== false || 
               strpos($currentPath, '/menu/') !== false || 
               strpos($currentPath, '/blog/') !== false || 
               strpos($currentPath, '/benefits/') !== false || 
               strpos($currentPath, '/offers/') !== false || 
               strpos($currentPath, '/activities/') !== false || 
               strpos($currentPath, '/contact/') !== false || 
               strpos($currentPath, '/cart/') !== false || 
               strpos($currentPath, '/checkout/') !== false || 
               strpos($currentPath, '/details/') !== false || 
               strpos($currentPath, '/profile/') !== false || 
               strpos($currentPath, '/rewards/') !== false || 
               strpos($currentPath, '/login/') !== false || 
               strpos($currentPath, '/register/') !== false) ? '../' : '';

$activePage = $activePage ?? '';
$cartCount = !empty($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<nav class="user-main-nav">
  <div class="logo"><a href="<?php echo $basePrefix; ?>home/index.php">Cozy Coffee Co.</a></div>

  <!-- CENTER DESKTOP NAV LINKS (Directly accessible on desktop) -->
  <ul class="nav-links desktop-nav-links">
    <li><a href="<?php echo $basePrefix; ?>home/index.php" class="<?php echo $activePage === 'home' ? 'active' : ''; ?>">Home</a></li>
    <li>
      <a href="<?php echo $basePrefix; ?>menu/index.php" class="<?php echo $activePage === 'menu' ? 'active' : ''; ?>">Menu ▾</a>
      <div class="dropdown">
        <a href="<?php echo $basePrefix; ?>menu/index.php?cat=specialty#specialty">🌟 Specialty</a>
        <a href="<?php echo $basePrefix; ?>menu/index.php?cat=classic#classic">☕ Classic Coffee</a>
        <a href="<?php echo $basePrefix; ?>menu/index.php?cat=noncoffein#noncoffein">🍃 Non-Coffein</a>
        <a href="<?php echo $basePrefix; ?>menu/index.php?cat=smoothies#smoothies">🍹 Smoothies &amp; Sodas</a>
        <a href="<?php echo $basePrefix; ?>menu/index.php?cat=mains#mains">🍽️ Main Dishes</a>
        <a href="<?php echo $basePrefix; ?>menu/index.php?cat=desserts#desserts">🍰 Desserts</a>
      </div>
    </li>
    <li><a href="<?php echo $basePrefix; ?>blog/index.php" class="<?php echo $activePage === 'blog' ? 'active' : ''; ?>">Blog</a></li>
    <li><a href="<?php echo $basePrefix; ?>benefits/index.php" class="<?php echo $activePage === 'benefits' ? 'active' : ''; ?>">Benefits</a></li>
    <li>
      <a href="<?php echo $basePrefix; ?>offers/index.php" class="<?php echo $activePage === 'offers' ? 'active' : ''; ?>">Offers ▾</a>
      <div class="dropdown">
        <a href="<?php echo $basePrefix; ?>offers/index.php#drinks">Drink Offers</a>
        <a href="<?php echo $basePrefix; ?>offers/index.php#food">Food Offers</a>
        <a href="<?php echo $basePrefix; ?>offers/index.php#partners">Partner Promotions</a>
      </div>
    </li>
    <li>
      <a href="<?php echo $basePrefix; ?>activities/index.php" class="<?php echo $activePage === 'activities' ? 'active' : ''; ?>">Activities ▾</a>
      <div class="dropdown">
        <a href="<?php echo $basePrefix; ?>activities/index.php#workshops">Coffee Workshops</a>
        <a href="<?php echo $basePrefix; ?>activities/index.php#giveback">Cozy Give-Back</a>
      </div>
    </li>
    <li><a href="<?php echo $basePrefix; ?>contact/index.php" class="<?php echo $activePage === 'contact' ? 'active' : ''; ?>">Contact</a></li>
  </ul>

  <!-- RIGHT UTILITIES (ALWAYS VISIBLE AT ALL SCREEN SIZES) -->
  <div class="nav-right-actions">

    <!-- NOTIFICATION BELL ENTRANCE (ALWAYS ON TOP BAR) -->
    <div class="notif-container">
      <button type="button" class="notif-bell-btn" id="globalNotifBtn" title="Notifications">
        🔔 <span class="notif-badge-count" id="globalNotifCount">0</span>
      </button>
      <div class="notif-dropdown" id="globalNotifDropdown">
        <div style="font-weight: 700; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-bottom: 8px; display:flex; justify-content:space-between; align-items:center;">
          <span>🔔 Notifications</span>
          <div style="display:flex; gap: 8px; align-items:center;">
            <button type="button" id="markReadBtn" style="background:none; border:none; color: var(--color-accent-dark); font-size:0.75rem; cursor:pointer; font-weight:700;">Mark as read ✓</button>
            <span style="font-size:0.75rem; color:#888; cursor:pointer;" onclick="closeNotifDropdown()">&times;</span>
          </div>
        </div>
        <div id="globalNotifList">
          <div style="text-align:center; padding:10px; color:#888; font-size:0.85rem;">Loading notifications...</div>
        </div>
      </div>
    </div>

    <!-- CART ENTRANCE (ALWAYS ON TOP BAR) -->
    <a href="<?php echo $basePrefix; ?>cart/index.php" class="nav-cart-link <?php echo $activePage === 'cart' ? 'active' : ''; ?>">
      🛒 Cart <span class="cart-badge nav-cart-badge" style="<?php echo $cartCount > 0 ? '' : 'display:none;'; ?>"><?php echo $cartCount; ?></span>
    </a>

    <!-- USER AUTH / PROFILE (ALWAYS ON TOP BAR) -->
    <?php if (isset($_SESSION['user_id'])): ?>
      <div class="user-profile-menu">
        <a href="<?php echo $basePrefix; ?>profile/index.php" class="user-profile-btn <?php echo $activePage === 'profile' ? 'active' : ''; ?>">
          👤 <?php echo htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username']); ?> ▾
        </a>
        <div class="dropdown profile-dropdown">
          <a href="<?php echo $basePrefix; ?>profile/index.php">My Profile</a>
          <a href="<?php echo $basePrefix; ?>rewards/index.php">Cozy Rewards</a>
          <a href="<?php echo $basePrefix; ?>logout.php">Logout</a>
        </div>
      </div>
    <?php else: ?>
      <a href="<?php echo $basePrefix; ?>login/index.php" class="nav-login-btn <?php echo $activePage === 'login' ? 'active' : ''; ?>">Login</a>
    <?php endif; ?>

    <!-- HAMBURGER BUTTON (Only visible when desktop links collapse) -->
    <button class="hamburger" id="sideNavToggle" aria-label="Menu Drawer">
      <span></span><span></span><span></span>
    </button>

  </div>
</nav>

<!-- SIDE SLIDE DRAWER OVERLAY -->
<div id="sideNavOverlay" class="side-nav-overlay"></div>

<!-- SIDE SLIDE DRAWER PANEL (Slides 300px from right) -->
<div id="sideNavDrawer" class="side-nav-drawer">
  <div class="side-drawer-header">
    <span class="side-drawer-title">Cozy Menu</span>
    <button type="button" id="sideNavClose" class="side-drawer-close">&times;</button>
  </div>
  <ul class="side-drawer-links">
    <li><a href="<?php echo $basePrefix; ?>home/index.php" class="<?php echo $activePage === 'home' ? 'active' : ''; ?>">🏠 Home</a></li>
    <li>
      <a href="<?php echo $basePrefix; ?>menu/index.php" class="<?php echo $activePage === 'menu' ? 'active' : ''; ?>">☕ Menu</a>
      <div class="side-sub-menu">
        <a href="<?php echo $basePrefix; ?>menu/index.php?cat=specialty#specialty">🌟 Specialty Coffee</a>
        <a href="<?php echo $basePrefix; ?>menu/index.php?cat=classic#classic">☕ Classic Coffee</a>
        <a href="<?php echo $basePrefix; ?>menu/index.php?cat=noncoffein#noncoffein">🍃 Non-Coffein</a>
        <a href="<?php echo $basePrefix; ?>menu/index.php?cat=smoothies#smoothies">🍹 Smoothies &amp; Sodas</a>
        <a href="<?php echo $basePrefix; ?>menu/index.php?cat=mains#mains">🍽️ Main Dishes</a>
        <a href="<?php echo $basePrefix; ?>menu/index.php?cat=desserts#desserts">🍰 Desserts</a>
      </div>
    </li>
    <li><a href="<?php echo $basePrefix; ?>blog/index.php" class="<?php echo $activePage === 'blog' ? 'active' : ''; ?>">📸 Blog</a></li>
    <li><a href="<?php echo $basePrefix; ?>benefits/index.php" class="<?php echo $activePage === 'benefits' ? 'active' : ''; ?>">🎁 Benefits</a></li>
    <li>
      <a href="<?php echo $basePrefix; ?>offers/index.php" class="<?php echo $activePage === 'offers' ? 'active' : ''; ?>">🏷️ Offers</a>
      <div class="side-sub-menu">
        <a href="<?php echo $basePrefix; ?>offers/index.php#drinks">Drink Offers</a>
        <a href="<?php echo $basePrefix; ?>offers/index.php#food">Food Offers</a>
        <a href="<?php echo $basePrefix; ?>offers/index.php#partners">Partner Promotions</a>
      </div>
    </li>
    <li>
      <a href="<?php echo $basePrefix; ?>activities/index.php" class="<?php echo $activePage === 'activities' ? 'active' : ''; ?>">🎉 Activities</a>
      <div class="side-sub-menu">
        <a href="<?php echo $basePrefix; ?>activities/index.php#workshops">Coffee Workshops</a>
        <a href="<?php echo $basePrefix; ?>activities/index.php#giveback">Cozy Give-Back</a>
      </div>
    </li>
    <li><a href="<?php echo $basePrefix; ?>contact/index.php" class="<?php echo $activePage === 'contact' ? 'active' : ''; ?>">📍 Contact Us</a></li>
  </ul>
</div>

<!-- FLOATING CUSTOMER SERVICE CHAT BUBBLE -->
<div id="csChatWidget" style="position: fixed; bottom: 24px; right: 24px; z-index: 9999;">
  <!-- Toggle Bubble Button -->
  <button type="button" id="csToggleBtn" style="width: 56px; height: 56px; border-radius: 50%; background: var(--color-accent-dark); color: #fff; border: none; box-shadow: 0 6px 20px rgba(168, 71, 47, 0.4); font-size: 1.5rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: transform 0.2s ease;">
    💬
  </button>

  <!-- Chat Box Container -->
  <div id="csChatBox" style="display: none; position: absolute; bottom: 70px; right: 0; width: 320px; background: #ffffff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.18); border: 1px solid var(--color-border); overflow: hidden; flex-direction: column;">
    <div style="background: var(--color-primary); color: #fff; padding: 14px 16px; display: flex; justify-content: space-between; align-items: center;">
      <div>
        <div style="font-weight: 700; font-size: 0.95rem;">☕ Customer Care &amp; Feedback</div>
        <div style="font-size: 0.75rem; opacity: 0.85;">We usually reply in a few minutes</div>
      </div>
      <button type="button" id="csCloseBtn" style="background: none; border: none; color: #fff; font-size: 1.2rem; cursor: pointer;">&times;</button>
    </div>
    
    <div id="csMessages" style="padding: 14px; max-height: 240px; overflow-y: auto; font-size: 0.88rem; display: flex; flex-direction: column; gap: 10px;">
      <div style="background: #f4ede4; padding: 10px 12px; border-radius: 12px; max-width: 85%; color: #4a3b32;">
        👋 Hi there! Have a question, suggestion, or complaint? Send us a message and our cozy barista team will assist you!
      </div>
    </div>

    <form id="csForm" style="display: flex; padding: 10px; border-top: 1px solid #eee; background: #faf5ee;">
      <input type="text" id="csInput" placeholder="Type your question or feedback..." style="flex: 1; border: 1px solid #ddd; border-radius: 20px; padding: 8px 12px; font-size: 0.85rem;" required>
      <button type="submit" style="background: var(--color-accent-dark); color: #fff; border: none; border-radius: 20px; padding: 8px 14px; margin-left: 6px; font-size: 0.85rem; font-weight: 700; cursor: pointer;">Send</button>
    </form>
  </div>
</div>

<script>
  // Side Slide Drawer Handler
  const sideToggleBtn = document.getElementById('sideNavToggle');
  const sideDrawer = document.getElementById('sideNavDrawer');
  const sideOverlay = document.getElementById('sideNavOverlay');
  const sideCloseBtn = document.getElementById('sideNavClose');

  function openSideDrawer() {
    sideDrawer?.classList.add('open');
    sideOverlay?.classList.add('show');
  }

  function closeSideDrawer() {
    sideDrawer?.classList.remove('open');
    sideOverlay?.classList.remove('show');
  }

  sideToggleBtn?.addEventListener('click', openSideDrawer);
  sideCloseBtn?.addEventListener('click', closeSideDrawer);
  sideOverlay?.addEventListener('click', closeSideDrawer);

  // Notification Bell Script
  const notifBtn = document.getElementById('globalNotifBtn');
  const notifDropdown = document.getElementById('globalNotifDropdown');
  const notifCountEl = document.getElementById('globalNotifCount');
  const notifListEl = document.getElementById('globalNotifList');
  const markReadBtn = document.getElementById('markReadBtn');

  function closeNotifDropdown() {
    notifDropdown?.classList.remove('show');
  }

  function fetchNotifications() {
    fetch('<?php echo $basePrefix; ?>includes/notif_fetch.php')
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          const count = parseInt(data.unread_count || 0);
          if (count > 0) {
            notifCountEl.style.display = 'inline-block';
            notifCountEl.textContent = count;
          } else {
            notifCountEl.style.display = 'none';
          }

          let html = '';
          if (data.items.length === 0) {
            html = '<div style="text-align:center; padding:10px; color:#888; font-size:0.85rem;">No new notifications</div>';
          } else {
            data.items.forEach(item => {
              html += `
                <div class="notif-item">
                  <a href="${item.link || '#'}" style="text-decoration:none; color:inherit; display:block;">
                    <div class="notif-title">${item.title}</div>
                    <div>${item.message}</div>
                  </a>
                </div>
              `;
            });
          }
          notifListEl.innerHTML = html;
        }
      })
      .catch(() => {
        notifListEl.innerHTML = '<div style="text-align:center; padding:8px; color:#888;">Notifications offline</div>';
      });
  }

  markReadBtn?.addEventListener('click', () => {
    notifCountEl.style.display = 'none';
    notifListEl.innerHTML = '<div style="text-align:center; padding:10px; color:#888; font-size:0.85rem;">All marked as read ✓</div>';
    
    const formData = new FormData();
    formData.append('action', 'mark_read');
    fetch('<?php echo $basePrefix; ?>includes/notif_fetch.php', { method: 'POST', body: formData });
  });

  notifBtn?.addEventListener('click', (e) => {
    e.stopPropagation();
    notifDropdown?.classList.toggle('show');
  });

  document.addEventListener('click', (e) => {
    if (!notifDropdown?.contains(e.target) && e.target !== notifBtn) {
      notifDropdown?.classList.remove('show');
    }
  });

  fetchNotifications();

  // Floating Customer Service Chatbox JS (Log-In Only & Store Messages)
  const csToggleBtn = document.getElementById('csToggleBtn');
  const csChatBox = document.getElementById('csChatBox');
  const csCloseBtn = document.getElementById('csCloseBtn');
  const csForm = document.getElementById('csForm');
  const csInput = document.getElementById('csInput');
  const csMessages = document.getElementById('csMessages');
  const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

  function loadChatMessages() {
    if (!isLoggedIn) return;
    fetch('<?php echo $basePrefix; ?>includes/chat_handler.php?action=fetch_messages')
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success' && data.messages) {
          let html = '';
          if (data.messages.length === 0) {
            html = '<div style="background: #f4ede4; padding: 10px 12px; border-radius: 12px; max-width: 85%; color: #4a3b32;">👋 Hi there! Have a question, suggestion, or complaint? Send us a message and our cozy barista team will assist you!</div>';
          } else {
            data.messages.forEach(m => {
              const isUser = m.sender === 'user';
              const bg = isUser ? 'var(--color-accent-dark)' : '#f4ede4';
              const col = isUser ? '#ffffff' : '#4a3b32';
              const align = isUser ? 'flex-end' : 'flex-start';
              const label = isUser ? 'You' : '☕ Cozy Barista Team';
              html += `
                <div style="background: ${bg}; color: ${col}; padding: 10px 12px; border-radius: 12px; max-width: 85%; align-self: ${align}; box-shadow: 0 2px 5px rgba(0,0,0,0.04);">
                  <div style="font-size:0.7rem; opacity:0.75; margin-bottom:2px;">${label}</div>
                  <div>${m.text}</div>
                  <div style="font-size:0.65rem; opacity:0.65; margin-top:4px; text-align:right;">${m.time}</div>
                </div>
              `;
            });
          }
          csMessages.innerHTML = html;
          csMessages.scrollTop = csMessages.scrollHeight;
        }
      });
  }

  csToggleBtn?.addEventListener('click', () => {
    if (!isLoggedIn) {
      alert("☕ Please log in to start a chat with our Cozy Barista Team!");
      window.location.href = '<?php echo $basePrefix; ?>login/index.php';
      return;
    }
    const isHidden = csChatBox.style.display === 'none' || csChatBox.style.display === '';
    csChatBox.style.display = isHidden ? 'flex' : 'none';
    if (isHidden) {
      loadChatMessages();
    }
  });

  csCloseBtn?.addEventListener('click', () => {
    csChatBox.style.display = 'none';
  });

  csForm?.addEventListener('submit', (e) => {
    e.preventDefault();
    if (!isLoggedIn) {
      window.location.href = '<?php echo $basePrefix; ?>login/index.php';
      return;
    }
    const msg = csInput.value.trim();
    if (!msg) return;

    const formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('message', msg);

    fetch('<?php echo $basePrefix; ?>includes/chat_handler.php', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          csInput.value = '';
          loadChatMessages();
        } else {
          alert(data.message || "Failed to send message.");
        }
      });
  });
</script>
