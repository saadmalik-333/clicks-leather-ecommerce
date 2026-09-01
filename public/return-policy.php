<?php
/**
 * Clicks Leather — Return & Refund Policy Page
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
    <meta name="description" content="Return & Refund Policy for Clicks Leather. Learn about our 14-day return policy for damaged items and fit issues.">
    <title>Return & Refund Policy — Clicks Leather</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/style.css?v=<?= time() ?>">
    <meta property="og:title" content="Return & Refund Policy — Clicks Leather">
    <meta property="og:description" content="Return & Refund Policy for Clicks Leather. Learn about our 14-day return policy for damaged items and fit issues.">
    <meta property="og:image" content="<?= PUBLIC_URL ?>/img/logo/clicks_leather_logo_dark_transparent.png">
    <meta property="og:url" content="<?= PUBLIC_URL ?>/return-policy.php">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <style>
        .policy-page {
            padding: 0;
            min-height: calc(100vh - 200px);
            max-width: 100%;
            overflow-x: hidden;
        }

        .policy-hero {
            position: relative;
            width: 100%;
            height: 455px;
            background: url('<?= PUBLIC_URL ?>/img/policy/SR.png') center/cover no-repeat;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            margin-bottom: 3rem;
        }

        .policy-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1;
        }

        .policy-hero h1 {
            font-family: var(--font-display);
            font-size: 2rem;
            margin-bottom: 1rem;
            color: white;
            letter-spacing: 0.02em;
            position: relative;
            z-index: 2;
        }

        .policy-hero p {
            color: rgba(255, 255, 255, 0.85);
            font-size: 1.1rem;
            margin-top: 0.5rem;
            position: relative;
            z-index: 2;
        }

        .policy-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 0;
            max-width: 1100px;
            margin: 0 auto 4rem;
            padding: 0 2rem;
        }

        .policy-sidebar {
            position: sticky;
            top: 2rem;
            height: fit-content;
        }

        .policy-sidebar nav {
            padding-top: 3rem;
        }

        .policy-sidebar nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .policy-sidebar nav li {
            margin-bottom: 0.5rem;
        }

        .policy-sidebar nav a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            transition: all var(--transition-fast);
            display: block;
            padding: 0.75rem 0 0.75rem 1.25rem;
            position: relative;
            border-left: 2px solid transparent;
        }

        .policy-sidebar nav a:hover {
            color: var(--color-primary);
            border-left-color: var(--color-primary);
        }

        .sidebar-label {
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }

        .policy-content {
            background: var(--bg-card-hover);
            padding: 3rem;
            border-radius: var(--radius-sm);
        }

        .policy-content h2 {
            font-family: var(--font-display);
            font-size: 1.6rem;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
            letter-spacing: 0.02em;
            padding-top: 2rem;
            margin-top: -2rem;
        }

        .policy-section {
            margin-bottom: 3rem;
        }

        .policy-section:last-child {
            margin-bottom: 0;
        }

        .policy-section p {
            color: var(--text-secondary);
            line-height: 1.75;
            font-size: 1.05rem;
            margin-bottom: 1rem;
        }

        .policy-section ul {
            color: var(--text-secondary);
            line-height: 1.75;
            margin: 0 0 1rem 0;
            padding-left: 0;
            list-style: none;
        }

        .policy-section li {
            margin-bottom: 0.5rem;
            padding-left: 1.5rem;
            position: relative;
        }

        .policy-section li::before {
            content: '•';
            position: absolute;
            left: 0;
            color: var(--color-primary);
            font-weight: 600;
        }

        /* Shipping accordion base styles */
        .shipping-timeline {
            margin-bottom: 4rem;
            position: relative;
        }

        .timeline-wrapper {
            position: relative;
            padding-left: 3rem;
        }

        .timeline-wrapper::before {
            content: '';
            position: absolute;
            left: 0.75rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--color-primary-light);
        }

        .timeline-step {
            position: relative;
            margin-bottom: 2.5rem;
        }

        .timeline-step:last-child {
            margin-bottom: 0;
        }

        .timeline-number {
            position: absolute;
            left: -3rem;
            width: 40px;
            height: 40px;
            background: var(--color-primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.1rem;
            flex-shrink: 0;
            z-index: 1;
        }

        .timeline-content h3 {
            font-size: 1.15rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 600;
        }

        .timeline-content p {
            color: var(--text-secondary);
            font-size: 1.05rem;
            line-height: 1.6;
            margin: 0;
        }

        .shipping-note {
            margin-bottom: 3rem;
        }

        .shipping-note h3 {
            font-family: var(--font-display);
            font-size: 1.6rem;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
        }

        .shipping-note p {
            color: var(--text-secondary);
            font-size: 1.05rem;
            line-height: 1.6;
            margin: 0;
        }

        /* Warranty accordion base styles */
        .warranty-section {
            margin-bottom: 3rem;
        }

        .warranty-section:last-child {
            margin-bottom: 0;
        }

        .warranty-section p {
            color: var(--text-secondary);
            line-height: 1.75;
            font-size: 1.05rem;
            margin-bottom: 1rem;
        }

        .warranty-section ul {
            color: var(--text-secondary);
            line-height: 1.75;
            margin: 0 0 1rem 0;
            padding-left: 0;
            list-style: none;
        }

        .warranty-section li {
            margin-bottom: 0.5rem;
            padding-left: 1.5rem;
            position: relative;
        }

        .warranty-section li::before {
            content: '•';
            position: absolute;
            left: 0;
            color: var(--color-primary);
            font-weight: 600;
        }

        @media (max-width: 767px) {
            .policy-hero {
                height: 300px;
            }

            .policy-hero h1 {
                font-size: 2rem;
            }

            .policy-hero p {
                font-size: 1rem;
            }

            .policy-layout {
                grid-template-columns: 1fr;
                gap: 2rem;
                padding: 0 1rem;
            }

            .policy-sidebar {
                position: static;
                min-width: 0;
            }

            .policy-sidebar nav {
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 0.5rem;
            }

            .policy-sidebar nav ul {
                display: flex;
                gap: 1.5rem;
            }

            .policy-sidebar nav li {
                margin-bottom: 0;
            }

            .policy-content {
                padding: 1.5rem;
            }

            .policy-content h2 {
                font-size: 1.5rem;
            }

            /* Additional mobile responsive fixes */
            .policy-section h2 {
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }

            .policy-section {
                margin-bottom: 1.5rem;
            }

            .policy-section p {
                font-size: 1rem;
            }

            /* Shipping accordion */
            .shipping-timeline h2,
            .shipping-note h3 {
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }

            .shipping-timeline,
            .shipping-note {
                margin-bottom: 1.5rem;
            }

            /* Warranty accordion */
            .warranty-section h2 {
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }

            .warranty-section {
                margin-bottom: 1.5rem;
            }
        }

        /* Desktop: show 2-column layout, hide unified accordion */
        @media (min-width: 1025px) {
            .policy-layout {
                display: grid;
                grid-template-columns: 250px 1fr;
                gap: 0;
                max-width: 1100px;
                margin: 0 auto 4rem;
                padding: 0 2rem;
            }

            .policy-sidebar {
                display: block;
            }

            .policy-content {
                display: block;
            }

            .unified-accordion-list {
                display: none;
            }
        }

        /* Tablet/Mobile: hide 2-column layout, show unified accordion */
        @media (max-width: 1024px) {
            .policy-layout {
                display: none;
            }

            .unified-accordion-list {
                display: block;
                max-width: 1100px;
                margin: 0 auto 4rem;
                padding: 0 2rem;
            }

            .sidebar-label {
                font-size: 0.9rem;
                font-weight: 500;
                text-transform: uppercase;
                letter-spacing: 0.1em;
                color: var(--text-secondary);
                margin-bottom: 1rem;
            }

            .sidebar-accordion-item {
                background: var(--bg-card-hover);
                border-radius: var(--radius-sm);
                margin-bottom: 1rem;
                overflow: hidden;
            }

            .sidebar-accordion-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                cursor: pointer;
                padding: 1rem;
                border-bottom: 1px solid var(--border-color);
            }

            .sidebar-accordion-header span:first-child {
                font-size: 1.1rem;
                font-weight: 500;
                color: var(--text-primary);
            }

            .sidebar-accordion-header .accordion-icon {
                font-size: 1.5rem;
                font-weight: 300;
                transition: transform 0.3s ease;
            }

            .sidebar-accordion-header.link-only a {
                flex: 1;
                color: var(--text-primary);
                text-decoration: none;
                font-size: 1.1rem;
                font-weight: 500;
            }

            .sidebar-accordion-body {
                display: none;
                padding: 1rem;
            }

            .sidebar-accordion-body.active {
                display: block;
            }

            .sidebar-accordion-header.active .accordion-icon {
                transform: rotate(45deg);
            }

            /* Content container padding */
            .sidebar-accordion-body {
                padding: 2rem;
            }

            /* Heading font-sizes */
            .policy-section h2 {
                font-size: 1.4rem;
                margin-bottom: 1.5rem;
            }

            /* Section spacing */
            .policy-section {
                margin-bottom: 2rem;
            }

            /* Paragraph font-size */
            .policy-section p {
                font-size: 1rem;
            }

            /* Shipping accordion */
            .shipping-timeline h2,
            .shipping-note h3 {
                font-size: 1.4rem;
                margin-bottom: 1.5rem;
            }

            .shipping-timeline,
            .shipping-note {
                margin-bottom: 2rem;
            }

            .timeline-step {
                margin-bottom: 2rem;
            }

            .timeline-number {
                width: 36px;
                height: 36px;
                font-size: 1rem;
            }

            .timeline-content h3 {
                font-size: 1.05rem;
            }

            .timeline-content p {
                font-size: 1rem;
            }

            /* Warranty accordion */
            .warranty-section h2 {
                font-size: 1.4rem;
                margin-bottom: 1.5rem;
            }

            .warranty-section {
                margin-bottom: 2rem;
            }

            .warranty-section p {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include PUBLIC_PATH . '/includes/header.php'; ?>
    <div class="page-wrapper">

    <main class="policy-page">
        <div class="policy-hero">
            <h1>Return & Refund Policy</h1>
            <p>Our policy for returns, exchanges, and replacements.</p>
        </div>

        <div class="policy-layout">
            <aside class="policy-sidebar">
                <nav>
                    <div class="sidebar-label">NEED HELP?</div>
                    <ul>
                        <li><a href="<?= PUBLIC_URL ?>/shipping-info.php">Shipping Policy</a></li>
                        <li><a href="<?= PUBLIC_URL ?>/warranty.php">Warranty</a></li>
                        <li><a href="<?= PUBLIC_URL ?>/faq.php">FAQ</a></li>
                        <li><a href="<?= PUBLIC_URL ?>/contact.php">Contact Us</a></li>
                    </ul>
                </nav>
            </aside>

            <div class="policy-content">
                <?php include INCLUDES_PATH . '/return-policy-content.php'; ?>
            </div>
        </div>

        <!-- Tablet/Mobile-only: unified accordion list -->
        <div class="unified-accordion-list">
            <div class="sidebar-label">NEED HELP?</div>
            
            <!-- Shipping Policy (expandable) -->
            <div class="sidebar-accordion-item">
                <div class="sidebar-accordion-header" data-target="shipping-content">
                    <span>Shipping Policy</span>
                    <span class="accordion-icon">+</span>
                </div>
                <div class="sidebar-accordion-body" id="shipping-content">
                    <?php 
                    // Include shipping variables for the partial
                    $shipping_is_free = get_setting($pdo, 'shipping_is_free', 'yes');
                    $shipping_flat_cost = floatval(get_setting($pdo, 'shipping_flat_cost', '15.00'));
                    include INCLUDES_PATH . '/shipping-content.php'; 
                    ?>
                </div>
            </div>

            <!-- Return Policy (expandable) -->
            <div class="sidebar-accordion-item">
                <div class="sidebar-accordion-header" data-target="return-content">
                    <span>Return Policy</span>
                    <span class="accordion-icon">+</span>
                </div>
                <div class="sidebar-accordion-body" id="return-content">
                    <?php include INCLUDES_PATH . '/return-policy-content.php'; ?>
                </div>
            </div>

            <!-- Warranty Policy (expandable) -->
            <div class="sidebar-accordion-item">
                <div class="sidebar-accordion-header" data-target="warranty-content">
                    <span>Warranty Policy</span>
                    <span class="accordion-icon">+</span>
                </div>
                <div class="sidebar-accordion-body" id="warranty-content">
                    <?php include INCLUDES_PATH . '/warranty-content.php'; ?>
                </div>
            </div>

            <!-- FAQ (plain link) -->
            <div class="sidebar-accordion-item">
                <div class="sidebar-accordion-header link-only">
                    <a href="<?= PUBLIC_URL ?>/faq.php">FAQ</a>
                    <span class="accordion-icon">+</span>
                </div>
            </div>

            <!-- Contact Us (plain link) -->
            <div class="sidebar-accordion-item">
                <div class="sidebar-accordion-header link-only">
                    <a href="<?= PUBLIC_URL ?>/contact.php">Contact Us</a>
                    <span class="accordion-icon">+</span>
                </div>
            </div>
        </div>
    </main>

    <?php include PUBLIC_PATH . '/includes/footer.php'; ?>

    <!-- Cart Drawer -->
    <?php include PUBLIC_PATH . '/includes/cart-drawer.php'; ?>

    <!-- Cart JavaScript -->
    <script src="<?= PUBLIC_URL ?>/js/cart.js"></script>
    <script src="<?= PUBLIC_URL ?>/js/header-scroll.js"></script>

    <!-- Unified Accordion JavaScript -->
    <script>
        (function() {
            const accordionHeaders = document.querySelectorAll('.sidebar-accordion-header');
            
            accordionHeaders.forEach(header => {
                header.addEventListener('click', function(e) {
                    // If it's a link-only row (FAQ/Contact), let the link navigate normally
                    if (this.classList.contains('link-only')) {
                        return;
                    }
                    
                    // For expandable rows (Shipping/Return/Warranty), toggle accordion
                    const targetId = this.getAttribute('data-target');
                    const body = document.getElementById(targetId);
                    
                    if (body) {
                        body.classList.toggle('active');
                        this.classList.toggle('active');
                    }
                });
            });
        })();
    </script>
    </div>
</body>
</html>
