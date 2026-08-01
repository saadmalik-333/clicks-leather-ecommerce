-- ============================================================
-- CART ITEMS DISCOUNT COLUMNS MIGRATION
-- Adds discounted_price and discount_percent columns to cart_items table
-- ============================================================

ALTER TABLE `cart_items` 
ADD COLUMN `discounted_price` DECIMAL(10,2) NULL AFTER `price`,
ADD COLUMN `discount_percent` DECIMAL(5,2) NULL AFTER `discounted_price`;

-- ============================================================
-- VERIFICATION QUERY (run this to verify the migration)
-- ============================================================
-- DESCRIBE cart_items;
