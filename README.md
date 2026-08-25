# Cozy Coffee Co. — Online Café Ordering & Customer Engagement Platform

A full-stack café ordering web application built with PHP and MySQL, where customers can browse handcrafted coffee and food menus, place custom orders, track barista preparation live, join the Cozy Rewards loyalty program, and share coffee check-in moments on the community blog.

---

## System Requirements

- **PHP 8.2 or 8.3** (with the `mysqli` extension enabled)
- **MySQL / MariaDB 8.x** (Database Port: `3308` or default `3306`)
- **Local Server Stack**: WampServer, XAMPP, or MAMP
- **No External Frameworks**: Plain HTML5, CSS3, and Vanilla JavaScript on the front end (Strictly complies with assignment guidelines)

---

## Setup & Installation Instructions

1. Start **Apache** and **MySQL** services from your local server control panel (e.g. WampServer).
2. Open phpMyAdmin (`http://localhost/phpmyadmin`) and create a new database named `cozy_coffee_db`.
3. Import `sql/database.sql` into `cozy_coffee_db`. This creates all 19 database tables and inserts sample development data (users, orders, menu products, vouchers, and customer care messages).
4. Verify that connection parameters in `includes/db_connect.php` (and the root-level `db.php`) match your local MySQL configuration:
   - Host: `127.0.0.1`
   - Port: `3308`
   - Database: `cozy_coffee_db`
   - Username: `root`
   - Password: `""` (empty)
5. Place the project directory into your web server's root directory:
   - Path: `C:\wamp64\www\coffee-ordering-platform`
6. Open your web browser and navigate to:
   - `http://localhost/coffee-ordering-platform/home/index.php`

---

## Default Test Credentials

For testing and marking evaluation, use the following imported accounts:

| User Role | Username / Email | Password | Access Level |
| :--- | :--- | :--- | :--- |
| **Store Admin** | `admin` | _(as set by team)_ | Full Admin Back-Office Management |
| **Demo Customer 1** | `laijieying` (email: `jieying47@1utar.my`) | _(as set by team)_ | Customer Account |
| **Demo Customer 2** | `yingchok` (email: `chokshiying06@gmail.com`) | _(as set by team)_ | Customer Account |
| **Demo Customer 3** | `chloewong` (email: `chloe.wong@gmail.com`) | _(as set by team)_ | Customer Account |

> Passwords are stored as bcrypt hashes in the database and cannot be reverse-derived — list the actual plaintext test password(s) here once confirmed with the team.

---

## Project Folder Structure

```text
coffee-ordering-platform/
│
├── includes/       # Shared utilities: db_connect.php, auth_check.php, admin_auth_check.php, header_nav.php
├── login/          # Customer authentication login page
├── register/       # Account registration page
├── home/           # Landing home page
├── menu/           # Food & beverage catalog
├── details/        # Product detail popup modals
├── cart/           # Shopping cart management
├── checkout/       # Order confirmation & payment placement
├── orders/         # Public live kitchen preparation monitor (track.php)
├── profile/        # Customer account, profile avatar upload & order history (orders.php)
├── blog/           # Coffee Moments community check-in feed
├── admin/          # Staff back-office management (manage_orders, manage_chat, dashboard)
├── rewards/        # Cozy Rewards membership registration
├── contact/        # Store location, opening hours & social media handles
├── benefits/       # Member tier perks
├── offers/         # Promotional discount vouchers & campaign deals
├── activities/     # Store events & barista workshops
├── images/         # Static graphic assets & product photos
├── uploads/        # User-uploaded profile avatars & blog photos
├── style/          # Shared CSS stylesheets (mystyle.css, admin.css, etc.)
├── sql/            # database.sql (schema DDL + sample data)
├── db.php          # Root database connection shortcut
├── index.php       # Root entry point
└── logout.php      # Session termination script
```

---

## Key Features

- Menu browsing with live search and item customisation (ice level, sweetness, remarks)
- Cart and checkout with multiple payment methods
- Live order tracking with a real-time status banner (Pending → Handcrafting → Ready)
- Order history with itemised receipts
- Coffee Moments blog — share what you ordered, your mood, and photos with the community
- Cozy Rewards loyalty program with OTP-verified activation
- Admin back office for managing menu items, orders, users, blog posts, offers, promos, activities and site content

---

## Known Issues

- Two separate database connection files exist (`db.php` at the project root and `includes/db_connect.php`); both must be kept in sync when local MySQL credentials change. Consolidating onto a single file is a planned improvement.
- `profile/order_detail.php` and `blog/user_posts.php` are legacy pages that are no longer linked from the site navigation, since their functionality has been superseded by the order receipt modal on `profile/orders.php` and the author preview modal on the Blog page respectively.
- `admin/manage_about.php` exists and is protected by the admin auth guard but is not yet linked from the admin sidebar.

## Video Demo Requirements
https://drive.google.com/file/d/10qJF2oBf9TigdZ_np3nhsxpVN-UlAGTC/view?usp=sharing
