<?php
/**
 * Clicks Leather — Shipping Information Page
 */
require_once __DIR__ . '/../includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Shipping Information for Clicks Leather. Learn about our delivery timeline and free shipping offer.">
    <title>Shipping Information — Clicks Leather</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/style.css">
    <style>
        .shipping-page {
            padding: 4rem 1rem 3rem;
            min-height: calc(100vh - 200px);
            max-width: 100%;
            overflow-x: hidden;
        }

        .shipping-hero {
            text-align: center;
            margin-bottom: 5rem;
            max-width: 720px;
            margin-left: auto;
            margin-right: auto;
        }

        .shipping-hero h1 {
            font-family: var(--font-display);
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .shipping-hero p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .shipping-container {
            max-width: 720px;
            margin: 0 auto;
        }

        .shipping-timeline {
            margin-bottom: 4rem;
            position: relative;
        }

        .shipping-timeline h2 {
            font-family: var(--font-display);
            font-size: 1.75rem;
            margin-bottom: 2rem;
            color: var(--color-primary);
            letter-spacing: 0.02em;
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
            padding: 2rem;
            background: var(--bg-card-hover);
            border-radius: var(--radius-sm);
            text-align: center;
        }

        .shipping-note h3 {
            font-family: var(--font-display);
            font-size: 1.5rem;
            margin-bottom: 0.75rem;
            color: var(--color-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .shipping-note h3 svg {
            width: 24px;
            height: 24px;
            color: var(--color-gold);
        }

        .shipping-note p {
            color: var(--text-secondary);
            font-size: 1.05rem;
            line-height: 1.6;
            margin: 0;
        }

        @media (max-width: 768px) {
            .shipping-hero h1 {
                font-size: 2rem;
            }

            .shipping-timeline h2 {
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

        <div class="shipping-container">
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

            <div class="shipping-note">
                <h3>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                    </svg>
                    Free Shipping Offer
                </h3>
                <p>We are currently offering free shipping on all orders. This is a limited-time offer and subject to change without notice.</p>
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
