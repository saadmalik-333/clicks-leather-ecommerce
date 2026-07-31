-- ============================================================
-- SETTINGS TABLE MIGRATION
-- Creates a key-value store for site-wide settings
-- ============================================================

CREATE TABLE IF NOT EXISTS `settings` (
    `setting_key` VARCHAR(100) PRIMARY KEY,
    `setting_value` TEXT NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- VERIFICATION QUERY (run this to verify the migration)
-- ============================================================
-- DESCRIBE settings;
