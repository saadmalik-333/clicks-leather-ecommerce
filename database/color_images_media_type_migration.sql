-- Migration for color-specific image galleries feature
-- This allows multiple images per color instead of just one

-- Step 1: Drop the old unique constraint that prevented multiple images per color
ALTER TABLE product_color_images DROP INDEX unique_product_color;

-- Step 2: Add media_type and sort_order columns to product_color_images table
ALTER TABLE product_color_images ADD COLUMN media_type VARCHAR(10) DEFAULT 'image' AFTER image_path;

ALTER TABLE product_color_images ADD COLUMN sort_order INT UNSIGNED NOT NULL DEFAULT 0 AFTER media_type;
