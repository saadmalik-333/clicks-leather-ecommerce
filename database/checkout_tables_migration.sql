-- ============================================================
-- CHECKOUT TABLES MIGRATION
-- Adds support for guest orders, detailed shipping addresses,
-- shipping methods/costs, and price capture at order time
-- ============================================================

-- Step 1: Add new columns to orders table before dropping the old one
ALTER TABLE `orders`
    ADD COLUMN `email` VARCHAR(255) NOT NULL AFTER `user_id`,
    ADD COLUMN `phone` VARCHAR(50) NOT NULL AFTER `email`,
    ADD COLUMN `full_name` VARCHAR(255) NOT NULL AFTER `phone`,
    ADD COLUMN `address_line1` VARCHAR(255) NOT NULL AFTER `full_name`,
    ADD COLUMN `address_line2` VARCHAR(255) DEFAULT NULL AFTER `address_line1`,
    ADD COLUMN `city` VARCHAR(100) NOT NULL AFTER `address_line2`,
    ADD COLUMN `state` VARCHAR(100) NOT NULL AFTER `city`,
    ADD COLUMN `country` VARCHAR(100) NOT NULL AFTER `state`,
    ADD COLUMN `postal_code` VARCHAR(20) NOT NULL AFTER `country`,
    ADD COLUMN `shipping_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `postal_code`,
    ADD COLUMN `shipping_method` VARCHAR(50) NOT NULL DEFAULT 'free' AFTER `shipping_cost`;

-- Step 2: Modify user_id to be nullable (for guest orders)
ALTER TABLE `orders`
    MODIFY COLUMN `user_id` INT UNSIGNED DEFAULT NULL;

-- Step 3: Modify status ENUM to include 'pending_payment'
ALTER TABLE `orders`
    MODIFY COLUMN `status` ENUM('pending_payment', 'pending', 'processing', 'shipped', 'delivered', 'cancelled') 
    NOT NULL DEFAULT 'pending_payment';

-- Step 4: Drop the old shipping_address column (now replaced by separate fields)
ALTER TABLE `orders`
    DROP COLUMN `shipping_address`;

-- Step 5: Add price_at_order column to order_items (to capture price at time of order)
ALTER TABLE `order_items`
    ADD COLUMN `price_at_order` DECIMAL(10,2) NOT NULL AFTER `variant_id`;

-- ============================================================
-- VERIFICATION QUERIES (run these to verify the migration)
-- ============================================================
-- DESCRIBE orders;
-- DESCRIBE order_items;
