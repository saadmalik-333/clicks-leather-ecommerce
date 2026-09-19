-- Add hex_code column to product_variants table
-- This column stores the hex color code for each color variant
-- Used to display color swatches on the product detail page
ALTER TABLE product_variants ADD COLUMN hex_code VARCHAR(7) DEFAULT NULL AFTER color;
