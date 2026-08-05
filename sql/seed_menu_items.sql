-- ============================================================
-- Cozy Coffee Co. — Menu Items Seed Script
-- Adds 10 items to each category: specialty, classic, noncoffein,
-- smoothies, mains, desserts.
--
-- Assumes the `categories` table already has rows with these
-- category_key values (as used in menu/index.php and cart nav
-- links). category_id is looked up dynamically via subquery, so
-- this works regardless of your actual auto-increment IDs.
--
-- Images use https://picsum.photos placeholder URLs (a seeded
-- random-photo service) so everything renders immediately without
-- needing your own files in ../images/menu/. Swap the `image`
-- value for a local filename any time — index.php already detects
-- and supports both a full URL and a local filename.
-- ============================================================
ALTER TABLE menu_items ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP;

-- ---------- Specialty Coffee (specialty) ----------
INSERT INTO menu_items (category_id, name, description, price, image, created_at) VALUES
((SELECT category_id FROM categories WHERE category_key = 'specialty'), 'Caramel Macchiato', 'Espresso, steamed milk, vanilla and a caramel drizzle', 14.90, 'https://picsum.photos/seed/caramel-macchiato/400/400', NOW() - INTERVAL 2 DAY),
((SELECT category_id FROM categories WHERE category_key = 'specialty'), 'Hazelnut Latte', 'Rich espresso with hazelnut syrup and silky milk foam', 13.90, 'https://picsum.photos/seed/hazelnut-latte/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'specialty'), 'Mocha Delight', 'Espresso, dark chocolate and whipped cream', 14.50, 'https://picsum.photos/seed/mocha-delight/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'specialty'), 'Vanilla Bean Latte', 'Smooth espresso with real vanilla bean syrup', 13.50, 'https://picsum.photos/seed/vanilla-bean-latte/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'specialty'), 'Salted Caramel Cappuccino', 'Bold cappuccino topped with salted caramel foam', 15.00, 'https://picsum.photos/seed/salted-caramel-cappuccino/400/400', NOW() - INTERVAL 3 DAY),
((SELECT category_id FROM categories WHERE category_key = 'specialty'), 'Pumpkin Spice Latte', 'Seasonal favorite with warm autumn spices', 15.50, 'https://picsum.photos/seed/pumpkin-spice-latte/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'specialty'), 'Honey Cinnamon Latte', 'Espresso sweetened with honey and a dust of cinnamon', 14.00, 'https://picsum.photos/seed/honey-cinnamon-latte/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'specialty'), 'White Chocolate Mocha', 'Creamy white chocolate blended with espresso', 15.20, 'https://picsum.photos/seed/white-chocolate-mocha/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'specialty'), 'Lavender Honey Latte', 'Floral lavender meets golden honey and espresso', 15.80, 'https://picsum.photos/seed/lavender-honey-latte/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'specialty'), 'Irish Cream Cold Brew', 'Cold brew laced with Irish cream syrup', 14.70, 'https://picsum.photos/seed/irish-cream-cold-brew/400/400', NOW() - INTERVAL 40 DAY);

-- ---------- Classic Coffee (classic) ----------
INSERT INTO menu_items (category_id, name, description, price, image, created_at) VALUES
((SELECT category_id FROM categories WHERE category_key = 'classic'), 'Espresso', 'A bold, concentrated shot of pure coffee', 8.00, 'https://picsum.photos/seed/espresso/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'classic'), 'Americano', 'Espresso diluted with hot water for a smooth cup', 9.00, 'https://picsum.photos/seed/americano/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'classic'), 'Cappuccino', 'Equal parts espresso, steamed milk and foam', 11.00, 'https://picsum.photos/seed/cappuccino/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'classic'), 'Café Latte', 'Espresso with generous steamed milk', 11.50, 'https://picsum.photos/seed/cafe-latte/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'classic'), 'Flat White', 'Velvety microfoam over a double espresso', 12.00, 'https://picsum.photos/seed/flat-white/400/400', NOW() - INTERVAL 1 DAY),
((SELECT category_id FROM categories WHERE category_key = 'classic'), 'Long Black', 'Hot water topped with a rich espresso shot', 9.50, 'https://picsum.photos/seed/long-black/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'classic'), 'Café au Lait', 'Drip coffee blended with steamed milk', 10.00, 'https://picsum.photos/seed/cafe-au-lait/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'classic'), 'Cortado', 'Equal parts espresso and warm milk', 11.20, 'https://picsum.photos/seed/cortado/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'classic'), 'Doppio', 'A double shot of pure espresso', 9.80, 'https://picsum.photos/seed/doppio/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'classic'), 'Macchiato', 'Espresso "stained" with a dollop of foam', 10.50, 'https://picsum.photos/seed/macchiato/400/400', NOW() - INTERVAL 40 DAY);

-- ---------- Non-Coffein (noncoffein) ----------
INSERT INTO menu_items (category_id, name, description, price, image, created_at) VALUES
((SELECT category_id FROM categories WHERE category_key = 'noncoffein'), 'English Breakfast Tea', 'Classic full-bodied black tea blend', 8.50, 'https://picsum.photos/seed/english-breakfast-tea/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'noncoffein'), 'Chamomile Tea', 'Soothing floral tea, naturally caffeine-free', 8.50, 'https://picsum.photos/seed/chamomile-tea/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'noncoffein'), 'Peppermint Tea', 'Refreshing minty herbal infusion', 8.50, 'https://picsum.photos/seed/peppermint-tea/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'noncoffein'), 'Matcha Latte', 'Stone-ground green tea whisked with milk', 13.00, 'https://picsum.photos/seed/matcha-latte/400/400', NOW() - INTERVAL 4 DAY),
((SELECT category_id FROM categories WHERE category_key = 'noncoffein'), 'Hot Chocolate', 'Rich melted chocolate with steamed milk', 12.00, 'https://picsum.photos/seed/hot-chocolate/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'noncoffein'), 'Golden Turmeric Latte', 'Turmeric, ginger and warm spices in milk', 13.50, 'https://picsum.photos/seed/golden-turmeric-latte/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'noncoffein'), 'Chai Latte', 'Spiced black tea blended with steamed milk', 12.50, 'https://picsum.photos/seed/chai-latte/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'noncoffein'), 'Rooibos Tea', 'Naturally caffeine-free South African red tea', 8.50, 'https://picsum.photos/seed/rooibos-tea/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'noncoffein'), 'Lemongrass Ginger Tea', 'Zesty lemongrass and warming ginger', 9.00, 'https://picsum.photos/seed/lemongrass-ginger-tea/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'noncoffein'), 'Vanilla Steamer', 'Steamed milk with vanilla syrup, no coffee', 9.50, 'https://picsum.photos/seed/vanilla-steamer/400/400', NOW() - INTERVAL 40 DAY);

-- ---------- Refreshing Smoothies & Sodas (smoothies) ----------
INSERT INTO menu_items (category_id, name, description, price, image, created_at) VALUES
((SELECT category_id FROM categories WHERE category_key = 'smoothies'), 'Mango Passionfruit Smoothie', 'Tropical mango blended with tangy passionfruit', 14.00, 'https://picsum.photos/seed/mango-passionfruit-smoothie/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'smoothies'), 'Strawberry Banana Smoothie', 'Classic fruity blend, thick and creamy', 13.50, 'https://picsum.photos/seed/strawberry-banana-smoothie/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'smoothies'), 'Berry Bliss Smoothie', 'Mixed berries blended with creamy yogurt', 14.50, 'https://picsum.photos/seed/berry-bliss-smoothie/400/400', NOW() - INTERVAL 5 DAY),
((SELECT category_id FROM categories WHERE category_key = 'smoothies'), 'Green Detox Smoothie', 'Spinach, apple and pineapple blend', 14.80, 'https://picsum.photos/seed/green-detox-smoothie/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'smoothies'), 'Peach Iced Tea Soda', 'Sparkling peach tea served over ice', 11.00, 'https://picsum.photos/seed/peach-iced-tea-soda/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'smoothies'), 'Citrus Sparkler', 'Orange and lemon soda with fresh mint', 10.50, 'https://picsum.photos/seed/citrus-sparkler/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'smoothies'), 'Watermelon Cooler', 'Fresh watermelon juice with soda water', 11.50, 'https://picsum.photos/seed/watermelon-cooler/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'smoothies'), 'Blue Lagoon Soda', 'Blue curaçao-flavored sparkling lemonade', 12.00, 'https://picsum.photos/seed/blue-lagoon-soda/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'smoothies'), 'Pineapple Coconut Smoothie', 'Piña colada-inspired, alcohol-free', 14.20, 'https://picsum.photos/seed/pineapple-coconut-smoothie/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'smoothies'), 'Avocado Chocolate Smoothie', 'Creamy avocado blended with rich cocoa', 15.00, 'https://picsum.photos/seed/avocado-chocolate-smoothie/400/400', NOW() - INTERVAL 40 DAY);

-- ---------- Main Dishes (mains) ----------
INSERT INTO menu_items (category_id, name, description, price, image, created_at) VALUES
((SELECT category_id FROM categories WHERE category_key = 'mains'), 'Classic Club Sandwich', 'Triple-decker with chicken, egg and veggies', 18.90, 'https://picsum.photos/seed/classic-club-sandwich/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'mains'), 'Beef Lasagna', 'Layers of pasta, beef ragu and cheese', 22.50, 'https://picsum.photos/seed/beef-lasagna/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'mains'), 'Chicken Caesar Wrap', 'Grilled chicken, romaine and Caesar dressing', 16.50, 'https://picsum.photos/seed/chicken-caesar-wrap/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'mains'), 'Mushroom Alfredo Pasta', 'Creamy alfredo sauce with sautéed mushrooms', 19.90, 'https://picsum.photos/seed/mushroom-alfredo-pasta/400/400', NOW() - INTERVAL 6 DAY),
((SELECT category_id FROM categories WHERE category_key = 'mains'), 'Grilled Chicken Panini', 'Pressed panini with pesto and mozzarella', 17.50, 'https://picsum.photos/seed/grilled-chicken-panini/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'mains'), 'Beef Bolognese', 'Slow-cooked beef ragu over spaghetti', 21.00, 'https://picsum.photos/seed/beef-bolognese/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'mains'), 'Veggie Quesadilla', 'Grilled tortilla with cheese and vegetables', 15.90, 'https://picsum.photos/seed/veggie-quesadilla/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'mains'), 'Fish and Chips', 'Crispy battered fish with golden fries', 20.50, 'https://picsum.photos/seed/fish-and-chips/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'mains'), 'Breakfast All-Day Plate', 'Eggs, sausage, toast and beans', 19.50, 'https://picsum.photos/seed/breakfast-all-day-plate/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'mains'), 'Truffle Mushroom Risotto', 'Creamy risotto finished with truffle oil', 23.90, 'https://picsum.photos/seed/truffle-mushroom-risotto/400/400', NOW() - INTERVAL 40 DAY);

-- ---------- Desserts (desserts) ----------
INSERT INTO menu_items (category_id, name, description, price, image, created_at) VALUES
((SELECT category_id FROM categories WHERE category_key = 'desserts'), 'New York Cheesecake', 'Rich and creamy classic baked cheesecake', 13.90, 'https://picsum.photos/seed/new-york-cheesecake/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'desserts'), 'Chocolate Lava Cake', 'Warm cake with a molten chocolate center', 14.50, 'https://picsum.photos/seed/chocolate-lava-cake/400/400', NOW() - INTERVAL 1 DAY),
((SELECT category_id FROM categories WHERE category_key = 'desserts'), 'Tiramisu', 'Espresso-soaked layers with mascarpone cream', 14.00, 'https://picsum.photos/seed/tiramisu/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'desserts'), 'Carrot Cake', 'Spiced cake with cream cheese frosting', 12.90, 'https://picsum.photos/seed/carrot-cake/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'desserts'), 'Croissant', 'Buttery, flaky French pastry', 7.50, 'https://picsum.photos/seed/croissant/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'desserts'), 'Blueberry Muffin', 'Freshly baked with juicy blueberries', 7.90, 'https://picsum.photos/seed/blueberry-muffin/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'desserts'), 'Almond Croissant', 'Flaky croissant filled with almond cream', 8.90, 'https://picsum.photos/seed/almond-croissant/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'desserts'), 'Chocolate Chip Cookie', 'Classic chewy cookie, baked daily', 6.50, 'https://picsum.photos/seed/chocolate-chip-cookie/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'desserts'), 'Apple Pie Slice', 'Warm cinnamon apple filling in a flaky crust', 11.50, 'https://picsum.photos/seed/apple-pie-slice/400/400', NOW() - INTERVAL 40 DAY),
((SELECT category_id FROM categories WHERE category_key = 'desserts'), 'Red Velvet Cupcake', 'Moist cupcake with cream cheese frosting', 8.50, 'https://picsum.photos/seed/red-velvet-cupcake/400/400', NOW() - INTERVAL 40 DAY);
