-- ============================================
-- COZY COFFEE CO. — DATABASE SCHEMA
-- Import this file in phpMyAdmin (or run via
-- mysql CLI) to create the database and tables.
-- ============================================

CREATE DATABASE IF NOT EXISTS cozy_coffee_co
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE cozy_coffee_co;

-- ============ ADMINS ============
-- Fixed staff account(s) — NOT self-registered like customers.
-- Seeded below with username 'admin' / password 'admin123'.
CREATE TABLE admins (
  admin_id      INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Password is 'admin123' — hashed with PHP's password_hash() (bcrypt).
-- Change this after first login in a real deployment.
INSERT INTO admins (username, password_hash) VALUES
('admin', '$2b$10$X0Nxo9iKPuKrb5qEPaaS2.7yPiJmwyF.vjN76lrCJwEADfKZwAyk.');

-- ============ CUSTOMER USERS ============
CREATE TABLE users (
  user_id       INT AUTO_INCREMENT PRIMARY KEY,
  fullname      VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============ MENU CATEGORIES ============
CREATE TABLE categories (
  category_id    INT AUTO_INCREMENT PRIMARY KEY,
  category_key   VARCHAR(30)  NOT NULL UNIQUE, -- used in filter buttons / anchors, e.g. 'specialty'
  category_label VARCHAR(100) NOT NULL,        -- shown on page, e.g. '🌟 Specialty Coffee'
  display_order  INT NOT NULL DEFAULT 0
);

INSERT INTO categories (category_key, category_label, display_order) VALUES
('specialty',   '🌟 Specialty Coffee',              1),
('classic',     '☕ Classic Coffee',                 2),
('noncoffein',  '🍃 Non-Coffein',                    3),
('smoothies',   '🍹 Refreshing Smoothies & Sodas',   4),
('mains',       '🍽️ Main Dishes',                    5),
('desserts',    '🍰 Desserts',                        6);

-- ============ MENU ITEMS ============
CREATE TABLE menu_items (
  item_id       INT AUTO_INCREMENT PRIMARY KEY,
  category_id   INT NOT NULL,
  name          VARCHAR(150) NOT NULL,
  description   TEXT,
  price         DECIMAL(6,2) NOT NULL,
  image         VARCHAR(150) DEFAULT NULL,   -- filename inside images/menu/
  display_order INT NOT NULL DEFAULT 0,
  FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
);

INSERT INTO menu_items (category_id, name, description, price, image, display_order) VALUES
(1, 'Ocean Eyes', 'Iced Cafe Latte | Watermelon & Peach | Butterfly Pea Flower infused salted cream.', 14.90, '1.png', 1),
(1, 'Creamy Dreamy', "It's fluffy, creamy, rich & smooth. Strong iced coffee topped with a thick layer of cold cream & a dusting of cocoa powder.", 13.90, '2.png', 2),
(1, 'Peanut Butter Latte', 'Real peanut butter cream with a shot of espresso.', 13.90, '3.png', 3),
(1, 'Coco Loco Latte', 'Barista coconut milk with luxurious creaminess and natural sweetness of imported coconut milk, with dried coconut on the side.', 12.90, '4.png', 4),
(1, 'Australian Iced Coffee', 'Double shot espresso over chilled milk, served with a big scoop of premium vanilla ice-cream.', 13.90, '5.jpeg', 5),
(1, 'Affogato', 'Hot espresso poured over two scoops of velvety premium vanilla ice cream.', 12.90, '6.jpeg', 6),

(2, 'Velvet Flat White', 'Ristretto double shot topped with velvety smooth microfoam. Silky and strong.', 12.90, '7.png', 1),
(2, 'Dirty Latte', 'Hot espresso poured directly over ice-cold fresh milk. Pure contrast in every sip.', 14.90, '8.png', 2),
(2, 'Cozy Cappuccino', 'Rich espresso layered with extra deep, fluffy milk foam and a dusting of dark cocoa powder.', 13.90, '9.png', 3),
(2, 'Piccolo Latte', 'A small but mighty shot of espresso cut with a small amount of steamed milk. Pure coffee flavor!', 11.90, '10.png', 4),
(2, 'Classic Cafe Latte', 'Smooth double shot espresso combined with perfectly steamed fresh milk.', 12.90, '11.png', 5),
(2, 'Midnight Long Black', 'Double shot espresso extracted over hot or iced water for a rich, aromatic crema.', 9.90, '12.png', 6),

(3, 'Houjicha Strawberry', "Niko-Neko's Tsubaki Houjicha | Jasmine Tea | Strawberry Cloud", 15.90, '13.png', 1),
(3, 'Honeycomb Iced Matcha', 'Uji Matcha topped with crunchy homemade honeycomb candy for that sweet crisp bite.', 15.90, '14.png', 2),
(3, 'Sesame Latte', 'Roasted black sesame | Creamy oatmilk — rich, nutty & comforting.', 13.90, '15.png', 3),
(3, 'Salted Apple Elixir', 'Salted green apple | Passion fruit | Mint | Soda water', 14.90, '16.png', 4),
(3, 'Seasalt Dark Choco Spänner', "Callebaut dark iced chocolate, topped with Solace's signature spänner topping", 14.90, '17.png', 5),
(3, 'Babycino', 'Warm frothy steamed milk dusted with dark cocoa powder & a tiny marshmallow topping.', 8.90, '18.png', 6),

(4, 'Morning Traffic', 'Strawberry | Lemon juice | Honey | Kiwi fruit | Yakult', 14.90, '19.png', 1),
(4, 'Pink Passion', 'Dragonfruit | Passionfruit | Pineapple | Honey | Yogurt', 15.90, '20.png', 2),
(4, 'Blue Mango Bliss', 'Sweet mango | Fresh milk | Butterfly pea flower tea', 15.90, '21.png', 3),
(4, 'Butterfly Yuzunade', 'Refreshing yuzu | Crushed orange | Lemon juice | Butterfly pea soda', 13.90, '22.png', 4),

(5, 'Truffle Wild Mushroom Risotto', 'Arborio rice cooked in rich vegetable broth, sautéed wild mushrooms, and black truffle oil.', 19.90, '23.png', 1),
(5, 'Sun-Dried Tomato & Burrata Pasta', 'Al dente linguine tossed in garlic sun-dried tomato pesto, topped with fresh creamy burrata.', 17.90, '24.png', 2),
(5, 'Herbed Searing Salmon Skillet', 'Pan-seared Atlantic salmon fillet on a bed of warm garlic mashed potato and dill cream sauce.', 20.90, '25.png', 3),
(5, 'Smoked Brisket Slider Trio', 'Slow-cooked smoked beef brisket, caramelized onions, and house barbecue glaze in mini brioche buns.', 19.90, '26.png', 4),
(5, 'Cozy Garden Grain Bowl', 'Warm quinoa, roasted sweet potato, edamame, and avocado drizzled with sesame tahini dressing.', 17.90, '27.png', 5),
(5, 'Artisanal Smoked Salmon Avocado Toast', 'Smoked Atlantic salmon, smashed avocado, capers, and poached egg on toasted sourdough.', 24.90, '28.png', 6),
(5, 'Citrus Chicken Waldorf Salad', 'Sous-vide chicken breast, crisp green apples, walnuts, and dried cranberries in a light yogurt dressing.', 15.90, '29.png', 7),
(5, 'Prosciutto & Fig Sourdough Tartine', 'Sliced prosciutto di Parma, fresh figs, whipped ricotta, and a drizzle of balsamic glaze on sourdough.', 16.90, '30.png', 8),

(6, 'Warm Valrhona Molten Lava Cake', 'Rich dark chocolate cake with a gooey molten center, served warm with Madagascar vanilla ice cream.', 9.90, '31.png', 1),
(6, 'Burnt Cinnamon & Apple Crumble', 'Spiced caramelized apples topped with a crunchy butter oat crumble, served hot with warm custard.', 9.90, '32.png', 2),
(6, 'Earl Grey Basque Burnt Cheesecake', 'Creamy burnt cheesecake infused with aromatic Earl Grey tea leaves, served chilled.', 15.90, '33.png', 3),
(6, 'Espresso Misu Tart', 'Butter pastry shell filled with coffee-soaked ladyfingers, velvety mascarpone cream, and cocoa powder.', 11.90, '34.png', 4);

-- ============ CONTACT PAGE INFO (single editable row) ============
CREATE TABLE contact_info (
  id            INT PRIMARY KEY DEFAULT 1,
  address       VARCHAR(255),
  phone         VARCHAR(50),
  email         VARCHAR(150),
  hours         VARCHAR(150),
  map_embed_url TEXT,
  instagram_url VARCHAR(255),
  facebook_url  VARCHAR(255),
  tiktok_url    VARCHAR(255),
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO contact_info (id, address, phone, email, hours, map_embed_url, instagram_url, facebook_url, tiktok_url) VALUES
(1,
 '7, Bandar Sungai Long, 43000 Kajang, Selangor',
 '+60 12-345 6789',
 'cozycoffee@gmail.com',
 'Mon–Sun, 11:00 AM – 5:00 PM',
 'https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d1346.744641350054!2d101.79352459067356!3d3.0400653684064136!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2smy!4v1783592577677!5m2!1sen!2smy',
 '#', '#', '#'
);

-- ============ ABOUT US TEXT (single editable row) ============
CREATE TABLE about_us (
  id          INT PRIMARY KEY DEFAULT 1,
  eyebrow     VARCHAR(100),
  heading     VARCHAR(150),
  body_text   TEXT,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO about_us (id, eyebrow, heading, body_text) VALUES
(1,
 'Our Story',
 'About Cozy Coffee Co.',
 'Cozy Coffee Co. started as a small neighbourhood roastery with one simple goal: serve honest, carefully brewed coffee in a space that feels like home. Every bean is roasted in small batches, every pastry is baked fresh each morning, and every cup is made to order. Whether you''re stopping by for a quiet moment or ordering ahead for pickup, we''re glad you''re here.'
);

-- ============ ORDERS (for future cart/checkout integration) ============
CREATE TABLE orders (
  order_id     INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  order_date   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  total_amount DECIMAL(8,2) NOT NULL,
  status       ENUM('Pending','Preparing','Ready','Completed') DEFAULT 'Pending',
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE order_items (
  order_item_id INT AUTO_INCREMENT PRIMARY KEY,
  order_id      INT NOT NULL,
  item_id       INT NOT NULL,
  quantity      INT NOT NULL DEFAULT 1,
  price_at_order DECIMAL(6,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
  FOREIGN KEY (item_id) REFERENCES menu_items(item_id)
);