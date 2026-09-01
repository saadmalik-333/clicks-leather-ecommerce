<?php
/**
 * Clicks Leather — About Us Page
 */
require_once __DIR__ . '/../includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for session expiry BEFORE any HTML output (only if user was logged in)
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    require_active_session();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="About Clicks Leather — Premium handcrafted leather goods made with 100% authentic leather.">
    <title>About Us — Clicks Leather</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/style.css?v=<?= time() ?>">
    <meta property="og:title" content="About Us — Clicks Leather">
    <meta property="og:description" content="About Clicks Leather — Premium handcrafted leather goods made with 100% authentic leather.">
    <meta property="og:image" content="<?= PUBLIC_URL ?>/img/logo/clicks_leather_logo_dark_transparent.png">
    <meta property="og:url" content="<?= PUBLIC_URL ?>/about.php">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <style>
        .about-page {
            min-height: calc(100vh - 200px);
            max-width: 100%;
            overflow-x: hidden;
        }

        /* Hero Section */
        .about-hero-section {
            position: relative;
            width: 100%;
            height: 455px;
            margin-bottom: var(--space-2xl);
        }

        .hero-image-wrapper {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .hero-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #8c5c38 0%, #6b4423 100%);
        }

        .hero-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
        }

        .hero-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: var(--space-2xl);
            color: white;
            z-index: 2;
        }

        .about-hero-section .hero-content {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: var(--space-xl);
            max-width: none;
            color: white;
            z-index: 2;
            margin-top: 0;
            animation: none;
        }

        .hero-heading {
            font-family: var(--font-display);
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: var(--space-sm);
            color: white;
        }

        .hero-tagline {
            font-family: var(--font-body);
            font-size: 1rem;
            opacity: 0.9;
        }

        /* Split Sections */
        .about-split-section {
            padding: var(--space-2xl) var(--space-xl);
            background: var(--bg-card);
        }

        .split-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-2xl);
            max-width: 1200px;
            margin: 0 auto;
            align-items: center;
        }

        .split-reverse .split-image {
            order: -1;
        }

        .split-text {
            padding: var(--space-lg);
        }

        .split-image {
            aspect-ratio: 4/3;
            border-radius: 0;
            overflow: hidden;
        }

        .image-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #B48560 0%, #8c5c38 100%);
            filter: brightness(0.9);
        }

        .section-heading {
            font-family: var(--font-display);
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--space-md);
        }

        .section-text {
            font-family: var(--font-body);
            font-size: 1.05rem;
            line-height: 1.7;
            color: var(--text-secondary);
        }

        .text-block {
            margin-bottom: var(--space-lg);
        }

        .text-block:last-child {
            margin-bottom: 0;
        }

        @media (max-width: 768px) {
            .about-hero-section {
                height: 300px;
            }
            
            .hero-heading {
                font-size: 1.5rem;
            }
            
            .hero-tagline {
                font-size: 0.9rem;
            }
            
            .split-content {
                grid-template-columns: 1fr;
                gap: var(--space-lg);
            }
            
            .split-reverse .split-image {
                order: 0;
            }
            
            .split-image {
                aspect-ratio: 16/9;
            }
            
            .section-heading {
                font-size: 1.5rem;
            }
            
            .section-text {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include PUBLIC_PATH . '/includes/header.php'; ?>
    <div class="page-wrapper">

    <main class="about-page">
        <!-- Hero Section -->
        <section class="about-hero-section">
            <div class="hero-image-wrapper">
                <div class="hero-placeholder" style="background-image: url('<?= PUBLIC_URL ?>/img/about/heroa.png'); background-size: cover; background-position: center;"></div>
                <div class="hero-overlay"></div>
                <div class="hero-content">
                    <h1 class="hero-heading">About Us</h1>
                    <p class="hero-tagline">Premium handcrafted leather goods, made with care and attention to detail.</p>
                </div>
            </div>
        </section>

        <!-- Section 1: Text Left, Image Right -->
        <section class="about-split-section">
            <div class="split-content">
                <div class="split-text">
                    <h2 class="section-heading">What We Do</h2>
                    <p class="section-text">We create honest, handcrafted leather goods using only genuine, high-quality materials — no shortcuts, no synthetic substitutes. Every piece is made to help you carry life's essentials with style that lasts.</p>
                    <p class="section-text" style="margin-top: var(--space-lg);">From selecting the finest hides to the final stitch, every step is done with care — because true craftsmanship shows in the details.</p>
                </div>
                <div class="split-image">
                    <div class="image-placeholder" style="background-image: url('<?= PUBLIC_URL ?>/img/about/section1.png'); background-size: cover; background-position: center;"></div>
                </div>
            </div>
        </section>

        <!-- Section 2: Image Left, Text Right (stacked content) -->
        <section class="about-split-section split-reverse">
            <div class="split-content">
                <div class="split-image">
                    <div class="image-placeholder" style="background-image: url('<?= PUBLIC_URL ?>/img/about/section2.png'); background-size: cover; background-position: center;"></div>
                </div>
                <div class="split-text">
                    <h2 class="section-heading">Made For You</h2>
                    <p class="section-text">Each piece is handcrafted and made to order. This ensures every item meets our quality standards and is crafted specifically for you.</p>
                    <p class="section-text" style="margin-top: var(--space-lg);">We offer laser engraving customization for personalization. Contact us before placing your order to discuss customization options and make your piece truly unique.</p>
                </div>
            </div>
        </section>
    </main>

    <?php include PUBLIC_PATH . '/includes/footer.php'; ?>

    <!-- Cart Drawer -->
    <?php include PUBLIC_PATH . '/includes/cart-drawer.php'; ?>

    <!-- Cart JavaScript -->
    <script src="<?= PUBLIC_URL ?>/js/cart.js"></script>
    <script src="<?= PUBLIC_URL ?>/js/header-scroll.js"></script>
    </div>
</body>
</html>
