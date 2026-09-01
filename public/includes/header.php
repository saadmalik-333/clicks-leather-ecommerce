<?php
/**
 * Clicks Leather — Shared Header Include
 * Contains sticky header with announcement bar, main header, and navigation
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for session expiry and redirect if needed
require_once INCLUDES_PATH . '/functions.php';
require_active_session();
?>
<!-- Sticky Header Container -->
<div class="header-container" id="site-header">
    
    <!-- Announcement Bar (Tier 1) -->
    <div class="announcement-bar">
        <span>60 DAY RETURNS</span>
        <span class="separator">|</span>
        <span>WORLDWIDE SHIPPING</span>
        <span class="separator">|</span>
        <span>1 YEAR WARRANTY</span>
    </div>

    <!-- Main Header (Tier 2) -->
    <header class="main-header">
        <div class="header-left">
        </div>
        
        <div class="header-center">
            <a href="<?= PUBLIC_URL ?>/index.php" class="header-logo-link">
                <img src="<?= PUBLIC_URL ?>/img/logo/clicks_leather_logo_dark_transparent.png" alt="Clicks Leather" class="header-logo-img">
            </a>
        </div>
        
        <div class="header-right">
            <div class="header-icons">
                <a href="#" class="icon-link" id="search-icon" title="Search">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </a>
                
                <?php if (is_logged_in()): ?>
                    <a href="<?= is_admin() ? ADMIN_URL . '/dashboard.php' : PUBLIC_URL . '/account.php' ?>" class="icon-link" title="Account">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </a>
                <?php else: ?>
                    <a href="<?= PUBLIC_URL ?>/login.php" class="icon-link" title="Account">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </a>
                <?php endif; ?>
                
                <a href="<?= PUBLIC_URL ?>/faq.php" class="icon-link faq-icon-link" title="Help">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                </a>
                
                <a href="#" class="icon-link cart-link" title="Bag">
                    <div class="cart-icon-wrapper">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 01-8 0"></path></svg>
                        <span class="cart-count">0</span>
                    </div>
                </a>
            </div>
            
            <!-- Search Dropdown -->
            <div id="search-dropdown" class="search-dropdown">
                <div class="search-input-wrapper">
                    <input type="text" id="search-input" class="search-input" placeholder="Search products..." autocomplete="off">
                </div>
                <div id="search-results" class="search-results">
                    <div class="search-message">Type to search products</div>
                </div>
            </div>
            
            <a href="#newsletter" class="btn btn-primary btn-sm subscribe-btn">SUBSCRIBE AND GET 10% OFF</a>
        </div>
    </header>

    <!-- Navigation Bar (Tier 3) -->
    <nav class="main-nav">
        <ul class="nav-categories">
            <li><a href="<?= PUBLIC_URL ?>/products.php?category=wallets">WALLETS</a></li>
            <li><a href="<?= PUBLIC_URL ?>/products.php?category=ladies-bags">LADIES BAGS</a></li>
            <li><a href="<?= PUBLIC_URL ?>/products.php?category=leather-jackets">LEATHER JACKETS</a></li>
            <li><a href="<?= PUBLIC_URL ?>/products.php?category=laptop-bags">LAPTOP BAGS</a></li>
            <li><a href="<?= PUBLIC_URL ?>/products.php?category=backpacks">BACKPACKS</a></li>
            <li><a href="<?= PUBLIC_URL ?>/products.php?category=duffel-bags">DUFFEL BAGS</a></li>
            <li><a href="<?= PUBLIC_URL ?>/products.php?category=leather-shoes">LEATHER SHOES</a></li>
        </ul>
    </nav>
</div>

<!-- Hamburger Button (visible below 968px) -->
<button class="hamburger-btn" id="hamburgerBtn" aria-label="Open menu">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
</button>

<!-- Mobile Menu Overlay (fixed, direct body child) -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-backdrop" id="mobileMenuBackdrop"></div>
    <div class="mobile-menu-content">
        <button class="mobile-menu-close" id="mobileMenuClose" aria-label="Close menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <nav class="mobile-nav">
            <ul class="mobile-nav-categories">
                <li><a href="<?= PUBLIC_URL ?>/products.php?category=wallets">Wallets</a></li>
                <li><a href="<?= PUBLIC_URL ?>/products.php?category=ladies-bags">Ladies Bags</a></li>
                <li><a href="<?= PUBLIC_URL ?>/products.php?category=leather-jackets">Leather Jackets</a></li>
                <li><a href="<?= PUBLIC_URL ?>/products.php?category=laptop-bags">Laptop Bags</a></li>
                <li><a href="<?= PUBLIC_URL ?>/products.php?category=backpacks">Backpacks</a></li>
                <li><a href="<?= PUBLIC_URL ?>/products.php?category=duffel-bags">Duffel Bags</a></li>
                <li><a href="<?= PUBLIC_URL ?>/products.php?category=leather-shoes">Leather Shoes</a></li>
                <li><a href="<?= PUBLIC_URL ?>/faq.php">FAQ</a></li>
                <li><a href="<?= PUBLIC_URL ?>/contact.php">Contact Us</a></li>
            </ul>
        </nav>
    </div>
</div>

<!-- Flash Messages -->
<div style="position:fixed; top:80px; right:20px; z-index:1001; max-width:400px;">
    <?= display_flash_message() ?>
</div>

<script src="<?= PUBLIC_URL ?>/js/flash-message.js"></script>

<!-- Search Script -->
<script src="<?= PUBLIC_URL ?>/js/search.js"></script>

<!-- Mobile Menu Script -->
<script src="<?= PUBLIC_URL ?>/js/mobile-menu.js"></script>

