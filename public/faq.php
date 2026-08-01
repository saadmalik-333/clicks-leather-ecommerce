<?php
/**
 * Clicks Leather — FAQ Page
 */
require_once __DIR__ . '/../includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

$page_title = 'FAQ — Clicks Leather';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Frequently Asked Questions about Clicks Leather products, returns, and shipping.">
    <title><?= $page_title ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/style.css">
    <style>
        .faq-page {
            padding: 4rem 1rem 3rem;
            min-height: calc(100vh - 200px);
        }

        .faq-hero {
            text-align: center;
            margin-bottom: 4rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .faq-hero h1 {
            font-family: var(--font-display);
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .faq-hero p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .faq-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .faq-category {
            margin-bottom: 3rem;
        }

        .faq-category h2 {
            font-family: var(--font-display);
            font-size: 1.75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--color-primary);
        }

        .faq-item {
            margin-bottom: 1.5rem;
        }

        .faq-question {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .faq-answer {
            color: var(--text-secondary);
            line-height: 1.7;
            padding-left: 0;
        }

        @media (max-width: 768px) {
            .faq-hero h1 {
                font-size: 2rem;
            }

            .faq-category h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sticky Header Container -->
    <div class="header-container" id="site-header">
        
        <!-- Announcement Bar (Tier 1) -->
        <div class="announcement-bar">
            <span>60 DAY RETURNS</span>
            <span class="separator">|</span>
            <span>WORLDWIDE SHIPPING</span>
        </div>

        <!-- Main Header (Tier 2) -->
        <header class="main-header">
            <div class="header-inner">
                <a href="<?= PUBLIC_URL ?>/index.php" class="logo">
                    <img src="<?= PUBLIC_URL ?>/images/logo.png" alt="Clicks Leather" class="logo-img">
                </a>

                <nav class="desktop-nav">
                    <a href="<?= PUBLIC_URL ?>/index.php" class="nav-link">Home</a>
                    <a href="<?= PUBLIC_URL ?>/products.php" class="nav-link">Shop</a>
                    <a href="<?= PUBLIC_URL ?>/about.php" class="nav-link">About</a>
                    <a href="<?= PUBLIC_URL ?>/contact.php" class="nav-link">Contact</a>
                </nav>

                <div class="header-actions">
                    <a href="<?= PUBLIC_URL ?>/search.php" class="header-icon" aria-label="Search">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </a>
                    <a href="<?= PUBLIC_URL ?>/account.php" class="header-icon" aria-label="Account">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </a>
                    <a href="<?= PUBLIC_URL ?>/faq.php" class="header-icon" aria-label="FAQ">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    </a>
                    <a href="<?= PUBLIC_URL ?>/cart.php" class="header-icon cart-icon" aria-label="Cart">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 01-8 0"></path></svg>
                        <span class="cart-count">0</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Mobile Header (Tier 3) -->
        <header class="mobile-header">
            <div class="mobile-header-inner">
                <a href="<?= PUBLIC_URL ?>/index.php" class="logo">
                    <img src="<?= PUBLIC_URL ?>/images/logo.png" alt="Clicks Leather" class="logo-img">
                </a>

                <div class="mobile-header-actions">
                    <a href="<?= PUBLIC_URL ?>/cart.php" class="header-icon cart-icon" aria-label="Cart">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 01-8 0"></path></svg>
                        <span class="cart-count">0</span>
                    </a>
                    <button class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="Menu">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Menu -->
            <nav class="mobile-menu" id="mobile-menu">
                <a href="<?= PUBLIC_URL ?>/index.php" class="mobile-nav-link">Home</a>
                <a href="<?= PUBLIC_URL ?>/products.php" class="mobile-nav-link">Shop</a>
                <a href="<?= PUBLIC_URL ?>/about.php" class="mobile-nav-link">About</a>
                <a href="<?= PUBLIC_URL ?>/contact.php" class="mobile-nav-link">Contact</a>
                <a href="<?= PUBLIC_URL ?>/account.php" class="mobile-nav-link">Account</a>
                <a href="<?= PUBLIC_URL ?>/faq.php" class="mobile-nav-link">FAQ</a>
            </nav>
        </header>
    </div>

    <!-- Main Content -->
    <main class="faq-page">
        <div class="faq-hero">
            <h1>Frequently Asked Questions</h1>
            <p>Find answers to common questions about our products, policies, and shipping.</p>
        </div>

        <div class="faq-container">
            <!-- About Us / Product Quality -->
            <div class="faq-category">
                <h2>About Us / Product Quality</h2>
                
                <div class="faq-item">
                    <div class="faq-question">What materials do you use?</div>
                    <div class="faq-answer">We use 100% real leather for all our products. We never use PU (synthetic) leather — only genuine, high-quality leather that ages beautifully over time.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">Are your products made-to-order?</div>
                    <div class="faq-answer">Yes, each piece is handcrafted and made to order. This ensures every item meets our quality standards and is crafted specifically for you.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">Can I customize my order?</div>
                    <div class="faq-answer">Yes, we offer laser engraving customization for personalization. Contact us before placing your order to discuss customization options.</div>
                </div>
            </div>

            <!-- Return & Refund Policy -->
            <div class="faq-category">
                <h2>Return & Refund Policy</h2>
                
                <div class="faq-item">
                    <div class="faq-question">What is your return policy?</div>
                    <div class="faq-answer">We accept returns within 14 days for damaged items or if you receive the wrong size. Please contact us immediately if you have any issues with your order.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">Can I return an item if I just changed my mind?</div>
                    <div class="faq-answer">No, made-to-order items cannot be returned once production has started. Since each piece is custom-made specifically for you, we cannot accept returns for change of mind.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">What if the item doesn't fit?</div>
                    <div class="faq-answer">We offer a free replacement for fit issues. If your item doesn't fit properly, contact us and we'll arrange a replacement at no additional cost to you.</div>
                </div>
            </div>

            <!-- Shipping -->
            <div class="faq-category">
                <h2>Shipping</h2>
                
                <div class="faq-item">
                    <div class="faq-question">How long does shipping take?</div>
                    <div class="faq-answer">Total delivery time is 14-15 days. This includes 4-6 days for manufacturing/handcrafting your order, plus 8-10 days for international shipping to your location.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">Do you ship worldwide?</div>
                    <div class="faq-answer">Yes, we offer worldwide shipping to most countries. Shipping costs and delivery times may vary depending on your location.</div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-column">
                    <h3>Clicks Leather</h3>
                    <p>Handcrafted leather goods made with passion and precision. Quality you can feel, style you can trust.</p>
                </div>
                <div class="footer-column">
                    <h3>Shop</h3>
                    <ul>
                        <li><a href="<?= PUBLIC_URL ?>/products.php">All Products</a></li>
                        <li><a href="<?= PUBLIC_URL ?>/products.php?category=wallets">Wallets</a></li>
                        <li><a href="<?= PUBLIC_URL ?>/products.php?category=bags">Bags</a></li>
                        <li><a href="<?= PUBLIC_URL ?>/products.php?category=belts">Belts</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Support</h3>
                    <ul>
                        <li><a href="<?= PUBLIC_URL ?>/contact.php">Contact Us</a></li>
                        <li><a href="<?= PUBLIC_URL ?>/faq.php">FAQ</a></li>
                        <li><a href="<?= PUBLIC_URL ?>/account.php">My Account</a></li>
                        <li><a href="<?= PUBLIC_URL ?>/cart.php">Shopping Cart</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Newsletter</h3>
                    <p>Subscribe for exclusive offers and new arrivals.</p>
                    <form method="POST" action="<?= PUBLIC_URL ?>/newsletter.php" class="newsletter-form">
                        <input type="email" name="email" placeholder="Your email address" required>
                        <button type="submit" class="btn btn-primary btn-sm">Subscribe</button>
                    </form>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Clicks Leather. All rights reserved. Handcrafted with ❤️</p>
            </div>
        </div>
    </footer>

    <!-- Cart JavaScript -->
    <script src="<?= PUBLIC_URL ?>/js/cart.js"></script>
    
    <!-- Mobile Menu Toggle -->
    <script>
        document.getElementById('mobile-menu-toggle').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('active');
        });
    </script>

    <!-- Multi-Stage Header Scroll Effect with Hysteresis -->
    <script src="<?= PUBLIC_URL ?>/js/header-scroll.js"></script>
</body>
</html>
