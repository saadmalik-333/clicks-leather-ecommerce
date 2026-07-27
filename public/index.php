<?php
/**
 * Clicks Leather — Homepage
 */
require_once __DIR__ . '/../includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

// Fetch categories
$categories = get_all_categories($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Clicks Leather — Premium handcrafted leather goods. Wallets, bags, jackets, shoes and more. Personalization available.">
    <title>Clicks Leather — Premium Handcrafted Leather Goods</title>
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
                <li class="nav-highlight"><a href="<?= PUBLIC_URL ?>/products.php?category=ladies-bags">LADIES BAGS</a></li>
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

    <!-- Hero Section -->
    <section class="hero-section" id="home" style="background-image: url('<?= PUBLIC_URL ?>/img/hero/brown_jacket.jpg'), linear-gradient(to right, rgba(255,255,255,0.3) 0%, rgba(255,255,255,0.1) 40%, transparent 60%); background-size: cover; background-position: center right; background-repeat: no-repeat;">
        <div class="hero-content">
            <span class="hero-subtitle">Handcrafted Excellence</span>
            <h1 class="hero-title">Premium Leather,<br>Timeless Craft</h1>
            <p class="hero-description">
                Discover our collection of handcrafted leather goods — from classic wallets
                to bespoke jackets. Each piece tells a story of quality and craftsmanship.
            </p>
            <div class="hero-actions">
                <a href="#categories" class="btn btn-primary" id="hero-explore-btn">Explore Collection</a>
                <a href="<?= PUBLIC_URL ?>/signup.php" class="btn btn-outline" id="hero-join-btn">Join Us</a>
            </div>
        </div>
    </section>

    <!-- 3-Column Showcase Section -->
    <section class="showcase-section">
        <div class="showcase-col">
            <div class="placeholder-media static-bg"></div>
            <div class="showcase-overlay">
                <h3>Everyday Carry</h3>
                <a href="#categories" class="btn btn-outline btn-sm">Shop Now</a>
            </div>
        </div>
        <div class="showcase-col">
            <div class="placeholder-media video-bg"></div>
            <div class="showcase-overlay">
                <h3>The Art of Craft</h3>
            </div>
        </div>
        <div class="showcase-col">
            <div class="placeholder-media static-bg-2"></div>
            <div class="showcase-overlay">
                <h3>Travel Collection</h3>
                <a href="#categories" class="btn btn-outline btn-sm">Shop Now</a>
            </div>
        </div>
    </section>

    <!-- Featured Section -->
    <section class="featured-section">
        <div class="section-header">
            <h2>Most Popular</h2>
            <p>Our bestselling handcrafted pieces</p>
        </div>
        <div class="featured-grid">
            <div class="product-card">
                <div class="product-img-placeholder"></div>
                <h4>Classic Leather Wallet</h4>
                <p>$85.00</p>
            </div>
            <div class="product-card">
                <div class="product-img-placeholder"></div>
                <h4>Weekender Duffle</h4>
                <p>$350.00</p>
            </div>
            <div class="product-card">
                <div class="product-img-placeholder"></div>
                <h4>Minimalist Cardholder</h4>
                <p>$45.00</p>
            </div>
            <div class="product-card">
                <div class="product-img-placeholder"></div>
                <h4>Heritage Briefcase</h4>
                <p>$420.00</p>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories-section" id="categories">
        <div class="section-header">
            <h2>Our Collections</h2>
            <p>Explore our range of premium leather products</p>
        </div>

        <div class="categories-grid">
            <?php 
            $category_icons = [
                'Wallets' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 10h20"/><circle cx="17" cy="14" r="1.5"/></svg>',
                'Ladies Bags' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>',
                'Leather Jackets' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2L8 6H4v4l-2 2 2 2v4h4l4 4 4-4h4v-4l2-2-2-2V6h-4l-4-4z"/></svg>',
                'Laptop Bags' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="2" y1="20" x2="22" y2="20"/></svg>',
                'Backpacks' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 10V20a2 2 0 002 2h12a2 2 0 002-2V10"/><path d="M8 10V6a4 4 0 018 0v4"/><path d="M4 10h16"/><line x1="12" y1="14" x2="12" y2="18"/></svg>',
                'Duffel Bags' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><ellipse cx="12" cy="12" rx="10" ry="6"/><path d="M2 12v0a10 6 0 0020 0"/><path d="M8 6v12"/><path d="M16 6v12"/></svg>',
                'Leather Shoes' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 18h20v2H2z"/><path d="M4 18c0-6 2-10 4-12h8c2 2 4 6 4 12"/></svg>',
            ];
            
            foreach ($categories as $cat): 
                $icon = $category_icons[$cat['naam']] ?? '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/></svg>';
                $category_slug = strtolower(str_replace(' ', '-', $cat['naam']));
            ?>
                <a href="<?= PUBLIC_URL ?>/products.php?category=<?= $category_slug ?>" class="category-card" id="category-<?= $cat['id'] ?>">
                    <div class="category-icon">
                        <?= $icon ?>
                    </div>
                    <h3><?= htmlspecialchars($cat['naam']) ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Trust Badges -->
    <section class="trust-badges">
        <div class="trust-badge">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12l5 5L20 7"></path></svg>
            <span>WorldWide Shipping</span>
        </div>
        <div class="trust-badge">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
            <span>60 Day Returns</span>
        </div>
        <div class="trust-badge">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
            <span>1 Year Warranty</span>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section">
        <div class="section-header">
            <h2>What Our Customers Say</h2>
        </div>
        <div class="testimonials-grid">
            <div class="review-card">
                <div class="stars">★★★★★</div>
                <p class="review-text">"Absolutely love the craftsmanship. The leather is top-notch and ages beautifully."</p>
                <p class="reviewer-name">- Michael S.</p>
            </div>
            <div class="review-card">
                <div class="stars">★★★★★</div>
                <p class="review-text">"Fast shipping and incredible quality. Will definitely be buying more as gifts."</p>
                <p class="reviewer-name">- Sarah L.</p>
            </div>
            <div class="review-card">
                <div class="stars">★★★★★</div>
                <p class="review-text">"The best wallet I've ever owned. Worth every penny."</p>
                <p class="reviewer-name">- David R.</p>
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
                    <li><a href="#">Wallets</a></li>
                    <li><a href="#">Bags</a></li>
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
        let currentStage = 1; // Track current stage to prevent flickering

        window.addEventListener('scroll', function() {
            if (!isTicking) {
                window.requestAnimationFrame(function() {
                    const header = document.getElementById('site-header');
                    const currentScrollY = window.scrollY;
                    
                    // Hysteresis thresholds (different activate/deactivate points)
                    const stage2Activate = 40;  // Activate Stage 2 when scrolling down past 40px
                    const stage2Deactivate = 20; // Deactivate Stage 2 when scrolling up below 20px
                    const stage3Activate = 160; // Activate Stage 3 when scrolling down past 160px
                    const stage3Deactivate = 140; // Deactivate Stage 3 when scrolling up below 140px
                    
                    // Base state (Stage 1)
                    if (currentStage === 1 && currentScrollY < stage2Activate) {
                        header.style.boxShadow = 'none';
                        header.classList.remove('stage-2', 'stage-3');
                    }
                    // Transition to Stage 2
                    else if (currentStage === 1 && currentScrollY >= stage2Activate) {
                        header.style.boxShadow = '0 4px 16px rgba(0,0,0,0.08)';
                        header.classList.add('stage-2');
                        header.classList.remove('stage-3');
                        currentStage = 2;
                    }
                    // Stay in Stage 2 (within hysteresis buffer)
                    else if (currentStage === 2 && currentScrollY >= stage2Deactivate && currentScrollY < stage3Activate) {
                        header.style.boxShadow = '0 4px 16px rgba(0,0,0,0.08)';
                        header.classList.add('stage-2');
                        header.classList.remove('stage-3');
                    }
                    // Transition back to Stage 1
                    else if (currentStage === 2 && currentScrollY < stage2Deactivate) {
                        header.style.boxShadow = 'none';
                        header.classList.remove('stage-2', 'stage-3');
                        currentStage = 1;
                    }
                    // Transition to Stage 3
                    else if ((currentStage === 2 || currentStage === 1) && currentScrollY >= stage3Activate) {
                        header.style.boxShadow = '0 4px 16px rgba(0,0,0,0.08)';
                        header.classList.add('stage-2', 'stage-3');
                        currentStage = 3;
                    }
                    // Stay in Stage 3
                    else if (currentStage === 3 && currentScrollY >= stage3Deactivate) {
                        header.style.boxShadow = '0 4px 16px rgba(0,0,0,0.08)';
                        header.classList.add('stage-2', 'stage-3');
                    }
                    // Transition back to Stage 2
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
