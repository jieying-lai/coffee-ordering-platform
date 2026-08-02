<?php
// Start the session to access logged-in user data
session_start();
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

<body class="menu-page">
<nav>
  <div class="logo"><a href="../home/index.php">Cozy Coffee Co.</a></div>
  <ul class="nav-links">
    <li><a href="../home/index.php">Home</a></li>
    <li>
      <a href="../menu/index.php" class="active">Menu ▾</a>
      <div class="dropdown">
        <a href="../menu/index.php?cat=specialty#specialty">Specialty</a>
        <a href="../menu/index.php?cat=classic#classic">Classic Coffee</a>
        <a href="../menu/index.php?cat=noncoffein#noncoffein">Non-Coffein</a>
        <a href="../menu/index.php?cat=smoothies#smoothies">Smoothies &amp; Sodas</a>
        <a href="../menu/index.php?cat=mains#mains">Main Dishes</a>
        <a href="../menu/index.php?cat=desserts#desserts">Desserts</a>
      </div>
    </li>
    <li><a href="../contact/index.php">Contact</a></li>
    <li><a href="../cart/index.php">Cart</a></li>

    <!-- DYNAMIC NAVIGATION LINK -->
    <?php if (isset($_SESSION['user_id'])): ?>
      <!-- Logged In State: Show Username & Profile Dropdown -->
      <li>
        <a href="../profile/index.php"><?php echo htmlspecialchars($_SESSION['username']); ?> ▾</a>
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

<div class="filters">
  <button class="filter-btn active" data-filter="all">All</button>
  <button class="filter-btn" data-filter="specialty">🌟 Specialty</button>
  <button class="filter-btn" data-filter="classic">☕ Classic</button>
  <button class="filter-btn" data-filter="noncoffein">🍃 Non-Coffein</button>
  <button class="filter-btn" data-filter="smoothies">🍹 Smoothies &amp; Sodas</button>
  <button class="filter-btn" data-filter="mains">🍽️ Main Dishes</button>
  <button class="filter-btn" data-filter="desserts">🍰 Desserts</button>
</div>

<!-- ============ COFFEIN & SPECIALTY COFFEE ============ -->
<div class="menu-category" data-category="specialty" id="specialty">
  <h2 class="category-title">🌟 Specialty Coffee</h2>

  <div class="menu-grid">

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/1.png" alt="OCEAN EYES"></div>
      <div class="item-body">
        <h3>Ocean Eyes</h3>
        <p>Iced Cafe Latte | Watermelon &amp; Peach | Butterfly Pea Flower infused salted cream.</p>
        <div class="item-footer">
          <span class="price">RM 14.90</span>
          <a href="../details/index.php?id=1"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/2.png" alt="CREAMY DREAMY"></div>
      <div class="item-body">
        <h3>Creamy Dreamy</h3>
        <p>It's fluffy, creamy, rich &amp; smooth. Strong iced coffee topped with a thick layer of cold cream &amp; a dusting of cocoa powder.</p>
        <div class="item-footer">
          <span class="price">RM 13.90</span>
          <a href="../details/index.php?id=2"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/3.png" alt="PEANUT BUTTER LATTE"></div>
      <div class="item-body">
        <h3>Peanut Butter Latte</h3>
        <p>Real peanut butter cream with a shot of espresso. </p>
        <div class="item-footer">
          <span class="price">RM 13.90</span>
          <a href="../details/index.php?id=3"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/4.png" alt="COCO LOCO LATTE"></div>
      <div class="item-body">
        <h3>Coco Loco Latte</h3>
        <p>Barista coconut milk with luxurious creaminess and natural sweetness of imported coconut milk, with dried coconut on the side.</p>
        <div class="item-footer">
          <span class="price">RM 12.90</span>
          <a href="../details/index.php?id=4"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/5.jpeg" alt="AUSTRALIAN ICED COFFEE"></div>
      <div class="item-body">
        <h3>Australian Iced Coffee</h3>
        <p>Double shot espresso over chilled milk, served with a big scoop of premium vanilla ice-cream.</p>
        <div class="item-footer">
          <span class="price">RM 13.90</span>
          <a href="../details/index.php?id=5"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/6.jpeg" alt="AFFOGATO"></div>
      <div class="item-body">
        <h3>Affogato</h3>
        <p>Hot espresso poured over two scoops of velvety premium vanilla ice cream.</p>
        <div class="item-footer">
          <span class="price">RM 12.90</span>
          <a href="../details/index.php?id=6"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- ============ CLASSIC COFFEE ============ -->
