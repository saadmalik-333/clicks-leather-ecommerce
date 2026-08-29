<?php
/**
 * Clicks Leather — Homepage
 */
require_once __DIR__ . '/../includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

// Fetch categories
$categories = get_all_categories($pdo);
$category_images = get_category_representative_images($pdo);

// Fetch popular products
$popular_products = get_popular_products($pdo, 8);
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
    <meta property="og:title" content="Clicks Leather — Premium Handcrafted Leather Goods">
    <meta property="og:description" content="Clicks Leather — Premium handcrafted leather goods. Wallets, bags, jackets, shoes and more. Personalization available.">
    <meta property="og:image" content="<?= PUBLIC_URL ?>/img/logo/clicks_leather_logo_dark_transparent.png">
    <meta property="og:url" content="<?= PUBLIC_URL ?>/index.php">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
</head>
<body>
    <?php include PUBLIC_PATH . '/includes/header.php'; ?>
    <div class="page-wrapper">
    

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

    <!-- Circular Category Showcase -->
    <section class="circular-showcase-section">
        <div class="section-header">
            <h2>Explore Collections</h2>
            <p>Browse our premium leather categories</p>
        </div>
        <div class="circular-showcase" id="circularShowcase">
            <div class="circular-showcase-track">
                <?php
                // Build image lookup array
                $image_lookup = [];
                foreach ($category_images as $img) {
                    $image_lookup[$img['category_name']] = $img['image_path'];
                }
                
                // Define custom category order
                $custom_order = ['Wallets', 'Ladies Bags', 'Leather Jackets', 'Laptop Bags', 'Backpacks', 'Duffel Bags', 'Leather Shoes'];
                
                // Reorder categories array to match custom order
                $ordered_categories = [];
                foreach ($custom_order as $category_name) {
                    foreach ($categories as $cat) {
                        if ($cat['naam'] === $category_name) {
                            $ordered_categories[] = $cat;
                            break;
                        }
                    }
                }
                
                foreach ($ordered_categories as $cat):
                    $category_slug = strtolower(str_replace(' ', '-', $cat['naam']));
                    $image_path = $image_lookup[$cat['naam']] ?? null;
                    $image_url = $image_path ? PUBLIC_URL . '/uploads/' . $image_path : PUBLIC_URL . '/img/placeholder.jpg';
                ?>
                    <a href="<?= PUBLIC_URL ?>/products.php?category=<?= $category_slug ?>" class="category-circle-wrapper">
                        <div class="category-circle">
                            <?php if ($image_path): ?>
                                <img src="<?= $image_url ?>" alt="<?= htmlspecialchars($cat['naam']) ?>">
                            <?php else: ?>
                                <div class="category-circle-placeholder"></div>
                            <?php endif; ?>
                        </div>
                        <span class="category-circle-label"><?= htmlspecialchars($cat['naam']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="circular-showcase-progress">
            <div class="circular-showcase-progress-bar" id="circularShowcaseProgress"></div>
        </div>
    </section>

    <!-- Featured Section -->
    <section class="featured-section">
        <div class="section-header">
            <h2>Most Popular</h2>
            <p>Our bestselling handcrafted pieces</p>
        </div>
        <div class="featured-grid">
            <?php foreach ($popular_products as $product):
                $product_slug = strtolower(str_replace(' ', '-', $product['naam']));
                $image_url = $product['image_path'] ? PUBLIC_URL . '/uploads/' . $product['image_path'] : PUBLIC_URL . '/img/placeholder.jpg';
                $category_slug = strtolower(str_replace(' ', '-', $product['category_name']));
            ?>
                <a href="<?= PUBLIC_URL ?>/products.php?category=<?= $category_slug ?>" class="featured-product-card">
                    <div class="featured-product-image">
                        <img src="<?= $image_url ?>" alt="<?= htmlspecialchars($product['naam']) ?>">
                    </div>
                    <div class="featured-product-info">
                        <h3 class="featured-product-name"><?= htmlspecialchars($product['naam']) ?></h3>
                        <p class="featured-product-price"><?= format_price($product['price']) ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Promotional Banners Section -->
    <section class="promotional-banners-section">
        <div class="banners-top-row">
            <div class="banner-card">
                <div class="banner-image-wrapper">
                    <div class="banner-placeholder" style="background-image: url('<?= PUBLIC_URL ?>/img/banner/shoes.jpeg'); background-size: cover; background-position: center;"></div>
                    <div class="banner-overlay"></div>
                    <div class="banner-content">
                        <h3 class="banner-heading">Step Into Craftsmanship</h3>
                        <p class="banner-subheading">Handcrafted Leather Shoes, Built to Last</p>
                    </div>
                </div>
            </div>
            <div class="banner-card">
                <div class="banner-image-wrapper">
                    <div class="banner-placeholder" style="background-image: url('<?= PUBLIC_URL ?>/img/banner/Jacket.jpeg'); background-size: cover; background-position: center;"></div>
                    <div class="banner-overlay"></div>
                    <div class="banner-content">
                        <h3 class="banner-heading">Timeless Outerwear</h3>
                        <p class="banner-subheading">Premium Leather Jackets for Every Season</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="banner-card banner-full-width">
            <div class="banner-image-wrapper">
                <div class="banner-placeholder" style="background-image: url('<?= PUBLIC_URL ?>/img/banner/bag.png'); background-size: cover; background-position: center;"></div>
                <div class="banner-overlay"></div>
                <div class="banner-content">
                    <h3 class="banner-heading">Everyday Essentials</h3>
                    <p class="banner-subheading">Crafted Leather Goods for the Modern Journey</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section">
        <div class="section-header">
            <h2>Customer Reviews</h2>
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

    <?php include PUBLIC_PATH . '/includes/footer.php'; ?>

    <!-- Cart Drawer -->
    <?php include PUBLIC_PATH . '/includes/cart-drawer.php'; ?>

    <!-- Cart JavaScript -->
    <script src="<?= PUBLIC_URL ?>/js/cart.js"></script>
    <script src="<?= PUBLIC_URL ?>/js/header-scroll.js"></script>
    <script src="<?= PUBLIC_URL ?>/js/circular-showcase.js"></script>

    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                // Skip if href is just "#"
                if (href === '#') return;
                
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
    </div>
</body>
</html>
