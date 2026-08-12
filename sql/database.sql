-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3308
-- Generation Time: Aug 12, 2026 at 04:15 PM
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
  `is_hidden` tinyint(1) DEFAULT '0',
  `is_deleted` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blog_posts`
--

INSERT INTO `blog_posts` (`id`, `user_id`, `ordered_item`, `mood`, `description`, `created_at`, `is_hidden`, `is_deleted`) VALUES
(1, 2, '', 'harmony', '', '2026-08-05 17:13:44', 1, 0),
(2, 2, 'Truffle Wild Mushroom Risotto', 'Happy', 'This Cozy Coffee Co. enviroment is very nice, dont have the smell of coffee will appear on your clothes after stay there 2 hours', '2026-08-05 17:16:01', 0, 0),
(3, 2, '', 'Relaxed', '', '2026-08-05 17:27:33', 0, 0),
(4, 2, 'Dirty Latte', 'Energized, Cozy, Grateful', 'best drink ever', '2026-08-05 17:34:44', 0, 0);

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
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `sender_type` enum('user','admin') NOT NULL DEFAULT 'user',
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `user_id`, `sender_type`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 'user', 'hi', 0, '2026-08-13 00:13:31');

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
(1, '7, Bandar Sungai Long, 43000 Kajang, Selangor', '+60 12-345 6789', 'cozycoffee@gmail.com', 'Mon??un, 11:00 AM ??5:00 PM', 'https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d1346.744641350054!2d101.79352459067356!3d3.0400653684064136!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2smy!4v1783592577677!5m2!1sen!2smy', '#', '#', '#', '2026-08-05 07:26:53');

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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`item_id`),
  KEY `category_id` (`category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`item_id`, `category_id`, `name`, `description`, `price`, `image`, `display_order`, `created_at`) VALUES
