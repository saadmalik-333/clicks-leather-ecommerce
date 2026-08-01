<?php
/**
 * Clicks Leather — Checkout Page
 * Single-page checkout with contact info, shipping address, and order summary
 */

require_once __DIR__ . '/../includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure cart session ID exists
if (!isset($_SESSION['cart_session_id'])) {
    $_SESSION['cart_session_id'] = uniqid('cart_', true);
}

// Redirect if cart is empty
$user_id = is_logged_in() ? $_SESSION['user_id'] : null;
$session_id = $user_id ? null : $_SESSION['cart_session_id'];

// Get cart items
if ($user_id) {
    $sql = "
        SELECT 
            ci.id as cart_item_id,
            ci.quantity,
            ci.discounted_price,
            ci.discount_percent,
            p.id as product_id,
            p.naam as product_name,
            p.price,
            p.image_path,
            pv.id as variant_id,
            pv.color,
            pv.size
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        LEFT JOIN product_variants pv ON ci.variant_id = pv.id
        WHERE ci.user_id = ?
        ORDER BY ci.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
} else {
    $sql = "
        SELECT 
            ci.id as cart_item_id,
            ci.quantity,
            ci.discounted_price,
            ci.discount_percent,
            p.id as product_id,
            p.naam as product_name,
            p.price,
            p.image_path,
            pv.id as variant_id,
            pv.color,
            pv.size
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        LEFT JOIN product_variants pv ON ci.variant_id = pv.id
        WHERE ci.session_id = ?
        ORDER BY ci.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$session_id]);
}

$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cart_items)) {
    set_flash_message('error', 'Your cart is empty.');
    redirect(PUBLIC_URL . '/index.php');
}

// Calculate subtotal using discounted price if available
$subtotal = 0;
$original_subtotal = 0;
foreach ($cart_items as $item) {
    $item_price = $item['discounted_price'] ?? $item['price'];
    $subtotal += $item_price * $item['quantity'];
    $original_subtotal += $item['price'] * $item['quantity'];
}

// Calculate total discount amount
$discount_amount = $original_subtotal - $subtotal;

// Get shipping settings
$shipping_is_free = get_setting($pdo, 'shipping_is_free', 'yes');
$shipping_flat_cost = floatval(get_setting($pdo, 'shipping_flat_cost', '15.00'));

// Calculate shipping cost
$shipping_cost = $shipping_is_free === 'yes' ? 0.00 : $shipping_flat_cost;
$shipping_method = $shipping_is_free === 'yes' ? 'free' : 'flat_rate';

$total = $subtotal + $shipping_cost;

// Fetch default address if logged in
$default_address = null;
if (is_logged_in()) {
    $stmt = $pdo->prepare("
        SELECT full_name, address_line1, address_line2, city, state, country, postal_code 
        FROM addresses 
        WHERE user_id = ? AND is_default = 1 
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $default_address = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Pre-fill form values with default address if available
$prefill_full_name = $default_address['full_name'] ?? '';
$prefill_address_line1 = $default_address['address_line1'] ?? '';
$prefill_address_line2 = $default_address['address_line2'] ?? '';
$prefill_city = $default_address['city'] ?? '';
$prefill_state = $default_address['state'] ?? '';
$prefill_country = $default_address['country'] ?? '';
$prefill_postal_code = $default_address['postal_code'] ?? '';

// Handle form submission
$errors = [];
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data = [
        'email' => sanitize_input($_POST['email'] ?? ''),
        'phone' => sanitize_input($_POST['phone'] ?? ''),
        'full_name' => sanitize_input($_POST['full_name'] ?? ''),
        'address_line1' => sanitize_input($_POST['address_line1'] ?? ''),
        'address_line2' => sanitize_input($_POST['address_line2'] ?? ''),
        'city' => sanitize_input($_POST['city'] ?? ''),
        'state' => sanitize_input($_POST['state'] ?? ''),
        'country' => sanitize_input($_POST['country'] ?? ''),
        'postal_code' => sanitize_input($_POST['postal_code'] ?? ''),
    ];

    // Validation
    if (empty($form_data['email'])) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($form_data['phone'])) {
        $errors[] = 'Phone number is required.';
    }

    if (empty($form_data['full_name'])) {
        $errors[] = 'Full name is required.';
    }

    if (empty($form_data['address_line1'])) {
        $errors[] = 'Address line 1 is required.';
    }

    if (empty($form_data['city'])) {
        $errors[] = 'City is required.';
    }

    if (empty($form_data['state'])) {
        $errors[] = 'State/Province is required.';
    }

    if (empty($form_data['country'])) {
        $errors[] = 'Country is required.';
    }

    if (empty($form_data['postal_code'])) {
        $errors[] = 'Postal/Zip code is required.';
    }

    // If no errors, create order
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insert order
            $order_sql = "
                INSERT INTO orders (
                    user_id, email, phone, full_name, 
                    address_line1, address_line2, city, state, 
                    country, postal_code, shipping_cost, shipping_method, discount_amount,
                    total_amount, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_payment')
            ";
            $order_stmt = $pdo->prepare($order_sql);
            $order_stmt->execute([
                $user_id,
                $form_data['email'],
                $form_data['phone'],
                $form_data['full_name'],
                $form_data['address_line1'],
                $form_data['address_line2'],
                $form_data['city'],
                $form_data['state'],
                $form_data['country'],
                $form_data['postal_code'],
                $shipping_cost,
                $shipping_method,
                $discount_amount,
                $total
            ]);

            $order_id = $pdo->lastInsertId();

            // Insert order items
            foreach ($cart_items as $item) {
                $item_price = $item['discounted_price'] ?? $item['price'];
                $order_item_sql = "
                    INSERT INTO order_items (
                        order_id, product_id, variant_id, 
                        quantity, price_at_order
                    ) VALUES (?, ?, ?, ?, ?)
                ";
                $order_item_stmt = $pdo->prepare($order_item_sql);
                $order_item_stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['variant_id'],
                    $item['quantity'],
                    $item_price
                ]);
            }

            // Clear cart
            if ($user_id) {
                $delete_sql = "DELETE FROM cart_items WHERE user_id = ?";
                $delete_stmt = $pdo->prepare($delete_sql);
                $delete_stmt->execute([$user_id]);
            } else {
                $delete_sql = "DELETE FROM cart_items WHERE session_id = ?";
                $delete_stmt = $pdo->prepare($delete_sql);
                $delete_stmt->execute([$session_id]);
            }

            $pdo->commit();

            // Redirect to confirmation page
            redirect(PUBLIC_URL . '/order-confirmation.php?order_id=' . $order_id);

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'An error occurred while processing your order. Please try again.';
            error_log("Checkout Error: " . $e->getMessage());
        }
    }
} else {
    // Form always starts empty - customer types their own info
    $form_data = [
        'email' => '',
        'phone' => '',
        'full_name' => '',
        'address_line1' => '',
        'address_line2' => '',
        'city' => '',
        'state' => '',
        'country' => '',
        'postal_code' => '',
    ];
}

