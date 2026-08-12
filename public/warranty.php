<?php
/**
 * Clicks Leather — Warranty Policy Page
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
    <meta name="description" content="Warranty Policy for Clicks Leather. Learn about our 1 year warranty coverage for manufacturing defects.">
    <title>Warranty Policy — Clicks Leather</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/style.css">
    <meta property="og:title" content="Warranty Policy — Clicks Leather">
    <meta property="og:description" content="Warranty Policy for Clicks Leather. Learn about our 1 year warranty coverage for manufacturing defects.">
    <meta property="og:image" content="<?= PUBLIC_URL ?>/img/logo/clicks_leather_logo_dark_transparent.png">
    <meta property="og:url" content="<?= PUBLIC_URL ?>/warranty.php">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <style>
        .warranty-page {
            padding: 0;
            min-height: calc(100vh - 200px);
            max-width: 100%;
            overflow-x: hidden;
        }

        .warranty-hero {
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

        .warranty-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1;
        }

        .warranty-hero h1 {
            font-family: var(--font-display);
            font-size: 2rem;
            margin-bottom: 1rem;
            color: white;
            letter-spacing: 0.02em;
            position: relative;
            z-index: 2;
        }

        .warranty-hero p {
            color: rgba(255, 255, 255, 0.85);
            font-size: 1.1rem;
            margin-top: 0.5rem;
            position: relative;
            z-index: 2;
        }

        .warranty-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 0;
            max-width: 1100px;
            margin: 0 auto 4rem;
            padding: 0 2rem;
        }

        .warranty-sidebar {
            position: sticky;
            top: 2rem;
            height: fit-content;
        }

        .warranty-sidebar nav {
            padding-top: 3rem;
        }

        .warranty-sidebar nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .warranty-sidebar nav li {
            margin-bottom: 0.5rem;
        }

        .warranty-sidebar nav a {
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

        .warranty-sidebar nav a:hover {
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

        .warranty-content {
            background: var(--bg-card-hover);
            padding: 3rem;
            border-radius: var(--radius-sm);
        }

        .warranty-content h2 {
            font-family: var(--font-display);
            font-size: 1.6rem;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
            letter-spacing: 0.02em;
            padding-top: 2rem;
            margin-top: -2rem;
        }

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

        @media (max-width: 768px) {
            .warranty-hero {
                height: 300px;
            }

            .warranty-hero h1 {
                font-size: 2rem;
            }

            .warranty-hero p {
                font-size: 1rem;
            }

            .warranty-layout {
                grid-template-columns: 1fr;
                gap: 2rem;
                padding: 0 1rem;
            }

            .warranty-sidebar {
                position: static;
            }

            .warranty-sidebar nav {
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 0.5rem;
            }

            .warranty-sidebar nav ul {
                display: flex;
                gap: 1.5rem;
            }

            .warranty-sidebar nav li {
                margin-bottom: 0;
            }

            .warranty-content {
                padding: 2rem;
            }

            .warranty-content h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include PUBLIC_PATH . '/includes/header.php'; ?>

    <main class="warranty-page">
        <div class="warranty-hero">
            <h1>Warranty Policy</h1>
            <p>Our commitment to quality and your peace of mind.</p>
        </div>

        <div class="warranty-layout">
            <aside class="warranty-sidebar">
                <nav>
                    <div class="sidebar-label">NEED HELP?</div>
                    <ul>
                        <li><a href="<?= PUBLIC_URL ?>/shipping-info.php">Shipping Policy</a></li>
                        <li><a href="<?= PUBLIC_URL ?>/return-policy.php">Return Policy</a></li>
                        <li><a href="<?= PUBLIC_URL ?>/faq.php">FAQ</a></li>
                        <li><a href="<?= PUBLIC_URL ?>/contact.php">Contact Us</a></li>
                    </ul>
                </nav>
            </aside>

            <div class="warranty-content">
                <div class="warranty-section">
                    <p>Every Clicks Leather piece is handcrafted with care using 100% genuine leather — no shortcuts, no PU. We're confident in the quality of our craftsmanship, which is why every purchase comes with a one year warranty from your date of purchase, for your complete peace of mind.</p>
                </div>

                <div class="warranty-section">
                    <h2>What's Covered</h2>
                    <p>We warrant that our materials and craftsmanship are free from manufacturing defects for 1 year from the date of purchase, for the original purchaser.</p>
                </div>

                <div class="warranty-section">
                    <h2>What's NOT Covered</h2>
                    <ul>
                        <li>Damage from misuse or neglect</li>
                        <li>Normal wear and tear</li>
                        <li>Change of mind</li>
                        <li>Overstuffing wallets/products beyond capacity</li>
                        <li>Deformation from misuse</li>
                        <li>Accidents</li>
                        <li>Exposure to extreme conditions</li>
                        <li>Color changes</li>
                        <li>Acids, ink, oils, solvents</li>
                        <li>Water</li>
                        <li>Malicious damage</li>
                    </ul>
                    <p>Example: overstuffing a wallet beyond its recommended capacity will naturally stretch the leather and stress the stitching — this would not be covered under warranty.</p>
                    <p>Incidental or consequential damages related to the product are not covered.</p>
                </div>

                <div class="warranty-section">
                    <h2>Our Judgment</h2>
                    <p>We reserve the right to inspect any returned item and make a fair judgment on whether the issue is covered under warranty. Depending on the case, we may offer a repair, an exchange for the same item (or the closest equivalent if unavailable), or a full refund. This warranty applies alongside your applicable consumer protection rights.</p>
                </div>

                <div class="warranty-section">
                    <h2>Faulty Items</h2>
                    <p>In the rare case your item arrives with a fault, please reach out to us (with photos, if possible) via our <a href="<?= PUBLIC_URL ?>/contact.php">Contact page</a>, and we'll guide you through the return process.</p>
                    <p>We recommend using a tracked and insured shipping service when sending an item back — we can't be held responsible for items lost or damaged in transit. Please keep proof of postage.</p>
                    <p>Once we receive and inspect the item, if it's confirmed faulty, we'll send a replacement or issue a refund (including shipping costs).</p>
                </div>

                <div class="warranty-section">
                    <h2>How to Make a Claim</h2>
                    <p>If your item develops a fault, please get in touch through our <a href="<?= PUBLIC_URL ?>/contact.php">Contact page</a> with photos and details — this helps us resolve things as quickly as possible.</p>
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