<div class="menu-category" data-category="classic" id="classic">
  <h2 class="category-title">☕ Classic Coffee</h2>

  <div class="menu-grid">

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/7.png" alt="VELVET FLAT WHITE"></div>
      <div class="item-body">
        <h3>Velvet Flat White</h3>
        <p>Ristretto double shot topped with velvety smooth microfoam. Silky and strong.</p>
        <div class="item-footer">
          <span class="price">RM 12.90</span>
          <a href="../details/index.php?id=7"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/8.png" alt="DIRTY LATTE"></div>
      <div class="item-body">
        <h3>Dirty Latte</h3>
        <p>Hot espresso poured directly over ice-cold fresh milk. Pure contrast in every sip.</p>
        <div class="item-footer">
          <span class="price">RM 14.90</span>
          <a href="../details/index.php?id=8"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/9.png" alt="COZY CAPPUCCINO"></div>
      <div class="item-body">
        <h3>Cozy Cappuccino</h3>
        <p>Rich espresso layered with extra deep, fluffy milk foam and a dusting of dark cocoa powder.</p>
        <div class="item-footer">
          <span class="price">RM 13.90</span>
          <a href="../details/index.php?id=9"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/10.png" alt="PICCOLO LATTE"></div>
      <div class="item-body">
        <h3>Piccolo Latte</h3>
        <p>A small but mighty shot of espresso cut with a small amount of steamed milk. Pure coffee flavor!</p>
        <div class="item-footer">
          <span class="price">RM 11.90</span>
          <a href="../details/index.php?id=10"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/11.png" alt="CLASSIC CAFE LATTE"></div>
      <div class="item-body">
        <h3>Classic Cafe Latte</h3>
        <p>Smooth double shot espresso combined with perfectly steamed fresh milk.</p>
        <div class="item-footer">
          <span class="price">RM 12.90</span>
          <a href="../details/index.php?id=11"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/12.png" alt="MIDNIGHT LONG BLACK"></div>
      <div class="item-body">
        <h3>Midnight Long Black</h3>
        <p>Double shot espresso extracted over hot or iced water for a rich, aromatic crema.</p>
        <div class="item-footer">
          <span class="price">RM 9.90</span>
          <a href="../details/index.php?id=12"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- ============ NON-COFFEIN ============ -->
