<?php
/**
 * Clicks Leather — FAQ Page
 */
require_once __DIR__ . '/../includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Frequently Asked Questions about Clicks Leather products, returns, and shipping.">
    <title>FAQ — Clicks Leather</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/style.css?v=<?= time() ?>">
    <meta property="og:title" content="FAQ — Clicks Leather">
    <meta property="og:description" content="Frequently Asked Questions about Clicks Leather products, returns, and shipping.">
    <meta property="og:image" content="<?= PUBLIC_URL ?>/img/logo/clicks_leather_logo_dark_transparent.png">
    <meta property="og:url" content="<?= PUBLIC_URL ?>/faq.php">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <style>
        .faq-page {
            padding: 4rem 1rem 3rem;
            min-height: calc(100vh - 200px);
            max-width: 100%;
            overflow-x: hidden;
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
            margin-bottom: 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            overflow: hidden;
        }

        .faq-question {
            font-weight: 500;
            font-size: 1.1rem;
            padding: 1rem 1.25rem;
            background: var(--bg-card);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.2s ease;
        }

        .faq-question:hover {
            background: var(--bg-light);
        }

        .faq-question::after {
            content: '+';
            font-size: 1.5rem;
            color: var(--color-primary);
            font-weight: 300;
            transition: transform 0.3s ease;
        }

        .faq-item.active .faq-question::after {
            content: '−';
            transform: rotate(180deg);
        }

        .faq-answer {
            color: var(--text-secondary);
            line-height: 1.7;
            padding: 0 1.25rem;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s ease;
            background: var(--bg-card);
        }

        .faq-item.active .faq-answer {
            padding: 1rem 1.25rem;
            max-height: 500px;
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
    <?php include PUBLIC_PATH . '/includes/header.php'; ?>
    <div class="page-wrapper">

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

    <?php include PUBLIC_PATH . '/includes/footer.php'; ?>

    <!-- Cart Drawer -->
    <?php include PUBLIC_PATH . '/includes/cart-drawer.php'; ?>

    <!-- Cart JavaScript -->
    <script src="<?= PUBLIC_URL ?>/js/cart.js"></script>

    <!-- Accordion JavaScript -->
    <script>
        document.querySelectorAll('.faq-question').forEach(function(question) {
            question.addEventListener('click', function() {
                const item = this.parentElement;
                const isActive = item.classList.contains('active');
                
                // Close all items
                document.querySelectorAll('.faq-item').forEach(function(otherItem) {
                    otherItem.classList.remove('active');
                });
                
                // Open clicked item if it wasn't already open
                if (!isActive) {
                    item.classList.add('active');
                }
            });
        });
    </script>

    <!-- Multi-Stage Header Scroll Effect with Hysteresis -->
    <script src="<?= PUBLIC_URL ?>/js/header-scroll.js"></script>
    </div>
</body>
</html>
