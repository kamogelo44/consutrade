-- ConsuTrade Database Schema
-- Author: Kamogelo Phale
-- Based on EERD designed in Deliverable 2

-- ============================================================
-- CATEGORIES TABLE
-- I want it first because PRODUCTS references it
-- ============================================================
CREATE TABLE categories (
    category_id   INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL
);

-- ============================================================
-- USERS TABLE
-- Parent table for Buyer, Seller and Admin (EERD specialisation)
-- ============================================================
CREATE TABLE users (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(255) NOT NULL,
    email       VARCHAR(255) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    phone       VARCHAR(20),
    location    VARCHAR(255),
    role        ENUM('buyer', 'seller', 'admin') NOT NULL DEFAULT 'buyer',
    id_verified TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- PRODUCTS TABLE
-- References users (seller) and categories
-- ============================================================
CREATE TABLE products (
    product_id   INT AUTO_INCREMENT PRIMARY KEY,
    seller_id    INT NOT NULL,
    category_id  INT NOT NULL,
    title        VARCHAR(255) NOT NULL,
    description  TEXT,
    price        DECIMAL(10, 2) NOT NULL,
    image_url    VARCHAR(500),
    status       ENUM('active', 'sold', 'suspended') NOT NULL DEFAULT 'active',
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id)   REFERENCES users(user_id)       ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE RESTRICT
);

-- ============================================================
-- ORDERS TABLE
-- References users (buyer and seller) and products
-- ============================================================
CREATE TABLE orders (
    order_id    INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id    INT NOT NULL,
    seller_id   INT NOT NULL,
    product_id  INT NOT NULL,
    quantity    INT NOT NULL DEFAULT 1,
    total_price DECIMAL(10, 2) NOT NULL,
    status      ENUM('pending', 'completed', 'disputed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id)   REFERENCES users(user_id)    ON DELETE CASCADE,
    FOREIGN KEY (seller_id)  REFERENCES users(user_id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- ============================================================
-- TRANSACTIONS TABLE
-- References orders — one transaction per order
-- ============================================================
CREATE TABLE transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id       INT NOT NULL UNIQUE,
    payfast_ref    VARCHAR(255),
    amount         DECIMAL(10, 2) NOT NULL,
    status         ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    paid_at        DATETIME,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
);

-- ============================================================
-- REVIEWS TABLE
-- References users (buyer and seller) and orders
-- ============================================================
CREATE TABLE reviews (
    review_id  INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id   INT NOT NULL,
    seller_id  INT NOT NULL,
    order_id   INT NOT NULL,
    rating     INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment    TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id)  REFERENCES users(user_id)  ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(user_id)  ON DELETE CASCADE,
    FOREIGN KEY (order_id)  REFERENCES orders(order_id) ON DELETE CASCADE
);

-- ============================================================
-- DEFAULT CATEGORIES
-- Seed data so the dropdowns work from the start
-- ============================================================
INSERT INTO categories (category_name) VALUES
('Clothing'),
('Electronics'),
('Food and Drinks'),
('Furniture'),
('Other');