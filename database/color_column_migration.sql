-- Add color column to cart_items and order_items tables
-- Run this in phpMyAdmin to add the columns

ALTER TABLE cart_items ADD COLUMN color VARCHAR(50) DEFAULT NULL AFTER variant_id;

ALTER TABLE order_items ADD COLUMN color VARCHAR(50) DEFAULT NULL AFTER variant_id;
