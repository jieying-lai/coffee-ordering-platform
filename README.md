```markdown
<div align="center">

  # ☕ Cozy Coffee Co. — Full-Stack Café Ordering & Management Platform

  **A framework-free, responsive commercial café ordering, live preparation tracking, and loyalty management system.**

  [![PHP](https://img.shields.io/badge/PHP-8.2%20%7C%208.3-777BB4?style=flat-square&logo=php&logoColor=white)](#)
  [![MySQL](https://img.shields.io/badge/MySQL-8.x%20(19%20Tables)-4479A1?style=flat-square&logo=mysql&logoColor=white)](#)
  [![JavaScript](https://img.shields.io/badge/JavaScript-Vanilla%20ES6+-F7DF1E?style=flat-square&logo=javascript&logoColor=black)](#)
  [![CSS3](https://img.shields.io/badge/CSS3-Pure%20Media%20Queries-1572B6?style=flat-square&logo=css3&logoColor=white)](#)
  [![Coursework](https://img.shields.io/badge/UTAR-UECS2094%20%2F%20UECS2194-blueviolet?style=flat-square)](#)

</div>

---

## 📌 Project Overview

**Cozy Coffee Co.** is a complete, responsive commercial café web application engineered for the **UECS2094 / UECS2194 / EECS2194 Web Application Development** coursework at Universiti Tunku Abdul Rahman (UTAR).

The platform bridges consumer-facing hospitality workflows with administrative back-office operations. It allows patrons to explore handcrafted beverage catalogs, customize item recipes, place dynamic orders, track barista preparation progress in real time, redeem loyalty vouchers, and interact on the "Coffee Moments" community blog. Behind the scenes, an authenticated administrative dashboard provides end-to-end management over menu items, order fulfillment, promotional vouchers, and customer service inquiries.

### 💡 Core Engineering Highlights

* **100% Framework-Free Implementation**: Strictly adhering to coursework guidelines, the application is built using **native HTML5, pure CSS3, and Vanilla ES6+ JavaScript** without third-party frameworks or UI toolkits (zero Bootstrap, Tailwind, jQuery, or React). Every layout grid, modal, and client-side validation is hand-coded.
* **Complex 19-Table Relational Schema**: Features an end-to-end relational MySQL database covering full CRUD (Create, Read, Update, Delete) data operations across orders, menu inventory, rewards, and blog posts.
* **Stateful Session & Access Control (RBAC)**: Implements server-side PHP session management to separate regular patrons from back-office management.
* **Live Order Lifecycle Tracking**: A real-time order monitor reflecting status transitions from `Pending` ➔ `Handcrafting` ➔ `Ready for Pickup`.

---

## ✨ System Features

### 1. Customer-Facing Storefront
* **Dynamic Menu & Customization**: Live search filtering with item-level options (sweetness level, ice ratio, special remarks).
* **Shopping Cart & Checkout**: Persistent cart management, dynamic subtotals, promotional voucher redemptions, and multiple payment options.
* **Live Order Tracking**: Kitchen preparation monitor allowing customers to track preparation status live.
* **Coffee Moments Blog**: Social check-in feed allowing customers to post their orders, mood tags, and beverage photos.
* **Cozy Rewards Program**: Tiered customer loyalty program with OTP-verified account activation and point tracking.

### 2. Back-Office Administrative Portal
* **Order Fulfillment**: Live management of pending orders with instant status transition updates.
* **Menu Inventory (CRUD)**: Complete management over food, beverage catalogs, pricing, and category classifications.
* **Marketing & Promotions**: Creation and distribution of promotional discount vouchers, campaign promo codes, and barista workshop events.
* **Community Moderation & Support**: Moderation pipeline for community posts and a centralized customer care communication channel.

---

## 🗄️ Database Architecture (19 Tables)

The underlying schema (`sql/database.sql`) models a normalized retail ecosystem:

* **Authentication & Identity**: `users`, `admin_users`, `user_sessions`, `password_resets`
* **Catalog & Orders**: `categories`, `products`, `product_variants`, `orders`, `order_items`, `order_status_logs`
* **Loyalty & Marketing**: `rewards_tiers`, `user_points`, `vouchers`, `user_vouchers`, `promotions`, `activities`
* **Engagement & Support**: `blog_posts`, `blog_comments`, `customer_care_messages`

---

## 📂 Project Directory Structure

```text
coffee-ordering-platform/
│
├── includes/          # Shared utilities: db_connect.php, auth_check.php, admin_auth_check.php, header_nav.php
├── login/             # Customer authentication entry
├── register/          # Customer account registration
├── home/              # Landing home page with hero showcase
├── menu/              # Beverage & food catalog with real-time filters
├── details/           # Product customizer modals
├── cart/              # Persistent shopping cart management
├── checkout/          # Transaction processing & voucher validation
├── orders/            # Live kitchen preparation monitor (track.php)
├── profile/           # User dashboard, avatar upload, and order history
├── blog/              # Coffee Moments community check-in feed
├── admin/             # Restricted administrative portal & management modules
├── rewards/           # Cozy Rewards membership registration & points ledger
├── contact/           # Location details, opening hours, and contact form
├── benefits/          # Membership tier perks & overview
├── offers/            # Promotional discount vouchers & active campaigns
├── activities/        # Store events & barista workshop listings
├── images/            # Static UI assets and product photography
├── uploads/           # User-uploaded profile avatars and community images
├── style/             # Native CSS stylesheets (mystyle.css, admin.css, etc.)
├── sql/               # database.sql (DDL schema + sample seed data)
├── db.php             # Root database connection shortcut
├── index.php          # Root web server entry point
└── logout.php         # Session termination script

