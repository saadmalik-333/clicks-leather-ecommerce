-- ============================================================
-- HERO SLIDES TABLE MIGRATION
-- Stores homepage hero carousel images (3 slides)
-- ============================================================

CREATE TABLE IF NOT EXISTS `hero_slides` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `image_path` VARCHAR(255) NOT NULL,
    `sort_order` TINYINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- VERIFICATION QUERY (run this to verify the migration)
-- ============================================================
-- DESCRIBE hero_slides;
