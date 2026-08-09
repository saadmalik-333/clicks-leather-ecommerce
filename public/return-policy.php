<?php
/**
 * Clicks Leather — Return & Refund Policy Page
 */
require_once __DIR__ . '/../includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';
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
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/style.css">
    <style>
        .policy-page {
            padding: 4rem 1rem 3rem;
            min-height: calc(100vh - 200px);
            max-width: 100%;
            overflow-x: hidden;
        }

        .policy-hero {
            text-align: center;
            margin-bottom: 5rem;
            max-width: 720px;
            margin-left: auto;
            margin-right: auto;
        }

        .policy-hero h1 {
            font-family: var(--font-display);
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .policy-hero p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .policy-container {
            max-width: 720px;
            margin: 0 auto;
        }

        .policy-section {
            margin-bottom: 4rem;
            padding: 2rem;
            border-radius: var(--radius-sm);
        }

        .policy-section:last-child {
            margin-bottom: 0;
        }

        .policy-section h2 {
            font-family: var(--font-display);
            font-size: 1.75rem;
            margin-bottom: 1.25rem;
            color: var(--color-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            letter-spacing: 0.02em;
        }

        .policy-section h2 svg {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
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

        /* Standard section */
        .policy-section.standard {
            border-left: 2px solid var(--color-primary);
        }

        .policy-section.standard h2 svg {
            color: var(--color-primary);
        }

        /* Warning section - Made-to-Order Items */
        .policy-section.warning {
            background: rgba(140, 92, 56, 0.08);
            border-left: 4px solid var(--color-primary);
        }

        .policy-section.warning h2 svg {
            color: var(--color-primary);
        }

        /* Positive section - Fit Issues */
        .policy-section.positive {
            background: rgba(201, 169, 110, 0.08);
            border-left: 3px solid var(--color-gold);
        }

        .policy-section.positive h2 svg {
            color: var(--color-gold);
        }

        @media (max-width: 768px) {
            .policy-hero h1 {
                font-size: 2rem;
            }

            .policy-section {
                padding: 1.5rem;
                margin-bottom: 3rem;
            }

            .policy-section h2 {
                font-size: 1.5rem;
            }

            .policy-section h2 svg {
                width: 20px;
                height: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include PUBLIC_PATH . '/includes/header.php'; ?>

    <main class="policy-page">
        <div class="policy-hero">
            <h1>Return & Refund Policy</h1>
            <p>Our policy for returns, exchanges, and replacements.</p>
        </div>

        <div class="policy-container">
            <div class="policy-section standard">
                <h2>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    Returns & Exchanges
                </h2>
                <p>Returns and exchanges are accepted within 14 days of delivery for the following reasons:</p>
                <ul>
                    <li>Damaged items received</li>
                    <li>Incorrect sizing</li>
                </ul>
                <p>Please contact us immediately if you receive a damaged item or have sizing issues.</p>
            </div>

            <div class="policy-section warning">
                <h2>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    Made-to-Order Items
                </h2>
                <p>Since each piece is custom-made specifically for you, we cannot accept returns for change of mind once production has started. This ensures that every item meets our quality standards and is crafted to your specifications.</p>
            </div>

            <div class="policy-section positive">
                <h2>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                    Fit Issues
                </h2>
                <p>We offer a free replacement for fit issues. If your item doesn't fit properly, contact us and we'll arrange a replacement at no additional cost to you.</p>
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