<div class="menu-category" data-category="noncoffein" id="noncoffein">
  <h2 class="category-title">🍃 Non-Coffein</h2>

  <div class="menu-grid">

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/13.png" alt="HOUJICHA STRAWBERRY"></div>
      <div class="item-body">
        <h3>Houjicha Strawberry</h3>
        <p>Niko-Neko's Tsubaki Houjicha | Jasmine Tea | Strawberry Cloud</p>
        <div class="item-footer">
          <span class="price">RM 15.90</span>
          <a href="../details/index.php?id=13"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/14.png" alt="HONEYCOMB ICED MATCHA"></div>
      <div class="item-body">
        <h3>Honeycomb Iced Matcha</h3>
        <p>Uji Matcha topped with crunchy homemade honeycomb candy for that sweet crisp bite.</p>
        <div class="item-footer">
          <span class="price">RM 15.90</span>
          <a href="../details/index.php?id=14"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/15.png" alt="SESAME LATTE"></div>
      <div class="item-body">
        <h3>Sesame Latte</h3>
        <p>Roasted black sesame | Creamy oatmilk — rich, nutty &amp; comforting.</p>
        <div class="item-footer">
          <span class="price">RM 13.90</span>
          <a href="../details/index.php?id=15"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/16.png" alt="SALTED APPLE ELIXIR"></div>
      <div class="item-body">
        <h3>Salted Apple Elixir</h3>
        <p>Salted green apple | Passion fruit | Mint | Soda water</p>
        <div class="item-footer">
          <span class="price">RM 14.90</span>
          <a href="../details/index.php?id=16"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/17.png" alt="SEASALT DARK CHOCO SPÄNNER"></div>
      <div class="item-body">
        <h3>Seasalt Dark Choco Spänner</h3>
        <p>Callebaut dark iced chocolate, topped with Solace's signature spänner topping</p>
        <div class="item-footer">
          <span class="price">RM 14.90</span>
          <a href="../details/index.php?id=17"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/18.png" alt="BABYCINO"></div>
      <div class="item-body">
        <h3>Babycino</h3>
        <p>Warm frothy steamed milk dusted with dark cocoa powder &amp; a tiny marshmallow topping.</p>
        <div class="item-footer">
          <span class="price">RM 8.90</span>
          <a href="../details/index.php?id=18"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- ============ SMOOTHIES & SODAS ============ -->
<div class="menu-category" data-category="smoothies" id="smoothies">
  <h2 class="category-title">🍹 Refreshing Smoothies &amp; Sodas</h2>

  <div class="menu-grid">

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/19.png" alt="MORNING TRAFFIC"></div>
      <div class="item-body">
        <h3>Morning Traffic</h3>
        <p>Strawberry | Lemon juice | Honey | Kiwi fruit | Yakult</p>
        <div class="item-footer">
          <span class="price">RM 14.90</span>
          <a href="../details/index.php?id=19"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/20.png" alt="PINK PASSION"></div>
      <div class="item-body">
        <h3>Pink Passion</h3>
        <p>Dragonfruit | Passionfruit | Pineapple | Honey | Yogurt</p>
        <div class="item-footer">
          <span class="price">RM 15.90</span>
          <a href="../details/index.php?id=20"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/21.png" alt="BLUE MANGO BLISS"></div>
      <div class="item-body">
        <h3>Blue Mango Bliss</h3>
        <p>Sweet mango | Fresh milk | Butterfly pea flower tea</p>
        <div class="item-footer">
          <span class="price">RM 15.90</span>
          <a href="../details/index.php?id=21"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/22.png" alt="BUTTERFLY YUZUNADE"></div>
      <div class="item-body">
        <h3>Butterfly Yuzunade</h3>
        <p>Refreshing yuzu | Crushed orange | Lemon juice | Butterfly pea soda</p>
        <div class="item-footer">
          <span class="price">RM 13.90</span>
          <a href="../details/index.php?id=22"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- ============ MAIN DISHES ============ -->