$countries = [
    'United States', 'United Kingdom', 'Canada', 'Australia', 
    'Germany', 'France', 'Italy', 'Spain', 'Netherlands', 
    'Belgium', 'Switzerland', 'Austria', 'Sweden', 'Norway', 
    'Denmark', 'Finland', 'Ireland', 'Portugal', 'Greece',
    'Pakistan', 'India', 'UAE', 'Saudi Arabia', 'Other'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Checkout — Clicks Leather. Premium handcrafted leather goods.">
    <title>Checkout — Clicks Leather</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/style.css">
    <style>
        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }

        .checkout-form-section {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            padding: 2rem;
            margin-bottom: 1.5rem;
        }

        .checkout-section-title {
            font-family: var(--font-display);
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
            font-family: var(--font-body);
            transition: border-color 0.2s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--color-primary);
        }

        .form-group .required {
            color: var(--color-error);
        }

        .order-summary {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            padding: 2rem;
            position: sticky;
            top: 2rem;
        }

        .order-summary-title {
            font-family: var(--font-display);
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
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

        .checkout-btn {
            width: 100%;
            padding: 1rem;
            background: var(--color-primary);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease;
            margin-top: 1.5rem;
        }

        .checkout-btn:hover {
            background: var(--color-primary-dark);
        }

        .error-message {
            background: rgba(220, 38, 38, 0.1);
            border: 1px solid rgba(220, 38, 38, 0.2);
            color: var(--color-error);
            padding: 1rem;
            border-radius: var(--radius-sm);
            margin-bottom: 1.5rem;
        }

        .error-message ul {
            margin: 0.5rem 0 0 1.5rem;
        }

        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }

            .order-summary {
                position: static;
                order: -1;
                margin-bottom: 2rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include PUBLIC_PATH . '/includes/header.php'; ?>

    <div class="checkout-container">
        <h1 class="page-title" style="text-align: center; margin-bottom: 2rem;">Checkout</h1>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <strong>Please fix the following errors:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="checkout-grid">
                <!-- Left Column: Form -->
                <div class="checkout-form">
                    <!-- Contact Info -->
                    <div class="checkout-form-section">
                        <h2 class="checkout-section-title">Contact Information</h2>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email <span class="required">*</span></label>
                                <input type="email" id="email" name="email" 
                                       value="<?= htmlspecialchars($form_data['email']) ?>" 
                                       placeholder="you@example.com"
                                       required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone <span class="required">*</span></label>
                                <input type="tel" id="phone" name="phone" 
                                       value="<?= htmlspecialchars($form_data['phone']) ?>" 
                                       placeholder="+92 300 1234567"
                                       required>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div class="checkout-form-section">
                        <h2 class="checkout-section-title">Shipping Address</h2>
                        <div class="form-group">
                            <label for="full_name">Full Name <span class="required">*</span></label>
                            <input type="text" id="full_name" name="full_name" 
                                   value="<?= htmlspecialchars($_SERVER['REQUEST_METHOD'] === 'POST' ? $form_data['full_name'] : $prefill_full_name) ?>" 
                                   placeholder="John Doe"
                                   required>
                        </div>
                        <div class="form-group">
                            <label for="address_line1">Address Line 1 <span class="required">*</span></label>
                            <input type="text" id="address_line1" name="address_line1" 
                                   value="<?= htmlspecialchars($_SERVER['REQUEST_METHOD'] === 'POST' ? $form_data['address_line1'] : $prefill_address_line1) ?>" 
                                   placeholder="House #, Street name"
                                   required>
                        </div>
                        <div class="form-group">
                            <label for="address_line2">Address Line 2 (Optional)</label>
                            <input type="text" id="address_line2" name="address_line2" 
                                   value="<?= htmlspecialchars($_SERVER['REQUEST_METHOD'] === 'POST' ? $form_data['address_line2'] : $prefill_address_line2) ?>" 
                                   placeholder="Apartment, floor, etc.">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City <span class="required">*</span></label>
                                <input type="text" id="city" name="city" 
                                       value="<?= htmlspecialchars($_SERVER['REQUEST_METHOD'] === 'POST' ? $form_data['city'] : $prefill_city) ?>" 
                                       placeholder="e.g. Karachi"
                                       required>
                            </div>
                            <div class="form-group">
                                <label for="state">State/Province <span class="required">*</span></label>
                                <input type="text" id="state" name="state" 
                                       value="<?= htmlspecialchars($_SERVER['REQUEST_METHOD'] === 'POST' ? $form_data['state'] : $prefill_state) ?>" 
                                       placeholder="e.g. Sindh"
                                       required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="country">Country <span class="required">*</span></label>
                                <select id="country" name="country" required>
                                    <option value="">Select Country</option>
                                    <?php foreach ($countries as $country): ?>
                                        <option value="<?= htmlspecialchars($country) ?>" 
                                                <?= ($_SERVER['REQUEST_METHOD'] === 'POST' ? $form_data['country'] : $prefill_country) === $country ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($country) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="postal_code">Postal/Zip Code <span class="required">*</span></label>
                                <input type="text" id="postal_code" name="postal_code" 
                                       value="<?= htmlspecialchars($_SERVER['REQUEST_METHOD'] === 'POST' ? $form_data['postal_code'] : $prefill_postal_code) ?>" 
                                       placeholder="e.g. 75500"
                                       required>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Method -->
                    <div class="checkout-form-section">
                        <h2 class="checkout-section-title">Shipping Method</h2>
                        <div style="padding: 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-sm); background: var(--bg-light);">
                            <label style="display: flex; align-items: center; cursor: pointer;">
                                <input type="radio" name="shipping_method" value="<?= $shipping_method ?>" checked 
                                       style="margin-right: 0.75rem;">
                                <div>
                                    <strong><?= $shipping_is_free === 'yes' ? 'Free Worldwide Shipping' : 'Standard Shipping - ' . format_price($shipping_cost) ?></strong>
                                    <div style="font-size: 0.9rem; color: var(--text-secondary);">Estimated delivery: 7-14 business days</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Order Summary -->
                <div class="order-summary">
                    <h2 class="order-summary-title">Order Summary</h2>
                    
                    <?php foreach ($cart_items as $item): ?>
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
                                    <?php 
                                    $item_price = $item['discounted_price'] ?? $item['price'];
                                    $item_total = $item_price * $item['quantity'];
                                    ?>
                                    <?php if ($item['discounted_price'] && $item['discounted_price'] < $item['price']): ?>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <span style="text-decoration: line-through; color: #999; font-size: 0.85rem;"><?= format_price($item['price'] * $item['quantity']) ?></span>
                                            <span style="color: var(--color-primary, #e63946); font-weight: 600;"><?= format_price($item_total) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span><?= format_price($item_total) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span><?= format_price($subtotal) ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span><?= format_price($shipping_cost) ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span><?= format_price($total) ?></span>
                    </div>

                    <button type="submit" class="checkout-btn">Place Order</button>
                </div>
            </div>
</form>

    <?php include PUBLIC_PATH . '/includes/footer.php'; ?>

    <!-- Cart Drawer -->
    <?php include PUBLIC_PATH . '/includes/cart-drawer.php'; ?>

    <!-- Cart JavaScript -->
    <script src="<?= PUBLIC_URL ?>/js/cart.js"></script>
    <script src="<?= PUBLIC_URL ?>/js/header-scroll.js"></script>
</body>
</html>