(2, 1, 'Einspanner', 'Rich espresso or cold brew topped with a thick, velvety layer of sweet cold cream and a dusting of cocoa powder.\r\n\r\n3:1 parts coffee to cold cream\r\n\r\n~180??10 kcal\r\n\r\nSize: 12 oz (350 ml)', 13.90, '1785944926_2818b37d.jpeg', 2, '2026-08-05 14:15:03'),
(3, 1, 'Peanut Butter Latte', 'Smooth espresso combined with steamed milk and rich, creamy peanut butter.\r\n\r\n1:3 parts espresso to steamed milk (plus ~2 tbsp peanut butter cream)\r\n\r\n~280??20 kcal\r\n\r\nSize: 12 oz (350 ml)', 13.90, '1785939485_82f304b8.png', 3, '2026-08-05 14:15:03'),
(4, 2, 'Coconut Latte', 'Rich espresso blended with creamy imported barista coconut milk, served with toasted dried coconut on the side.\r\n\r\n1:3 parts espresso to barista coconut milk\r\n\r\n~210??40 kcal\r\n\r\nSize: 12 oz (350 ml)', 12.90, '1785944899_96742b0d.jpeg', 4, '2026-08-05 14:15:03'),
(5, 1, 'Australian Iced Coffee', 'Double shot espresso poured over chilled milk, topped with a scoop of premium vanilla ice cream and whipped cream.\r\n\r\n1:2:1 parts espresso, chilled milk, and vanilla ice cream\r\n\r\n~280??20 kcal\r\n\r\nSize: 12 oz (350 ml)', 13.90, '1785939552_1e50d81b.png', 5, '2026-08-05 14:15:03'),
(6, 1, 'Affogato', 'A scoop of creamy vanilla ice cream drowned in a hot shot of freshly pulled espresso.\r\n\r\n1:1 ratio (1 shot espresso to 1 scoop ice cream)\r\n\r\n~140??60 kcal\r\n\r\nSize: 4 oz (120 ml)', 12.90, '1785939576_0449163b.jpeg', 6, '2026-08-05 14:15:03'),
(8, 2, 'Dirty Latte', 'Cold milk base topped with a hot espresso shot, creating a striking visually layered effect and contrast of temperatures.\r\n\r\n1:2 parts espresso to cold milk\r\n\r\n~100??20 kcal\r\n\r\nSize: 5?? oz (150??80 ml)', 14.90, '1785944949_d183b6ca.jpeg', 2, '2026-08-05 14:15:03'),
(10, 2, 'Piccolo Latte', 'Ristretto shot topped with warm steamed milk and a light layer of foam for a strong espresso punch.\r\n\r\n1:2 parts ristretto to steamed milk\r\n\r\n~45??0 kcal\r\n\r\nSize: 4 oz (120 ml)', 11.90, '1785944839_fd4cf106.jpeg', 4, '2026-08-05 14:15:03'),
(13, 3, 'Houjicha Strawberry', 'Roasted Niko-Neko Tsubaki Houjicha layered with floral jasmine tea and crowned with a light, fruity strawberry cloud foam.\r\n\r\n1:1:1 parts houjicha to jasmine tea to strawberry cloud foam\r\n\r\n~150??90 kcal\r\n\r\nSize: 12 oz (350 ml)', 15.90, '1785945826_03e1df6e.webp', 1, '2026-08-05 14:15:03'),
(14, 3, 'Honeycomb Iced Matcha', 'Smooth Uji matcha iced base topped with crunchy homemade honeycomb candy, delivering a sweet crisp bite to balance the rich green tea flavor.\r\n\r\n1:3 parts Uji matcha base to milk, topped with honeycomb crunch\r\n\r\n~160??00 kcal\r\n\r\nSize: 16 oz (480 ml)', 15.90, '1785939453_4868ef2c.png', 2, '2026-08-05 14:15:03'),
(15, 3, 'Sesame Latte', 'Nutty blend of deeply roasted black sesame paste shaken or stirred into rich, creamy oat milk for a comforting, velvety treat.\r\n\r\n1:3 parts roasted black sesame paste to oat milk\r\n\r\n~180??20 kcal\r\n\r\nSize: 12 oz (350 ml)', 13.90, '1785940171_b9a46497.png', 3, '2026-08-05 14:15:03'),
(16, 3, 'Salted Apple Elixir', 'Crisp salted green apple and tangy passion fruit shaken with fresh mint, finished with crisp soda water for a bright, refreshing sparkle.\r\n\r\n1:2 parts fruit-mint elixir base to soda water\r\n\r\n~90??20 kcal\r\n\r\nSize: 16 oz (480 ml)', 14.90, '1785945706_cc78cb04.jpeg', 4, '2026-08-05 14:15:03'),
(17, 3, 'Seasalt Dark Choco Sp?nner', 'Decadent iced Callebaut dark chocolate poured over ice, crowned with sp?nner cream topping.\r\n\r\n1:2 parts iced dark chocolate base to signature sp?nner cream\r\n\r\n~240??80 kcal\r\n\r\nSize: 12 oz (350 ml)', 14.90, '1785940066_4a7ba797.png', 5, '2026-08-05 14:15:03'),
(18, 3, 'Babycino', 'Warm, frothed milk served in a mini espresso cup, topped with a dusting of cocoa powder for a kid-friendly cafe experience.\r\n\r\n1:0 parts warm frothed milk (caffeine-free)\r\n\r\n~30??5 kcal\r\n\r\nSize: 3 oz (90 ml)', 8.90, '1785945576_c6ef0025.webp', 6, '2026-08-05 14:15:03'),
(19, 4, 'Morning Traffic', 'Tangy and refreshing fusion of sweet strawberries, tart kiwi, and zesty lemon juice, sweetened with honey and blended with probiotic Yakult for a crisp, fruity lift.\r\n\r\n1:2 parts strawberry-kiwi blend to lemon-honey Yakult\r\n\r\n~130 kcal\r\n\r\nSize: 12 oz (350 ml)', 14.90, '1785940089_fd95928b.png', 1, '2026-08-05 14:15:03'),
(20, 4, 'Pink Passion', 'Vibrant, tropical blend of dragonfruit, tangy passionfruit, and sweet pineapple, whipped together with honey and creamy yogurt for a smooth, pitaya-pink punch.\r\n\r\n1:2 parts fruit blend to honey-yogurt base\r\n\r\n~220 kcal\r\n\r\nSize: 16 oz (480 ml)', 15.90, '1785939412_d4118e26.png', 2, '2026-08-05 14:15:03'),
(21, 4, 'Blue Mango Bliss', 'Luscious layers of sweet mango puree and fresh milk topped with vibrant butterfly pea flower tea for a stunning, tropical indigo-and-gold treat.\r\n\r\n1:1:1 parts mango puree to fresh milk to butterfly pea flower tea\r\n\r\n~160 kcal\r\n\r\nSize: 12 oz (350 ml)', 15.90, '1785940115_096149c7.png', 3, '2026-08-05 14:15:03'),
(22, 4, 'Butterfly Yuzunade', 'Zesty layer of citrusy yuzu, crushed orange, and tart lemon juice topped with vibrant, color-changing butterfly pea soda for an enchanting sparkle.\r\n\r\n1:2 parts citrus blend to butterfly pea soda\r\n\r\n~100 kcal\r\n\r\nSize: 12 oz (350 ml)', 13.90, '1785940132_b09a9c8a.png', 4, '2026-08-05 14:15:03'),
(24, 5, 'Sun-Dried Tomato & Burrata Pasta', 'Pan-seared salmon fillet encrusted with aromatic fresh herbs, served alongside buttery mashed potatoes and roasted vegetables with a squeeze of fresh lemon.\r\n\r\nSalmon Fillet | Mashed Potatoes | Fresh Herbs | Butter | Garlic | Lemon | Seasonal Vegetables\r\n\r\n~520??40 kcal', 17.90, '1785947299_bd4d7386.jpeg', 2, '2026-08-05 14:15:03'),
(25, 5, 'Herbed Searing Salmon Skillet', 'Pan-seared salmon fillet encrusted with aromatic fresh herbs, served alongside buttery mashed potatoes and roasted vegetables with a squeeze of fresh lemon.\r\n\r\nSalmon Fillet | Mashed Potatoes | Fresh Herbs | Butter | Garlic | Lemon | Seasonal Vegetables\r\n\r\n~520??40 kcal', 20.90, '1785947244_2b8e9434.jpeg', 3, '2026-08-05 14:15:03'),
(28, 5, 'Artisanal Smoked Salmon Avocado Toast', 'Thinly sliced smoked salmon layered over creamy mashed avocado, capers, and fresh dill on a toasted slice of sourdough bread.\r\n\r\nSourdough Bread | Smoked Salmon | Avocado | Capers | Fresh Dill\r\n\r\n~340??40 kcal', 24.90, '1785947149_7e817863.jpeg', 6, '2026-08-05 14:15:03'),
(30, 5, 'Prosciutto & Fig Sourdough Tartine', 'Salty, delicate prosciutto paired with sweet fresh figs, creamy ricotta, and a drizzle of honey on toasted artisan sourdough.\r\n\r\nSourdough Bread | Prosciutto | Fresh Figs | Ricotta Cheese | Honey | Arugula\r\n\r\n~320??20 kcal', 16.90, '1785947111_304fb7b9.jpeg', 8, '2026-08-05 14:15:03'),
(31, 6, 'Warm Valrhona Molten Lava Cake', 'Rich, decadent chocolate cake made with premium Valrhona dark chocolate, featuring a soft exterior and a warm, flowing molten center.\r\n\r\nValrhona Dark Chocolate | Butter | Eggs | Sugar | Flour | Cocoa Powder | Vanilla Extract\r\n\r\n~380??80 kcal', 9.90, '1785947765_b52ad3ee.jpeg', 1, '2026-08-05 14:15:03'),
(32, 6, 'Burnt Cinnamon & Apple Crumble', 'Warm, tender apples baked with a dark caramelization and ground cinnamon, covered with a crisp, buttery oat crumble topping.\r\n\r\nApples | Oats | Butter | Cinnamon | Brown Sugar | Flour | Vanilla Extract\r\n\r\n~310??10 kcal', 9.90, '1785947731_72324287.jpeg', 2, '2026-08-05 14:15:03'),
(33, 6, 'Earl Grey Basque Burnt Cheesecake', 'Smooth, velvety Basque-style cheesecake infused with fragrant Earl Grey tea, baked to a caramelized, deeply scorched top with a creamy center.\r\n\r\nCream Cheese | Heavy Cream | Earl Grey Tea | Sugar | Eggs | Flour | Vanilla Extract\r\n\r\n~360??60 kcal', 15.90, '1785947700_9d5e52b0.jpeg', 3, '2026-08-05 14:15:03'),
(34, 6, 'Espresso Misu Tart', 'Rich espresso-infused mascarpone cream layered over a dark chocolate ganache, set inside a crisp pastry shell and dusted with cocoa powder.\r\n\r\nPastry Shell | Mascarpone Cheese | Espresso | Dark Chocolate | Heavy Cream | Cocoa Powder | Sugar\r\n\r\n~340??40 kcal', 11.90, '1785947671_88ad2d29.jpg', 4, '2026-08-05 14:15:03'),
(35, 1, 'Caramel Macchiato', 'Freshly steamed milk marked with espresso shots and sweetened with vanilla syrup, finished with a caramel drizzle.\r\n\r\n1:3 parts espresso to steamed milk (plus ~2 pumps vanilla syrup & caramel drizzle)\r\n\r\n~190??20 kcal\r\n\r\nSize: 12 oz (350 ml)', 14.90, '1785949075_638e698a.jpeg', 0, '2026-08-03 15:08:23'),
(36, 1, 'Hazelnut Latte', 'Espresso combined with smooth steamed milk and sweet, nutty hazelnut syrup.\r\n\r\n1:3 parts espresso to steamed milk (plus ~2 pumps hazelnut syrup)\r\n\r\n~190??20 kcal\r\n\r\nSize: 12 oz (350 ml)', 13.90, '1785943081_e216d810.jpeg', 0, '2026-06-26 15:08:23'),
(37, 1, 'Mocha Delight', 'Rich espresso blended with chocolate syrup and steamed milk, topped with whipped cream and a drizzle of chocolate sauce.\r\n\r\n1:3 parts espresso to steamed milk (plus ~2 tbsp chocolate syrup)\r\n\r\n~290??20 kcal\r\n\r\nSize: 12 oz (350 ml)', 14.50, '1785943060_b29834c8.jpeg', 0, '2026-06-26 15:08:23'),
(38, 1, 'Vanilla Bean Latte', 'Espresso blended with steamed milk and sweet vanilla bean paste or syrup, infused with real vanilla specks.\r\n\r\n1:3 parts espresso to steamed milk (plus ~2 tbsp vanilla bean syrup/paste)\r\n\r\n~200??30 kcal\r\n\r\nSize: 12 oz (350 ml)', 13.50, '1785943036_ae1279fa.jpeg', 0, '2026-06-26 15:08:23'),
(39, 1, 'Salted Caramel Cappuccino', 'Bold cappuccino topped with salted caramel foam. Espresso combined with rich caramel sauce and steamed milk, topped with a thick layer of milk foam and a pinch of sea salt.\r\n\r\n1:1:1 parts espresso, steamed milk, and milk foam (plus ~1.5 tbsp caramel sauce & sea salt)\r\n\r\n~180??10 kcal\r\n\r\nSize: 8 oz (240 ml)', 15.00, '1785943018_2e6489c6.jpg', 0, '2026-08-02 15:08:23'),
(40, 1, 'Pumpkin Spice Latte', 'Seasonal favorite with warm autumn spices. Espresso blended with steamed milk, sweet pumpkin spice sauce, and warm spices, topped with whipped cream and pumpkin pie spice.\r\n\r\n1:3 parts espresso to steamed milk (plus ~2 tbsp pumpkin spice sauce)\r\n\r\n~300??30 kcal\r\n\r\nSize: 12 oz (350 ml)', 15.50, '1785942993_f4bbbce5.jpeg', 0, '2026-06-26 15:08:23'),
(41, 1, 'Honey Cinnamon Latte', 'Espresso blended with steamed milk, natural honey, and ground cinnamon.\r\n\r\n1:3 parts espresso to steamed milk (plus ~1 tbsp honey & a dash of cinnamon)\r\n\r\n~200??30 kcal\r\n\r\nSize: 12 oz (350 ml)', 14.00, '1785942975_5d230054.jpeg', 0, '2026-06-26 15:08:23'),
(42, 1, 'White Chocolate Mocha', 'Rich espresso combined with sweet white chocolate sauce and steamed milk, topped with sweetened whipped cream.\r\n\r\n1:3 parts espresso to steamed milk (plus ~2 tbsp white chocolate sauce)\r\n\r\n~400??30 kcal\r\n\r\nSize: 12 oz (350 ml)', 15.20, '1785942951_6db67de1.jpeg', 0, '2026-06-26 15:08:23'),
(43, 1, 'Lavender Honey Latte', 'Floral lavender meets golden honey and espresso. Smooth espresso blended with steamed milk, natural honey, and subtle floral lavender syrup.\r\n\r\n1:3 parts espresso to steamed milk (plus ~1 tbsp honey & 2 pumps lavender syrup)\r\n\r\n~210??40 kcal\r\n\r\nSize: 12 oz (350 ml)', 15.80, '1785942926_c92101fc.jpeg', 0, '2026-06-26 15:08:23'),
(44, 1, 'Irish Cream Cold Brew', 'Smooth cold-brew coffee sweetened with Irish cream syrup over ice, topped with a layer of vanilla cream cold foam and a sprinkle of cocoa powder.\r\n\r\n4:1 parts cold brew coffee to cold foam (plus ~2 pumps Irish cream syrup)\r\n\r\n~190??00 kcal\r\n\r\nSize: 16 oz (475 ml)', 14.70, '1785942901_fdc9d818.jpeg', 0, '2026-06-26 15:08:23'),
(45, 2, 'Espresso', 'Concentrated shot under high pressure. Rich crema on top. Intense, full-bodied flavor.\r\n\r\n1:0 parts espresso (pure shot)\r\n\r\n~5??0 kcal\r\n\r\nSize: 1 oz (30 ml)', 8.00, '1785944785_4362788e.jpeg', 0, '2026-06-26 15:08:23'),
(46, 2, 'Americano', 'Espresso first, then water. Dilutes crema. Smooth taste.\r\nRich espresso diluted with hot water, producing a smooth, full-bodied cup with a light crema layer.\r\n\r\n2:1 parts hot water to espresso\r\n\r\n~5??0 kcal\r\n\r\nSize: 12 oz (350 ml)', 9.00, '1785944692_91327c53.jpeg', 0, '2026-06-26 15:08:23'),
(47, 2, 'Cappuccino', 'Equal parts espresso, warm steamed milk, and a thick layer of airy milk foam.\r\n\r\n1:1:1 parts espresso, steamed milk, and milk foam\r\n\r\n~110??30 kcal\r\n\r\nSize: 8 oz (240 ml)', 11.00, '1785944359_886c1155.jpeg', 0, '2026-06-26 15:08:23'),
(48, 2, 'Caf? Latte', 'Espresso with generous steamed milk and topped with a light layer of foam.\r\n\r\n1:3 parts espresso to steamed milk\r\n\r\n~120??50 kcal\r\n\r\nSize: 12 oz (350 ml)', 11.50, '1785944274_1ca028e2.jpg', 0, '2026-06-26 15:08:23'),
(49, 2, 'Flat White', 'Double shot of espresso combined with microfoamed steamed milk for a velvety, smooth texture with a thin layer of fine foam.\r\n\r\n1:2 parts espresso to microfoamed milk\r\n\r\n~120??40 kcal\r\n\r\nSize: 6 oz (180 ml)', 12.00, '1785944230_d45e6877.jpeg', 0, '2026-08-04 15:08:23'),
(50, 2, 'Long Black', 'Water first, then espresso. Retains crema. Stronger taste. Double shot of espresso poured directly over hot water, preserving a rich layer of crema.\r\n\r\n2:1 parts hot water to double espresso shot\r\n\r\n~5??0 kcal\r\n\r\nSize: 6 oz (180 ml)', 9.50, '1785943347_a6ba0057.jpeg', 0, '2026-06-26 15:08:23'),
(51, 2, 'Caf? au Lait', 'Freshly brewed drip coffee combined with an equal portion of warm steamed milk.\r\n\r\n1:1 parts drip coffee to steamed milk\r\n\r\n~70??0 kcal\r\n\r\nSize: 8 oz (240 ml)', 10.00, '1785943285_c326b067.jpeg', 0, '2026-06-26 15:08:23'),
(52, 2, 'Cortado', 'Equal parts rich espresso and warm steamed milk to reduce acidity without cutting the bold coffee flavor.\r\n\r\n1:1 parts espresso to steamed milk\r\n\r\n~35??0 kcal\r\n\r\nSize: 4 oz (120 ml)', 11.20, '1785943257_843997cb.jpeg', 0, '2026-06-26 15:08:23'),
(53, 2, 'Doppio', 'Two rich shots of pure espresso extracted under high pressure for double the depth and flavor.\r\n\r\n1:0 ratio (pure double shot espresso, no milk or water added)\r\n\r\n~5??0 kcal\r\n\r\nSize: 2 oz (60 ml)', 9.80, '1785943174_72bf13a1.jpeg', 0, '2026-06-26 15:08:23'),
(54, 2, 'Macchiato', 'A shot of rich espresso \"stained\" with a small dollop of steamed milk foam.\r\n\r\n4:1 parts espresso to milk foam\r\n\r\n~10??5 kcal\r\n\r\nSize: 2 oz (60 ml)', 10.50, '1785943140_572298d5.png', 0, '2026-06-26 15:08:23'),
(55, 3, 'English Breakfast Tea', 'Robust, full-bodied blend of black teas with a rich malt flavor, perfect with a splash of milk.\r\n\r\n1:0 parts pure black tea steep\r\n\r\n~0?? kcal\r\n\r\nSize: 12 oz (350 ml)', 8.50, '1785945504_93d4a73f.jpeg', 0, '2026-06-26 15:08:23'),
(56, 3, 'Chamomile Tea', 'Gentle herbal infusion made from dried chamomile flowers, delivering delicate floral notes and a naturally calming, caffeine-free sip.\r\n\r\n1:0 parts pure chamomile flower steep\r\n\r\n~0?? kcal\r\n\r\nSize: 12 oz (350 ml)', 8.50, '1785945455_d385e0cf.jpg', 0, '2026-06-26 15:08:23'),
(57, 3, 'Peppermint Tea', 'Refreshing, cool herbal infusion made from steep peppermint leaves, known for its crisp menthol aroma and digestive benefits.\r\n\r\n1:0 parts pure peppermint leaf steep\r\n\r\n~0?? kcal\r\n\r\nSize: 12 oz (350 ml)', 8.50, '1785945421_455a050c.jpeg', 0, '2026-06-26 15:08:23'),
(58, 3, 'Matcha Latte', 'Vibrant Japanese green tea powder whisked with hot water, then blended with smooth, warm steamed milk.\r\n\r\n1:3 parts matcha paste to steamed milk\r\n\r\n~140??80 kcal\r\n\r\nSize: 12 oz (350 ml)', 13.00, '1785945369_a8acb8ae.jpeg', 0, '2026-08-01 15:08:23'),
(59, 3, 'Hot Chocolate', 'Rich chocolate melted into warm steamed milk, creating a deep, velvety, and sweet comfort drink.\r\n\r\n1:3 parts chocolate base to steamed milk\r\n\r\n~200??40 kcal\r\n\r\nSize: 12 oz (350 ml)', 12.00, '1785945325_8d3dae33.jpg', 0, '2026-06-26 15:08:23'),
(60, 3, 'Golden Turmeric Latte', 'Vibrant blend of warm steamed milk, ground turmeric, and aromatic spices, offering an earthy, caffeine-free golden brew.\r\n\r\n1:3 parts turmeric spice blend to steamed milk\r\n\r\n~130??60 kcal\r\n\r\nSize: 12 oz (350 ml)', 13.50, '1785945286_7627ed06.jpeg', 0, '2026-06-26 15:08:23'),
(61, 3, 'Chai Latte', 'Spiced black tea concentration blended with warm steamed milk, topped with a light dusting of cinnamon or nutmeg.\r\n\r\n1:2 parts chai concentrate to steamed milk\r\n\r\n~180??20 kcal\r\n\r\nSize: 12 oz (350 ml)', 12.50, '1785945244_9da305b1.jpeg', 0, '2026-06-26 15:08:23'),
(62, 3, 'Rooibos Tea', 'Naturally caffeine-free South African red tea and earthy herbal infusion with subtle nutty notes, completely caffeine-free and rich in antioxidants.\r\n\r\n1:0 parts pure rooibos leaf steep\r\n\r\n~0?? kcal\r\n\r\nSize: 12 oz (350 ml)', 8.50, '1785945198_e113c054.jpeg', 0, '2026-06-26 15:08:23'),
(63, 3, 'Lemongrass Ginger Tea', 'Aromatic herbal infusion of fresh lemongrass and fiery ginger root, offering a soothing, caffeine-free citrus spice blend.\r\n\r\n1:1 parts lemongrass to ginger root steep\r\n\r\n~0?? kcal\r\n\r\nSize: 12 oz (350 ml)', 9.00, '1785945121_cf679b79.jpeg', 0, '2026-06-26 15:08:23'),
(64, 3, 'Vanilla Steamer', 'Warm steamed milk blended with rich vanilla syrup, creating a sweet and cozy caffeine-free drink.\r\n\r\n1:4 parts vanilla syrup to steamed milk\r\n\r\n~160??00 kcal\r\n\r\nSize: 12 oz (350 ml)', 9.50, '1785945083_69a3dc50.jpeg', 0, '2026-06-26 15:08:23'),
(65, 4, 'Mango Passionfruit Smoothie', 'Tropical blend of sweet mangoes and tangy passionfruit, whipped into a bright, refreshing smoothie bursting with sunshine.\r\n\r\n1:2 parts passionfruit base to blended mango\r\n\r\n~230 kcal\r\n\r\nSize: 16 oz (480 ml)', 14.00, '1785946509_4445f847.jpeg', 0, '2026-06-26 15:08:23'),
(66, 4, 'Strawberry Banana Smoothie', 'Classic, luscious blend of ripe bananas and juicy strawberries, whipped together for a naturally sweet, smooth, and fruit-forward treat.\r\n\r\n1:2 parts strawberries to banana-milk blend\r\n\r\n~220 kcal\r\n\r\nSize: 16 oz (480 ml)', 13.50, '1785946440_322498c3.jpg', 0, '2026-06-26 15:08:23'),
(67, 4, 'Berry Bliss Smoothie', 'Vibrant blend of sweet-tart mixed berries and velvety yogurt, delivering a thick, antioxidants-rich treat with a smooth, refreshing finish.\r\n\r\n1:2 parts mixed berries to yogurt-milk blend\r\n\r\n~170??10 kcal\r\n\r\nSize: 16 oz (480 ml)', 14.50, '1785946398_8bcd47f5.jpeg', 0, '2026-07-31 15:08:23'),
(68, 4, 'Green Detox Smoothie', 'Nutrient-dense blend of leafy greens, green apple, cucumber, and lemon, offering a crisp, clean taste packed with antioxidants.\r\n\r\n1:2 parts leafy greens to liquid base with blended fruits\r\n\r\n~120??60 kcal\r\n\r\nSize: 16 oz (480 ml)', 14.80, '1785946336_1dde4aac.jpg', 0, '2026-06-26 15:08:23'),
(69, 4, 'Peach Iced Tea Soda', 'Bubbly fusion of sweet peach iced tea topped off with sparkling soda water and served over ice for a crisp, fruit-forward lift.\r\n\r\n1:2 parts peach iced tea base to sparkling soda water\r\n\r\n~130 kcal\r\n\r\nSize: 16 oz (480 ml)', 11.00, '1785946287_1ada01fc.jpeg', 0, '2026-06-26 15:08:23'),
(70, 4, 'Citrus Sparkler', 'Bright, sparkling blend of fresh orange and lemon juices topped with bubbly soda and fresh mint leaves for a zesty, invigorating twist.\r\n\r\n1:3 parts citrus juice base to sparkling soda\r\n\r\n~90??20 kcal\r\n\r\nSize: 16 oz (480 ml)', 10.50, '1785946248_392792ee.jpeg', 0, '2026-06-26 15:08:23'),
(71, 4, 'Watermelon Cooler', 'Refreshing mix of fresh crushed watermelon and crisp soda, served over ice for a light, hydrating summer sip.\r\n\r\n1:2 parts fresh watermelon juice base to sparkling soda\r\n\r\n~80??10 kcal\r\n\r\nSize: 16 oz (480 ml)', 11.50, '1785946195_342a9d88.webp', 0, '2026-06-26 15:08:23'),
(72, 4, 'Blue Lagoon Soda', 'Tropical blend of juicy pineapple and rich coconut cream, delivering a smooth, alcohol-free pi?a colada experience.\r\n\r\n1:2 parts coconut cream base to blended pineapple\r\n\r\n~200??50 kcal\r\n\r\nSize: 16 oz (480 ml)', 12.00, '1785946010_f4bf2233.jpeg', 0, '2026-06-26 15:08:23'),
(73, 4, 'Pineapple Coconut Smoothie Pi?a', 'Tropical blend of juicy pineapple and rich coconut cream, delivering a smooth, alcohol-free pi?a colada experience.\r\n\r\n1:2 parts coconut cream base to blended pineapple\r\n\r\n~200??50 kcal\r\n\r\nSize: 16 oz (480 ml)', 14.20, '1785945919_cbf6e535.jpeg', 0, '2026-06-26 15:08:23'),
(74, 4, 'Avocado Chocolate Smoothie', 'Velvety blend of ripe avocado and rich cocoa, creating a smooth, chocolatey smoothie with a naturally creamy texture.\r\n\r\n1:2 parts avocado-cocoa base to milk or alternative\r\n\r\n~220??70 kcal\r\n\r\nSize: 16 oz (480 ml)', 15.00, '1785945878_ddcb2058.jpeg', 0, '2026-06-26 15:08:23'),
(75, 5, 'Classic Club Sandwich', 'Triple-decker toasted sandwich layered with sliced turkey, crispy bacon, fresh lettuce, juicy tomato, and mayonnaise.\r\n\r\nToasted Bread | Turkey Breast | Bacon | Lettuce | Tomato | Mayonnaise\r\n\r\n~500??20 kcal', 18.90, '1785947072_8b5fb528.jpg', 0, '2026-06-26 15:08:23'),
(77, 5, 'Chicken Caesar Wrap', 'Tender grilled chicken, crisp romaine lettuce, and shaved Parmesan tossed in creamy Caesar dressing and wrapped in a flour tortilla.\r\n\r\nChicken Breast | Flour Tortilla | Romaine Lettuce | Parmesan Cheese | Caesar Dressing | Garlic Croutons\r\n\r\n~420??40 kcal', 16.50, '1785947032_8817b71c.jpeg', 0, '2026-06-26 15:08:23'),
(78, 5, 'Mushroom Alfredo Pasta', 'Al dente pasta tossed in a velvety garlic cream sauce with saut?ed wild mushrooms and freshly grated Parmesan.\r\n\r\nPasta | Wild Mushrooms | Heavy Cream | Parmesan Cheese | Garlic | Butter\r\n\r\n~520??50 kcal', 19.90, '1785947001_8ccdee4e.jpeg', 0, '2026-07-30 15:08:23'),
(79, 5, 'Grilled Chicken Panini', 'Juicy grilled chicken breast layered with melted cheese, fresh tomato, and pesto toasted inside crisp panini bread.\r\n\r\nChicken Breast | Panini Bread | Cheese | Tomato | Pesto | Olive Oil\r\n\r\n~450??80 kcal', 17.50, '1785946962_6af25f6d.jpeg', 0, '2026-06-26 15:08:23'),
(81, 5, 'Veggie Quesadilla', 'Warm toasted tortilla folded with melted cheese, saut?ed bell peppers, onions, and sweet corn served with fresh salsa.\r\n\r\nTortilla | Cheddar Cheese | Bell Peppers | Onions | Sweet Corn | Salsa\r\n\r\n~380??80 kcal', 15.90, '1785946931_cd28608a.jpeg', 0, '2026-06-26 15:08:23'),
(82, 5, 'Fish and Chips', 'Crispy, golden beer-battered fish fillets served alongside thick-cut French fries and tangy tartar sauce.\r\n\r\nFish Fillet | Potatoes | Flour Batter | Tartar Sauce | Lemon\r\n\r\n~650??00 kcal', 20.50, '1785946898_ebd841c8.jpg', 0, '2026-06-26 15:08:23'),
(83, 5, 'Breakfast All-Day Plate', 'Hearty all-day platter featuring eggs made to order, savory sausage, golden toast, and rich baked beans.\r\n\r\nEggs | Sausage | Toast | Baked Beans | Butter | Mushrooms\r\n\r\n~550??80 kcal', 19.50, '1785946857_5aa68d66.jpeg', 0, '2026-06-26 15:08:23'),
(84, 5, 'Truffle Mushroom Risotto', 'Rich, creamy Arborio rice slow-cooked with earthy wild mushrooms, finished with decadent truffle oil, garlic, and freshly grated Parmesan cheese.\r\n\r\nArborio Rice | Wild Mushrooms | Truffle Oil | Parmesan Cheese | Garlic | Onion | Vegetable Broth | Butter\r\n\r\n~450??50 kcal', 23.90, '1785946807_9ecb47ac.jpeg', 0, '2026-06-26 15:08:23'),
(85, 6, 'New York Cheesecake', 'Dense, smooth, and rich baked cheesecake with a buttery graham cracker crust and a hint of fresh vanilla and lemon zest.\r\n\r\nCream Cheese | Graham Cracker Crust | Sugar | Eggs | Heavy Cream | Vanilla Extract | Lemon Zest\r\n\r\n~400??20 kcal', 13.90, '1785947636_40c4e216.jpeg', 0, '2026-06-26 15:08:23'),
(88, 6, 'Carrot Cake', 'Moist, spiced cake packed with grated carrots and warm spices, layered and frosted with smooth cream cheese icing.\r\n\r\nFlour | Grated Carrots | Cream Cheese | Butter | Sugar | Cinnamon | Walnuts\r\n\r\n~350??50 kcal', 12.90, '1785947582_5244ffa9.jpeg', 0, '2026-06-26 15:08:23'),
(90, 6, 'Blueberry Muffin', 'Soft, fluffy muffin packed with juicy whole blueberries and finished with a crunchy sugar top.\r\n\r\nFlour | Fresh Blueberries | Butter | Sugar | Milk | Eggs | Baking Powder\r\n\r\n~320??10 kcal', 7.90, '1785947545_a2f12d72.jpeg', 0, '2026-06-26 15:08:23'),
(91, 6, 'Almond Croissant', 'Flaky, buttery croissant filled with rich almond frangipane cream and topped with toasted sliced almonds and powdered sugar.\r\n\r\nCroissant Dough | Butter | Almond Flour | Sugar | Eggs | Sliced Almonds | Powdered Sugar\r\n\r\n~380??80 kcal', 8.90, '1785947497_b6b86b71.jpeg', 0, '2026-06-26 15:08:23'),
(93, 6, 'Apple Pie Slice', 'Warm, spiced apples baked inside a flaky, buttery crust and finished with a dusting of cinnamon.\r\n\r\nApples | Pie Crust | Butter | Cinnamon | Sugar | Flour | Nutmeg\r\n\r\n~300??00 kcal', 11.50, '1785947406_e462bdf8.jpeg', 0, '2026-06-26 15:08:23'),
(94, 6, 'Red Velvet Cupcake', 'Moist, vibrant red velvet cake topped with a rich, silky swirl of classic cream cheese frosting.\r\n\r\nFlour | Cocoa Powder | Buttermilk | Butter | Sugar | Cream Cheese | Vanilla Extract\r\n\r\n~280??60 kcal', 8.50, '1785947372_404f5f4d.jpeg', 0, '2026-06-26 15:08:23');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT '0',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `order_date`, `total_amount`, `status`) VALUES
(1, 1, '2026-08-12 14:48:19', 18.90, 'Completed');

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
  `item_options` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `item_id` (`item_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `item_id`, `quantity`, `price_at_order`, `item_options`) VALUES
(1, 1, 2, 1, 13.90, 'Hot, No Sugar');

-- --------------------------------------------------------

--
-- Table structure for table `otp_verifications`
--

DROP TABLE IF EXISTS `otp_verifications`;
CREATE TABLE IF NOT EXISTS `otp_verifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `birthdate` date NOT NULL,
  `activation_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `attempt_count` int NOT NULL DEFAULT '0',
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `otp_verifications`
--

INSERT INTO `otp_verifications` (`id`, `user_id`, `phone`, `birthdate`, `activation_code`, `is_verified`, `attempt_count`, `expires_at`, `created_at`) VALUES
(1, 3, '+60192723941', '2006-08-08', '888953', 1, 0, '2026-08-08 01:51:47', '2026-08-08 01:48:47'),
(2, 1, '+601120970647', '2006-04-07', '982169', 1, 0, '2026-08-12 14:45:27', '2026-08-12 14:42:27');

-- --------------------------------------------------------

--
-- Table structure for table `points_history`
--

DROP TABLE IF EXISTS `points_history`;
CREATE TABLE IF NOT EXISTS `points_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `points` int NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `points_history`
--

INSERT INTO `points_history` (`id`, `user_id`, `points`, `description`, `created_at`) VALUES
(1, 1, -50, 'Redeemed Coupon: COZY3OFF', '2026-08-12 15:40:57');

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
  `is_rewards_member` tinyint(1) NOT NULL DEFAULT '0',
  `rewards_points` int NOT NULL DEFAULT '0',
  `rewards_member_no` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rewards_joined_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `username`, `password`, `created_at`, `birthday`, `gender`, `phone`, `profile_pic`, `is_rewards_member`, `rewards_points`, `rewards_member_no`, `rewards_joined_at`) VALUES
