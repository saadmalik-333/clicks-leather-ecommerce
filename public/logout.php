<?php
/**
 * Clicks Leather — Logout
 */
require_once __DIR__ . '/../includes/auth.php';

logout_user();
set_flash_message('success', 'You have been logged out successfully.');
redirect(PUBLIC_URL . '/login.php');
