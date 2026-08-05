-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3308
-- Generation Time: Aug 05, 2026 at 02:04 PM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cozy_coffee_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `about_us`
--

DROP TABLE IF EXISTS `about_us`;
CREATE TABLE IF NOT EXISTS `about_us` (
  `id` int NOT NULL DEFAULT '1',
  `eyebrow` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `heading` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `about_us`
--

INSERT INTO `about_us` (`id`, `eyebrow`, `heading`, `body_text`, `updated_at`) VALUES
(1, 'Our Story', 'About Cozy Coffee Co.', 'Cozy Coffee Co. started as a small neighbourhood roastery with one simple goal: serve honest, carefully brewed coffee in a space that feels like home. Every bean is roasted in small batches, every pastry is baked fresh each morning, and every cup is made to order. Whether you\'re stopping by for a quiet moment or ordering ahead for pickup, we\'re glad you\'re here.', '2026-08-05 07:26:53');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `password_hash`, `created_at`) VALUES
(1, 'admin', '$2b$10$X0Nxo9iKPuKrb5qEPaaS2.7yPiJmwyF.vjN76lrCJwEADfKZwAyk.', '2026-08-05 07:26:52');

-- --------------------------------------------------------

--
-- Table structure for table `blog_photos`
--

DROP TABLE IF EXISTS `blog_photos`;
CREATE TABLE IF NOT EXISTS `blog_photos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `post_id` int NOT NULL,
  `image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blog_photos`
--

INSERT INTO `blog_photos` (`id`, `post_id`, `image_path`, `created_at`) VALUES
(1, 3, 'uploads/blog/blog_2_1785922053_0_7fe398.webp', '2026-08-05 09:27:33'),
(2, 4, 'uploads/blog/blog_2_1785922484_0_d7d5be.png', '2026-08-05 09:34:44'),
(3, 4, 'uploads/blog/blog_2_1785922484_1_8f9132.jpg', '2026-08-05 09:34:44'),
(4, 4, 'uploads/blog/blog_2_1785922484_2_1f077c.jpg', '2026-08-05 09:34:44');

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

DROP TABLE IF EXISTS `blog_posts`;
CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `ordered_item` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mood` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blog_posts`
--

INSERT INTO `blog_posts` (`id`, `user_id`, `ordered_item`, `mood`, `description`, `created_at`) VALUES
(1, 2, '', 'harmony', '', '2026-08-05 17:13:44'),
(2, 2, 'Truffle Wild Mushroom Risotto', 'Happy', 'This Cozy Coffee Co. enviroment is very nice, dont have the smell of coffee will appear on your clothes after stay there 2 hours', '2026-08-05 17:16:01'),
(3, 2, '', 'Relaxed', '', '2026-08-05 17:27:33'),
(4, 2, 'Dirty Latte', 'Energized, Cozy, Grateful', 'best drink ever', '2026-08-05 17:34:44');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `category_key` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_label` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_key` (`category_key`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_key`, `category_label`, `display_order`) VALUES
(1, 'specialty', '🌟 Specialty Coffee', 1),
(2, 'classic', '☕ Classic Coffee', 2),
(3, 'noncoffein', '🍃 Non-Coffein', 3),
(4, 'smoothies', '🍹 Refreshing Smoothies & Sodas', 4),
(5, 'mains', '🍽️ Main Dishes', 5),
(6, 'desserts', '🍰 Desserts', 6);

-- --------------------------------------------------------

--
-- Table structure for table `contact_info`
--