(1, 'Jie Ying', 'jieying47@1utar.my', 'laijieying', '$2y$10$kNkzG8Ab1FuWgfVbtbz7P.aGvTAKgdEFHsuzrq5b71TtwwiRVbwxy', '2026-08-01 21:32:31', '2006-04-07', 'Female', '+601120970647', 'user_1_1785652687.jpg', 1, 0, 'CR000001', '2026-08-12 14:42:42'),
(2, 'Chok Shi Ying', 'chokshiying06@gmail.com', 'yingchok', '$2y$10$Amklq4awtRbRlrfNk82VzuzhFOP1gJvuhvZIKXpXmu4k.nj.h2tKm', '2026-08-05 07:57:54', NULL, NULL, NULL, 'user_2_1785922625.jpg', 0, 0, NULL, NULL),
(3, 'Zhi Qing', 'changzhiqing1996@gmail.com', 'ZhiQing', '$2y$10$tsJdBo23ikIpXBLAYxbyfuOQe7C/yviTlDC54y9nttvNAgWcKOrbG', '2026-08-08 01:47:26', '2006-08-08', NULL, '+60192723941', 'default.png', 1, 50, 'CR000003', '2026-08-08 01:49:04');

-- --------------------------------------------------------

--
-- Table structure for table `user_vouchers`
--

DROP TABLE IF EXISTS `user_vouchers`;
CREATE TABLE IF NOT EXISTS `user_vouchers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `voucher_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `discount_amount` decimal(6,2) NOT NULL,
  `min_spend` decimal(6,2) NOT NULL DEFAULT '0.00',
  `terms` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_vouchers`
--

INSERT INTO `user_vouchers` (`id`, `user_id`, `voucher_code`, `discount_amount`, `min_spend`, `terms`, `is_used`, `created_at`) VALUES
(1, 1, 'COZY3OFF', 3.00, 10.00, 'Valid for RM3 off any takeaway order over RM10. Cannot be combined with other promos.', 0, '2026-08-12 15:40:57');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
