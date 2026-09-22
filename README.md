# 🐸 Pepe CC Shop — PHP Project

Full-featured PHP + MySQL e-commerce shop with Stripe payment integration.

---

## 📁 File Structure

```
pepe_cc_shop/
│
├── config/
│   ├── database.php      ← DB credentials & connection function
│   ├── helpers.php       ← Session, auth, flash, sanitize helpers
│   ├── install.sql         ← Run ONCE to create DB + seed data
│   └── stripe.php        ← Stripe PaymentIntent helper
│
├── includes/
│   ├── header.php        ← Shared HTML head + topbar (included on every page)
│   └── footer.php        ← Shared footer
│
├── assets/
│   └── style.css         ← All CSS styles
│
├── auth/
│   ├── login.php         ← Login form & handler
│   ├── register.php      ← Registration form & handler
│   └── logout.php        ← Session destroy & redirect
│
├── admin/
│   ├── index.php         ← Admin dashboard with stats
│   ├── products.php      ← List / delete / update stock
│   ├── add_product.php   ← Add new product
│   ├── edit_product.php  ← Edit existing product
│   ├── users.php         ← List users, change roles, delete
│   ├── orders.php        ← All orders, update status
│   └── news.php          ← Publish / delete news articles
│
├── stripe/
│   └── webhook.php       ← Stripe webhook handler
│
├── index.php             ← Homepage with featured products
├── shop.php              ← Credit card shop (search + sort)
├── merch.php             ← Merchandise store (tabs by category)
├── checkout.php          ← Checkout page + Stripe payment form
├── orders.php            ← Order history (login required)
├── memes.php             ← Meme gallery (upload + like)
├── community.php         ← Forum (post + like)
└── news.php              ← News feed
```

---

## ⚡ Quick Start

### 1. Requirements
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Apache / Nginx with mod_rewrite
- Composer (for Stripe SDK)

### 2. Database Setup

```bash
mysql -u root -p < config/install.sql
```

This creates the `pepe_cc_shop` database and seeds all tables with demo data.

### 3. Configure Database

Edit `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'pepe_cc_shop');
```

### 4. Install Stripe SDK

```bash
composer require stripe/stripe-php
```

### 5. Configure Stripe

Edit `config/stripe.php`:

```php
define('STRIPE_PUBLIC_KEY',  'pk_test_...');
define('STRIPE_SECRET_KEY',  'sk_test_...');
define('STRIPE_WEBHOOK_SECRET', 'whsec_...');
```

Then in `checkout.php`, uncomment the real Stripe block (see comments in the file).

### 6. Configure Web Server

**Apache** — create `.htaccess` in project root:

```apache
RewriteEngine On
RewriteBase /

# Redirect to HTTPS
# RewriteCond %{HTTPS} off
# RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

**Nginx** — add to your server block:

```nginx
root /var/www/pepe_cc_shop;
index index.php;
location / { try_files $uri $uri/ /index.php?$query_string; }
location ~ \.php$ { fastcgi_pass unix:/run/php/php8.1-fpm.sock; include fastcgi_params; }
```

### 7. Register Stripe Webhook

In your Stripe Dashboard → Webhooks → Add endpoint:
```
https://yourdomain.com/stripe/webhook.php
```

Events to select:
- `payment_intent.succeeded`
- `payment_intent.payment_failed`
- `charge.refunded`

---

## 👤 Demo Accounts

| Role  | Email            | Password  |
|-------|-----------------|-----------|
| Admin | admin@pepe.cc   | password  |
| User  | pepe@feels.good | password  |

> Passwords are hashed with `password_hash()` (bcrypt). The seed SQL uses the hash for `"password"`.

---

## 🗄️ Database Tables

| Table        | Purpose                              |
|-------------|---------------------------------------|
| `users`      | Registered users (admin/user roles)  |
| `products`   | Shop & merch products                |
| `orders`     | Customer orders                      |
| `order_items`| Line items per order                 |
| `memes`      | Pepe meme gallery entries            |
| `meme_likes` | Many-to-many: user ↔ meme likes      |
| `posts`      | Community forum posts                |
| `post_likes` | Many-to-many: user ↔ post likes      |
| `news`       | News articles                        |

---

## 💳 Stripe Integration

The checkout flow works like this:

1. User fills shipping info + card details
2. `checkout.php` calls `createPaymentIntent()` from `config/stripe.php`
3. Stripe returns a `payment_intent_id`
4. Order is saved to DB with `status='pending'`
5. Stripe fires webhook → `stripe/webhook.php` sets `status='paid'`

In **test/demo mode**, the real Stripe call is skipped and a simulated `pi_sim_...` ID is used.

---

## 🛡️ Security Notes

- All user input is sanitized with `clean()` (htmlspecialchars + strip_tags)
- Passwords are hashed with PHP's `password_hash()` (bcrypt)
- Prepared statements used for ALL database queries
- Admin pages check `isAdmin()` on every load
- Stripe webhook signature is verified via `verifyStripeWebhook()`

---

## 🐸 Feels Good, Man.
