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
               strpos($currentPath, '/orders/') !== false || 
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
      </div>
    </li>
    <li><a href="<?php echo $basePrefix; ?>activities/index.php" class="<?php echo $activePage === 'activities' ? 'active' : ''; ?>">Activities</a></li>
    <li><a href="<?php echo $basePrefix; ?>contact/index.php" class="<?php echo $activePage === 'contact' ? 'active' : ''; ?>">Contact</a></li>
  </ul>

  <!-- RIGHT UTILITIES (ALWAYS VISIBLE AT ALL SCREEN SIZES) -->
  <div class="nav-right-actions">

    <!-- NOTIFICATION BELL ENTRANCE (ALWAYS ON TOP BAR) -->
    <div class="notif-container" style="position: relative; display: inline-block;">
      <button type="button" class="notif-bell-btn" id="globalNotifBtn" title="Notifications" style="color: #FAF7F2; border: 1px solid rgba(255,255,255,0.25);">
        🔔 <span class="notif-badge-count" id="globalNotifCount">0</span>
      </button>
      <div class="notif-dropdown" id="globalNotifDropdown" style="position: absolute; right: 0; top: calc(100% + 10px); z-index: 99999;">
        <div style="font-weight: 700; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-bottom: 8px; display:flex; justify-content:space-between; align-items:center;">
          <span>🔔 Notifications</span>
          <div style="display:flex; gap: 8px; align-items:center;">
            <button type="button" id="markReadBtn" style="background:none; border:none; color: var(--color-accent-dark); font-size:0.75rem; cursor:pointer; font-weight:700;">Mark as read ✓</button>
            <span style="font-size:0.75rem; color:#888; cursor:pointer;" onclick="closeNotifDropdown()">&times;</span>
          </div>
        </div>
        <div id="globalNotifList" style="max-height: 160px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; padding-right: 4px;">
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
          <?php echo htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username']); ?> ▾
        </a>
        <div class="dropdown profile-dropdown">
          <a href="<?php echo $basePrefix; ?>profile/index.php">My Profile</a>
          <a href="<?php echo $basePrefix; ?>profile/orders.php">My Orders &amp; Live Status</a>
          <a href="<?php echo $basePrefix; ?>rewards/index.php">Cozy Rewards</a>
          <div style="border-top: 1px solid #E8DDD0; margin: 4px 0;"></div>
          <a href="<?php echo $basePrefix; ?>logout.php" class="nav-logout-btn" style="color: #DC2626 !important; font-weight: 800 !important; display: flex; align-items: center; gap: 6px;">🚪 Logout</a>
        </div>
      </div>
    <?php else: ?>
      <a href="<?php echo $basePrefix; ?>orders/track.php" class="<?php echo $activePage === 'track' ? 'active' : ''; ?>" style="color: #FAF7F2; background: <?php echo $activePage === 'track' ? 'var(--color-accent-dark)' : 'rgba(255,255,255,0.12)'; ?>; padding: 6px 14px; border-radius: 20px; font-size: 0.82rem; font-weight: 700; text-decoration: none; border: 1px solid rgba(255,255,255,0.25);">⚡ Track Order</a>
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
      </div>
    </li>
    <li><a href="<?php echo $basePrefix; ?>activities/index.php" class="<?php echo $activePage === 'activities' ? 'active' : ''; ?>">🎉 Activities</a></li>
    <li><a href="<?php echo $basePrefix; ?>contact/index.php" class="<?php echo $activePage === 'contact' ? 'active' : ''; ?>">📍 Contact Us</a></li>
  </ul>
</div>