<div class="menu-category" data-category="mains" id="mains">
  <h2 class="category-title">🍽️ Main Dishes</h2>

  <div class="menu-grid">

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/23.png" alt="TRUFFLE WILD MUSHROOM RISOTTO"></div>
      <div class="item-body">
        <h3>Truffle Wild Mushroom Risotto</h3>
        <p>Arborio rice cooked in rich vegetable broth, sautéed wild mushrooms, and black truffle oil.</p>
        <div class="item-footer">
          <span class="price">RM 19.90</span>
          <a href="../details/index.php?id=23"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/24.png" alt="SUN-DRIED TOMATO & BURRATA PASTA"></div>
      <div class="item-body">
        <h3>Sun-Dried Tomato &amp; Burrata Pasta</h3>
        <p>Al dente linguine tossed in garlic sun-dried tomato pesto, topped with fresh creamy burrata.</p>
        <div class="item-footer">
          <span class="price">RM 17.90</span>
          <a href="../details/index.php?id=24"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/25.png" alt="HERBED SEARING SALMON SKILLET"></div>
      <div class="item-body">
        <h3>Herbed Searing Salmon Skillet</h3>
        <p>Pan-seared Atlantic salmon fillet on a bed of warm garlic mashed potato and dill cream sauce.</p>
        <div class="item-footer">
          <span class="price">RM 20.90</span>
          <a href="../details/index.php?id=25"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/27.png" alt="COZY GARDEN GRAIN BOWL"></div>
      <div class="item-body">
        <h3>Cozy Garden Grain Bowl</h3>
        <p>Warm quinoa, roasted sweet potato, edamame, and avocado drizzled with sesame tahini dressing.</p>
        <div class="item-footer">
          <span class="price">RM 17.90</span>
          <a href="../details/index.php?id=27"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/28.png" alt="ARTISANAL SMOKED SALMON AVOCADO TOAST"></div>
      <div class="item-body">
        <h3>Artisanal Smoked Salmon Avocado Toast</h3>
        <p>Smoked Atlantic salmon, smashed avocado, capers, and poached egg on toasted sourdough.</p>
        <div class="item-footer">
          <span class="price">RM 24.90</span>
          <a href="../details/index.php?id=28"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/29.png" alt="CITRUS CHICKEN WALDORF SALAD"></div>
      <div class="item-body">
        <h3>Citrus Chicken Waldorf Salad</h3>
        <p>Sous-vide chicken breast, crisp green apples, walnuts, and dried cranberries in a light yogurt dressing.</p>
        <div class="item-footer">
          <span class="price">RM 15.90</span>
          <a href="../details/index.php?id=29"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/30.png" alt="PROSCIUTTO & FIG SOURDOUGH TARTINE"></div>
      <div class="item-body">
        <h3>Prosciutto &amp; Fig Sourdough Tartine</h3>
        <p>Sliced prosciutto di Parma, fresh figs, whipped ricotta, and a drizzle of balsamic glaze on sourdough.</p>
        <div class="item-footer">
          <span class="price">RM 16.90</span>
          <a href="../details/index.php?id=30"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- ============ DESSERTS ============ -->
<div class="menu-category" data-category="desserts" id="desserts">
  <h2 class="category-title">🍰 Desserts</h2>

  <div class="menu-grid">

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/31.png" alt="WARM VALRHONA MOLTEN LAVA CAKE"></div>
      <div class="item-body">
        <h3>Warm Valrhona Molten Lava Cake</h3>
        <p>Rich dark chocolate cake with a gooey molten center, served warm with Madagascar vanilla ice cream.</p>
        <div class="item-footer">
          <span class="price">RM 9.90</span>
          <a href="../details/index.php?id=31"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/32.png" alt="BURNT CINNAMON & APPLE CRUMBLE"></div>
      <div class="item-body">
        <h3>Burnt Cinnamon &amp; Apple Crumble</h3>
        <p>Spiced caramelized apples topped with a crunchy butter oat crumble, served hot with warm custard.</p>
        <div class="item-footer">
          <span class="price">RM 9.90</span>
          <a href="../details/index.php?id=32"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/33.png" alt="EARL GREY BASQUE BURNT CHEESECAKE"></div>
      <div class="item-body">
        <h3>Earl Grey Basque Burnt Cheesecake</h3>
        <p>Creamy burnt cheesecake infused with aromatic Earl Grey tea leaves, served chilled.</p>
        <div class="item-footer">
          <span class="price">RM 15.90</span>
          <a href="../details/index.php?id=33"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

    <div class="item-card">
      <div class="item-image"><img src="../images/menu/34.png" alt="ESPRESSO MISU TART"></div>
      <div class="item-body">
        <h3>Espresso Misu Tart</h3>
        <p>Butter pastry shell filled with coffee-soaked ladyfingers, velvety mascarpone cream, and cocoa powder.</p>
        <div class="item-footer">
          <span class="price">RM 11.90</span>
          <a href="../details/index.php?id=34"><button class="add-btn">View</button></a>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
  // Category filter — shows/hides whole .menu-category blocks.
  // TODO: once PHP/MySQL is connected, replace this with a real query
  // filtered by category_id instead of just toggling visibility.
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