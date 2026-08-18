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
<?php 
  $activePage = 'menu';
  require_once '../includes/header_nav.php'; 
?>

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
        <div class="item-card view-details-btn" 
             style="cursor: pointer;"
             data-id="<?php echo $item['item_id']; ?>"
             data-name="<?php echo htmlspecialchars($item['name']); ?>"
             data-price="<?php echo number_format($item['price'], 2); ?>"
             data-image="<?php echo htmlspecialchars($imagePath); ?>"
             data-description="<?php echo htmlspecialchars($item['description']); ?>"
             data-category="<?php echo htmlspecialchars(strtolower($cat['category_key'])); ?>"
             data-search-name="<?php echo htmlspecialchars(strtolower($item['name'])); ?>" 
             data-search-desc="<?php echo htmlspecialchars(strtolower($item['description'])); ?>">
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
            <p><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
            <div class="item-footer" style="display: flex; justify-content: space-between; align-items: center; width: 100%; margin-top: 8px;">
              <span class="price" style="font-size: 1.05rem;">RM <?php echo number_format($item['price'], 2); ?></span>
              <button type="button" class="add-btn" style="width: 34px; height: 34px; border-radius: 50%; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; background: var(--color-accent-dark); border: none; color: #fff; cursor: pointer; transition: transform 0.2s ease; box-shadow: 0 4px 10px rgba(140,109,88,0.3);" title="Customize &amp; Add">+</button>
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
    <form id="addToCartForm" action="../cart/add_to_cart.php" method="POST">
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
            <div class="custom-section" style="margin-bottom: 14px;">
              <label class="option-label">Temperature Option:</label>
              <div class="option-group">
                <input type="radio" name="temperature" id="temp1" value="Regular Ice" checked><label for="temp1">Regular Ice</label>
                <input type="radio" name="temperature" id="temp2" value="Less Ice"><label for="temp2">Less Ice</label>
                <input type="radio" name="temperature" id="temp3" value="No Ice"><label for="temp3">No Ice</label>
                <input type="radio" name="temperature" id="temp4" value="Warm"><label for="temp4">Warm</label>
                <input type="radio" name="temperature" id="temp5" value="Hot"><label for="temp5">Hot</label>
              </div>
            </div>

            <div class="custom-section" style="margin-bottom: 14px;">
              <label class="option-label">Sweetness Level:</label>
              <div class="option-group">
                <input type="radio" name="sweetness" id="sweet1" value="Regular Sugar" checked><label for="sweet1">Regular Sugar</label>
                <input type="radio" name="sweetness" id="sweet2" value="Less Sugar"><label for="sweet2">Less Sugar</label>
                <input type="radio" name="sweetness" id="sweet3" value="No Sugar"><label for="sweet3">No Sugar</label>
              </div>
            </div>
          </div>

          <!-- REMARKS -->
          <div class="option-group">
            <label class="option-label" for="itemRemarks">Special Remarks:</label>
            <textarea name="remarks" id="itemRemarks" rows="2" placeholder="e.g. Extra hot, oat milk alternative, etc."></textarea>
          </div>

          <!-- QUANTITY & SUBMIT -->
          <div class="modal-action-row" style="margin-top: 18px; margin-bottom: 24px; padding-bottom: 12px;">
            <div class="quantity-control">
              <button type="button" id="qtyMinus">-</button>
              <input type="number" name="quantity" id="itemQty" value="1" min="1" max="99" readonly>
              <button type="button" id="qtyPlus">+</button>
            </div>
            <button type="submit" class="submit-cart-btn" id="submitCartBtn" style="padding: 12px 18px; font-size: 0.95rem; font-weight: 700;">Add to Cart ☕</button>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="message" id="message"></div>

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
        const name = card.dataset.searchName || '';
        const description = card.dataset.searchDesc || '';
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
      if (cat.includes('coffee') || cat.includes('drink') || cat.includes('beverage') || cat.includes('tea') || cat.includes('specialty') || cat.includes('classic') || cat.includes('noncoffein') || cat.includes('smoothies')) {
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

  // AJAX Add to Cart Submission
  const addToCartForm = document.getElementById('addToCartForm');
  const submitCartBtn = document.getElementById('submitCartBtn');

  addToCartForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(addToCartForm);
    formData.append('ajax', '1');

    submitCartBtn.disabled = true;
    submitCartBtn.textContent = 'Adding...';

    fetch('../cart/add_to_cart.php', {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
      submitCartBtn.disabled = false;
      submitCartBtn.textContent = 'Add to Cart';
      modal.style.display = 'none';

      if (data.status === 'success') {
        const itemName = document.getElementById('modalItemName').textContent;
        const msg = document.getElementById('message');
        msg.textContent = `${itemName} added to cart ☕`;
        msg.classList.add('show');
        setTimeout(() => msg.classList.remove('show'), 3000);

        // Update nav cart badge if present
        const cartBadge = document.querySelector('.nav-cart-badge');
        if (cartBadge) {
          cartBadge.textContent = data.cart_count;
          cartBadge.style.display = data.cart_count > 0 ? 'inline-flex' : 'none';
        }
      }
    })
    .catch(() => {
      submitCartBtn.disabled = false;
      submitCartBtn.textContent = 'Add to Cart';
      modal.style.display = 'none';
    });
  });

  // Hamburger menu toggle
  document.querySelector('.hamburger').addEventListener('click', () => {
    const nav = document.querySelector('.nav-links');
    nav.classList.toggle('nav-active');
  });
</script>

</body>
</html>