```

---

## ⚙️ System Requirements

* **PHP Version**: `8.2` or `8.3` (with `mysqli` extension enabled)
* **Database**: MySQL / MariaDB `8.x` (Port `3308` or default `3306`)
* **Local Web Server**: WampServer, XAMPP, or MAMP
* **Browser**: Any modern browser (Google Chrome, Microsoft Edge, Firefox, Safari)

---

## 🚀 Setup & Local Installation

1. **Clone the Repository:**
```bash
git clone [https://github.com/jieying-lai/coffee-ordering-platform.git](https://github.com/jieying-lai/coffee-ordering-platform.git)

```


2. **Web Server Placement:**
Copy or move the `coffee-ordering-platform` folder into your local web root directory:
* **WampServer**: `C:\wamp64\www\coffee-ordering-platform`
* **XAMPP**: `C:\xampp\htdocs\coffee-ordering-platform`


3. **Database Initialization:**
* Launch your local MySQL service.
* Open phpMyAdmin (`http://localhost/phpmyadmin`).
* Create a new database named `cozy_coffee_db`.
* Import the SQL file located at `sql/database.sql` into `cozy_coffee_db` to build all 19 tables and insert development data.


4. **Database Configuration Verification:**
Verify connection parameters in `includes/db_connect.php` (and root `db.php`) to match your local server environment:
```php
$host     = "127.0.0.1";
$port     = 3308; // Update to 3306 if using standard port
$dbname   = "cozy_coffee_db";
$username = "root";
$password = "";     // Set local root password if configured

```


5. **Run the Application:**
Open your browser and navigate to:
```text
http://localhost/coffee-ordering-platform/home/index.php

```



---

## 🔑 Demo Test Credentials

For project assessment and grading evaluation, use the following pre-seeded test accounts:

### Administrative Back-Office

* **Portal URL**: `http://localhost/coffee-ordering-platform/admin/login.php`
* **Username**: `admin`
* **Password**: `admin123`
* **Role**: Full Administrator (Orders, Menu Inventory, Vouchers, Blog Moderation, Customer Inquiries)

### Customer Accounts

* **Portal URL**: `http://localhost/coffee-ordering-platform/login/index.php`

| Customer Profile | Username | Email | Password | Account Tier & Status |
| --- | --- | --- | --- | --- |
| **VIP Customer** | `customer_vip` | `vip@demo.com` | `12345678` | Cozy Rewards VIP (`CR000001` · 350 pts) |
| **Standard Member** | `customer_regular` | `member@demo.com` | `12345678` | Cozy Rewards Member (`CR000007` · 150 pts) |

---

## ⚠️ Architecture Notes & Known Behaviors

* **Database Connection Files**: The application references connection configurations in both `includes/db_connect.php` and the root-level `db.php`. When updating local database port or password credentials, ensure both files remain synchronized.
* **Legacy Modal Routes**: `profile/order_detail.php` and `blog/user_posts.php` are legacy entry scripts preserved for routing compatibility. Their functional workflows have been superseded by the integrated receipt modal on `profile/orders.php` and the author preview modal on `blog/index.php`.
* **Admin Module Protection**: `admin/manage_about.php` is protected by administrative session authentication guards and accessible via direct path pending sidebar navigation integration.

---

## 🎥 Video Demonstration

* **Project Walkthrough & Feature Demo**: [Cozy Coffee Co. - Demonstration Video](https://drive.google.com/file/d/10qJF2oBf9TigdZ_np3nhsxpVN-UlAGTC/view?usp=sharing)

---
