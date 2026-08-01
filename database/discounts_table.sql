-- ============================================================
-- DISCOUNTS TABLE MIGRATION
-- Creates a table for managing product, category, and sitewide discounts
-- ============================================================

CREATE TABLE IF NOT EXISTS `discounts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `type` ENUM('sitewide', 'category', 'product') NOT NULL,
    `target_id` INT UNSIGNED NULL,
    `discount_percent` DECIMAL(5,2) NOT NULL,
    `start_date` DATE NULL,
    `end_date` DATE NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_type_target` (`type`, `target_id`),
    INDEX `idx_active_dates` (`is_active`, `start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- VERIFICATION QUERY (run this to verify the migration)
-- ============================================================
-- DESCRIBE discounts;