<!-- FLOATING CUSTOMER SERVICE CHAT BUBBLE (BOTTOM-LEFT POSITION) -->
<div id="csChatWidget" style="position: fixed; bottom: 24px; left: 24px; z-index: 9999;">
  <!-- Toggle Bubble Button -->
  <button type="button" id="csToggleBtn" style="width: 56px; height: 56px; border-radius: 50%; background: var(--color-accent-dark); color: #fff; border: none; box-shadow: 0 6px 20px rgba(168, 71, 47, 0.4); font-size: 1.5rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: transform 0.2s ease;">
    💬
  </button>

  <!-- Chat Box Container -->
  <div id="csChatBox" style="display: none; position: absolute; bottom: 70px; left: 0; width: 320px; background: #ffffff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.18); border: 1px solid var(--color-border); overflow: hidden; flex-direction: column;">
    <div style="background: linear-gradient(135deg, #2C1C14 0%, #1A100B 100%); color: #FAF7F2; padding: 14px 16px; border-bottom: 1.5px solid #C85A3E;">
      <div style="font-weight: 800; font-size: 0.95rem; color: #FFFFFF;">☕ Customer Care</div>
      <div style="font-size: 0.75rem; color: #F2C94C; font-weight: 700; margin-top: 2px;">We usually reply in a few minutes</div>
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
            html = '<div style="text-align:center; padding:12px; color:#8A7769; font-size:0.85rem;">No notifications</div>';
          } else {
            data.items.forEach(item => {
              const isRead = parseInt(item.is_read || 0) === 1;
              const cardBg = isRead ? '#FAF7F2' : '#FFFBF5';
              const cardBorder = isRead ? '1px solid #E8DDD0' : '1.5px solid #C85A3E';
              const titleWeight = isRead ? '600' : '800';
              const titleCol = isRead ? '#7A685A' : '#2C1C14';
              const msgCol = isRead ? '#9E8C7E' : '#4A3B32';
              const unreadDot = isRead ? '' : '<span style="color:#C85A3E; margin-right:4px;">●</span>';

              html += `
                <div class="notif-item" style="background: ${cardBg}; border: ${cardBorder}; border-radius: 10px; padding: 10px; transition: all 0.2s ease;">
                  <a href="${item.link || '#'}" style="text-decoration:none; color:inherit; display:block;">
                    <div class="notif-title" style="font-weight: ${titleWeight}; color: ${titleCol}; font-size: 0.88rem; margin-bottom: 2px;">
                      ${unreadDot}${item.title}
                    </div>
                    <div style="font-size: 0.8rem; color: ${msgCol}; line-height: 1.4;">${item.message}</div>
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
    const formData = new FormData();
    formData.append('action', 'mark_read');
    fetch('<?php echo $basePrefix; ?>includes/notif_fetch.php', { method: 'POST', body: formData })
      .then(() => fetchNotifications());
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
            let lastDate = '';
            data.messages.forEach(m => {
              if (m.date_display && m.date_display !== lastDate) {
                lastDate = m.date_display;
                html += `
                  <div style="text-align: center; margin: 8px 0; font-size: 0.72rem; color: #7A685A; font-weight: 800;">
                    <span style="background: #FAF4EB; border: 1px solid #E8DDD0; padding: 3px 12px; border-radius: 12px; display: inline-block; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                      📅 ${m.date_display}
                    </span>
                  </div>
                `;
              }

              const isUser = m.sender === 'user';
              if (isUser) {
                const deleteBtnHtml = `<button type="button" onclick="deleteUserChatMessage(${m.id})" style="background:rgba(255,255,255,0.25); border:none; color:#ffffff; font-size:0.7rem; border-radius:50%; width:16px; height:16px; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; line-height:1; transition:all 0.2s ease;" title="Delete message">&times;</button>`;
                html += `
                  <div id="user-msg-bubble-${m.id}" style="background: var(--color-accent-dark); color: #ffffff; padding: 7px 11px; border-radius: 14px 14px 2px 14px; width: fit-content; max-width: 68%; align-self: flex-end; box-shadow: 0 2px 5px rgba(0,0,0,0.04); word-break: break-word; overflow-wrap: break-word;">
                    <div style="font-size:0.85rem; line-height:1.35; white-space: pre-line; word-break: break-word;">${m.text}</div>
                    <div style="display:flex; justify-content:flex-end; align-items:center; gap:5px; margin-top:2px; font-size:0.6rem; opacity:0.8;">
                      <span>${m.time}</span>
                      ${deleteBtnHtml}
                    </div>
                  </div>
                `;
              } else {
                html += `
                  <div style="background: #FAF4EB; color: #2C1C14; border: 1.5px solid #E8DDD0; padding: 7px 11px; border-radius: 14px 14px 14px 2px; width: fit-content; max-width: 68%; align-self: flex-start; box-shadow: 0 2px 5px rgba(0,0,0,0.04); word-break: break-word; overflow-wrap: break-word;">
                    <div style="font-size:0.68rem; font-weight:800; color:#C85A3E; margin-bottom:2px;">Cozy Barista</div>
                    <div style="font-size:0.85rem; line-height:1.35; white-space: pre-line; word-break: break-word;">${m.text}</div>
                    <div style="font-size:0.6rem; color:#8C7A6D; margin-top:2px; text-align:right;">${m.time}</div>
                  </div>
                `;
              }
            });
          }
          csMessages.innerHTML = html;
          csMessages.scrollTop = csMessages.scrollHeight;
        }
      });
  }

  window.deleteUserChatMessage = function(msgId) {
    if (!confirm('Are you sure you want to delete this message?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_message');
    formData.append('msg_id', msgId);

    fetch('<?php echo $basePrefix; ?>includes/chat_handler.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success') {
        const el = document.getElementById('user-msg-bubble-' + msgId);
        if (el) {
          el.style.transition = 'all 0.3s ease';
          el.style.opacity = '0';
          el.style.transform = 'scale(0.9)';
          setTimeout(() => el.remove(), 300);
        }
      } else {
        alert(data.message || 'Could not delete message.');
      }
    })
    .catch(err => console.error('Error deleting message:', err));
  };

  csToggleBtn?.addEventListener('click', () => {
    if (!isLoggedIn) {
      alert("☕ Please log in to start a chat with our Cozy Barista Team!");
      return;
    }
    const isHidden = csChatBox.style.display === 'none' || csChatBox.style.display === '';
    if (isHidden) {
      csChatBox.style.display = 'flex';
      csToggleBtn.innerHTML = '✕';
      loadChatMessages();
    } else {
      csChatBox.style.display = 'none';
      csToggleBtn.innerHTML = '💬';
    }
  });

  csForm?.addEventListener('submit', (e) => {
    e.preventDefault();
    if (!isLoggedIn) {
      alert("☕ Please log in to start a chat with our Cozy Barista Team!");
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
