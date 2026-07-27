-- ============================================================
-- Clicks Leather — Database Schema
-- Database: clicks_leather
-- ============================================================

CREATE DATABASE IF NOT EXISTS `clicks_leather` 
  DEFAULT CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `clicks_leather`;

-- ============================================================
-- 1. USERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `naam` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password_hashed` VARCHAR(255) DEFAULT NULL,
    `google_id` VARCHAR(255) DEFAULT NULL,
    `role` ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role`),
    INDEX `idx_google_id` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. CATEGORIES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `naam` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed categories
INSERT INTO `categories` (`naam`) VALUES
    ('Wallets'),
    ('Ladies Bags'),
    ('Leather Jackets'),
    ('Laptop Bags'),
    ('Backpacks'),
    ('Duffel Bags'),
    ('Leather Shoes');

-- ============================================================
-- 3. PRODUCTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `naam` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `price` DECIMAL(10,2) NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `has_personalization` ENUM('yes', 'no') NOT NULL DEFAULT 'no',
    `color` VARCHAR(50) DEFAULT NULL,
    `material` VARCHAR(50) DEFAULT NULL,
    `detail_title` VARCHAR(500) DEFAULT NULL,
    `detail_description` TEXT DEFAULT NULL,
    `image_path` VARCHAR(500) DEFAULT NULL,
    `image_path_alt` VARCHAR(500) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_category` (`category_id`),
    INDEX `idx_price` (`price`),
    INDEX `idx_color` (`color`),
    INDEX `idx_material` (`material`),

    CONSTRAINT `fk_products_category`
        FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. PRODUCT IMAGES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `product_images` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT UNSIGNED NOT NULL,
    `image_path` VARCHAR(500) NOT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_product` (`product_id`),
    INDEX `idx_sort_order` (`sort_order`),

    CONSTRAINT `fk_product_images_product`
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. PRODUCT VARIANTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `product_variants` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT UNSIGNED NOT NULL,
    `size` VARCHAR(50) DEFAULT NULL,
    `color` VARCHAR(50) DEFAULT NULL,
    `stock_quantity` INT UNSIGNED NOT NULL DEFAULT 0,

    INDEX `idx_product` (`product_id`),

    CONSTRAINT `fk_variants_product`
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. ORDERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `status` ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') 
        NOT NULL DEFAULT 'pending',
    `shipping_address` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_user` (`user_id`),
    INDEX `idx_status` (`status`),

    CONSTRAINT `fk_orders_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. ORDER ITEMS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `variant_id` INT UNSIGNED DEFAULT NULL,
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
    `personalization_text` VARCHAR(255) DEFAULT NULL,

    INDEX `idx_order` (`order_id`),
    INDEX `idx_product` (`product_id`),

    CONSTRAINT `fk_orderitems_order`
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `fk_orderitems_product`
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT `fk_orderitems_variant`
        FOREIGN KEY (`variant_id`) REFERENCES `product_variants`(`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DEFAULT ADMIN USER
-- Password: Admin@123 (change after first login!)
-- Generated with: password_hash('Admin@123', PASSWORD_BCRYPT)
-- ============================================================
INSERT INTO `users` (`naam`, `email`, `password_hashed`, `role`) VALUES
    ('Admin', 'admin@clicksleather.com', '$2y$10$rIKWU5GM9ay6h3eA/jedGOPnodf4a4RT/v6eWPl.fTBdc/pvhXW0.', 'admin');
