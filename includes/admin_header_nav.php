<?php
// includes/admin_header_nav.php — Collapsible Left Vertical Sidebar Component
$adminActivePage = $adminActivePage ?? '';
?>

<!-- LEFT VERTICAL SIDEBAR (COLLAPSIBLE) -->
<aside class="admin-sidebar" id="adminSidebar">
  <div>
    <!-- SIDEBAR BRAND HEADER -->
    <div class="sidebar-brand">
      <a href="dashboard.php">
        <span class="brand-icon">☕</span>
        <div>
          <div class="brand-title">Cozy Barista</div>
          <div class="brand-subtitle">Admin Control Portal</div>
        </div>
      </a>
    </div>

    <!-- SIDEBAR NAVIGATION MENU (PERFECTLY CENTERED TEXT) -->
    <nav class="sidebar-nav">
      
      <!-- GROUP 1: OVERVIEW -->
      <div class="nav-section-label">Overview</div>
      <a href="dashboard.php" class="sidebar-link <?php echo $adminActivePage === 'dashboard' ? 'active' : ''; ?>">
        Dashboard
      </a>

      <!-- GROUP 2: STORE OPERATIONS -->
      <div class="nav-section-label">Operations</div>
      <a href="manage_orders.php" class="sidebar-link <?php echo $adminActivePage === 'orders' ? 'active' : ''; ?>">
        Customer Orders
      </a>
      <a href="manage_menu.php" class="sidebar-link <?php echo $adminActivePage === 'menu' ? 'active' : ''; ?>">
        Menu Items
      </a>
      <a href="manage_chat.php" class="sidebar-link <?php echo $adminActivePage === 'chat' ? 'active' : ''; ?>">
        Customer Care Chat
      </a>

      <!-- GROUP 3: MARKETING & PROMOTIONS -->
      <div class="nav-section-label">Marketing &amp; Deals</div>
      <a href="manage_offers.php" class="sidebar-link <?php echo $adminActivePage === 'offers' ? 'active' : ''; ?>">
        Special Offers
      </a>
      <a href="manage_promos.php" class="sidebar-link <?php echo $adminActivePage === 'promos' ? 'active' : ''; ?>">
        Promo Codes
      </a>
      <a href="manage_activities.php" class="sidebar-link <?php echo $adminActivePage === 'activities' ? 'active' : ''; ?>">
        Store Activities
      </a>
      <a href="manage_blog.php" class="sidebar-link <?php echo $adminActivePage === 'blog' ? 'active' : ''; ?>">
        Community Blog
      </a>

      <!-- GROUP 4: SETTINGS & INFO -->
      <div class="nav-section-label">Settings &amp; Info</div>
      <a href="manage_users.php" class="sidebar-link <?php echo $adminActivePage === 'users' ? 'active' : ''; ?>">
        User Accounts
      </a>
      <a href="manage_contact.php" class="sidebar-link <?php echo $adminActivePage === 'contact' ? 'active' : ''; ?>">
        Contact Page
      </a>

    </nav>
  </div>

  <!-- SIDEBAR FOOTER LOGOUT AREA WITH CONFIRMATION POPUP -->
  <div class="sidebar-footer">
    <div style="font-size: 0.78rem; color: #A89587; margin-bottom: 8px; font-weight: 600; text-align: center;">
      Barista Session Active
    </div>
    <a href="logout.php" onclick="return confirm('⚠️ Are you sure you want to log out of Barista Admin?');" class="sidebar-logout-btn">
      <span>Log Out Admin</span>
    </a>
  </div>
</aside>

<!-- MAIN CONTENT WRAPPER NEXT TO SIDEBAR -->
<div class="admin-main-wrapper" id="mainWrapper">
  
  <!-- TOP APP BAR WITH HAMBURGER COLLAPSE TOGGLE -->
  <header class="admin-top-appbar">
    <div style="display: flex; align-items: center; gap: 14px;">
      <!-- HAMBURGER COLLAPSE / EXPAND TOGGLE -->
      <button type="button" id="sidebarToggle" class="sidebar-toggle-btn" aria-label="Toggle Sidebar" title="Collapse / Expand Sidebar">
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
      </button>

      <?php 
        $appbarPageTitles = [
          'dashboard' => 'Dashboard',
          'orders' => 'Orders',
          'chat' => 'Customer Care Chat',
          'menu' => 'Menu Items',
          'offers' => 'Special Offers',
          'promos' => 'Promo Codes',
          'activities' => 'Store Activities',
          'blog' => 'Community Blog',
          'users' => 'User Accounts',
          'contact' => 'Contact Page'
        ];
        $appbarTitle = $appbarPageTitles[$adminActivePage] ?? ucfirst($adminActivePage ?: 'Dashboard');
      ?>

      <div class="appbar-breadcrumb">
        <a href="dashboard.php" style="color: #7A685A; text-decoration: none; font-weight: 700; transition: color 0.2s ease;" onmouseover="this.style.color='#C85A3E'" onmouseout="this.style.color='#7A685A'">Barista Admin</a>
        <span class="sep">›</span>
        <span class="current-page"><?php echo htmlspecialchars($appbarTitle); ?></span>
      </div>
    </div>

    <div style="display: flex; align-items: center; gap: 14px;">
      <a href="../home/index.php" target="_blank" rel="noopener noreferrer" class="view-store-link">
        User Page &rarr;
      </a>
    </div>
  </header>

  <div class="admin-content-area">

<script>
  // Apply saved sidebar collapse preference immediately to prevent flash
  (function() {
    if (localStorage.getItem('adminSidebarCollapsed') === 'true') {
      document.body.classList.add('sidebar-collapsed');
    }
  })();

  document.getElementById('sidebarToggle')?.addEventListener('click', function() {
    document.body.classList.toggle('sidebar-collapsed');
    const isCollapsed = document.body.classList.contains('sidebar-collapsed');
    localStorage.setItem('adminSidebarCollapsed', isCollapsed);
  });

  // Preserve sidebar internal scroll position across navigation
  const sidebar = document.getElementById('adminSidebar');
  if (sidebar) {
    const savedPos = sessionStorage.getItem('adminSidebarScrollPos');
    if (savedPos !== null) {
      sidebar.scrollTop = parseInt(savedPos, 10);
    }
    sidebar.addEventListener('scroll', function() {
      sessionStorage.setItem('adminSidebarScrollPos', sidebar.scrollTop);
    });
  }
</script>
