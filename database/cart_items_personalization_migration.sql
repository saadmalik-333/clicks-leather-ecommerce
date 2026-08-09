-- Add personalization_text column to cart_items table
ALTER TABLE cart_items ADD COLUMN personalization_text VARCHAR(255) DEFAULT NULL AFTER discount_percent;
