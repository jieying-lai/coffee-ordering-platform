<?php
session_start();
require_once '../includes/db_connect.php';
$categories = $conn->query('SELECT * FROM categories ORDER BY display_order');
$categoryList = [];
while ($row = $categories->fetch_assoc()) {
    $categoryList[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/menu.css">
  <title>Cozy Coffee Co. — Menu</title>
</head>

<body>
<nav>
  <div class="logo"><a href="../home/index.php">Cozy Coffee Co.</a></div>
  <ul class="nav-links">
    <li><a href="../home/index.php">Home</a></li>
    <li><a href="../menu/index.php" class="active">Menu ▾</a></li>
    <li><a href="../blog/index.php">Blog</a></li>
    <li><a href="../contact/index.php">Contact</a></li>
    <li><a href="../cart/index.php">Cart</a></li>
        <!-- DYNAMIC NAVIGATION LINK -->
    <?php if (isset($_SESSION['user_id'])): ?>
      <!-- Logged In State: Show Username & Profile Dropdown -->
      <li>
        <a href="../profile/index.php"><?php echo htmlspecialchars($_SESSION['fullname']); ?> ▾</a>
        <div class="dropdown">
          <a href="../profile/index.php">My Profile</a>
          <a href="../logout.php">Logout</a>
        </div>
      </li>
    <?php else: ?>
      <!-- Guest State: Show Login Link -->
      <li><a href="../login/index.php">Login</a></li>
    <?php endif; ?>
  </ul>
  <button class="hamburger" aria-label="Menu"><span></span><span></span><span></span></button>
</nav>

<!-- Search Control -->
<div class="search-container">
  <input type="text" id="menuSearchInput" class="search-input" placeholder="🔍 Search menu items or ingredients...">
</div>

<div class="filters">
  <button class="filter-btn active" data-filter="all">All</button>
  <?php foreach ($categoryList as $cat): ?>
    <button class="filter-btn" data-filter="<?php echo htmlspecialchars($cat['category_key']); ?>">
      <?php echo htmlspecialchars($cat['category_label']); ?>
    </button>
  <?php endforeach; ?>
</div>

<div id="noResultsMessage" class="no-results">No menu items match your search.</div>

<?php foreach ($categoryList as $cat): ?>
  <div class="menu-category" data-category="<?php echo htmlspecialchars($cat['category_key']); ?>" id="<?php echo htmlspecialchars($cat['category_key']); ?>">
    <h2 class="category-title"><?php echo htmlspecialchars($cat['category_label']); ?></h2>

    <div class="menu-grid">
      <?php
        $stmt = $conn->prepare('SELECT * FROM menu_items WHERE category_id = ? ORDER BY created_at DESC, item_id DESC');
        $stmt->bind_param('i', $cat['category_id']);
        $stmt->execute();
        $items = $stmt->get_result();
        $sevenDaysAgo = strtotime('-7 days');

        while ($item = $items->fetch_assoc()):
            $isNew = false;
            if (!empty($item['created_at'])) {
                if (strtotime($item['created_at']) >= $sevenDaysAgo) {
                    $isNew = true;
                }
            }

            $imagePath = (preg_match('/^https?:\/\//i', $item['image']))
                ? $item['image']
                : '../images/menu/' . $item['image'];
      ?>
        <div class="item-card" data-name="<?php echo htmlspecialchars(strtolower($item['name'])); ?>" data-description="<?php echo htmlspecialchars(strtolower($item['description'])); ?>">
          <div class="item-image">
            <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars(strtoupper($item['name'])); ?>">
          </div>
          <div class="item-body">
            <h3>
              <?php echo htmlspecialchars($item['name']); ?>
              <?php if ($isNew): ?>
                <span class="badge-new">NEW</span>
              <?php endif; ?>
            </h3>
            <p><?php echo htmlspecialchars($item['description']); ?></p>
            <div class="item-footer">
              <span class="price">RM <?php echo number_format($item['price'], 2); ?></span>
              <button class="add-btn view-details-btn" 
                      data-id="<?php echo $item['item_id']; ?>"
                      data-name="<?php echo htmlspecialchars($item['name']); ?>"
                      data-price="<?php echo number_format($item['price'], 2); ?>"
                      data-image="<?php echo htmlspecialchars($imagePath); ?>"
                      data-description="<?php echo htmlspecialchars($item['description']); ?>"
                      data-category="<?php echo htmlspecialchars(strtolower($cat['category_key'])); ?>">View</button>
            </div>
          </div>
        </div>
      <?php endwhile; $stmt->close(); ?>
    </div>
  </div>
<?php endforeach; ?>

<!-- ITEM DETAILS MODAL -->
<div id="itemModal" class="modal-overlay">
  <div class="modal-content">
    <button class="modal-close" id="closeModal">&times;</button>
    <form action="../cart/add_to_cart.php" method="POST">
      <input type="hidden" name="item_id" id="modalItemId">
      
      <div class="modal-body">
        <div class="modal-img-wrap">
          <img id="modalItemImg" src="" alt="">
        </div>
        
        <div class="modal-details">
          <h2 id="modalItemName"></h2>
          <p class="modal-price">RM <span id="modalItemPrice"></span></p>
          <p class="modal-desc" id="modalItemDesc"></p>

          <!-- DRINK OPTIONS (ONLY FOR DRINKS) -->
          <div id="drinkOptionsSection" class="custom-section">
            <div class="option-group">
              <label class="option-label">Temperature Option:</label>
              <div class="radio-group">
                <label class="chip-btn"><input type="radio" name="temperature" value="Regular Ice" checked> <span>Regular Ice</span></label>
                <label class="chip-btn"><input type="radio" name="temperature" value="Less Ice"> <span>Less Ice</span></label>
                <label class="chip-btn"><input type="radio" name="temperature" value="No Ice"> <span>No Ice</span></label>
                <label class="chip-btn"><input type="radio" name="temperature" value="Warm"> <span>Warm</span></label>
                <label class="chip-btn"><input type="radio" name="temperature" value="Hot"> <span>Hot</span></label>
              </div>
            </div>

            <div class="option-group">
              <label class="option-label">Sweetness Level:</label>
              <div class="radio-group">
                <label class="chip-btn"><input type="radio" name="sweetness" value="Regular Sugar" checked> <span>Regular</span></label>
                <label class="chip-btn"><input type="radio" name="sweetness" value="Less Sugar"> <span>Less Sugar</span></label>
                <label class="chip-btn"><input type="radio" name="sweetness" value="No Sugar"> <span>No Sugar</span></label>
              </div>
            </div>
          </div>

          <!-- REMARKS -->
          <div class="option-group">
            <label class="option-label" for="itemRemarks">Special Remarks:</label>
            <textarea name="remarks" id="itemRemarks" rows="2" placeholder="e.g. Extra hot, oat milk alternative, etc."></textarea>
          </div>

          <!-- QUANTITY & SUBMIT -->
          <div class="modal-action-row">
            <div class="quantity-control">
              <button type="button" id="qtyMinus">-</button>
              <input type="number" name="quantity" id="itemQty" value="1" min="1" max="99" readonly>
              <button type="button" id="qtyPlus">+</button>
            </div>
            <button type="submit" class="submit-cart-btn">Add to Cart</button>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
  // Filter & Search Logic
  const filterBtns = document.querySelectorAll('.filter-btn');
  const categories = document.querySelectorAll('.menu-category');
  const searchInput = document.getElementById('menuSearchInput');
  const noResultsMsg = document.getElementById('noResultsMessage');

  function filterMenu() {
    const query = searchInput.value.toLowerCase().trim();
    const activeFilterBtn = document.querySelector('.filter-btn.active');
    const activeCategory = activeFilterBtn ? activeFilterBtn.dataset.filter : 'all';
    
    let totalVisibleItems = 0;

    categories.forEach(categorySection => {
      const categoryKey = categorySection.dataset.category;
      const matchesCategory = (activeCategory === 'all' || categoryKey === activeCategory);
      
      let visibleItemsInCategory = 0;
      const cardsInCategory = categorySection.querySelectorAll('.item-card');

      cardsInCategory.forEach(card => {
        const name = card.dataset.name;
        const description = card.dataset.description;
        const matchesSearch = name.includes(query) || description.includes(query);

        if (matchesCategory && matchesSearch) {
          card.style.display = '';
          visibleItemsInCategory++;
          totalVisibleItems++;
        } else {
          card.style.display = 'none';
        }
      });

      categorySection.style.display = (visibleItemsInCategory > 0) ? '' : 'none';
    });

    noResultsMsg.style.display = (totalVisibleItems === 0) ? 'block' : 'none';
  }

  searchInput.addEventListener('input', filterMenu);

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      filterMenu();
    });
  });

  // Modal Functionality
  const modal = document.getElementById('itemModal');
  const closeModalBtn = document.getElementById('closeModal');
  const drinkOptionsSection = document.getElementById('drinkOptionsSection');

  document.querySelectorAll('.view-details-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      document.getElementById('modalItemId').value = this.dataset.id;
      document.getElementById('modalItemName').textContent = this.dataset.name;
      document.getElementById('modalItemPrice').textContent = this.dataset.price;
      document.getElementById('modalItemDesc').textContent = this.dataset.description;
      document.getElementById('modalItemImg').src = this.dataset.image;
      document.getElementById('itemQty').value = 1;
      document.getElementById('itemRemarks').value = '';

      // Check category to display drink options
      const cat = this.dataset.category;
      if (cat.includes('coffee') || cat.includes('drink') || cat.includes('beverage') || cat.includes('tea')) {
        drinkOptionsSection.style.display = 'block';
      } else {
        drinkOptionsSection.style.display = 'none';
      }

      modal.style.display = 'flex';
    });
  });

  closeModalBtn.addEventListener('click', () => { modal.style.display = 'none'; });
  window.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });

  // Quantity Stepper
  const qtyInput = document.getElementById('itemQty');
  document.getElementById('qtyMinus').addEventListener('click', () => {
    let val = parseInt(qtyInput.value) || 1;
    if (val > 1) qtyInput.value = val - 1;
  });
  document.getElementById('qtyPlus').addEventListener('click', () => {
    let val = parseInt(qtyInput.value) || 1;
    qtyInput.value = val + 1;
  });

  // Hamburger menu toggle
  document.querySelector('.hamburger').addEventListener('click', () => {
    const nav = document.querySelector('.nav-links');
    nav.style.display = nav.style.display === 'flex' ? 'none' : 'flex';
  });
</script>

</body>
</html>