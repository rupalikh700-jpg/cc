SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET character_set_client     = utf8mb4;
SET character_set_results    = utf8mb4;
SET character_set_connection = utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = '';

CREATE DATABASE IF NOT EXISTS try_sex
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE try_sex;

CREATE TABLE IF NOT EXISTS users (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    first_name        VARCHAR(100) NOT NULL,
    last_name         VARCHAR(100) NOT NULL,
    email             VARCHAR(191) NOT NULL UNIQUE,
    username          VARCHAR(100) NOT NULL UNIQUE,
    password          VARCHAR(255) NOT NULL,
    role              ENUM('user','admin','vendor') DEFAULT 'user',
    vendor_status     ENUM('pending','approved','rejected') DEFAULT NULL,
    shop_name         VARCHAR(255) DEFAULT NULL,
    shop_desc         TEXT         DEFAULT NULL,
    vendor_applied_at DATETIME     DEFAULT NULL,
    balance           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_spent       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    referral_code     VARCHAR(20)  DEFAULT NULL UNIQUE,
    referred_by       INT          DEFAULT NULL,
    vip_level         INT          DEFAULT 0,
    created_at        DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    price             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    sales             INT           DEFAULT 0,
    vendor_id         INT           DEFAULT NULL,
    vendor_status     ENUM('pending','approved','rejected') DEFAULT 'approved',
    card_number       VARCHAR(255)  DEFAULT NULL,
    card_expiry       VARCHAR(10)   DEFAULT NULL,
    card_cvv          VARCHAR(10)   DEFAULT NULL,
    card_name         VARCHAR(255)  DEFAULT NULL,
    card_bin          VARCHAR(20)   DEFAULT NULL,
    card_type         ENUM('credit','debit') DEFAULT 'credit',
    card_brand        VARCHAR(50)   DEFAULT 'visa',
    card_email        VARCHAR(255)  DEFAULT NULL,
    card_phone        VARCHAR(50)   DEFAULT NULL,
    card_dob          VARCHAR(20)   DEFAULT NULL,
    card_address      TEXT          DEFAULT NULL,
    card_city         VARCHAR(100)  DEFAULT NULL,
    card_state        VARCHAR(100)  DEFAULT NULL,
    card_zip          VARCHAR(20)   DEFAULT NULL,
    card_country      VARCHAR(100)  DEFAULT NULL,
    bank_name         VARCHAR(255)  DEFAULT NULL,
    card_balance_min  DECIMAL(10,2) DEFAULT NULL,
    card_balance_max  DECIMAL(10,2) DEFAULT NULL,
    card_vbv          ENUM('yes','no','unknown') DEFAULT 'unknown',
    card_status       ENUM('live','dead','unknown') DEFAULT 'unknown',
    stock             INT           DEFAULT 1,
    created_at        DATETIME      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    order_code      VARCHAR(20)   NOT NULL UNIQUE,
    user_id         INT           NOT NULL,
    total_price     DECIMAL(10,2) NOT NULL,
    status          ENUM('pending','completed','refunded','cancelled') DEFAULT 'pending',
    payment_method  VARCHAR(50)   DEFAULT NULL,
    payment_id      VARCHAR(255)  DEFAULT NULL,
    created_at      DATETIME      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    order_id        INT           NOT NULL,
    product_id      INT           NOT NULL,
    quantity        INT           NOT NULL DEFAULT 1,
    price           DECIMAL(10,2) NOT NULL,
    vendor_id       INT           DEFAULT NULL,
    card_number     VARCHAR(255)  DEFAULT NULL,
    card_expiry     VARCHAR(10)   DEFAULT NULL,
    card_cvv        VARCHAR(10)   DEFAULT NULL,
    card_name       VARCHAR(255)  DEFAULT NULL,
    card_bin        VARCHAR(20)   DEFAULT NULL,
    card_type       VARCHAR(20)   DEFAULT NULL,
    card_brand      VARCHAR(50)   DEFAULT NULL,
    card_email      VARCHAR(255)  DEFAULT NULL,
    card_phone      VARCHAR(50)   DEFAULT NULL,
    card_dob        VARCHAR(20)   DEFAULT NULL,
    card_address    TEXT          DEFAULT NULL,
    card_city       VARCHAR(100)  DEFAULT NULL,
    card_state      VARCHAR(100)  DEFAULT NULL,
    card_zip        VARCHAR(20)   DEFAULT NULL,
    card_country    VARCHAR(100)  DEFAULT NULL,
    bank_name       VARCHAR(255)  DEFAULT NULL,
    check_status    ENUM('unchecked','live','dead','unknown') DEFAULT 'unchecked',
    checked_at      DATETIME      DEFAULT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS card_checks (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    order_item_id   INT           NOT NULL,
    user_id         INT           NOT NULL,
    result          ENUM('live','dead','unknown') DEFAULT 'unknown',
    response_data   TEXT          DEFAULT NULL,
    cost            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    checked_at      DATETIME      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS card_bundles (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    description     TEXT         DEFAULT NULL,
    price           DECIMAL(10,2) NOT NULL,
    card_count      INT          NOT NULL DEFAULT 3,
    card_brand      VARCHAR(50)  DEFAULT NULL,
    card_country    VARCHAR(100) DEFAULT NULL,
    card_type       VARCHAR(20)  DEFAULT NULL,
    is_active       TINYINT(1)   DEFAULT 1,
    created_at      DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS deposit_bonuses (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    min_amount      DECIMAL(10,2) NOT NULL,
    bonus_percent   INT           NOT NULL,
    max_bonus       DECIMAL(10,2) NOT NULL DEFAULT 100.00,
    is_active       TINYINT(1)    DEFAULT 1,
    created_at      DATETIME      DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS referrals (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    referrer_id     INT NOT NULL,
    referred_id     INT NOT NULL,
    bonus_amount    DECIMAL(10,2) DEFAULT 0.00,
    status          ENUM('pending','credited') DEFAULT 'pending',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vip_levels (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    min_spent       DECIMAL(10,2) NOT NULL,
    discount_percent INT          DEFAULT 0,
    badge_color     VARCHAR(20)  DEFAULT '#00c853',
    badge_icon      VARCHAR(10)  DEFAULT 'star',
    created_at      DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS api_tokens (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    token           VARCHAR(64) NOT NULL UNIQUE,
    label           VARCHAR(255) DEFAULT NULL,
    last_used       DATETIME     DEFAULT NULL,
    created_at      DATETIME     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS memes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    emoji      VARCHAR(20)  NOT NULL DEFAULT '',
    category   ENUM('classic','rare','sad') DEFAULT 'classic',
    user_id    INT NOT NULL,
    likes      INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS meme_likes (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    meme_id INT NOT NULL,
    user_id INT NOT NULL,
    UNIQUE KEY unique_like (meme_id, user_id),
    FOREIGN KEY (meme_id) REFERENCES memes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    title      VARCHAR(255) NOT NULL,
    body       TEXT         NOT NULL,
    user_id    INT          NOT NULL,
    category   ENUM('general','memes','rare','market') DEFAULT 'general',
    likes      INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS post_likes (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    UNIQUE KEY unique_post_like (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    title      VARCHAR(255) NOT NULL,
    body       TEXT         NOT NULL,
    tag        VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS deposits (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT           NOT NULL,
    amount          DECIMAL(10,2) NOT NULL,
    bonus           DECIMAL(10,2) DEFAULT 0.00,
    currency        VARCHAR(10)   NOT NULL DEFAULT 'USDT',
    network         VARCHAR(20)   DEFAULT NULL,
    oxapay_id       VARCHAR(255)  DEFAULT NULL,
    oxapay_address  VARCHAR(255)  DEFAULT NULL,
    tx_hash         VARCHAR(255)  DEFAULT NULL,
    type            ENUM('deposit','refund','bonus') DEFAULT 'deposit',
    status          ENUM('pending','paid','expired','cancelled') DEFAULT 'pending',
    created_at      DATETIME      DEFAULT CURRENT_TIMESTAMP,
    paid_at         DATETIME      DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

SET @hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

INSERT IGNORE INTO users
    (first_name, last_name, email, username, password, role, vendor_status, shop_name, shop_desc, referral_code, vendor_applied_at)
VALUES
    ('Admin',  'Pepe', 'admin@pepe.cc',   'admin',      @hash, 'admin',  NULL,       NULL,               NULL, 'ADMIN001', NULL),
    ('Pepe',   'Frog', 'pepe@feels.good', 'pepefrog',   @hash, 'user',   NULL,       NULL,               NULL, 'PEPE001',  NULL),
    ('Vendor', 'Demo', 'vendor@pepe.cc',  'vendordemo', @hash, 'vendor', 'approved', 'Demo Vendor Shop', 'Official demo vendor store.', 'VEND001', NOW());

INSERT IGNORE INTO products
    (price, sales, stock, vendor_status, bank_name, card_balance_min, card_balance_max, card_vbv, card_number, card_expiry, card_cvv, card_name, card_bin, card_type, card_brand, card_email, card_phone, card_dob, card_address, card_city, card_state, card_zip, card_country)
VALUES
    (149.99, 128, 5, 'approved', 'JPMorgan Chase Bank', 5000, 10000, 'vbv', '4242424242424242', '12/26', '123', 'JOHN DOE', '424242', 'credit', 'visa', 'john@email.com', '+15551234567', '01/15/1990', '123 Main Street', 'New York', 'NY', '10001', 'United States'),
    (499.99, 42, 3, 'approved', 'Citibank', 10000, 25000, 'vbv', '5412751234567890', '08/27', '456', 'JANE SMITH', '541275', 'credit', 'mastercard', 'jane@email.com', '+15559876543', '03/22/1985', '456 Oak Avenue', 'Los Angeles', 'CA', '90001', 'United States'),
    (299.99, 77, 4, 'approved', 'American Express', 15000, 50000, 'vbv', '378282246310005', '05/28', '789', 'BOB WILSON', '378282', 'credit', 'amex', 'bob@email.com', '+15555551234', '07/10/1988', '789 Pine Road', 'Chicago', 'IL', '60601', 'United States'),
    (99.99, 210, 8, 'approved', 'Discover Bank', 2000, 5000, 'non_vbv', '6011111111111117', '11/25', '321', 'ALICE BROWN', '601111', 'debit', 'discover', 'alice@email.com', '+15554443333', '11/05/1992', '321 Elm Street', 'Houston', 'TX', '77001', 'United States'),
    (199.99, 95, 6, 'approved', 'JCB International', 3000, 8000, 'unknown', '3530111333300000', '09/26', '654', 'CHARLIE DAVIS', '353011', 'debit', 'jcb', 'charlie@email.com', '+15552224444', '02/18/1987', '654 Maple Drive', 'Phoenix', 'AZ', '85001', 'United States'),
    (24.99, 340, 12, 'approved', 'Diners Club', 500, 2000, 'non_vbv', '30569309025904', '03/25', '987', 'DAVID LEE', '305693', 'debit', 'diners', 'david@email.com', '+15551115555', '06/30/1995', '987 Cedar Lane', 'Philadelphia', 'PA', '19101', 'United States');

INSERT IGNORE INTO memes (name, emoji, category, user_id, likes) VALUES
    ('Classic Pepe', 'frog', 'classic', 1, 420), ('Sad Pepe', 'sad', 'sad', 1, 666),
    ('Smug Pepe', 'smug', 'classic', 1, 333), ('Rare Pepe #001', 'rare', 'rare', 1, 1337),
    ('Feels Good Man', 'classic', 'classic', 2, 888), ('Ultra Rare Pepe', 'rainbow', 'rare', 2, 9001);

INSERT IGNORE INTO posts (title, body, user_id, category, likes) VALUES
    ('Feels good man, just got my Gold Card!', 'Finally received my Rare Pepe Gold Edition card.', 1, 'market', 47),
    ('Best rare Pepe of 2024?', 'I think the Crystal Pepe Collector Card is the rarest.', 2, 'rare', 102),
    ('My Pepe meme collection', 'Been collecting rare Pepe memes since 2016.', 2, 'memes', 215);

INSERT IGNORE INTO news (title, body, tag) VALUES
    ('Crystal Edition Card Now Available', 'The most exclusive card in our lineup.', 'Product'),
    ('New Cashback Program', 'Cardholders now enjoy 5% cashback.', 'Finance'),
    ('Community Hits 10,000 Members', 'Our forum has reached a major milestone!', 'Community');

INSERT INTO deposit_bonuses (min_amount, bonus_percent, max_bonus, is_active) VALUES
    (10, 10, 5, 1), (50, 30, 30, 1), (100, 100, 100, 1);

INSERT INTO vip_levels (name, min_spent, discount_percent, badge_color, badge_icon) VALUES
    ('Bronze', 0, 0, '#cd7f32', 'bronze'),
    ('Silver', 500, 3, '#c0c0c0', 'silver'),
    ('Gold', 2000, 5, '#ffd700', 'gold'),
    ('Platinum', 5000, 8, '#e5e4e2', 'platinum'),
    ('Diamond', 15000, 12, '#b9f2ff', 'diamond');

INSERT INTO card_bundles (name, description, price, card_count, card_brand, card_country, is_active) VALUES
    ('3 USA Visa Pack', '3 random Visa credit cards from USA', 399.99, 3, 'visa', 'United States', 1),
    ('5 Mastercard Mix', '5 random Mastercard cards worldwide', 799.99, 5, 'mastercard', NULL, 1),
    ('2 AMEX Premium', '2 premium American Express cards', 599.99, 2, 'amex', NULL, 1);

INSERT INTO settings (setting_key, setting_value) VALUES
('oxapay_api_key', ''), ('oxapay_api_secret', ''), ('oxapay_webhook_url', ''),
('deposit_min', '10'), ('deposit_max', '10000'), ('currency', 'USD'),
('site_name', 'Pepe CC Shop'), ('site_tagline', 'Premium CC Cards'),
('checker_cost', '0.50'), ('checker_api_url', 'https://api.chkr.cc/'), ('checker_api_token', ''), ('checker_enabled', '1'),
('auto_fetch_bank_name', '1'), ('bin_lookup_api', 'binlist.net'),
('show_balance_range', '1'), ('show_vbv_status', '1'), ('show_daily_counter', '1'),
('deposit_bonuses_enabled', '1'), ('packs_enabled', '1'),
('referral_enabled', '1'), ('referral_bonus', '5.00'),
('vip_enabled', '1'),
('telegram_bot_token', ''), ('telegram_chat_id', ''),
('telegram_notify_new_order', '1'), ('telegram_notify_deposit', '1'), ('telegram_notify_checker', '1'),
('api_enabled', '0'), ('maintenance_mode', '0')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

SELECT 'pepe_cc_shop ready!' AS result;

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('order','deposit','system','support','alert') DEFAULT 'system',
    is_read TINYINT(1) DEFAULT 0,
    link VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Support tickets
CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    status ENUM('open','replied','closed') DEFAULT 'open',
    priority ENUM('low','medium','high') DEFAULT 'medium',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ticket messages
CREATE TABLE IF NOT EXISTS ticket_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    sender_id INT NOT NULL,
    message TEXT NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key, setting_value) VALUES
('support_enabled', '1')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);
