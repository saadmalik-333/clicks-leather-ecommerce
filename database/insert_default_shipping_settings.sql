-- ============================================================
-- INSERT DEFAULT SHIPPING SETTINGS
-- Inserts default shipping configuration into settings table
-- ============================================================

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('shipping_is_free', 'yes'),
('shipping_flat_cost', '15.00')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- ============================================================
-- VERIFICATION QUERY (run this to verify the insertion)
-- ============================================================
-- SELECT * FROM settings WHERE setting_key IN ('shipping_is_free', 'shipping_flat_cost');