DROP TABLE IF EXISTS `contact_info`;
CREATE TABLE IF NOT EXISTS `contact_info` (
  `id` int NOT NULL DEFAULT '1',
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hours` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `map_embed_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `instagram_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facebook_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tiktok_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_info`
--

INSERT INTO `contact_info` (`id`, `address`, `phone`, `email`, `hours`, `map_embed_url`, `instagram_url`, `facebook_url`, `tiktok_url`, `updated_at`) VALUES
(1, '7, Bandar Sungai Long, 43000 Kajang, Selangor', '+60 12-345 6789', 'cozycoffee@gmail.com', 'Mon–Sun, 11:00 AM – 5:00 PM', 'https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d1346.744641350054!2d101.79352459067356!3d3.0400653684064136!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2smy!4v1783592577677!5m2!1sen!2smy', '#', '#', '#', '2026-08-05 07:26:53');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

DROP TABLE IF EXISTS `menu_items`;
CREATE TABLE IF NOT EXISTS `menu_items` (
  `item_id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `price` decimal(6,2) NOT NULL,
  `image` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`),
  KEY `category_id` (`category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`item_id`, `category_id`, `name`, `description`, `price`, `image`, `display_order`) VALUES
(1, 1, 'Ocean Eyes', 'Iced Cafe Latte | Watermelon & Peach | Butterfly Pea Flower infused salted cream.', 14.90, '1.png', 1),
(2, 1, 'Creamy Dreamy', 'It\'s fluffy, creamy, rich & smooth. Strong iced coffee topped with a thick layer of cold cream & a dusting of cocoa powder.', 13.90, '2.png', 2),
(3, 1, 'Peanut Butter Latte', 'Real peanut butter cream with a shot of espresso.', 13.90, '3.png', 3),
(4, 1, 'Coco Loco Latte', 'Barista coconut milk with luxurious creaminess and natural sweetness of imported coconut milk, with dried coconut on the side.', 12.90, '4.png', 4),
(5, 1, 'Australian Iced Coffee', 'Double shot espresso over chilled milk, served with a big scoop of premium vanilla ice-cream.', 13.90, '5.jpeg', 5),
(6, 1, 'Affogato', 'Hot espresso poured over two scoops of velvety premium vanilla ice cream.', 12.90, '6.jpeg', 6),
(7, 2, 'Velvet Flat White', 'Ristretto double shot topped with velvety smooth microfoam. Silky and strong.', 12.90, '7.png', 1),
(8, 2, 'Dirty Latte', 'Hot espresso poured directly over ice-cold fresh milk. Pure contrast in every sip.', 14.90, '8.png', 2),
(9, 2, 'Cozy Cappuccino', 'Rich espresso layered with extra deep, fluffy milk foam and a dusting of dark cocoa powder.', 13.90, '9.png', 3),
(10, 2, 'Piccolo Latte', 'A small but mighty shot of espresso cut with a small amount of steamed milk. Pure coffee flavor!', 11.90, '10.png', 4),
(11, 2, 'Classic Cafe Latte', 'Smooth double shot espresso combined with perfectly steamed fresh milk.', 12.90, '11.png', 5),
(12, 2, 'Midnight Long Black', 'Double shot espresso extracted over hot or iced water for a rich, aromatic crema.', 9.90, '12.png', 6),
(13, 3, 'Houjicha Strawberry', 'Niko-Neko\'s Tsubaki Houjicha | Jasmine Tea | Strawberry Cloud', 15.90, '13.png', 1),
(14, 3, 'Honeycomb Iced Matcha', 'Uji Matcha topped with crunchy homemade honeycomb candy for that sweet crisp bite.', 15.90, '14.png', 2),
(15, 3, 'Sesame Latte', 'Roasted black sesame | Creamy oatmilk — rich, nutty & comforting.', 13.90, '15.png', 3),
(16, 3, 'Salted Apple Elixir', 'Salted green apple | Passion fruit | Mint | Soda water', 14.90, '16.png', 4),
(17, 3, 'Seasalt Dark Choco Spänner', 'Callebaut dark iced chocolate, topped with Solace\'s signature spänner topping', 14.90, '17.png', 5),
(18, 3, 'Babycino', 'Warm frothy steamed milk dusted with dark cocoa powder & a tiny marshmallow topping.', 8.90, '18.png', 6),
(19, 4, 'Morning Traffic', 'Strawberry | Lemon juice | Honey | Kiwi fruit | Yakult', 14.90, '19.png', 1),
(20, 4, 'Pink Passion', 'Dragonfruit | Passionfruit | Pineapple | Honey | Yogurt', 15.90, '20.png', 2),
(21, 4, 'Blue Mango Bliss', 'Sweet mango | Fresh milk | Butterfly pea flower tea', 15.90, '21.png', 3),
(22, 4, 'Butterfly Yuzunade', 'Refreshing yuzu | Crushed orange | Lemon juice | Butterfly pea soda', 13.90, '22.png', 4),
(23, 5, 'Truffle Wild Mushroom Risotto', 'Arborio rice cooked in rich vegetable broth, sautéed wild mushrooms, and black truffle oil.', 19.90, '23.png', 1),
(24, 5, 'Sun-Dried Tomato & Burrata Pasta', 'Al dente linguine tossed in garlic sun-dried tomato pesto, topped with fresh creamy burrata.', 17.90, '24.png', 2),
(25, 5, 'Herbed Searing Salmon Skillet', 'Pan-seared Atlantic salmon fillet on a bed of warm garlic mashed potato and dill cream sauce.', 20.90, '25.png', 3),
(26, 5, 'Smoked Brisket Slider Trio', 'Slow-cooked smoked beef brisket, caramelized onions, and house barbecue glaze in mini brioche buns.', 19.90, '26.png', 4),
(27, 5, 'Cozy Garden Grain Bowl', 'Warm quinoa, roasted sweet potato, edamame, and avocado drizzled with sesame tahini dressing.', 17.90, '27.png', 5),
(28, 5, 'Artisanal Smoked Salmon Avocado Toast', 'Smoked Atlantic salmon, smashed avocado, capers, and poached egg on toasted sourdough.', 24.90, '28.png', 6),
(29, 5, 'Citrus Chicken Waldorf Salad', 'Sous-vide chicken breast, crisp green apples, walnuts, and dried cranberries in a light yogurt dressing.', 15.90, '29.png', 7),
(30, 5, 'Prosciutto & Fig Sourdough Tartine', 'Sliced prosciutto di Parma, fresh figs, whipped ricotta, and a drizzle of balsamic glaze on sourdough.', 16.90, '30.png', 8),
(31, 6, 'Warm Valrhona Molten Lava Cake', 'Rich dark chocolate cake with a gooey molten center, served warm with Madagascar vanilla ice cream.', 9.90, '31.png', 1),
(32, 6, 'Burnt Cinnamon & Apple Crumble', 'Spiced caramelized apples topped with a crunchy butter oat crumble, served hot with warm custard.', 9.90, '32.png', 2),
(33, 6, 'Earl Grey Basque Burnt Cheesecake', 'Creamy burnt cheesecake infused with aromatic Earl Grey tea leaves, served chilled.', 15.90, '33.png', 3),
(34, 6, 'Espresso Misu Tart', 'Butter pastry shell filled with coffee-soaked ladyfingers, velvety mascarpone cream, and cocoa powder.', 11.90, '34.png', 4);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `order_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `total_amount` decimal(8,2) NOT NULL,
  `status` enum('Pending','Preparing','Ready','Completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  PRIMARY KEY (`order_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `order_item_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `item_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `price_at_order` decimal(6,2) NOT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `item_id` (`item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `birthday` date DEFAULT NULL,
  `gender` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_pic` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'default.png',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `username`, `password`, `created_at`, `birthday`, `gender`, `phone`, `profile_pic`) VALUES
(1, 'Jie Ying', 'jieying47@1utar.my', 'laijieying', '$2y$10$kNkzG8Ab1FuWgfVbtbz7P.aGvTAKgdEFHsuzrq5b71TtwwiRVbwxy', '2026-08-01 21:32:31', '2006-04-07', 'Female', NULL, 'user_1_1785652687.jpg'),
(2, 'Chok Shi Ying', 'chokshiying06@gmail.com', 'yingchok', '$2y$10$Amklq4awtRbRlrfNk82VzuzhFOP1gJvuhvZIKXpXmu4k.nj.h2tKm', '2026-08-05 07:57:54', NULL, NULL, NULL, 'user_2_1785922625.jpg');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
