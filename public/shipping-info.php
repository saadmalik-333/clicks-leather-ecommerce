<?php
/**
 * Clicks Leather — Shipping Information Page
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

// Get shipping settings
$shipping_is_free = get_setting($pdo, 'shipping_is_free', 'yes');
$shipping_flat_cost = floatval(get_setting($pdo, 'shipping_flat_cost', '15.00'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Shipping Information for Clicks Leather. Learn about our delivery timeline and <?= $shipping_is_free === 'yes' ? 'free shipping' : 'shipping from ' . format_price($shipping_flat_cost) ?> offer.">
    <title>Shipping Information — Clicks Leather</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/style.css">
    <meta property="og:title" content="Shipping Information — Clicks Leather">
    <meta property="og:description" content="Shipping Information for Clicks Leather. Learn about our delivery timeline and <?= $shipping_is_free === 'yes' ? 'free shipping' : 'shipping from ' . format_price($shipping_flat_cost) ?> offer.">
    <meta property="og:image" content="<?= PUBLIC_URL ?>/img/logo/clicks_leather_logo_dark_transparent.png">
    <meta property="og:url" content="<?= PUBLIC_URL ?>/shipping-info.php">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <style>
        .shipping-page {
            padding: 0;
            min-height: calc(100vh - 200px);
            max-width: 100%;
            overflow-x: hidden;
        }

        .shipping-hero {
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

        .shipping-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1;
        }

        .shipping-hero h1 {
            font-family: var(--font-display);
            font-size: 2rem;
            margin-bottom: 1rem;
            color: white;
            letter-spacing: 0.02em;
            position: relative;
            z-index: 2;
        }

        .shipping-hero p {
            color: rgba(255, 255, 255, 0.85);
            font-size: 1.1rem;
            margin-top: 0.5rem;
            position: relative;
            z-index: 2;
        }

        .shipping-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 0;
            max-width: 1100px;
            margin: 0 auto 4rem;
            padding: 0 2rem;
        }

        .shipping-sidebar {
            position: sticky;
            top: 2rem;
            height: fit-content;
        }

        .shipping-sidebar nav {
            padding-top: 3rem;
        }

        .shipping-sidebar nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .shipping-sidebar nav li {
            margin-bottom: 0.5rem;
        }

        .shipping-sidebar nav a {
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

        .shipping-sidebar nav a:hover {
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

        .shipping-content {
            background: var(--bg-card-hover);
            padding: 3rem;
            border-radius: var(--radius-sm);
        }

        .shipping-content h2 {
            font-family: var(--font-display);
            font-size: 1.6rem;
            margin-bottom: 2rem;
            color: var(--text-primary);
            letter-spacing: 0.02em;
            padding-top: 2rem;
            margin-top: -2rem;
        }

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

        @media (max-width: 768px) {
            .shipping-hero {
                height: 300px;
            }

            .shipping-hero h1 {
                font-size: 2rem;
            }

            .shipping-hero p {
                font-size: 1rem;
            }

            .shipping-layout {
                grid-template-columns: 1fr;
                gap: 2rem;
                padding: 0 1rem;
            }

            .shipping-sidebar {
                position: static;
            }

            .shipping-sidebar nav {
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 0.5rem;
            }

            .shipping-sidebar nav ul {
                display: flex;
                gap: 1.5rem;
            }

            .shipping-sidebar nav li {
                margin-bottom: 0;
            }

            .shipping-content {
                padding: 2rem;
            }

            .shipping-content h2 {
                font-size: 1.5rem;
            }

            .timeline-wrapper {
                padding-left: 2.5rem;
            }

            .timeline-wrapper::before {
                left: 0.5rem;
            }

            .timeline-number {
                left: -2.5rem;
                width: 36px;
                height: 36px;
                font-size: 1rem;
            }

            .timeline-step {
                margin-bottom: 2rem;
            }

            .shipping-note h3 {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <?php include PUBLIC_PATH . '/includes/header.php'; ?>

    <main class="shipping-page">
        <div class="shipping-hero">
            <h1>Shipping Information</h1>
            <p>Estimated delivery timeline and shipping details.</p>
        </div>

        <div class="shipping-layout">
            <aside class="shipping-sidebar">
                <nav>
                    <div class="sidebar-label">NEED HELP?</div>
                    <ul>
                        <li><a href="<?= PUBLIC_URL ?>/return-policy.php">Return Policy</a></li>
                        <li><a href="<?= PUBLIC_URL ?>/warranty.php">Warranty</a></li>
                        <li><a href="<?= PUBLIC_URL ?>/faq.php">FAQ</a></li>
                        <li><a href="<?= PUBLIC_URL ?>/contact.php">Contact Us</a></li>
                    </ul>
                </nav>
            </aside>

            <div class="shipping-content">
                <div class="shipping-note">
                    <h3><?= $shipping_is_free === 'yes' ? 'Free Shipping' : 'Shipping' ?> Offer</h3>
                    <p><?= $shipping_is_free === 'yes' ? 'We are currently offering free shipping on all orders. This is a limited-time offer and subject to change without notice.' : 'Standard shipping is ' . format_price($shipping_flat_cost) . ' per order, calculated at checkout.' ?></p>
                </div>

                <div class="shipping-timeline">
                    <h2>Delivery Timeline</h2>
                
                <div class="timeline-wrapper">
                    <div class="timeline-step">
                        <div class="timeline-number">1</div>
                        <div class="timeline-content">
                            <h3>Order Placed</h3>
                            <p>Your order is confirmed and production begins.</p>
                        </div>
                    </div>

                    <div class="timeline-step">
                        <div class="timeline-number">2</div>
                        <div class="timeline-content">
                            <h3>Manufacturing</h3>
                            <p>4-6 days — Your item is handcrafted to order.</p>
                        </div>
                    </div>

                    <div class="timeline-step">
                        <div class="timeline-number">3</div>
                        <div class="timeline-content">
                            <h3>International Shipping</h3>
                            <p>8-10 days — Your item ships to your location.</p>
                        </div>
                    </div>

                    <div class="timeline-step">
                        <div class="timeline-number">4</div>
                        <div class="timeline-content">
                            <h3>Delivered</h3>
                            <p>Total delivery time: 14-15 days.</p>
                        </div>
                    </div>
                </div>
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
</body>
</html>
