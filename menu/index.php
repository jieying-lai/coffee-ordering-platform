<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="../style/mystyle.css">
	<title>Cozy Coffee Co. — Menu</title>
</head>

<body>
<nav>
  <div class="logo"><a href="../index.php">Cozy Coffee Co.</a></div>
  <ul class="nav-links">
    <li><a href="../index.php">Home</a></li>
    <li>
      <a href="index.php" class="active">Menu ▾</a>
      <div class="dropdown">
        <a href="index.php?cat=hot">Hot Coffee</a>
        <a href="index.php?cat=cold">Cold Coffee</a>
        <a href="index.php?cat=pastries">Pastries</a>
      </div>
    </li>
    <li><a href="../contact/index.php">Contact</a></li>
    <li><a href="../cart/index.php">Cart</a></li>
    <li><a href="../login/index.php">Login</a></li>
  </ul>
  <button class="hamburger" aria-label="Menu"><span></span><span></span><span></span></button>
</nav>

<section class="hero">
  <div class="eyebrow">Small Batch · Slow Roasted</div>
  <h1>Warm cups, cozy corners.</h1>
  <p>Freshly roasted coffee and house-made pastries, ready for pickup or delivery.</p>
</section>

<div class="filters">
  <button class="filter-btn active">All</button>
  <button class="filter-btn">Hot Coffee</button>
  <button class="filter-btn">Cold Coffee</button>
  <button class="filter-btn">Pastries</button>
</div>

<div class="menu-grid">

  <div class="item-card">
    <div class="item-image">ETHIOPIAN YIRGACHEFFE</div>
    <div class="item-body">
      <h3>Ethiopian Yirgacheffe</h3>
      <p>Floral, bright, with notes of bergamot and stone fruit.</p>
      <div class="item-footer">
        <span class="price">RM 12.90</span>
        <a href="../details/index.php?id=1"><button class="add-btn">View</button></a>
      </div>
    </div>
  </div>

  <div class="item-card">
    <div class="item-image">HONEY OAT LATTE</div>
    <div class="item-body">
      <h3>Honey Oat Latte</h3>
      <p>Espresso, steamed oat milk, and a drizzle of raw honey.</p>
      <div class="item-footer">
        <span class="price">RM 14.50</span>
        <a href="../details/index.php?id=2"><button class="add-btn">View</button></a>
      </div>
    </div>
  </div>

  <div class="item-card">
    <div class="item-image">ICED SPANISH LATTE</div>
    <div class="item-body">
      <h3>Iced Spanish Latte</h3>
      <p>Sweet condensed milk over a double shot, served over ice.</p>
      <div class="item-footer">
        <span class="price">RM 13.90</span>
        <a href="../details/index.php?id=3"><button class="add-btn">View</button></a>
      </div>
    </div>
  </div>

  <div class="item-card">
    <div class="item-image">BUTTER CROISSANT</div>
    <div class="item-body">
      <h3>Butter Croissant</h3>
      <p>Flaky, all-butter, baked fresh every morning.</p>
      <div class="item-footer">
        <span class="price">RM 7.90</span>
        <a href="../details/index.php?id=4"><button class="add-btn">View</button></a>
      </div>
    </div>
  </div>

  <div class="item-card">
    <div class="item-image">COLD BREW</div>
    <div class="item-body">
      <h3>Signature Cold Brew</h3>
      <p>Steeped 18 hours for a smooth, low-acid finish.</p>
      <div class="item-footer">
        <span class="price">RM 11.50</span>
        <a href="../details/index.php?id=5"><button class="add-btn">View</button></a>
      </div>
    </div>
  </div>

  <div class="item-card">
    <div class="item-image">CINNAMON ROLL</div>
    <div class="item-body">
      <h3>Cinnamon Roll</h3>
      <p>Warm, gooey, topped with a light cream cheese glaze.</p>
      <div class="item-footer">
        <span class="price">RM 8.50</span>
        <a href="../details/index.php?id=6"><button class="add-btn">View</button></a>
      </div>
    </div>
  </div>

</div>

<script>
  const filterBtns = document.querySelectorAll('.filter-btn');
  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      // TODO: connect this to a PHP/MySQL query filtered by category_id
    });
  });

  document.querySelector('.hamburger').addEventListener('click', () => {
    const nav = document.querySelector('.nav-links');
    nav.style.display = nav.style.display === 'flex' ? 'none' : 'flex';
  });
</script>

</body>
</html>