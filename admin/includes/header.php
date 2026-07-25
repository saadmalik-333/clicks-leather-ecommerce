<?php
/**
 * Clicks Leather — Admin Header
 * Shared across all admin pages
 */
require_once dirname(__DIR__, 2) . '/includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

// Verify admin access
require_admin();

// Get current page for active nav highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Admin Panel' ?> — Clicks Leather Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/admin.css">
</head>
<body class="admin-body">
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="admin-sidebar">
        <div class="sidebar-header">
            <a href="<?= ADMIN_URL ?>/dashboard.php" class="sidebar-logo">
                <span class="logo-icon">CL</span>
                <span class="logo-text">Clicks Leather</span>
            </a>
            <button class="sidebar-toggle" id="sidebar-toggle" aria-label="Toggle sidebar">
                <span></span><span></span><span></span>
            </button>
        </div>

        <nav class="sidebar-nav">
            <ul>
                <li class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>/dashboard.php" id="nav-dashboard">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                            <rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'products.php' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>/products.php" id="nav-products">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/>
                            <line x1="7" y1="7" x2="7.01" y2="7"/>
                        </svg>
                        <span>All Products</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'add_product.php' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>/add_product.php" id="nav-add-product">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/>
                            <line x1="8" y1="12" x2="16" y2="12"/>
                        </svg>
                        <span>Add Product</span>
                    </a>
                </li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <div class="admin-user-info">
                <div class="admin-avatar"><?= strtoupper(substr($_SESSION['user_naam'] ?? 'A', 0, 1)) ?></div>
                <div class="admin-user-details">
                    <span class="admin-user-name"><?= htmlspecialchars($_SESSION['user_naam'] ?? 'Admin') ?></span>
                    <span class="admin-user-role">Administrator</span>
                </div>
            </div>
            <a href="<?= PUBLIC_URL ?>/logout.php" class="sidebar-logout" id="admin-logout">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                    <polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main" id="admin-main">
        <header class="admin-topbar">
            <button class="mobile-toggle" id="mobile-toggle" aria-label="Toggle menu">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/>
                    <line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
            <h2 class="page-title"><?= $page_title ?? 'Dashboard' ?></h2>
            <div class="topbar-actions">
                <a href="<?= PUBLIC_URL ?>/index.php" class="btn btn-outline btn-sm" target="_blank" id="view-site-btn">
                    View Site ↗
                </a>
            </div>
        </header>

        <div class="admin-content">
            <?= display_flash_message() ?>
