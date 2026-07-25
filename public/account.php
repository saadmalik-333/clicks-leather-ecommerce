<?php
/**
 * Clicks Leather — Customer Account Page
 */
require_once __DIR__ . '/../includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

// Require login
require_login();

// Get user info from session
$user_name = $_SESSION['user_name'] ?? '';
$user_email = $_SESSION['user_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Clicks Leather — My Account">
    <title>My Account — Clicks Leather</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/style.css">
</head>
<body>
    <!-- Sticky Header Container -->
    <div class="header-container" id="site-header">
        
        <!-- Announcement Bar (Tier 1) -->
        <div class="announcement-bar">
            <span>60 DAY RETURNS</span>
            <span class="separator">|</span>
            <span>FREE WORLDWIDE SHIPPING</span>
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

    <!-- Account Page Section -->
    <section class="account-page">
        <div class="container">
            <div class="account-header">
                <h1>My Account</h1>
                <p>Welcome back, <?= htmlspecialchars($user_name) ?>!</p>
            </div>

            <div class="account-layout">
                <!-- Left Sidebar: Account Navigation -->
                <aside class="account-sidebar">
                    <nav class="account-nav">
                        <a href="#orders" class="account-nav-item active">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            <span>My Orders</span>
                        </a>
                        <a href="#profile" class="account-nav-item">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <span>Profile Settings</span>
                        </a>
                        <a href="#addresses" class="account-nav-item">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            <span>Addresses</span>
                        </a>
                    </nav>

                    <div class="account-info">
                        <div class="account-info-item">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?= htmlspecialchars($user_email) ?></span>
                        </div>
                    </div>

                    <form method="POST" action="<?= PUBLIC_URL ?>/logout.php" class="logout-form">
                        <button type="submit" class="btn btn-outline btn-full">Logout</button>
                    </form>
                </aside>

                <!-- Right Side: Account Content -->
                <main class="account-main">
                    <!-- My Orders Section -->
                    <div class="account-section" id="orders">
                        <h2>My Orders</h2>
                        <div class="empty-state">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            <h3>No orders yet</h3>
                            <p>You haven't placed any orders yet. Start shopping to see your order history here.</p>
                            <a href="<?= PUBLIC_URL ?>/index.php" class="btn btn-primary">Start Shopping</a>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-grid">
            <div class="footer-col">
                <a href="<?= PUBLIC_URL ?>/index.php" class="footer-logo-link">
                    <img src="<?= PUBLIC_URL ?>/img/logo/clicks_leather_logo_dark_transparent.png" alt="Clicks Leather" class="footer-logo-img">
                </a>
                <p>Premium handcrafted leather goods.</p>
            </div>
            <div class="footer-col">
                <h4>Shop</h4>
                <ul>
                    <li><a href="<?= PUBLIC_URL ?>/products.php?category=wallets">Wallets</a></li>
                    <li><a href="<?= PUBLIC_URL ?>/products.php?category=ladies-bags">Bags</a></li>
                    <li><a href="#">Accessories</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Company</h4>
                <ul>
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Contact</a></li>
                    <li><a href="#">Journal</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Support</h4>
                <ul>
                    <li><a href="#">FAQ</a></li>
                    <li><a href="#">Shipping & Returns</a></li>
                    <li><a href="#">Warranty</a></li>
                </ul>
            </div>
            <div class="footer-col newsletter-col">
                <h4>Newsletter</h4>
                <p>Join our list for 10% off your first order.</p>
                <form class="newsletter-form">
                    <input type="email" placeholder="Your email address" required>
                    <button type="submit" class="btn btn-primary btn-sm">Subscribe</button>
                </form>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> Clicks Leather. All rights reserved. Handcrafted with ❤️</p>
        </div>
    </footer>

    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Multi-Stage Header Scroll Effect with Hysteresis
        let isTicking = false;
        let currentStage = 1;

        window.addEventListener('scroll', function() {
            if (!isTicking) {
                window.requestAnimationFrame(function() {
                    const header = document.getElementById('site-header');
                    const currentScrollY = window.scrollY;
                    
                    const stage2Activate = 40;
                    const stage2Deactivate = 20;
                    const stage3Activate = 160;
                    const stage3Deactivate = 140;
                    
                    if (currentStage === 1 && currentScrollY < stage2Activate) {
                        header.style.boxShadow = 'none';
                        header.classList.remove('stage-2', 'stage-3');
                    }
                    else if (currentStage === 1 && currentScrollY >= stage2Activate) {
                        header.style.boxShadow = '0 4px 16px rgba(0,0,0,0.08)';
                        header.classList.add('stage-2');
                        header.classList.remove('stage-3');
                        currentStage = 2;
                    }
                    else if (currentStage === 2 && currentScrollY >= stage2Deactivate && currentScrollY < stage3Activate) {
                        header.style.boxShadow = '0 4px 16px rgba(0,0,0,0.08)';
                        header.classList.add('stage-2');
                        header.classList.remove('stage-3');
                    }
                    else if (currentStage === 2 && currentScrollY < stage2Deactivate) {
                        header.style.boxShadow = 'none';
                        header.classList.remove('stage-2', 'stage-3');
                        currentStage = 1;
                    }
                    else if ((currentStage === 2 || currentStage === 1) && currentScrollY >= stage3Activate) {
                        header.style.boxShadow = '0 4px 16px rgba(0,0,0,0.08)';
                        header.classList.add('stage-2', 'stage-3');
                        currentStage = 3;
                    }
                    else if (currentStage === 3 && currentScrollY >= stage3Deactivate) {
                        header.style.boxShadow = '0 4px 16px rgba(0,0,0,0.08)';
                        header.classList.add('stage-2', 'stage-3');
                    }
                    else if (currentStage === 3 && currentScrollY < stage3Deactivate) {
                        header.style.boxShadow = '0 4px 16px rgba(0,0,0,0.08)';
                        header.classList.add('stage-2');
                        header.classList.remove('stage-3');
                        currentStage = 2;
                    }
                    
                    isTicking = false;
                });
                isTicking = true;
            }
        });
    </script>
</body>
</html>
