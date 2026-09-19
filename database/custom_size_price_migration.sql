-- Add custom_size_price column to products table
-- Run this in phpMyAdmin to add the column

ALTER TABLE products ADD COLUMN custom_size_price DECIMAL(10,2) DEFAULT NULL AFTER price;
