<?php
/**
 * Clicks Leather — Order Confirmation Page
 * Displays order details after successful checkout
 */

require_once __DIR__ . '/../includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get order_id from URL
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    set_flash_message('error', 'Invalid order ID.');
    redirect(PUBLIC_URL . '/index.php');
}

// Fetch order details
$order_stmt = $pdo->prepare("
    SELECT o.*, 
           CASE WHEN o.user_id IS NOT NULL THEN u.naam ELSE NULL END as user_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$order_stmt->execute([$order_id]);
$order = $order_stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    set_flash_message('error', 'Order not found.');
    redirect(PUBLIC_URL . '/index.php');
}

// Fetch order items
$items_stmt = $pdo->prepare("
    SELECT 
        oi.*,
        p.naam as product_name,
        p.image_path,
        pv.color,
        pv.size
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN product_variants pv ON oi.variant_id = pv.id
    WHERE oi.order_id = ?
    ORDER BY oi.id ASC
");
$items_stmt->execute([$order_id]);
$order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate item count (total quantity)
$total_items = array_sum(array_column($order_items, 'quantity'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Order Confirmation — Clicks Leather. Premium handcrafted leather goods.">
    <title>Order Confirmation — Clicks Leather</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/style.css?v=<?= time() ?>">
    <meta property="og:title" content="Order Confirmation — Clicks Leather">
    <meta property="og:description" content="Order Confirmation — Clicks Leather. Premium handcrafted leather goods.">
    <meta property="og:image" content="<?= PUBLIC_URL ?>/img/logo/clicks_leather_logo_dark_transparent.png">
    <meta property="og:url" content="<?= PUBLIC_URL ?>/order-confirmation.php">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 3rem 1rem;
        }

        .confirmation-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .confirmation-icon {
            width: 80px;
            height: 80px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .confirmation-icon svg {
            width: 40px;
            height: 40px;
            stroke: white;
        }

        .confirmation-title {
            font-family: var(--font-display);
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .confirmation-subtitle {
            font-size: 1.1rem;
            color: var(--text-secondary);
        }

        .order-details-card {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .order-number {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .order-number strong {
            color: var(--text-primary);
        }

        .order-item {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-light);
        }

        .order-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .order-item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: var(--radius-sm);
        }

        .order-item-details {
            flex: 1;
        }

        .order-item-name {
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .order-item-variant {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .order-item-qty-price {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
            color: var(--text-secondary);
        }

        .summary-row.total {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--text-primary);
        }

        .payment-message {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            color: #1e40af;
            padding: 1.25rem;
            border-radius: var(--radius-sm);
            margin-bottom: 2rem;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .confirm-action-btn {
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-sm);
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .confirm-action-btn-primary {
            background: var(--color-primary);
            color: white;
            border: none;
        }

        .confirm-action-btn-primary:hover {
            background: var(--color-primary-dark);
        }

        .subscribe-btn {
            font-size: 0.75rem;
            padding: 0.5rem 1rem;
            letter-spacing: 0.5px;
        }

        .confirm-action-btn-outline {
            background: transparent;
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .confirm-action-btn-outline:hover {
            border-color: var(--color-primary);
            color: var(--color-primary);
        }

        @media (max-width: 768px) {
            .confirmation-title {
                font-size: 1.5rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .confirm-action-btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include PUBLIC_PATH . '/includes/header.php'; ?>
    <div class="page-wrapper">

    <div class="confirmation-container">
        <div class="confirmation-header">
            <div class="confirmation-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
            </div>
            <h1 class="confirmation-title">Order Confirmed!</h1>
            <p class="confirmation-subtitle">Thank you for your purchase</p>
        </div>

        <div class="payment-message">
            <strong>Payment Pending:</strong> Your order has been received. We will contact you shortly to confirm payment via Payoneer.
        </div>

        <div class="order-details-card">
            <div class="order-number">
                Order Number: <strong>#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></strong>
            </div>

            <?php foreach ($order_items as $item): ?>
                <div class="order-item">
                    <?php
                    $image_url = $item['image_path'] 
                        ? PUBLIC_URL . '/uploads/' . $item['image_path'] 
                        : PUBLIC_URL . '/img/placeholder.jpg';
                    ?>
                    <img src="<?= $image_url ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" 
                         class="order-item-image">
                    <div class="order-item-details">
                        <div class="order-item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                        <?php if ($item['color'] || $item['size']): ?>
                            <div class="order-item-variant">
                                <?php if ($item['color']): ?>Color: <?= htmlspecialchars($item['color']) ?><?php endif; ?>
                                <?php if ($item['color'] && $item['size']): ?> • <?php endif; ?>
                                <?php if ($item['size']): ?>Size: <?= htmlspecialchars($item['size']) ?><?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="order-item-qty-price">
                            <span>Qty: <?= $item['quantity'] ?></span>
                            <span><?= format_price($item['price_at_order'] * $item['quantity']) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($order['discount_amount'] > 0): ?>
                <div class="summary-row" style="color: #27ae60;">
                    <span>Discount</span>
                    <span>-<?= format_price($order['discount_amount']) ?></span>
                </div>
            <?php endif; ?>
            <div class="summary-row">
                <span>Subtotal (<?= $total_items ?> item<?= $total_items !== 1 ? 's' : '' ?>)</span>
                <span><?= format_price($order['total_amount'] - $order['shipping_cost'] + $order['discount_amount']) ?></span>
            </div>
            <div class="summary-row">
                <span>Shipping (<?= ucfirst($order['shipping_method']) ?>)</span>
                <span><?= format_price($order['shipping_cost']) ?></span>
            </div>
            <div class="summary-row total">
                <span>Total</span>
                <span><?= format_price($order['total_amount']) ?></span>
            </div>
        </div>

        <div class="action-buttons">
            <a href="<?= PUBLIC_URL ?>/index.php" class="confirm-action-btn confirm-action-btn-outline">Continue Shopping</a>
            <?php if (is_logged_in()): ?>
                <a href="<?= PUBLIC_URL ?>/account.php" class="confirm-action-btn confirm-action-btn-outline">View My Orders</a>
            <?php endif; ?>
        </div>
    </div>

    <?php include PUBLIC_PATH . '/includes/footer.php'; ?>

    <!-- Cart Drawer -->
    <?php include PUBLIC_PATH . '/includes/cart-drawer.php'; ?>

    <!-- Cart JavaScript -->
    <script src="<?= PUBLIC_URL ?>/js/cart.js"></script>
    <script src="<?= PUBLIC_URL ?>/js/header-scroll.js"></script>
    </div>
</body>
</html>
