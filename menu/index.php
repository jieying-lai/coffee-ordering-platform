<?php
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
    <li>
      <a href="index.php" class="active">Menu ▾</a>
      <div class="dropdown">
        <?php foreach ($categoryList as $cat): ?>
          <a href="#<?php echo htmlspecialchars($cat['category_key']); ?>"><?php echo htmlspecialchars(preg_replace('/^\S+\s/', '', $cat['category_label'])); ?></a>
        <?php endforeach; ?>
      </div>
    </li>
    <li><a href="../contact/index.php">Contact</a></li>
    <li><a href="../cart/index.php">Cart</a></li>
    <li><a href="../login/index.php">Login</a></li>
  </ul>
  <button class="hamburger" aria-label="Menu"><span></span><span></span><span></span></button>
</nav>

<div class="filters">
  <button class="filter-btn active" data-filter="all">All</button>
  <?php foreach ($categoryList as $cat): ?>
    <button class="filter-btn" data-filter="<?php echo htmlspecialchars($cat['category_key']); ?>">
      <?php echo htmlspecialchars($cat['category_label']); ?>
    </button>
  <?php endforeach; ?>
</div>

<?php foreach ($categoryList as $cat): ?>
  <div class="menu-category" data-category="<?php echo htmlspecialchars($cat['category_key']); ?>" id="<?php echo htmlspecialchars($cat['category_key']); ?>">
    <h2 class="category-title"><?php echo htmlspecialchars($cat['category_label']); ?></h2>

    <div class="menu-grid">
      <?php
        $stmt = $conn->prepare('SELECT * FROM menu_items WHERE category_id = ? ORDER BY display_order');
        $stmt->bind_param('i', $cat['category_id']);
        $stmt->execute();
        $items = $stmt->get_result();
        while ($item = $items->fetch_assoc()):
      ?>
        <div class="item-card">
          <div class="item-image">
            <img src="../images/menu/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars(strtoupper($item['name'])); ?>">
          </div>
          <div class="item-body">
            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
            <p><?php echo htmlspecialchars($item['description']); ?></p>
            <div class="item-footer">
              <span class="price">RM <?php echo number_format($item['price'], 2); ?></span>
              <a href="../details/index.php?id=<?php echo $item['item_id']; ?>"><button class="add-btn">View</button></a>
            </div>
          </div>
        </div>
      <?php endwhile; $stmt->close(); ?>
    </div>
  </div>
<?php endforeach; ?>

<script>
  // Category filter — shows/hides whole .menu-category blocks.
  const filterBtns = document.querySelectorAll('.filter-btn');
  const categories = document.querySelectorAll('.menu-category');

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const target = btn.dataset.filter;
      categories.forEach(cat => {
        cat.style.display = (target === 'all' || cat.dataset.category === target) ? '' : 'none';
      });
    });
  });

  document.querySelector('.hamburger').addEventListener('click', () => {
    const nav = document.querySelector('.nav-links');
    nav.style.display = nav.style.display === 'flex' ? 'none' : 'flex';
  });
</script>

</body>
</html>