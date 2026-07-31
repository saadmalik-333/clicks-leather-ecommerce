<?php
/**
 * Clicks Leather — Shared Header Include
 * Contains sticky header with announcement bar, main header, and navigation
 */
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
                <a href="#" class="icon-link" title="Search">
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
                
                <a href="#" class="icon-link" title="Help">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                </a>
                
                <a href="#" class="icon-link cart-link" title="Bag">
                    <div class="cart-icon-wrapper">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 01-8 0"></path></svg>
                        <span class="cart-count">0</span>
                    </div>
                </a>
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

<!-- Flash Messages -->
<div style="position:fixed; top:80px; right:20px; z-index:1001; max-width:400px;">
    <?= display_flash_message() ?>
</div>
