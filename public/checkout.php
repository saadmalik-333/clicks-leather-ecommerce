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

// Check for session expiry and redirect immediately (before any cart logic)
require_active_session();

// Ensure cart session ID exists
if (!isset($_SESSION['cart_session_id'])) {
    $_SESSION['cart_session_id'] = uniqid('cart_', true);
}

// Restore form data if returning from login
if (isset($_SESSION['checkout_form_data'])) {
    $form_data = $_SESSION['checkout_form_data'];
    unset($_SESSION['checkout_form_data']);
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
            ci.personalization_text,
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
            ci.personalization_text,
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
        // Require login to place order
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            // Store form data in session for restoration after login
            $_SESSION['checkout_form_data'] = $form_data;
            
            // Store guest cart session ID for merge after login
            $_SESSION['guest_cart_session_id'] = $_SESSION['cart_session_id'];
            
            // Set flash message
            set_flash_message('info', 'Please login or create an account to complete your order.');
            
            // Redirect to login with return URL
            redirect(PUBLIC_URL . '/login.php?redirect=checkout');
        }

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
                        quantity, price_at_order, personalization_text
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ";
                $order_item_stmt = $pdo->prepare($order_item_sql);
                $order_item_stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['variant_id'],
                    $item['quantity'],
                    $item_price,
                    $item['personalization_text'] ?? null
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
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/style.css?v=<?= time() ?>">
    <meta property="og:title" content="Checkout — Clicks Leather">
    <meta property="og:description" content="Checkout — Clicks Leather. Premium handcrafted leather goods.">
    <meta property="og:image" content="<?= PUBLIC_URL ?>/img/logo/clicks_leather_logo_dark_transparent.png">
    <meta property="og:url" content="<?= PUBLIC_URL ?>/checkout.php">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@29.1.1/dist/css/intlTelInput.min.css">
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

        /* intl-tel-input styling overrides */
        .iti {
            width: 100%;
            max-width: 100%;
            position: relative;
            z-index: 1;
        }

        .iti__flag-container {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-right: none;
            border-radius: var(--radius-sm) 0 0 var(--radius-sm);
        }

        .iti__selected-flag {
            padding: 0 8px 0 10px;
        }

        .iti__country-list {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            max-height: 200px;
            overflow-y: auto;
            z-index: 10000 !important;
            position: absolute;
        }

        .iti__country {
            color: var(--text-primary);
        }

        .iti__country:hover {
            background: rgba(140, 92, 56, 0.08);
        }

        .iti__country.iti__highlight {
            background: rgba(140, 92, 56, 0.12);
        }

        .iti__dial-code {
            color: var(--text-secondary);
        }

        .iti__selected-dial-code {
            color: var(--text-primary);
            font-weight: 400;
        }

        .iti__country-name {
            font-family: var(--font-body);
            font-size: 0.875rem;
        }

        .iti__search-input-wrapper {
            border-bottom: none;
        }

        .iti__search-input {
            width: calc(100% - 1rem);
            padding: 0.5rem 0.5rem 0.5rem 2.5rem !important;
            border: 1px solid var(--border-color) !important;
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-family: var(--font-body);
            color: var(--text-primary);
            background: var(--bg-card);
            margin: 0.5rem;
        }

        .iti__search-input:focus {
            outline: none;
            border-color: var(--color-primary) !important;
            box-shadow: 0 0 0 3px rgba(140, 92, 56, 0.1);
        }

        .iti__search-icon {
            left: 1rem !important;
        }

        #phone {
            width: 100%;
            padding: 0.75rem 0.75rem 0.75rem 52px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
            color: var(--text-primary);
            background: var(--bg-card);
            font-family: var(--font-body);
            transition: border-color 0.2s ease;
        }

        #phone:focus {
            outline: none;
            border-color: var(--color-primary);
        }

        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }

            .order-summary {
                position: static;
                order: 1;
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
    <div class="page-wrapper">

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
                        <span><?= $shipping_is_free === 'yes' ? 'Free' : format_price($shipping_cost) ?></span>
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
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@29.1.1/dist/js/intlTelInput.min.js"></script>

    <!-- Country-based placeholder updater -->
    <script>
        (function() {
            const countryExamples = {
                'United States': {
                    address: '123 Main Street, Apt 4B',
                    city: 'New York',
                    state: 'New York',
                    postal: '10001'
                },
                'United Kingdom': {
                    address: '42 Baker Street',
                    city: 'London',
                    state: 'England',
                    postal: 'SW1A 1AA'
                },
                'Canada': {
                    address: '789 Queen Street West',
                    city: 'Toronto',
                    state: 'Ontario',
                    postal: 'M5H 2N2'
                },
                'Australia': {
                    address: '10 Collins Street',
                    city: 'Melbourne',
                    state: 'Victoria',
                    postal: '3000'
                },
                'Germany': {
                    address: 'Unter den Linden 10',
                    city: 'Berlin',
                    state: 'Berlin',
                    postal: '10117'
                },
                'France': {
                    address: '5 Avenue des Champs-Élysées',
                    city: 'Paris',
                    state: 'Île-de-France',
                    postal: '75008'
                },
                'Italy': {
                    address: 'Via Roma 123',
                    city: 'Milan',
                    state: 'Lombardy',
                    postal: '20121'
                },
                'Spain': {
                    address: 'Calle Gran Vía 45',
                    city: 'Madrid',
                    state: 'Madrid',
                    postal: '28013'
                },
                'Netherlands': {
                    address: 'Herengracht 123',
                    city: 'Amsterdam',
                    state: 'North Holland',
                    postal: '1015 BT'
                },
                'Belgium': {
                    address: 'Rue de la Loi 16',
                    city: 'Brussels',
                    state: 'Brussels',
                    postal: '1000'
                },
                'Switzerland': {
                    address: 'Bahnhofstrasse 10',
                    city: 'Zurich',
                    state: 'Zurich',
                    postal: '8001'
                },
                'Austria': {
                    address: 'Kärntner Straße 10',
                    city: 'Vienna',
                    state: 'Vienna',
                    postal: '1010'
                },
                'Sweden': {
                    address: 'Drottninggatan 10',
                    city: 'Stockholm',
                    state: 'Stockholm',
                    postal: '111 22'
                },
                'Norway': {
                    address: 'Karl Johans gate 10',
                    city: 'Oslo',
                    state: 'Oslo',
                    postal: '0154'
                },
                'Denmark': {
                    address: 'Strøget 10',
                    city: 'Copenhagen',
                    state: 'Capital Region',
                    postal: '1000'
                },
                'Finland': {
                    address: 'Aleksanterinkatu 10',
                    city: 'Helsinki',
                    state: 'Uusimaa',
                    postal: '00100'
                },
                'Ireland': {
                    address: 'Grafton Street 10',
                    city: 'Dublin',
                    state: 'Dublin',
                    postal: 'D02 P123'
                },
                'Portugal': {
                    address: 'Avenida da Liberdade 10',
                    city: 'Lisbon',
                    state: 'Lisbon',
                    postal: '1250-142'
                },
                'Greece': {
                    address: 'Ermou Street 10',
                    city: 'Athens',
                    state: 'Attica',
                    postal: '105 55'
                },
                'Pakistan': {
                    address: 'House #123, Main Boulevard',
                    city: 'Lahore',
                    state: 'Punjab',
                    postal: '54000'
                },
                'India': {
                    address: '45 MG Road, Block B',
                    city: 'Mumbai',
                    state: 'Maharashtra',
                    postal: '400001'
                },
                'UAE': {
                    address: 'Sheikh Zayed Road, Tower A',
                    city: 'Dubai',
                    state: 'Dubai',
                    postal: '12345'
                },
                'Saudi Arabia': {
                    address: 'Olaya Street, Building 12',
                    city: 'Riyadh',
                    state: 'Riyadh',
                    postal: '12345'
                },
                'Other': {
                    address: 'Street address, building',
                    city: 'City name',
                    state: 'State/Province',
                    postal: 'Postal/ZIP code'
                }
            };

            const countrySelect = document.getElementById('country');
            const phoneField = document.getElementById('phone');
            const addressField = document.getElementById('address_line1');
            const cityField = document.getElementById('city');
            const stateField = document.getElementById('state');
            const postalField = document.getElementById('postal_code');

            function updatePlaceholders() {
                const selectedCountry = countrySelect.value;
                const examples = countryExamples[selectedCountry] || countryExamples['Other'];

                if (addressField) addressField.placeholder = examples.address;
                if (cityField) cityField.placeholder = examples.city;
                if (stateField) stateField.placeholder = examples.state;
                if (postalField) postalField.placeholder = examples.postal;
            }

            // Initialize intl-tel-input
            let iti = null;
            if (phoneField) {
                iti = intlTelInput(phoneField, {
                    initialCountry: 'pk',
                    separateDialCode: true,
                    placeholderNumberPolicy: "POLITE",
                    countrySearch: false,
                    loadUtils: () => import('https://cdn.jsdelivr.net/npm/intl-tel-input@29.1.1/dist/js/utils.js')
                });

                // Restore country from existing phone value if present
                if (phoneField.value) {
                    iti.setNumber(phoneField.value);
                }
            }

            if (countrySelect) {
                countrySelect.addEventListener('change', function() {
                    updatePlaceholders();
                });
                // Initialize on page load
                updatePlaceholders();
            }

            // Format phone number on form submission
            const checkoutForm = document.querySelector('form');
            if (checkoutForm && iti) {
                checkoutForm.addEventListener('submit', function(e) {
                    // Validate phone number before submission
                    if (!iti.isValidNumber()) {
                        e.preventDefault();
                        alert('Please enter a valid phone number for the selected country.');
                        phoneField.focus();
                        return;
                    }

                    // Format to E164 for server submission
                    phoneField.value = iti.getNumber('E164');
                });
            }
        })();

        // Force dropdown positioning - override library's internal logic
        (function() {
            function forcePositionDropdown() {
                const phoneField = document.getElementById('phone');
                const dropdown = document.querySelector('.iti__country-list');
                if (!phoneField || !dropdown) return;

                const fieldRect = phoneField.getBoundingClientRect();

                // Force fixed positioning, anchored to the phone field's real current position
                dropdown.style.setProperty('position', 'fixed', 'important');
                dropdown.style.setProperty('top', (fieldRect.bottom + 4) + 'px', 'important');
                dropdown.style.setProperty('left', fieldRect.left + 'px', 'important');
                dropdown.style.setProperty('bottom', 'auto', 'important');
                dropdown.style.setProperty('right', 'auto', 'important');
                dropdown.style.setProperty('width', Math.max(fieldRect.width, 280) + 'px', 'important');
                dropdown.style.setProperty('max-height', '200px', 'important');
                dropdown.style.setProperty('z-index', '99999', 'important');
            }

            // Watch for dropdown insertion and force position immediately
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType !== 1) return;

                        const dropdownNode = node.classList && node.classList.contains('iti__country-list')
                            ? node
                            : (node.querySelector ? node.querySelector('.iti__country-list') : null);

                        if (dropdownNode) {
                            // Dropdown just inserted - force position with timing fixes
                            forcePositionDropdown();

                            requestAnimationFrame(() => {
                                requestAnimationFrame(() => {
                                    forcePositionDropdown();
                                });
                            });

                            setTimeout(forcePositionDropdown, 50);
                            setTimeout(forcePositionDropdown, 150);
                        }
                    });
                });
            });

            // Start observing after a short delay to ensure DOM is ready
            setTimeout(() => {
                const phoneField = document.getElementById('phone');
                if (phoneField) {
                    // Observe document.body to catch dropdown insertion anywhere in DOM
                    observer.observe(document.body, { childList: true, subtree: true });
                }
            }, 100);

            // Reposition on scroll/resize while dropdown is open
            window.addEventListener('scroll', forcePositionDropdown);
            window.addEventListener('resize', forcePositionDropdown);
        })();
    </script>
    </div>
</body>
</html>
